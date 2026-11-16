<?php

namespace App\Services;

use App\Models\SystemMaintenanceOperation;
use App\Models\SystemMaintenanceSetting;
use App\Models\ActivityLog;
use App\Services\ActivityLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class SystemBackupService
{
    public function __construct(
        private readonly LocalSqliteDatabase $sqlite,
        private readonly SystemMaintenanceLock $lock
    ) {}

    public function tick(): void
    {
        $settings = SystemMaintenanceSetting::current();
        $now = now();
        $settings->update(['last_scheduler_seen_at' => $now]);

        if (! $settings->automatic_backup_enabled || ! $this->isBackupDue($settings, $now)) {
            return;
        }

        $runKey = $this->backupRunKey($settings, $now);
        if (SystemMaintenanceOperation::query()
            ->where('operation_key', $runKey)
            ->where('status', 'SUCCESS')
            ->exists()) {
            return;
        }

        try {
            $this->createBackup('AUTOMATIC', $runKey);
        } catch (Throwable $exception) {
            Log::error('Automatic SQLite backup failed.', ['exception' => $exception->getMessage()]);
        }
    }

    public function createBackup(string $origin = 'MANUAL', ?string $runKey = null): string
    {
        return $this->lock->run(function () use ($origin, $runKey): string {
            if ($runKey && SystemMaintenanceOperation::query()->where('operation_key', $runKey)->where('status', 'SUCCESS')->exists()) {
                return (string) SystemMaintenanceOperation::query()->where('operation_key', $runKey)->where('status', 'SUCCESS')->value('file_name');
            }

            $settings = SystemMaintenanceSetting::current();
            $operation = $this->operation($origin, $runKey);
            $settings->update([
                'last_backup_attempt_at' => now(),
                'last_backup_status' => 'RUNNING',
                'last_backup_message' => 'Pembuatan snapshot database sedang berjalan.',
                'last_backup_run_key' => $runKey,
            ]);

            $directory = storage_path('app/system/backups');
            $this->ensureDirectory($directory);
            $fileName = $this->fileName($origin === 'RESTORE_SAFETY' ? 'before-restore' : null);
            $temporaryPath = $directory.DIRECTORY_SEPARATOR.'.'.$fileName.'.partial';
            $finalPath = $directory.DIRECTORY_SEPARATOR.$fileName;

            try {
                $this->sqlite->createSnapshot($this->sqlite->configuredPath(), $temporaryPath);
                $tables = $this->sqlite->assertValid($temporaryPath);
                if (! rename($temporaryPath, $finalPath)) {
                    throw new RuntimeException('Snapshot terverifikasi tidak dapat disimpan ke folder backup.');
                }

                $message = 'Snapshot SQLite berhasil dibuat dan lolos pemeriksaan integritas.';
                $settings->update([
                    'last_backup_success_at' => now(),
                    'last_backup_status' => 'SUCCESS',
                    'last_backup_message' => $message,
                ]);
                $operation->update([
                    'status' => 'SUCCESS',
                    'file_name' => $fileName,
                    'message' => $message,
                    'metadata' => ['tables' => count($tables), 'bytes' => filesize($finalPath), 'origin' => $origin],
                ]);
                ActivityLogger::log(
                    ActivityLog::ACTION_EXPORT,
                    'Pemeliharaan Sistem',
                    'Backup penuh database berhasil dibuat.',
                    ['file_name' => $fileName, 'origin' => $origin]
                );
                if ($origin !== 'RESTORE_SAFETY') {
                    try {
                        $this->pruneBackups($settings->backup_retention);
                    } catch (Throwable $exception) {
                        $retentionMessage = $message.' Retensi tidak dapat diterapkan sepenuhnya.';
                        Log::warning('Backup retention cleanup failed.', ['exception' => $exception->getMessage()]);
                        try {
                            $settings->update(['last_backup_message' => $retentionMessage]);
                            $operation->update(['message' => $retentionMessage]);
                        } catch (Throwable $statusException) {
                            Log::error('Backup retention failure status could not be saved.', ['exception' => $statusException->getMessage()]);
                        }
                    }
                }

                return $fileName;
            } catch (Throwable $exception) {
                if (is_file($temporaryPath)) {
                    unlink($temporaryPath);
                }
                $message = 'Snapshot gagal dibuat atau diverifikasi. Database dan backup sebelumnya tidak dihapus.';
                $settings->update(['last_backup_status' => 'FAILED', 'last_backup_message' => $message]);
                $operation->update(['status' => 'FAILED', 'message' => $message]);
                Log::error('SQLite backup failed.', ['exception' => $exception->getMessage()]);

                throw new RuntimeException($message, previous: $exception);
            }
        });
    }

    public function isBackupDue(SystemMaintenanceSetting $settings, Carbon $now): bool
    {
        if ($now->format('H:i') !== $settings->backup_time) {
            return false;
        }

        return match ($settings->backup_frequency) {
            'daily' => true,
            'weekly' => $now->dayOfWeek === $settings->backup_weekly_day,
            'monthly' => $now->day === min($settings->backup_monthly_day, $now->daysInMonth),
            default => false,
        };
    }

    public function backupRunKey(SystemMaintenanceSetting $settings, Carbon $now): string
    {
        $period = match ($settings->backup_frequency) {
            'weekly' => $now->format('o-\WW'),
            'monthly' => $now->format('Y-m'),
            default => $now->format('Y-m-d'),
        };

        return 'backup:'.$period.':'.$settings->backup_time;
    }

    public function backupFiles(): array
    {
        $directory = storage_path('app/system/backups');
        if (! is_dir($directory)) {
            return [];
        }

        $files = [];
        foreach (glob($directory.DIRECTORY_SEPARATOR.'erp_*.sqlite') ?: [] as $path) {
            $fileName = basename($path);
            if (! preg_match('/^erp_\d{4}-\d{2}-\d{2}_\d{6}_\d{6}(?:_before-restore)?\.sqlite$/', $fileName)) {
                continue;
            }

            $files[] = [
                'name' => $fileName,
                'size' => filesize($path),
                'created_at' => Carbon::createFromTimestamp(filemtime($path)),
                'safety' => str_ends_with($fileName, '_before-restore.sqlite'),
            ];
        }

        usort($files, fn (array $a, array $b) => $b['created_at'] <=> $a['created_at']);

        return $files;
    }

    public function resolveBackupPath(string $fileName): string
    {
        abort_unless(preg_match('/^erp_\d{4}-\d{2}-\d{2}_\d{6}_\d{6}(?:_before-restore)?\.sqlite$/', $fileName) === 1, 404);
        $directory = realpath(storage_path('app/system/backups'));
        $path = realpath(storage_path('app/system/backups'.DIRECTORY_SEPARATOR.$fileName));

        abort_unless($directory && $path && dirname($path) === $directory && is_file($path), 404);

        return $path;
    }

    public function restore(string $fileName): string
    {
        return $this->lock->run(function () use ($fileName): string {
            $backupPath = $this->resolveBackupPath($fileName);
            $this->sqlite->assertValid($backupPath);

            $databasePath = $this->sqlite->configuredPath();
            $suffix = bin2hex(random_bytes(6));
            $stagePath = $databasePath.'.restore-'.$suffix;
            $rollbackPath = $databasePath.'.rollback-'.$suffix;
            $safetyFile = null;
            $rollbackSafe = true;
            $maintenanceMode = app()->maintenanceMode();
            abort_if($maintenanceMode->active(), 409, 'Selesaikan mode pemeliharaan yang sedang aktif sebelum memulihkan database.');

            $operationKey = 'restore:'.bin2hex(random_bytes(12));
            SystemMaintenanceOperation::create([
                'operation' => 'RESTORE',
                'status' => 'RUNNING',
                'file_name' => $fileName,
                'operation_key' => $operationKey,
                'message' => 'Snapshot database sedang dipulihkan.',
            ]);
            try {
                $maintenanceMode->activate([
                    'time' => now()->timestamp,
                    'message' => 'Pemulihan database sedang berlangsung.',
                    'retry' => 60,
                ]);

                $pdo = DB::connection('sqlite')->getPdo();
                $pdo->exec('BEGIN EXCLUSIVE');
                $pdo->exec('COMMIT');
                unset($pdo);
                $this->sqlite->checkpoint($databasePath);
                $this->sqlite->createSnapshot($databasePath, $stagePath.'.safety');
                $this->sqlite->assertValid($stagePath.'.safety');
                $safetyFile = $this->persistSafetySnapshot($stagePath.'.safety');
                $this->sqlite->createSnapshot($backupPath, $stagePath);
                $this->sqlite->assertValid($stagePath);

                DB::purge('sqlite');
                foreach ([$databasePath.'-wal', $databasePath.'-shm'] as $sidecar) {
                    if (is_file($sidecar) && ! unlink($sidecar)) {
                        throw new RuntimeException('Berkas WAL SQLite tidak dapat ditutup sebelum pemulihan.');
                    }
                }

                if (! rename($databasePath, $rollbackPath)) {
                    throw new RuntimeException('Database aktif tidak dapat dipindahkan ke titik pemulihan sementara.');
                }
                if (! rename($stagePath, $databasePath)) {
                    rename($rollbackPath, $databasePath);
                    throw new RuntimeException('Snapshot tervalidasi tidak dapat dipasang sebagai database aktif.');
                }

                $this->sqlite->assertValid($databasePath);

                $settings = SystemMaintenanceSetting::current();
                $settings->update([
                    'last_backup_success_at' => now(),
                    'last_backup_status' => 'SUCCESS',
                    'last_backup_message' => 'Database berhasil dipulihkan. Salinan sebelum pemulihan: '.$safetyFile,
                ]);
                SystemMaintenanceOperation::query()->updateOrCreate(
                    ['operation_key' => $operationKey],
                    [
                        'operation' => 'RESTORE',
                        'status' => 'SUCCESS',
                        'file_name' => $fileName,
                        'message' => 'Database dipulihkan setelah backup keselamatan berhasil dibuat.',
                        'metadata' => ['safety_backup' => $safetyFile],
                    ]
                );

                ActivityLogger::log(ActivityLog::ACTION_UPDATE, 'Pemeliharaan Sistem', 'Database utama dipulihkan dari berkas backup.', [
                    'backup_file' => $fileName,
                    'safety_backup' => $safetyFile,
                ]);
                if (is_file($rollbackPath) && ! unlink($rollbackPath)) {
                    throw new RuntimeException('Database berhasil dipulihkan, tetapi berkas rollback sementara tidak dapat dibersihkan.');
                }

                return $safetyFile;
            } catch (Throwable $exception) {
                DB::purge('sqlite');
                foreach ([$databasePath.'-wal', $databasePath.'-shm'] as $sidecar) {
                    if (is_file($sidecar)) {
                        if (! unlink($sidecar)) {
                            $rollbackSafe = false;
                        }
                    }
                }
                if (is_file($rollbackPath)) {
                    if (is_file($databasePath) && ! unlink($databasePath)) {
                        $rollbackSafe = false;
                    }
                    if (! is_file($databasePath) && ! rename($rollbackPath, $databasePath)) {
                        $rollbackSafe = false;
                    }
                } elseif (! is_file($databasePath)) {
                    $rollbackSafe = false;
                }
                if (is_file($stagePath)) {
                    unlink($stagePath);
                }
                if ($rollbackSafe && Schema::hasTable('system_maintenance_operations')) {
                    SystemMaintenanceOperation::query()->updateOrCreate(
                        ['operation_key' => $operationKey],
                        [
                            'operation' => 'RESTORE',
                            'status' => 'FAILED',
                            'file_name' => $fileName,
                            'message' => 'Pemulihan gagal. Database sebelumnya dipulihkan, bila pergantian berkas telah dimulai.',
                            'metadata' => ['safety_backup' => $safetyFile],
                        ]
                    );
                }
                if (! $rollbackSafe) {
                    $maintenanceMode->activate([
                        'time' => now()->timestamp,
                        'message' => 'Pemulihan gagal dan rollback otomatis belum terverifikasi. Jangan gunakan aplikasi sebelum database dipulihkan dari backup keselamatan: '.$safetyFile,
                        'retry' => 60,
                    ]);
                }
                Log::log($rollbackSafe ? 'error' : 'critical', 'SQLite restore failed.', [
                    'exception' => $exception->getMessage(),
                    'backup' => $fileName,
                    'safety_backup' => $safetyFile,
                    'rollback_safe' => $rollbackSafe,
                ]);

                throw new RuntimeException(
                    $rollbackSafe
                        ? 'Pemulihan gagal. Database aktif dikembalikan ke salinan sebelumnya.'
                        : 'Pemulihan gagal dan rollback otomatis tidak dapat diverifikasi. Aplikasi tetap dalam mode pemeliharaan; gunakan backup keselamatan '.$safetyFile.' untuk pemulihan.',
                    previous: $exception
                );
            } finally {
                foreach ([$stagePath, $stagePath.'.safety'] as $temporaryPath) {
                    if (is_file($temporaryPath)) {
                        unlink($temporaryPath);
                    }
                }
                if ($rollbackSafe && $maintenanceMode->active()) {
                    $maintenanceMode->deactivate();
                }
            }
        });
    }

    public function persistSafetySnapshot(string $temporaryPath): string
    {
        $directory = storage_path('app/system/backups');
        $fileName = $this->fileName('before-restore');
        $path = $directory.DIRECTORY_SEPARATOR.$fileName;
        if (! rename($temporaryPath, $path)) {
            throw new RuntimeException('Backup keselamatan sebelum pemulihan tidak dapat disimpan.');
        }

        return $fileName;
    }

    private function operation(string $origin, ?string $runKey): SystemMaintenanceOperation
    {
        if ($runKey) {
            return SystemMaintenanceOperation::query()->updateOrCreate(
                ['operation_key' => $runKey],
                ['operation' => 'BACKUP', 'status' => 'RUNNING', 'message' => 'Snapshot database sedang dibuat.', 'metadata' => ['origin' => $origin]]
            );
        }

        return SystemMaintenanceOperation::create([
            'operation' => 'BACKUP',
            'status' => 'RUNNING',
            'message' => 'Snapshot database sedang dibuat.',
            'metadata' => ['origin' => $origin],
        ]);
    }

    private function pruneBackups(int $retention): void
    {
        foreach (array_slice($this->backupFiles(), $retention) as $file) {
            $path = $this->resolveBackupPath($file['name']);
            if (! unlink($path)) {
                throw new RuntimeException('Backup lama tidak dapat dibersihkan sesuai retensi yang dipilih.');
            }
        }
    }

    private function fileName(?string $suffix): string
    {
        return 'erp_'.now()->format('Y-m-d_His_u').($suffix ? '_'.$suffix : '').'.sqlite';
    }

    private function ensureDirectory(string $directory): void
    {
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException('Folder penyimpanan backup tidak dapat dibuat.');
        }
    }
}
