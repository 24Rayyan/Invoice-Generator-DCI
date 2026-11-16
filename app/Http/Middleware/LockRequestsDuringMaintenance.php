<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class LockRequestsDuringMaintenance
{
    private const EXCLUSIVE_OPERATION_ROUTES = [
        'system-maintenance.backup',
        'system-maintenance.archive',
        'system-maintenance.backups.restore',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->route()?->getName(), self::EXCLUSIVE_OPERATION_ROUTES, true)) {
            return $next($request);
        }

        $directory = storage_path('app/system/locks');
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException('Folder penguncian pemeliharaan sistem tidak dapat dibuat.');
        }

        $handle = fopen($directory.DIRECTORY_SEPARATOR.'system-maintenance.lock', 'c+b');
        if ($handle === false) {
            throw new RuntimeException('Berkas penguncian pemeliharaan sistem tidak dapat dibuka.');
        }

        try {
            if (! flock($handle, LOCK_SH | LOCK_NB)) {
                return response('Operasi pemeliharaan sedang berlangsung. Coba lagi setelah selesai.', 503);
            }

            return $next($request);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
