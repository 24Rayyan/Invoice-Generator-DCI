<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('modules') || !Schema::hasTable('role_has_modules')) {
            return;
        }

        $modules = [
            'customers' => 'Master Customer',
            'invoice_margin' => 'Margin & Biaya Invoice (akses Invoice diperlukan)',
            'projects' => 'Manajemen Proyek',
            'categories' => 'Master Akun & COA',
            'financial_reports' => 'Laporan Keuangan',
            'profit_loss' => 'Laporan Laba Rugi',
            'project_margin' => 'Margin Proyek / Invoice',
            'settings' => 'Pengaturan Perusahaan',
        ];

        foreach ($modules as $key => $name) {
            DB::table('modules')->insertOrIgnore([
                'key' => $key,
                'name' => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $legacyModuleGrants = [
            'invoices' => ['customers', 'invoice_margin', 'projects'],
            'expenses' => ['categories'],
            'reports' => ['financial_reports', 'profit_loss', 'project_margin'],
        ];

        foreach ($legacyModuleGrants as $legacyKey => $newKeys) {
            $legacyModuleId = DB::table('modules')->where('key', $legacyKey)->value('id');
            if (!$legacyModuleId) {
                continue;
            }

            $roleIds = DB::table('role_has_modules')
                ->where('module_id', $legacyModuleId)
                ->pluck('role_id');

            foreach ($newKeys as $newKey) {
                $newModuleId = DB::table('modules')->where('key', $newKey)->value('id');
                if (!$newModuleId) {
                    continue;
                }

                foreach ($roleIds as $roleId) {
                    DB::table('role_has_modules')->insertOrIgnore([
                        'role_id' => $roleId,
                        'module_id' => $newModuleId,
                    ]);
                }
            }

            $legacyReportsId = DB::table('modules')->where('key', 'reports')->value('id');
            if ($legacyReportsId) {
                DB::table('role_has_modules')->where('module_id', $legacyReportsId)->delete();
                DB::table('modules')->where('id', $legacyReportsId)->delete();
            }
        }
    }

    public function down(): void
    {
        // Keep module grants intact to avoid removing access configuration on rollback.
    }
};
