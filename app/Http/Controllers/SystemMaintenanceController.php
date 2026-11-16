<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\SystemMaintenanceOperation;
use App\Models\SystemMaintenanceSetting;
use App\Services\ActivityLogArchiveService;
use App\Services\ActivityLogger;
use App\Services\LocalSqliteDatabase;
use App\Services\SystemBackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

class SystemMaintenanceController extends Controller
{
    public function __construct(
        private readonly SystemBackupService $backups,
        private readonly ActivityLogArchiveService $archives,
        private readonly LocalSqliteDatabase $sqlite
    ) {}

    public function index()
    {
        $settings = SystemMaintenanceSetting::current();
        $backupFiles = $this->backups->backupFiles();
        $archivePeriods = $this->archives->archivePeriods();
        $archiveCount = 0;
        foreach ($archivePeriods as $period) {
            $archiveCount += $this->archives->archiveDetails($period)['count'];
        }

        $databasePath = $this->sqlite->configuredPath();
        $integrity = 'FAILED';
        $journalMode = 'Unavailable';
        $databaseStatus = 'Tidak tersedia';
        try {
            DB::connection('sqlite')->selectOne('SELECT 1 AS connected');
            $databaseStatus = 'Terhubung';
            $integrity = $this->sqlite->assertValid($databasePath) ? 'OK' : 'FAILED';
            $database = new \SQLite3($databasePath, SQLITE3_OPEN_READONLY);
            $journalMode = strtoupper((string) $database->querySingle('PRAGMA journal_mode'));
            $database->close();
        } catch (Throwable $exception) {
            Log::warning('System maintenance overview could not inspect SQLite.', ['exception' => $exception->getMessage()]);
        }

        return view('system_maintenance.index', [
            'settings' => $settings,
            'backupFiles' => $backupFiles,
            'archivePeriods' => $archivePeriods,
            'archiveCount' => $archiveCount,
            'activeLogCount' => ActivityLog::count(),
            'backupBytes' => array_sum(array_column($backupFiles, 'size')),
            'archiveBytes' => $this->archives->archiveBytes(),
            'databaseBytes' => is_file($databasePath) ? filesize($databasePath) : 0,
            'databaseIntegrity' => $integrity,
            'databaseStatus' => $databaseStatus,
            'journalMode' => $journalMode,
            'schedulerStatus' => $settings->last_scheduler_seen_at?->greaterThan(now()->subMinutes(2)) ? 'Verified' : 'Not Verified',
            'nextBackupAt' => $this->nextBackupAt($settings),
            'nextArchiveAt' => $this->nextArchiveAt($settings),
            'operations' => SystemMaintenanceOperation::query()->latest('id')->limit(30)->get(),
            'phpExecutable' => PHP_BINARY,
            'projectDirectory' => base_path(),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'automatic_backup_enabled' => ['nullable', 'boolean'],
            'backup_frequency' => ['required', Rule::in(['daily', 'weekly', 'monthly'])],
            'backup_time' => ['required', 'date_format:H:i'],
            'backup_weekly_day' => ['required_if:backup_frequency,weekly', 'integer', 'between:0,6'],
            'backup_monthly_day' => ['required_if:backup_frequency,monthly', 'integer', 'between:1,31'],
            'backup_retention' => ['required', 'integer', 'between:1,365'],
            'automatic_archive_enabled' => ['nullable', 'boolean'],
            'active_log_retention_days' => ['required', 'integer', 'between:30,3650'],
            'archive_retention_days' => ['nullable', 'integer', 'between:30,36500'],
        ]);

        $settings = SystemMaintenanceSetting::current();
        $before = $settings->only([
            'automatic_backup_enabled', 'backup_frequency', 'backup_time', 'backup_weekly_day',
            'backup_monthly_day', 'backup_retention', 'automatic_archive_enabled',
            'active_log_retention_days', 'archive_retention_days',
        ]);
        $settings->update([
            ...$validated,
            'automatic_backup_enabled' => (bool) ($validated['automatic_backup_enabled'] ?? false),
            'automatic_archive_enabled' => (bool) ($validated['automatic_archive_enabled'] ?? false),
        ]);
        $after = $settings->only(array_keys($before));

        if ($before !== $after) {
            ActivityLogger::log(ActivityLog::ACTION_UPDATE, 'Pemeliharaan Sistem', 'Pengaturan jadwal backup dan retensi log diubah.', [
                'old' => $before,
                'new' => $after,
            ]);
        }

        return back()->with('success', 'Pengaturan pemeliharaan sistem berhasil disimpan.');
    }

    public function createBackup()
    {
        try {
            $fileName = $this->backups->createBackup();

            return back()->with('success', "Backup database berhasil dibuat: {$fileName}");
        } catch (Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }

    public function downloadBackup(string $fileName)
    {
        $path = $this->backups->resolveBackupPath($fileName);
        $this->sqlite->assertValid($path);
        ActivityLogger::log(ActivityLog::ACTION_DOWNLOAD, 'Pemeliharaan Sistem', 'Berkas backup database diunduh.', ['file_name' => $fileName]);

        return response()->download($path, $fileName, ['Content-Type' => 'application/vnd.sqlite3']);
    }

    public function restore(Request $request, string $fileName)
    {
        $request->validate(['confirmation' => ['required', 'accepted']]);

        try {
            $safetyFile = $this->backups->restore($fileName);

            return redirect()->route('system-maintenance.index')
                ->with('success', "Database berhasil dipulihkan. Backup sebelum pemulihan: {$safetyFile}. Silakan masuk kembali bila sesi berakhir.");
        } catch (Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }

    public function archiveNow(Request $request)
    {
        $request->validate(['confirmation' => ['required', 'accepted']]);

        try {
            $result = $this->archives->archiveOldLogs(now(), true);

            return back()->with('success', "{$result['archived']} log aktivitas berhasil diarsipkan.");
        } catch (Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }

    private function nextBackupAt(SystemMaintenanceSetting $settings): ?Carbon
    {
        if (! $settings->automatic_backup_enabled) {
            return null;
        }

        $today = now()->startOfDay();
        $candidates = [];
        for ($offset = 0; $offset <= 370; $offset++) {
            $date = $today->copy()->addDays($offset);
            $scheduled = match ($settings->backup_frequency) {
                'daily' => true,
                'weekly' => $date->dayOfWeek === $settings->backup_weekly_day,
                'monthly' => $date->day === min($settings->backup_monthly_day, $date->daysInMonth),
                default => false,
            };
            if (! $scheduled) {
                continue;
            }

            [$hour, $minute] = array_map('intval', explode(':', $settings->backup_time));
            $candidate = $date->setTime($hour, $minute);
            if ($candidate->greaterThan(now())) {
                $candidates[] = $candidate;
                break;
            }
        }

        return $candidates[0] ?? null;
    }

    private function nextArchiveAt(SystemMaintenanceSetting $settings): ?Carbon
    {
        if (! $settings->automatic_archive_enabled) {
            return null;
        }

        $now = now();
        $next = $now->copy()->startOfMonth();
        if ($now->day > 1 || $settings->last_archive_success_at?->isSameMonth($now)) {
            $next->addMonth();
        }

        return $next->startOfDay();
    }
}
