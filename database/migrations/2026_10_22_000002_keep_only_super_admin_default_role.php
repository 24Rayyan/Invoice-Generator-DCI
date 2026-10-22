<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $superAdminId = DB::table('roles')->where('name', 'super_admin')->value('id');

        if (!$superAdminId) {
            throw new RuntimeException('Cannot remove default roles because the super_admin role does not exist.');
        }

        $removedRoleIds = DB::table('roles')
            ->whereIn('name', ['admin', 'finance'])
            ->pluck('id');

        if ($removedRoleIds->isEmpty()) {
            return;
        }

        DB::table('users')
            ->whereIn('role_id', $removedRoleIds)
            ->update(['role_id' => null]);

        DB::table('role_has_modules')
            ->whereIn('role_id', $removedRoleIds)
            ->delete();

        DB::table('roles')
            ->whereIn('id', $removedRoleIds)
            ->delete();
    }

    public function down(): void
    {
        // Removed default role assignments cannot be restored safely.
    }
};
