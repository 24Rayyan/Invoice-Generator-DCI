<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * ActivityLogger
 *
 * Titik tunggal (single source of truth) untuk menulis log aktivitas.
 * Dipanggil otomatis oleh trait App\Models\Concerns\LogsActivity melalui
 * model event Eloquent, sehingga controller tidak perlu menulis log manual.
 *
 * Untuk aksi yang tidak dapat ditangkap model event (mis. sinkronisasi pivot
 * role-module atau event login/logout), tersedia method log() generik.
 */
class ActivityLogger
{
    /**
     * Cache status keberadaan tabel per-request (di CLI tidak di-cache agar
     * aman saat proses migrasi/testing).
     */
    protected static ?bool $tableAvailable = null;

    /**
     * Catat aktivitas CRUD yang berasal dari sebuah model Eloquent.
     */
    public static function record(
        string $action,
        Model $model,
        ?string $description = null,
        ?array $properties = null
    ): ?ActivityLog {
        $action = strtoupper($action);

        $module = method_exists($model, 'activityModuleName')
            ? $model->activityModuleName()
            : Str::headline(class_basename($model));

        return static::write(
            $action,
            $module,
            $description ?? static::describe($action, $model),
            $properties ?? static::properties($action, $model),
            [
                'subject_type' => $model->getMorphClass(),
                'subject_id'   => $model->getKey(),
            ]
        );
    }

    /**
     * Catat aktivitas generik yang tidak berbasis model (mis. login/logout
     * atau perubahan pivot role-module).
     */
    public static function log(
        string $action,
        string $module,
        string $description,
        ?array $properties = null,
        ?int $userId = null,
        ?string $userName = null
    ): ?ActivityLog {
        return static::write(
            strtoupper($action),
            $module,
            $description,
            $properties,
            [],
            $userId,
            $userName
        );
    }

    /**
     * Menulis baris log ke database. Seluruh kegagalan dicatat sebagai
     * warning dan tidak boleh sampai menggagalkan aksi utama user.
     */
    protected static function write(
        string $action,
        string $module,
        string $description,
        ?array $properties,
        array $subject = [],
        ?int $userId = null,
        ?string $userName = null
    ): ?ActivityLog {
        if (!static::tableAvailable()) {
            return null;
        }

        try {
            $user = Auth::user();

            return ActivityLog::create([
                'user_id'      => $userId ?? $user?->getKey(),
                'user_name'    => $userName ?? ($user?->username ?? $user?->name),
                'action'       => $action,
                'module_name'  => $module,
                'subject_type' => $subject['subject_type'] ?? null,
                'subject_id'   => $subject['subject_id'] ?? null,
                'description'  => $description,
                'properties'   => $properties,
            ]);
        } catch (\Throwable $e) {
            Log::warning('[ActivityLog] Gagal mencatat aktivitas: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Menyusun deskripsi naratif, contoh:
     * "User admin memperbarui data Customer \"PT ABC\"."
     */
    protected static function describe(string $action, Model $model): string
    {
        $user = Auth::user();
        $actor = $user?->username ?? $user?->name ?? 'Sistem';

        $verb = match ($action) {
            ActivityLog::ACTION_CREATE => 'menambahkan',
            ActivityLog::ACTION_UPDATE => 'memperbarui',
            ActivityLog::ACTION_DELETE => 'menghapus',
            default                    => strtolower($action),
        };

        $subject = method_exists($model, 'activitySubjectName')
            ? $model->activitySubjectName()
            : Str::headline(class_basename($model));

        $label = method_exists($model, 'activityLabel')
            ? $model->activityLabel()
            : '#' . $model->getKey();

        return "User {$actor} {$verb} data {$subject} \"{$label}\".";
    }

    /**
     * Menyusun payload properties (perubahan data lama vs baru).
     */
    protected static function properties(string $action, Model $model): ?array
    {
        $hidden = array_merge($model->getHidden(), ['created_at', 'updated_at']);
        $filter = static fn (array $attributes): array => array_diff_key($attributes, array_flip($hidden));

        return match ($action) {
            ActivityLog::ACTION_CREATE => [
                'attributes' => $filter($model->getAttributes()),
            ],
            ActivityLog::ACTION_DELETE => [
                'old' => $filter($model->getOriginal()),
            ],
            ActivityLog::ACTION_UPDATE => (function () use ($model, $filter): array {
                $changes  = $filter($model->getChanges());
                $original = $filter($model->getOriginal());

                return [
                    'old' => array_intersect_key($original, $changes),
                    'new' => $changes,
                ];
            })(),
            default => null,
        };
    }

    /**
     * Cek keberadaan tabel log. Amannya: bila aplikasi belum di-migrate,
     * logging dilewati tanpa error.
     */
    protected static function tableAvailable(): bool
    {
        if (static::$tableAvailable !== null) {
            return static::$tableAvailable;
        }

        $available = false;

        try {
            $available = Schema::hasTable('activity_logs');
        } catch (\Throwable $e) {
            $available = false;
        }

        // Di CLI (migrate/test) jangan cache agar perubahan skema langsung terdeteksi.
        if (!app()->runningInConsole()) {
            static::$tableAvailable = $available;
        }

        return $available;
    }

    /**
     * Reset cache status tabel (utilitas untuk pengujian).
     */
    public static function forgetTableCache(): void
    {
        static::$tableAvailable = null;
    }
}
