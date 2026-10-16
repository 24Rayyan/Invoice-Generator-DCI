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

    public function test_builtin_role_cannot_be_deleted(): void
    {
        $role = Role::where('name', 'admin')->firstOrFail();

        $this->actingAs($this->admin)
            ->delete(route('management.roles.destroy', $role))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }
}
