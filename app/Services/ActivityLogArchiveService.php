<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\SystemMaintenanceOperation;
use App\Models\SystemMaintenanceSetting;
use App\Support\ActivityLogSearch;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use SQLite3;
use Throwable;

class ActivityLogArchiveService
{
    public function __construct(private readonly SystemMaintenanceLock $lock) {}

    public function tick(): void
    {
        $now = now();
        $settings = SystemMaintenanceSetting::current();
        if (! $settings->automatic_archive_enabled || $now->day !== 1) {
            return;
        }

        try {
            $this->archiveOldLogs($now);
        } catch (Throwable $exception) {
            Log::error('Scheduled activity log archive failed.', ['exception' => $exception->getMessage()]);
        }
    }

    public function archiveOldLogs(?Carbon $now = null, bool $manual = false): array
    {
        return $this->lock->run(function () use ($now, $manual): array {
            $settings = SystemMaintenanceSetting::current();
            $now ??= now();
            $cutoff = $now->copy()->subDays($settings->active_log_retention_days);
            $runKey = $manual ? 'archive:manual:'.bin2hex(random_bytes(8)) : 'archive:'.$now->format('Y-m');

            if (SystemMaintenanceOperation::query()->where('operation_key', $runKey)->where('status', 'SUCCESS')->exists()) {
                return ['archived' => 0, 'periods' => []];
            }

            $operation = SystemMaintenanceOperation::query()->updateOrCreate(
                ['operation_key' => $runKey],
                ['operation' => 'ARCHIVE', 'status' => 'RUNNING', 'message' => 'Arsip log aktivitas sedang dibuat.']
            );
            $settings->update([
                'last_archive_attempt_at' => $now,
                'last_archive_status' => 'RUNNING',
                'last_archive_message' => 'Arsip log aktivitas sedang dibuat.',
                'last_archive_run_key' => $runKey,
            ]);

            try {
                $periods = DB::table('activity_logs')
                    ->where('created_at', '<', $cutoff->toDateTimeString())
                    ->selectRaw("substr(created_at, 1, 7) as period")
                    ->distinct()
                    ->orderBy('period')
                    ->pluck('period');
                $archivedCount = 0;

                foreach ($periods as $period) {
                    if (! preg_match('/^\d{4}-\d{2}$/', $period)) {
                        continue;
                    }

                    $archivedCount += $this->archivePeriod($period, $cutoff);
                }

                $this->pruneArchives($settings->archive_retention_days, $now);
                $message = "{$archivedCount} log aktivitas berhasil diarsipkan.";
                $settings->update([
                    'last_archive_success_at' => $now,
                    'last_archive_status' => 'SUCCESS',
                    'last_archive_message' => $message,
                ]);
                $operation->update([
                    'status' => 'SUCCESS',
                    'message' => $message,
                    'metadata' => ['periods' => $periods->all(), 'record_count' => $archivedCount],
                ]);
                ActivityLogger::log(
                    ActivityLog::ACTION_EXPORT,
                    'Log Aktivitas',
                    'Pengarsipan log aktivitas selesai.',
                    ['archived_count' => $archivedCount, 'periods' => $periods->all(), 'manual' => $manual]
                );

                return ['archived' => $archivedCount, 'periods' => $periods->all()];
            } catch (Throwable $exception) {
                $message = 'Arsip gagal diselesaikan. Log yang belum diverifikasi tetap tersedia pada database aktif.';
                $settings->update(['last_archive_status' => 'FAILED', 'last_archive_message' => $message]);
                $operation->update(['status' => 'FAILED', 'message' => $message]);
                Log::error('Activity log archive failed.', ['exception' => $exception->getMessage()]);

                throw new RuntimeException($message, previous: $exception);
            }
        });
    }

    public function archivePeriods(): array
    {
        $directory = storage_path('app/system/archives');
        if (! is_dir($directory)) {
            return [];
        }

        $periods = [];
        foreach (glob($directory.DIRECTORY_SEPARATOR.'activity_logs_*.sqlite') ?: [] as $path) {
            if (is_file($path) && preg_match('/^activity_logs_(\d{4}_\d{2})\.sqlite$/', basename($path), $matches)) {
                $periods[] = str_replace('_', '-', $matches[1]);
            }
        }

        rsort($periods);

        return $periods;
    }

