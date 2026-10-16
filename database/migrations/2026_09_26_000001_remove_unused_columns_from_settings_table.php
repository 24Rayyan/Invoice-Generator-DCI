<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $columnsToDrop = [];

            if (Schema::hasColumn('settings', 'po_last_number')) {
                $columnsToDrop[] = 'po_last_number';
            }

            if (Schema::hasColumn('settings', 'invoice_last_number')) {
                $columnsToDrop[] = 'invoice_last_number';
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'po_last_number')) {
                $table->integer('po_last_number')->default(0)->after('po_prefix');
            }

            if (!Schema::hasColumn('settings', 'invoice_last_number')) {
                $table->integer('invoice_last_number')->default(0)->after('invoice_prefix');
            }
        });
    }
};
