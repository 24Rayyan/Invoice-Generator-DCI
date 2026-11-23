<?php

namespace App\Services;

use RuntimeException;
use SQLite3;

class LocalSqliteDatabase
{
    private const REQUIRED_TABLES = [
        'users',
        'roles',
        'modules',
        'role_has_modules',
        'customers',
        'documents',
        'document_items',
        'projects',
        'categories',
        'transactions',
        'project_transaction_allocations',
        'settings',
        'activity_logs',
        'activity_logs_fts',
        'system_maintenance_settings',
        'system_maintenance_operations',
    ];

    public function configuredPath(): string
    {
        $path = (string) config('database.connections.sqlite.database');

        if ($path === '' || $path === ':memory:' || str_starts_with($path, 'file:')) {
            throw new RuntimeException('Database pemeliharaan hanya mendukung berkas SQLite lokal.');
        }

        if (! str_starts_with($path, DIRECTORY_SEPARATOR) && ! preg_match('/^[A-Za-z]:[\\\\\/]/', $path)) {
            $path = base_path($path);
        }

        return $path;
    }

    public function createSnapshot(string $sourcePath, string $destinationPath): void
    {
        if (! class_exists(SQLite3::class) || ! method_exists(SQLite3::class, 'backup')) {
            throw new RuntimeException('Ekstensi SQLite3 dengan Online Backup API tidak tersedia pada PHP aktif.');
        }

        $source = new SQLite3($sourcePath, SQLITE3_OPEN_READONLY);
        $destination = new SQLite3($destinationPath, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
        $source->enableExceptions(true);
        $destination->enableExceptions(true);
        $destination->busyTimeout(10000);

        try {
            if (! $source->backup($destination)) {
                throw new RuntimeException('SQLite Online Backup API gagal membuat salinan database.');
            }
        } finally {
            $source->close();
            $destination->close();
        }
    }

    public function assertValid(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('Berkas SQLite tidak ditemukan atau tidak dapat dibaca.');
        }

        $database = new SQLite3($path, SQLITE3_OPEN_READONLY);
        $database->enableExceptions(true);

        try {
            $integrity = $database->querySingle('PRAGMA integrity_check');
            if ($integrity !== 'ok') {
                throw new RuntimeException('Pemeriksaan integritas SQLite gagal.');
            }

            $tables = [];
            $result = $database->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'");
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $tables[] = $row['name'];
            }

            $missing = array_values(array_diff(self::REQUIRED_TABLES, $tables));
            if ($missing !== []) {
                throw new RuntimeException('Berkas SQLite tidak memiliki seluruh tabel aplikasi yang diwajibkan.');
            }

            return $tables;
        } finally {
            $database->close();
        }
    }

    public function checkpoint(string $path): void
    {
        $database = new SQLite3($path, SQLITE3_OPEN_READWRITE);
        $database->enableExceptions(true);

        try {
            $result = $database->query('PRAGMA wal_checkpoint(TRUNCATE)');
            $checkpoint = $result->fetchArray(SQLITE3_NUM);
            if ($checkpoint && (int) $checkpoint[0] !== 0) {
                throw new RuntimeException('Database sedang dipakai; checkpoint WAL tidak dapat diselesaikan.');
            }
        } finally {
            $database->close();
        }
    }
}