    public function archivedLogs(string $period, array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $path = $this->archivePath($period);
        $database = new SQLite3($path, SQLITE3_OPEN_READONLY);
        $database->enableExceptions(true);

        try {
            $where = [];
            $bindings = [];
            foreach (['action' => 'action', 'module' => 'module_name', 'user_id' => 'user_id'] as $filter => $column) {
                if (! empty($filters[$filter])) {
                    $where[] = "\"{$column}\" = :{$filter}";
                    $bindings[$filter] = $filter === 'action' ? strtoupper($filters[$filter]) : $filters[$filter];
                }
            }
            foreach (['date_from' => '>=', 'date_to' => '<'] as $filter => $operator) {
                if (! empty($filters[$filter])) {
                    $where[] = '"created_at" '.$operator.' :'.$filter;
                    $bindings[$filter] = $filter === 'date_to'
                        ? Carbon::parse($filters[$filter])->addDay()->toDateString()
                        : Carbon::parse($filters[$filter])->startOfDay()->toDateTimeString();
                }
            }
            if (isset($filters['search']) && $filters['search'] !== '') {
                $search = ActivityLogSearch::ftsMatchExpression($filters['search']);
                if ($search === null) {
                    $where[] = '1 = 0';
                } elseif ($this->hasFullTextIndex($database)) {
                    $where[] = 'id IN (SELECT rowid FROM activity_logs_fts WHERE activity_logs_fts MATCH :search)';
                    $bindings['search'] = $search;
                } else {
                    $where[] = '(description LIKE :search OR user_name LIKE :search OR module_name LIKE :search)';
                    $bindings['search'] = '%'.$filters['search'].'%';
                }
            }

            $clause = $where ? ' WHERE '.implode(' AND ', $where) : '';
            $count = $database->prepare('SELECT COUNT(*) FROM activity_logs'.$clause);
            $this->bind($count, $bindings);
            $total = (int) $count->execute()->fetchArray(SQLITE3_NUM)[0];

            $statement = $database->prepare('SELECT * FROM activity_logs'.$clause.' ORDER BY id DESC LIMIT :limit OFFSET :offset');
            $this->bind($statement, $bindings);
            $statement->bindValue(':limit', $perPage, SQLITE3_INTEGER);
            $page = LengthAwarePaginator::resolveCurrentPage();
            $statement->bindValue(':offset', ($page - 1) * $perPage, SQLITE3_INTEGER);
            $result = $statement->execute();
            $logs = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $logs[] = (new ActivityLog())->newFromBuilder($row);
            }

            return new LengthAwarePaginator(
                $logs,
                $total,
                $perPage,
                $page,
                ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => request()->query()]
            );
        } finally {
            $database->close();
        }
    }

    public function archiveDetails(string $period): array
    {
        $database = new SQLite3($this->archivePath($period), SQLITE3_OPEN_READONLY);
        $database->enableExceptions(true);

        try {
            return [
                'actions' => $this->columnValues($database, 'action'),
                'modules' => $this->columnValues($database, 'module_name'),
                'action_counts' => $this->actionCounts($database),
                'count' => (int) $database->querySingle('SELECT COUNT(*) FROM activity_logs'),
            ];
        } finally {
            $database->close();
        }
    }

    public function findArchivedLog(string $period, int $id): ?ActivityLog
    {
        $database = new SQLite3($this->archivePath($period), SQLITE3_OPEN_READONLY);
        $database->enableExceptions(true);

        try {
            $statement = $database->prepare('SELECT * FROM activity_logs WHERE id = :id LIMIT 1');
            $statement->bindValue(':id', $id, SQLITE3_INTEGER);
            $row = $statement->execute()->fetchArray(SQLITE3_ASSOC);

            return $row ? (new ActivityLog())->newFromBuilder($row) : null;
        } finally {
            $database->close();
        }
    }

    public function archiveBytes(): int
    {
        $bytes = 0;
        foreach ($this->archivePeriods() as $period) {
            $bytes += filesize($this->archivePath($period)) ?: 0;
        }

        return $bytes;
    }

    public function archivePath(string $period): string
    {
        abort_unless(preg_match('/^\d{4}-\d{2}$/', $period) === 1, 404);
        $directory = realpath(storage_path('app/system/archives'));
        $fileName = 'activity_logs_'.str_replace('-', '_', $period).'.sqlite';
        $path = realpath(storage_path('app/system/archives'.DIRECTORY_SEPARATOR.$fileName));

        abort_unless($directory && $path && dirname($path) === $directory && is_file($path), 404);

        return $path;
    }

    private function archivePeriod(string $period, Carbon $cutoff): int
    {
        $directory = storage_path('app/system/archives');
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException('Folder arsip aktivitas tidak dapat dibuat.');
        }

        $fileName = 'activity_logs_'.str_replace('-', '_', $period).'.sqlite';
        $path = $directory.DIRECTORY_SEPARATOR.$fileName;
        $database = new SQLite3($path, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
        $database->enableExceptions(true);

        try {
            $database->exec('PRAGMA journal_mode = DELETE');
            $database->exec('CREATE TABLE IF NOT EXISTS activity_logs (
                id INTEGER PRIMARY KEY,
                user_id INTEGER NULL,
                user_name TEXT NULL,
                action TEXT NOT NULL,
                module_name TEXT NOT NULL,
                subject_type TEXT NULL,
                subject_id INTEGER NULL,
                description TEXT NOT NULL,
                properties TEXT NULL,
                created_at TEXT NULL,
                updated_at TEXT NULL
            )');
            $database->exec('CREATE INDEX IF NOT EXISTS activity_logs_archive_created_at_index ON activity_logs(created_at)');
            $database->exec("CREATE VIRTUAL TABLE IF NOT EXISTS activity_logs_fts USING fts5(description, user_name, module_name, content='activity_logs', content_rowid='id')");
            $expected = (int) DB::table('activity_logs')
                ->whereRaw('substr(created_at, 1, 7) = ?', [$period])
                ->where('created_at', '<', $cutoff->toDateTimeString())
                ->count();

            DB::table('activity_logs')
                ->whereRaw('substr(created_at, 1, 7) = ?', [$period])
                ->where('created_at', '<', $cutoff->toDateTimeString())
                ->orderBy('id')
                ->chunkById(500, function ($records) use ($database): void {
                    $database->exec('BEGIN IMMEDIATE');
                    try {
                        $statement = $database->prepare('INSERT OR REPLACE INTO activity_logs
                            (id, user_id, user_name, action, module_name, subject_type, subject_id, description, properties, created_at, updated_at)
                            VALUES (:id, :user_id, :user_name, :action, :module_name, :subject_type, :subject_id, :description, :properties, :created_at, :updated_at)');

                        foreach ($records as $record) {
                            foreach ([
                                'id', 'user_id', 'user_name', 'action', 'module_name', 'subject_type',
                                'subject_id', 'description', 'properties', 'created_at', 'updated_at',
                            ] as $field) {
                                $statement->bindValue(':'.$field, $record->{$field}, $record->{$field} === null ? SQLITE3_NULL : (in_array($field, ['id', 'user_id', 'subject_id'], true) ? SQLITE3_INTEGER : SQLITE3_TEXT));
                            }
                            $statement->execute();
                            $statement->reset();
                        }

                        $database->exec('COMMIT');
                    } catch (Throwable $exception) {
                        $database->exec('ROLLBACK');
                        throw $exception;
                    }
                });

            $database->exec("INSERT INTO activity_logs_fts(activity_logs_fts) VALUES ('rebuild')");
            if ($database->querySingle('PRAGMA integrity_check') !== 'ok') {
                throw new RuntimeException("Integrity check arsip {$period} gagal.");
            }

            $verified = 0;
            DB::table('activity_logs')
                ->whereRaw('substr(created_at, 1, 7) = ?', [$period])
                ->where('created_at', '<', $cutoff->toDateTimeString())
                ->orderBy('id')
                ->chunkById(500, function ($records) use ($database, &$verified): void {
                    $ids = $records->pluck('id')->all();
                    $placeholders = implode(',', array_fill(0, count($ids), '?'));
                    $statement = $database->prepare("SELECT COUNT(*) FROM activity_logs WHERE id IN ({$placeholders})");
                    foreach ($ids as $index => $id) {
                        $statement->bindValue($index + 1, $id, SQLITE3_INTEGER);
                    }
                    $verified += (int) $statement->execute()->fetchArray(SQLITE3_NUM)[0];
                });

            if ($verified !== $expected) {
                throw new RuntimeException("Jumlah record arsip {$period} tidak sesuai dengan data aktif.");
            }

            $deleted = DB::transaction(function () use ($period, $cutoff, $expected): int {
                $query = DB::table('activity_logs')
                    ->whereRaw('substr(created_at, 1, 7) = ?', [$period])
                    ->where('created_at', '<', $cutoff->toDateTimeString());
                if ($query->count() !== $expected) {
                    throw new RuntimeException("Jumlah log aktif periode {$period} berubah saat pengarsipan.");
                }

                return $query->delete();
            });

            if ($deleted !== $expected) {
                throw new RuntimeException("Tidak seluruh record periode {$period} berhasil dihapus setelah verifikasi.");
            }

            return $deleted;
        } finally {
            $database->close();
        }
    }

    private function pruneArchives(?int $retentionDays, Carbon $now): void
    {
        if ($retentionDays === null) {
            return;
        }

        foreach ($this->archivePeriods() as $period) {
            $archiveMonthEnd = Carbon::createFromFormat('Y-m', $period)->endOfMonth();
            if ($archiveMonthEnd->lt($now->copy()->subDays($retentionDays))) {
                $path = $this->archivePath($period);
                if (! unlink($path)) {
                    throw new RuntimeException("Arsip periode {$period} tidak dapat dibersihkan.");
                }
            }
        }
    }

    private function columnValues(SQLite3 $database, string $column): array
    {
        $statement = $database->prepare("SELECT DISTINCT \"{$column}\" FROM activity_logs ORDER BY \"{$column}\"");
        $result = $statement->execute();
        $values = [];
        while ($row = $result->fetchArray(SQLITE3_NUM)) {
            $values[] = $row[0];
        }

        return $values;
    }

    private function actionCounts(SQLite3 $database): array
    {
        $result = $database->query('SELECT action, COUNT(*) AS count FROM activity_logs GROUP BY action');
        $counts = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $counts[$row['action']] = (int) $row['count'];
        }

        return $counts;
    }

    private function hasFullTextIndex(SQLite3 $database): bool
    {
        return (int) $database->querySingle("SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = 'activity_logs_fts'") > 0;
    }

    private function bind(\SQLite3Stmt $statement, array $bindings): void
    {
        foreach ($bindings as $name => $value) {
            $statement->bindValue(':'.$name, $value, is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT);
        }
    }
}
