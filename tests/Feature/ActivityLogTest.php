<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $roleName): User
    {
        $role = Role::where('name', $roleName)->firstOrFail();

        return User::create([
            'username'  => 'user_' . $roleName,
            'name'      => ucfirst($roleName),
            'email'     => $roleName . '@example.test',
            'password'  => Hash::make('password'),
            'role_id'   => $role->id,
            'is_active' => true,
        ]);
    }

    public function test_activity_logs_table_is_registered_and_permanent(): void
    {
        $this->assertDatabaseHas('modules', ['key' => 'activity_logs']);

        $superAdmin = Role::where('name', 'super_admin')->firstOrFail();
        $this->assertTrue($superAdmin->hasModule('activity_logs'));
    }

    public function test_crud_actions_are_logged_automatically(): void
    {
        $user = $this->makeUser('super_admin');
        $this->actingAs($user);

        $customer = Customer::create([
            'name'                         => 'PT Audit Sejahtera',
            'tax_id_type'                  => 'NPWP16',
            'default_tax_transaction_code' => '040',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id'      => $user->id,
            'action'       => ActivityLog::ACTION_CREATE,
            'module_name'  => 'Invoicing & PO',
            'subject_type' => Customer::class,
            'subject_id'   => $customer->id,
        ]);

        $createLog = ActivityLog::where('action', ActivityLog::ACTION_CREATE)
            ->where('subject_type', Customer::class)
            ->where('subject_id', $customer->id)
            ->firstOrFail();

        $this->assertStringContainsString('menambahkan data Customer', $createLog->description);
        $this->assertStringContainsString('PT Audit Sejahtera', $createLog->description);

        // Update
        $customer->update(['name' => 'PT Audit Sejahtera Abadi']);

        $updateLog = ActivityLog::where('action', ActivityLog::ACTION_UPDATE)
            ->where('subject_type', Customer::class)
            ->where('subject_id', $customer->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertStringContainsString('memperbarui data Customer', $updateLog->description);
        $this->assertSame('PT Audit Sejahtera', $updateLog->properties['old']['name']);
        $this->assertSame('PT Audit Sejahtera Abadi', $updateLog->properties['new']['name']);

        // Delete
        $customer->delete();

        $this->assertDatabaseHas('activity_logs', [
            'action'       => ActivityLog::ACTION_DELETE,
            'module_name'  => 'Invoicing & PO',
            'subject_type' => Customer::class,
            'subject_id'   => $customer->id,
        ]);
    }

    public function test_activity_logs_index_is_accessible_for_super_admin(): void
    {
        $user = $this->makeUser('super_admin');
        $this->actingAs($user);

        Customer::create([
            'name'                         => 'PT Contoh Log',
            'tax_id_type'                  => 'NPWP16',
            'default_tax_transaction_code' => '040',
        ]);

        $response = $this->get(route('activity_logs.index'));

        $response->assertStatus(200);
        $response->assertSee('Log Aktivitas Pengguna');
        $response->assertSee('PT Contoh Log');
    }

    public function test_activity_log_integrated_with_rbac_denies_unauthorized_role(): void
    {
        // Finance tidak diberi akses activity_logs secara default.
        $user = $this->makeUser('finance');
        $this->actingAs($user);

        $this->get(route('activity_logs.index'))->assertStatus(403);
    }

    public function test_logs_are_retained_after_user_is_deleted(): void
    {
        $actor = $this->makeUser('finance');
        $this->actingAs($actor);

        Customer::create([
            'name'                         => 'PT Retensi Log',
            'tax_id_type'                  => 'NPWP16',
            'default_tax_transaction_code' => '040',
        ]);

        $logId = ActivityLog::where('subject_type', Customer::class)->latest('id')->value('id');

        // Admin lain menghapus akun pelaku.
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin);
        $actor->delete();

        $log = ActivityLog::find($logId);

        $this->assertNotNull($log, 'Log harus tetap tersimpan setelah user dihapus.');
        $this->assertNull($log->user_id, 'Relasi user_id menjadi null, namun log tidak ikut terhapus.');
        $this->assertSame('user_finance', $log->user_name, 'Snapshot nama pelaku tetap terjaga.');
    }

    public function test_activity_log_detail_endpoint_returns_properties(): void
    {
        $user = $this->makeUser('super_admin');
        $this->actingAs($user);

        Customer::create([
            'name'                         => 'PT Detail Log',
            'tax_id_type'                  => 'NPWP16',
            'default_tax_transaction_code' => '040',
        ]);

        $log = ActivityLog::where('action', ActivityLog::ACTION_CREATE)
            ->where('subject_type', Customer::class)
            ->latest('id')
            ->firstOrFail();

        $response = $this->getJson(route('activity_logs.show', $log));

        $response->assertStatus(200);
        $response->assertJsonPath('action', ActivityLog::ACTION_CREATE);
        $response->assertJsonPath('module_name', 'Invoicing & PO');
        $response->assertJsonStructure(['properties' => ['attributes']]);
    }
}
