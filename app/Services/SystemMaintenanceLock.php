<?php

namespace App\Services;

use RuntimeException;

class SystemMaintenanceLock
{
    public function run(callable $operation): mixed
    {
        $directory = storage_path('app/system/locks');
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException('Folder penguncian pemeliharaan sistem tidak dapat dibuat.');
        }

        $handle = fopen($directory.DIRECTORY_SEPARATOR.'system-maintenance.lock', 'c+b');
        if ($handle === false) {
            throw new RuntimeException('Berkas penguncian pemeliharaan sistem tidak dapat dibuka.');
        }

        try {
            if (! flock($handle, LOCK_EX | LOCK_NB)) {
                throw new RuntimeException('Operasi pemeliharaan lain sedang berjalan. Silakan coba kembali.');
            }

            return $operation();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
