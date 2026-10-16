<?php

namespace App\Models\Concerns;

use App\Models\ActivityLog;
use App\Services\ActivityLogger;
use Illuminate\Support\Str;

/**
 * Trait LogsActivity
 *
 * Cukup tambahkan `use LogsActivity;` pada sebuah model Eloquent, maka
 * seluruh operasi Create / Update / Delete pada model tersebut akan
 * tercatat otomatis ke tabel activity_logs — tanpa menulis kode log
 * berulang-ulang di setiap controller.
 *
 * Model dapat meng-override method berikut untuk mempercantik log:
 *  - activityModuleName()  : nama modul aplikasi (mis. "Invoicing & PO")
 *  - activitySubjectName() : label entitas (mis. "Invoice")
 *  - activityLabel()       : identitas unik record (mis. nomor dokumen)
 */
trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            ActivityLogger::record(ActivityLog::ACTION_CREATE, $model);
        });

        static::updated(function ($model) {
            ActivityLogger::record(ActivityLog::ACTION_UPDATE, $model);
        });

        static::deleted(function ($model) {
            ActivityLogger::record(ActivityLog::ACTION_DELETE, $model);
        });
    }

    /**
     * Nama modul tempat aksi dilakukan.
     */
    public function activityModuleName(): string
    {
        return Str::headline(class_basename($this));
    }

    /**
     * Nama entitas/subjek naratif (mis. "Customer").
     */
    public function activitySubjectName(): string
    {
        return Str::headline(class_basename($this));
    }

    /**
     * Identitas unik record untuk deskripsi log. Mengambil atribut umum
     * yang tersedia, atau fallback ke primary key.
     */
    public function activityLabel(): string
    {
        foreach (['name', 'title', 'display_name', 'document_number', 'transaction_number', 'username', 'email'] as $attribute) {
            $value = $this->getAttribute($attribute);

            if (!empty($value)) {
                return (string) $value;
            }
        }

        return '#' . $this->getKey();
    }
}
