<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Module;
use App\Models\Project;
use App\Models\Role;
use App\Models\SystemMaintenanceSetting;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ActivityLogArchiveService;
use App\Services\LocalSqliteDatabase;
use App\Services\SystemBackupService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SystemMaintenanceTest extends TestCase
{
    private string $temporaryRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->temporaryRoot = sys_get_temp_dir().DIRECTORY_SEPARATOR.'mini-erp-maintenance-'.bin2hex(random_bytes(6));
        $databaseDirectory = $this->temporaryRoot.DIRECTORY_SEPARATOR.'database';
        $storageDirectory = $this->temporaryRoot.DIRECTORY_SEPARATOR.'storage';
        mkdir($databaseDirectory, 0700, true);
        mkdir($storageDirectory.DIRECTORY_SEPARATOR.'framework', 0700, true);

        config(['database.connections.sqlite.database' => $databaseDirectory.DIRECTORY_SEPARATOR.'testing.sqlite']);
        DB::purge('sqlite');
        $this->app->useStoragePath($storageDirectory);

        Artisan::call('migrate', ['--force' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        DB::purge('sqlite');
        File::deleteDirectory($this->temporaryRoot);

        parent::tearDown();
    }

    public function test_full_backup_restore_and_archived_logs_are_verified_and_accessible(): void
    {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin);

        $customer = \App\Models\Customer::create([
            'name' => 'PT Snapshot Awal',
            'tax_id_type' => 'NPWP16',
            'default_tax_transaction_code' => '040',
        ]);
        $backupService = app(SystemBackupService::class);
        $backupFile = $backupService->createBackup();
        $backupPath = $backupService->resolveBackupPath($backupFile);

        $this->assertContains('activity_logs', app(LocalSqliteDatabase::class)->assertValid($backupPath));
        $this->assertContains('customers', app(LocalSqliteDatabase::class)->assertValid($backupPath));
        $this->get(route('system-maintenance.index'))->assertOk()->assertSee('Backup Database Penuh');
        $this->get(route('system-maintenance.backups.download', $backupFile))->assertOk();

        $customer->update(['name' => 'PT Perubahan Setelah Snapshot']);
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'PT Perubahan Setelah Snapshot']);

        $backupDirectory = storage_path('app/system/backups');
        file_put_contents($backupDirectory.DIRECTORY_SEPARATOR.'erp_2026-11-16_000000_000001.sqlite', 'not a sqlite database');
        $invalidRestoreError = null;
        try {
            $backupService->restore('erp_2026-11-16_000000_000001.sqlite');
        } catch (\Throwable $exception) {
            $invalidRestoreError = $exception;
        }
        $this->assertNotNull($invalidRestoreError, 'An invalid SQLite backup must be rejected.');
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'PT Perubahan Setelah Snapshot']);

        $this->post(route('system-maintenance.backups.restore', $backupFile), [])
            ->assertSessionHasErrors('confirmation');
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'PT Perubahan Setelah Snapshot']);

        $this->post(route('system-maintenance.backups.restore', $backupFile), ['confirmation' => '1'])
            ->assertRedirect(route('system-maintenance.index'))
            ->assertSessionHas('success');
        $this->assertTrue(collect($backupService->backupFiles())->contains(fn (array $file) => $file['safety']));
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'PT Snapshot Awal']);
        $this->assertDatabaseHas('system_maintenance_operations', ['operation' => 'RESTORE', 'status' => 'SUCCESS']);

        $archivedAt = now()->subDays(181);
        $oldLog = ActivityLog::create([
            'user_id' => $admin->id,
            'user_name' => $admin->username,
            'action' => ActivityLog::ACTION_EXPORT,
            'module_name' => 'Test',
            'description' => 'Archive record 100% complete batch',
        ]);
        $oldLog->forceFill(['created_at' => $archivedAt, 'updated_at' => $archivedAt])->saveQuietly();
        for ($index = 1; $index <= 10; $index++) {
            $batchLog = ActivityLog::create([
                'user_id' => $admin->id,
                'user_name' => $admin->username,
                'action' => ActivityLog::ACTION_EXPORT,
                'module_name' => 'Test',
                'description' => 'Archive batch record '.$index,
            ]);
            $batchLog->forceFill(['created_at' => $archivedAt, 'updated_at' => $archivedAt])->saveQuietly();
        }
        $result = app(ActivityLogArchiveService::class)->archiveOldLogs(now(), true);

        $this->assertSame(11, $result['archived']);
        $this->assertDatabaseMissing('activity_logs', ['id' => $oldLog->id]);
        $this->assertFileExists(storage_path('app/system/archives/activity_logs_'.$archivedAt->format('Y_m').'.sqlite'));

        $this->get(route('activity_logs.index', [
            'source' => 'archive',
            'period' => $archivedAt->format('Y-m'),
            'search' => '100% complete',
        ]))->assertOk()->assertSee('Archive record 100% complete batch');
        $this->get(route('activity_logs.index', [
            'source' => 'archive',
            'period' => $archivedAt->format('Y-m'),
            'search' => 'batch',
            'per_page' => 10,
            'page' => 2,
        ]))->assertOk()->assertSee('11 aktivitas')->assertSee('Menampilkan 1 aktivitas');

        $this->get(route('activity_logs.archives.show', [$archivedAt->format('Y-m'), $oldLog->id]))
            ->assertOk()
            ->assertJsonPath('description', 'Archive record 100% complete batch');

        ActivityLog::create([
            'action' => ActivityLog::ACTION_EXPORT,
            'module_name' => 'Test',
            'description' => 'Active record contains literal 100% value',
        ]);
        $this->get(route('activity_logs.index', ['search' => 'literal 100% value']))
            ->assertOk()
            ->assertSee('Active record contains literal 100% value');
    }

    public function test_archiving_failure_keeps_original_activity_log(): void
    {
        $now = Carbon::create(2026, 11, 16, 12);
        $oldLog = ActivityLog::create([
            'action' => ActivityLog::ACTION_DELETE,
            'module_name' => 'Test',
            'description' => 'Must remain active',
        ]);
        $oldLog->forceFill([
            'created_at' => $now->copy()->subYear(),
            'updated_at' => $now->copy()->subYear(),
        ])->saveQuietly();
        $this->assertDatabaseHas('activity_logs', ['id' => $oldLog->id]);

        $archiveDirectory = storage_path('app/system/archives');
        mkdir($archiveDirectory, 0700, true);
        mkdir($archiveDirectory.DIRECTORY_SEPARATOR.'activity_logs_2025_11.sqlite', 0700, true);

        $exception = null;
        try {
            app(ActivityLogArchiveService::class)->archiveOldLogs($now, true);
        } catch (\RuntimeException $caught) {
            $exception = $caught;
        }
        $this->assertNotNull($exception, 'A corrupt archive file must make the archive operation fail.');
        $this->assertDatabaseHas('activity_logs', ['id' => $oldLog->id]);
    }

    public function test_backup_schedule_configuration_validates_and_handles_short_months(): void
    {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin);

        $this->post(route('system-maintenance.settings'), [
            'automatic_backup_enabled' => '1',
            'backup_frequency' => 'monthly',
            'backup_time' => '02:00',
            'backup_monthly_day' => 31,
            'backup_retention' => 14,
            'automatic_archive_enabled' => '1',
            'active_log_retention_days' => 180,
            'archive_retention_days' => '',
        ])->assertRedirect();

        $settings = SystemMaintenanceSetting::current();
        $this->assertTrue($settings->automatic_backup_enabled);

        $dueInFebruary = Carbon::create(2026, 2, 28, 2, 0);
        $this->assertTrue(app(SystemBackupService::class)->isBackupDue($settings, $dueInFebruary));

        $backupService = app(SystemBackupService::class);
        $settings->update([
            'backup_frequency' => 'daily',
            'backup_time' => '02:00',
            'automatic_backup_enabled' => false,
        ]);
        Carbon::setTestNow($dueInFebruary);
        $backupService->tick();
        $this->assertCount(0, $backupService->backupFiles());

        $settings->update(['automatic_backup_enabled' => true]);
        $backupService->tick();
        $backupService->tick();
        $this->assertCount(1, $backupService->backupFiles());
        $settings->update(['backup_retention' => 1]);
        Carbon::setTestNow($dueInFebruary->copy()->addSecond());
        $backupService->createBackup();
        $this->assertCount(1, $backupService->backupFiles());

        $settings->update([
            'backup_frequency' => 'weekly',
            'backup_weekly_day' => $dueInFebruary->dayOfWeek,
        ]);
        $this->assertTrue($backupService->isBackupDue($settings, $dueInFebruary));

        $this->post(route('system-maintenance.settings'), [
            'automatic_backup_enabled' => '1',
            'backup_frequency' => 'weekly',
            'backup_time' => '26:00',
            'backup_weekly_day' => 1,
            'backup_retention' => 14,
            'active_log_retention_days' => 180,
        ])->assertSessionHasErrors('backup_time');
    }

    public function test_users_without_maintenance_permission_cannot_access_backup_controls(): void
    {
        $finance = $this->makeUser('finance');
        $this->actingAs($finance);

        $this->get(route('system-maintenance.index'))->assertForbidden();
    }

    public function test_database_access_is_grantable_from_the_user_management_role_group(): void
    {
        $admin = $this->makeUser('super_admin');
        $databaseUser = $this->makeUser('database_operator');
        $module = Module::query()->where('key', 'system_maintenance')->firstOrFail();
        $response = $this->actingAs($admin)->get(route('management.roles.index'))->assertOk();
        $groups = $response->getOriginalContent()->getData()['moduleGroups'];

        $userManagement = $groups->firstWhere('name', 'User Management');
        $settings = $groups->firstWhere('name', 'Pengaturan');
        $this->assertTrue($userManagement['modules']->contains('key', 'system_maintenance'));
        $this->assertFalse($settings['modules']->contains('key', 'system_maintenance'));
        $this->actingAs($databaseUser)->get(route('system-maintenance.index'))->assertForbidden();

        $role = $databaseUser->role;
        $this->actingAs($admin)
            ->post(route('management.roles.grant-modules', $role), ['modules' => [$module->id]])
            ->assertRedirect();

        $this->actingAs($databaseUser->fresh())
            ->get(route('system-maintenance.index'))
            ->assertOk()
            ->assertSee('Pengelolaan System')
            ->assertSee('Database &amp; Pemeliharaan', false);
    }

    public function test_requests_are_blocked_while_a_restore_holds_the_maintenance_lock(): void
    {
        $directory = storage_path('app/system/locks');
        mkdir($directory, 0700, true);
        $handle = fopen($directory.DIRECTORY_SEPARATOR.'system-maintenance.lock', 'c+b');
        $this->assertNotFalse($handle);
        $this->assertTrue(flock($handle, LOCK_EX | LOCK_NB));

        try {
            $this->get('/login')->assertStatus(503)->assertSee('Operasi pemeliharaan sedang berlangsung');
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    public function test_failed_login_is_logged_without_storing_the_password(): void
    {
        $this->post('/login', ['username' => 'unknown.local.user', 'password' => 'never-store-this'])
            ->assertSessionHasErrors('username');

        $log = ActivityLog::query()->where('action', ActivityLog::ACTION_LOGIN_FAILED)->firstOrFail();
        $this->assertStringContainsString('unknown.local.user', $log->description);
        $this->assertStringNotContainsString('never-store-this', json_encode($log->properties));
    }

    public function test_user_create_and_update_activity_payloads_omit_passwords(): void
    {
        $admin = $this->makeUser('super_admin');
        $createLog = ActivityLog::query()
            ->where('subject_type', User::class)
            ->where('subject_id', $admin->id)
            ->where('action', ActivityLog::ACTION_CREATE)
            ->latest('id')
            ->firstOrFail();

        $admin->update(['password' => 'changed-sensitive-password']);
        $updateLog = ActivityLog::query()
            ->where('subject_type', User::class)
            ->where('subject_id', $admin->id)
            ->where('action', ActivityLog::ACTION_UPDATE)
            ->latest('id')
            ->firstOrFail();

        $this->assertStringNotContainsString('password', json_encode($createLog->properties));
        $this->assertStringNotContainsString('changed-sensitive-password', json_encode($updateLog->properties));
        $this->assertArrayNotHasKey('password', $updateLog->properties['new']);
        $this->assertArrayNotHasKey('password', $updateLog->properties['old']);
    }

    public function test_project_linking_and_unlinking_logs_one_business_action_without_model_update_duplicates(): void
    {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin);

        $customer = Customer::create([
            'name' => 'Project Log Client',
            'tax_id_type' => 'NPWP16',
            'default_tax_transaction_code' => '040',
        ]);
        $project = Project::create([
            'project_name' => 'Activity Log Project',
            'client_id' => $customer->id,
            'status' => 'active',
        ]);
        $category = Category::create([
            'code' => 'TEST-EXPENSE',
            'name' => 'Test Expense',
            'type' => 'expense',
        ]);
        $transaction = Transaction::create([
            'transaction_number' => 'TRX-TEST-ATTACH',
            'type' => 'expense',
            'category_id' => $category->id,
            'amount' => 100,
            'transaction_date' => now()->toDateString(),
            'payment_method' => 'Cash',
            'description' => 'Project expense',
        ]);
        $invoice = Document::create([
            'customer_id' => $customer->id,
            'type' => 'INVOICE',
            'document_number' => 'INV-TEST-ATTACH',
            'date' => now()->toDateString(),
            'status' => 'SENT',
            'total_amount' => 100,
        ]);

        $this->post(route('projects.attach-expenses', $project), ['transaction_ids' => [$transaction->id]])
            ->assertSessionHas('success');
        $this->post(route('projects.attach-invoices', $project), ['invoice_ids' => [$invoice->id]])
            ->assertSessionHas('success');

        $this->assertSame($project->id, $transaction->fresh()->project_id);
        $this->assertSame($project->id, $invoice->fresh()->project_id);
        $this->assertSame(2, ActivityLog::query()
            ->where('subject_type', Project::class)
            ->where('subject_id', $project->id)
            ->where('action', ActivityLog::ACTION_ATTACH)
            ->count());
        $this->assertSame(0, ActivityLog::query()
            ->where('subject_type', Transaction::class)
            ->where('subject_id', $transaction->id)
            ->where('action', ActivityLog::ACTION_UPDATE)
            ->count());
        $this->assertSame(0, ActivityLog::query()
            ->where('subject_type', Document::class)
            ->where('subject_id', $invoice->id)
            ->where('action', ActivityLog::ACTION_UPDATE)
            ->count());

        $this->post(route('projects.detach-expense', [$project, $transaction]))
            ->assertSessionHas('success');
        $this->post(route('projects.detach-invoice', [$project, $invoice]))
            ->assertSessionHas('success');

        $this->assertSame(1, ActivityLog::query()
            ->where('subject_type', Project::class)
            ->where('subject_id', $project->id)
            ->where('action', ActivityLog::ACTION_DETACH)
            ->count());
        $this->assertSame(1, ActivityLog::query()
            ->where('subject_type', Document::class)
            ->where('subject_id', $invoice->id)
            ->where('action', ActivityLog::ACTION_DETACH)
            ->count());
    }

    private function makeUser(string $roleName): User
    {
        $role = Role::firstOrCreate(
            ['name' => $roleName],
            ['display_name' => ucfirst($roleName), 'description' => 'Test role']
        );

        return User::create([
            'username' => 'user_'.$roleName,
            'name' => ucfirst($roleName),
            'email' => $roleName.'@example.test',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }
}
