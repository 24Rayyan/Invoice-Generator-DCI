<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mendaftarkan modul "activity_logs" ke dalam RBAC yang sudah ada,
     * sehingga hak akses halaman Log Aktivitas dapat diatur melalui
     * matriks Role & Module Access. Secara default hanya Super Admin
     * yang diberikan akses (role lain dapat di-grant lewat halaman role).
     */
    public function up(): void
    {
        if (!Schema::hasTable('modules')) {
            return;
        }

        $moduleId = DB::table('modules')->where('key', 'activity_logs')->value('id');

        if (!$moduleId) {
            $moduleId = DB::table('modules')->insertGetId([
                'key'        => 'activity_logs',
                'name'       => 'Log Aktivitas (Audit Trail)',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (Schema::hasTable('role_has_modules')) {
            $superAdminId = DB::table('roles')->where('name', 'super_admin')->value('id');

            if ($superAdminId) {
                DB::table('role_has_modules')->insertOrIgnore([
                    'role_id'   => $superAdminId,
                    'module_id' => $moduleId,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Dibiarkan kosong demi keamanan: modul & grant tidak dihapus otomatis.
    }
};
