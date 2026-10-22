<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Insert Default Roles
        $roles = [
            ['id' => 1, 'name' => 'super_admin', 'display_name' => 'Super Admin', 'description' => 'Akses penuh seluruh sistem', 'created_at' => now(), 'updated_at' => now()],
        ];
        DB::table('roles')->insertOrIgnore($roles);

        // 2. Insert Default Modules
        $modules = [
            ['id' => 1, 'key' => 'dashboard', 'name' => 'Dashboard Summary', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'key' => 'invoices', 'name' => 'Manajemen Invoice', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'key' => 'expenses', 'name' => 'Pengeluaran & Operasional', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'key' => 'reports', 'name' => 'Laporan Keuangan & Coretax', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'key' => 'user_management', 'name' => 'User & Role Management', 'created_at' => now(), 'updated_at' => now()],
        ];
        DB::table('modules')->insertOrIgnore($modules);

        // 3. Grant All Modules to Super Admin
        $superAdminId = DB::table('roles')->where('name', 'super_admin')->value('id');

        foreach ($modules as $module) {
            DB::table('role_has_modules')->insertOrIgnore([
                'role_id' => $superAdminId,
                'module_id' => $module['id'],
            ]);
        }

        // 4. Set existing users without role to Super Admin (opsional/aman)
        DB::table('users')->whereNull('role_id')->update(['role_id' => $superAdminId]);
    }

    public function down(): void
    {
        // No action needed for safety
    }
};