<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index(['action', 'created_at'], 'activity_logs_action_created_at_index');
            $table->index(['user_id', 'created_at'], 'activity_logs_user_created_at_index');
            $table->index(['module_name', 'created_at'], 'activity_logs_module_created_at_index');
        });

        Schema::create('system_maintenance_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('automatic_backup_enabled')->default(false);
            $table->string('backup_frequency', 10)->default('daily');
            $table->string('backup_time', 5)->default('02:00');
            $table->unsignedTinyInteger('backup_weekly_day')->default(1);
            $table->unsignedTinyInteger('backup_monthly_day')->default(1);
            $table->unsignedSmallInteger('backup_retention')->default(14);
            $table->boolean('automatic_archive_enabled')->default(true);
            $table->unsignedSmallInteger('active_log_retention_days')->default(180);
            $table->unsignedSmallInteger('archive_retention_days')->nullable();
            $table->timestamp('last_scheduler_seen_at')->nullable();
            $table->timestamp('last_backup_attempt_at')->nullable();
            $table->timestamp('last_backup_success_at')->nullable();
            $table->string('last_backup_status', 20)->nullable();
            $table->text('last_backup_message')->nullable();
            $table->string('last_backup_run_key')->nullable();
            $table->timestamp('last_archive_attempt_at')->nullable();
            $table->timestamp('last_archive_success_at')->nullable();
            $table->string('last_archive_status', 20)->nullable();
            $table->text('last_archive_message')->nullable();
            $table->string('last_archive_run_key')->nullable();
            $table->timestamps();
        });

        Schema::create('system_maintenance_operations', function (Blueprint $table) {
            $table->id();
            $table->string('operation', 20);
            $table->string('status', 20);
            $table->string('file_name')->nullable();
            $table->string('operation_key')->nullable()->unique();
            $table->text('message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['operation', 'created_at']);
            $table->index(['operation', 'status']);
        });

        if (Schema::hasTable('modules')) {
            $moduleId = DB::table('modules')->where('key', 'system_maintenance')->value('id');
            if (! $moduleId) {
                $moduleId = DB::table('modules')->insertGetId([
                    'key' => 'system_maintenance',
                    'name' => 'Pemeliharaan Sistem',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $superAdminId = DB::table('roles')->where('name', 'super_admin')->value('id');
            if ($superAdminId && Schema::hasTable('role_has_modules')) {
                DB::table('role_has_modules')->insertOrIgnore([
                    'role_id' => $superAdminId,
                    'module_id' => $moduleId,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('system_maintenance_operations');
        Schema::dropIfExists('system_maintenance_settings');
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex('activity_logs_action_created_at_index');
            $table->dropIndex('activity_logs_user_created_at_index');
            $table->dropIndex('activity_logs_module_created_at_index');
        });
    }
};
