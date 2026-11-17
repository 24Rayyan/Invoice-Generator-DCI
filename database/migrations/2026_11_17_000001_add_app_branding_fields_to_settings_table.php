<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'app_title')) {
                $table->string('app_title', 100)->nullable();
            }

            if (!Schema::hasColumn('settings', 'app_favicon')) {
                $table->string('app_favicon')->nullable();
            }
        });
    }

    public function down(): void
    {
        $columns = array_filter([
            Schema::hasColumn('settings', 'app_title') ? 'app_title' : null,
            Schema::hasColumn('settings', 'app_favicon') ? 'app_favicon' : null,
        ]);

        if ($columns) {
            Schema::table('settings', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
