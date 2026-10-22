<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $superAdmin = Role::where('name', 'super_admin')->firstOrFail();

        User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'admin',
                'username' => 'admin',
                'password' => 'admin123',
                'role_id' => $superAdmin->id,
                'is_active' => true,
            ]
        );

    }
}
