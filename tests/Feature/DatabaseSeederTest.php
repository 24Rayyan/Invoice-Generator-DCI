<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_an_active_super_admin_who_can_log_in(): void
    {
        $this->seed();

        $user = User::where('email', 'test@example.com')->firstOrFail();

        $this->assertSame('admin', $user->username);
        $this->assertSame(Role::where('name', 'super_admin')->value('id'), $user->role_id);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('admin123', $user->password));
        $this->assertSame(['super_admin'], Role::orderBy('id')->pluck('name')->all());

        $this->post('/login', [
            'username' => 'admin',
            'password' => 'admin123',
        ])->assertRedirect('/dashboard');
    }
}
