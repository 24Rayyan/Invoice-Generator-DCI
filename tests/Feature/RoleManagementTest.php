<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::where('name', 'super_admin')->firstOrFail();
        $this->admin = User::create([
            'username' => 'role_admin',
            'name' => 'Role Admin',
            'email' => 'role-admin@example.test',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);
    }

    public function test_role_matrix_renders_role_usage_and_delete_action(): void
    {
        $role = Role::create([
            'name' => 'project_manager',
            'display_name' => 'Project Manager',
            'description' => 'Pengelola proyek',
        ]);

        Module::query()->firstOrFail();

        $this->actingAs($this->admin)
            ->get(route('management.roles.index'))
            ->assertOk()
            ->assertSee('Role & Module Access Matrix', false)
            ->assertSee('Total Role')
            ->assertSee('Project Manager')
            ->assertSee(route('management.roles.destroy', $role));
    }

    public function test_role_matrix_includes_granular_project_and_invoice_margin_modules(): void
    {
        $this->actingAs($this->admin)
            ->get(route('management.roles.index'))
            ->assertOk()
            ->assertSee('Margin &amp; Biaya Invoice', false)
            ->assertSee('Margin Proyek / Invoice')
            ->assertSee('Manajemen Proyek')
            ->assertSee('Laporan Laba Rugi')
            ->assertSee('Pengaturan Perusahaan')
            ->assertSee('Invoicing &amp; PO', false)
            ->assertSee('Buku Kas &amp; Transaksi', false)
            ->assertSee('Laporan Keuangan', false)
            ->assertSee('User Management', false)
            ->assertSee('Pengaturan', false)
            ->assertSee('alpinejs@3.x.x/dist/cdn.min.js', false)
            ->assertSee('isOpen: false', false)
            ->assertSee('Atur Akses')
            ->assertSee('style="display: none;"', false);
    }

    public function test_admin_role_module_status_matches_its_effective_full_access(): void
    {
        $fullAccessRole = Role::where('name', 'super_admin')->firstOrFail();
        $settingsModule = Module::where('key', 'settings')->firstOrFail();

        $this->assertTrue($fullAccessRole->hasFullAccess());

        $response = $this->actingAs($this->admin)->get(route('management.roles.index'));

        $response->assertOk();
        $this->assertMatchesRegularExpression(
            '/<input type="checkbox"(?=[^>]*id="mod_'.$fullAccessRole->id.'_'.$settingsModule->id.'")(?=[^>]*checked)(?=[^>]*disabled)[^>]*>/',
            $response->getContent()
        );
    }

    public function test_project_margin_access_can_be_granted_independently_from_other_reports(): void
    {
        $role = Role::create([
            'name' => 'project_analyst',
            'display_name' => 'Project Analyst',
            'description' => 'Akses laporan margin proyek',
        ]);
        $user = User::create([
            'username' => 'project_analyst',
            'name' => 'Project Analyst',
            'email' => 'project-analyst@example.test',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('reports.project_margin'))
            ->assertForbidden();

        $role->modules()->attach(Module::where('key', 'project_margin')->firstOrFail());

        $this->actingAs($user->fresh())
            ->get(route('reports.project_margin'))
            ->assertOk();
        $this->get(route('reports.financial_statement'))->assertForbidden();
        $this->get(route('reports.profit_loss'))->assertForbidden();
        $this->get(route('projects.index'))->assertForbidden();
    }

    public function test_unused_custom_role_can_be_deleted_with_its_module_grants(): void
    {
        $role = Role::create([
            'name' => 'project_manager',
            'display_name' => 'Project Manager',
            'description' => 'Pengelola proyek',
        ]);
        $module = Module::query()->firstOrFail();
        $role->modules()->attach($module);

        $this->actingAs($this->admin)
            ->delete(route('management.roles.destroy', $role))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
        $this->assertDatabaseMissing('role_has_modules', [
            'role_id' => $role->id,
            'module_id' => $module->id,
        ]);
    }

    public function test_role_in_use_cannot_be_deleted(): void
    {
        $role = Role::create([
            'name' => 'project_manager',
            'display_name' => 'Project Manager',
            'description' => 'Pengelola proyek',
        ]);
        $user = User::create([
            'username' => 'project_user',
            'name' => 'Project User',
            'email' => 'project-user@example.test',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('management.roles.destroy', $role))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('roles', ['id' => $role->id]);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'role_id' => $role->id]);
    }

    public function test_only_super_admin_default_role_cannot_be_deleted(): void
    {
        $role = Role::where('name', 'super_admin')->firstOrFail();

        $this->actingAs($this->admin)
            ->delete(route('management.roles.destroy', $role))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    public function test_other_roles_can_be_created_without_automatic_full_access(): void
    {
        $role = Role::create([
            'name' => 'admin',
            'display_name' => 'Custom Admin',
            'description' => 'Custom role, not the super admin role.',
        ]);

        $this->assertFalse($role->hasFullAccess());
    }

    public function test_migration_removes_legacy_default_roles_and_unassigns_their_users(): void
    {
        $module = Module::where('key', 'settings')->firstOrFail();
        $legacyRoles = collect([
            Role::create(['name' => 'admin', 'display_name' => 'Admin Operasional']),
            Role::create(['name' => 'finance', 'display_name' => 'Finance / Kasir']),
        ]);

        $users = $legacyRoles->map(function (Role $role, int $index) {
            return User::create([
                'username' => 'legacy_role_user_'.$index,
                'name' => 'Legacy Role User '.$index,
                'email' => 'legacy-role-'.$index.'@example.test',
                'password' => Hash::make('password'),
                'role_id' => $role->id,
                'is_active' => true,
            ]);
        });
        foreach ($legacyRoles as $role) {
            $role->modules()->attach($module);
        }

        $migration = require database_path('migrations/2026_10_22_000002_keep_only_super_admin_default_role.php');
        $migration->up();

        foreach ($legacyRoles as $index => $role) {
            $this->assertDatabaseMissing('roles', ['id' => $role->id]);
            $this->assertDatabaseMissing('role_has_modules', [
                'role_id' => $role->id,
                'module_id' => $module->id,
            ]);
            $this->assertDatabaseHas('users', [
                'id' => $users[$index]->id,
                'role_id' => null,
            ]);
        }
    }
}
