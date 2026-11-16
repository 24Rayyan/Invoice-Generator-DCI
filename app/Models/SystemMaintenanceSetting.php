<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemMaintenanceSetting extends Model
{
    protected $fillable = [
        'automatic_backup_enabled',
        'backup_frequency',
        'backup_time',
        'backup_weekly_day',
        'backup_monthly_day',
        'backup_retention',
        'automatic_archive_enabled',
        'active_log_retention_days',
        'archive_retention_days',
        'last_scheduler_seen_at',
        'last_backup_attempt_at',
        'last_backup_success_at',
        'last_backup_status',
        'last_backup_message',
        'last_backup_run_key',
        'last_archive_attempt_at',
        'last_archive_success_at',
        'last_archive_status',
        'last_archive_message',
        'last_archive_run_key',
    ];

    protected function casts(): array
    {
        return [
            'automatic_backup_enabled' => 'boolean',
            'automatic_archive_enabled' => 'boolean',
            'backup_weekly_day' => 'integer',
            'backup_monthly_day' => 'integer',
            'backup_retention' => 'integer',
            'active_log_retention_days' => 'integer',
            'archive_retention_days' => 'integer',
            'last_scheduler_seen_at' => 'datetime',
            'last_backup_attempt_at' => 'datetime',
            'last_backup_success_at' => 'datetime',
            'last_archive_attempt_at' => 'datetime',
            'last_archive_success_at' => 'datetime',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1]);
    }
}
