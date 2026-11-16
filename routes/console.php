<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Console\Command;
use App\Services\ActivityLogArchiveService;
use App\Services\SystemBackupService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('system:backup', function (SystemBackupService $backups) {
    try {
        $file = $backups->createBackup();
        $this->info("Backup SQLite berhasil dibuat: {$file}");
        return Command::SUCCESS;
    } catch (Throwable $exception) {
        $this->error($exception->getMessage());
        return Command::FAILURE;
    }
})->purpose('Create and verify a full local SQLite database backup');

Artisan::command('activity-logs:archive', function (ActivityLogArchiveService $archives) {
    try {
        $result = $archives->archiveOldLogs(now(), true);
        $this->info("Log aktivitas diarsipkan: {$result['archived']}");
        return Command::SUCCESS;
    } catch (Throwable $exception) {
        $this->error($exception->getMessage());
        return Command::FAILURE;
    }
})->purpose('Archive verified activity logs older than the configured retention period');

Schedule::call(fn () => app(SystemBackupService::class)->tick())
    ->everyMinute()
    ->name('system-maintenance-backup-scheduler');

Schedule::call(fn () => app(ActivityLogArchiveService::class)->tick())
    ->everyMinute()
    ->name('activity-log-archive-scheduler');
