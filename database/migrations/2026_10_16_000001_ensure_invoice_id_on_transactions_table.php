<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ensure cash transactions can optionally be linked to a single invoice.
     * Existing installs already have this column from the original transactions migration.
     */
    public function up(): void
    {
        if (! Schema::hasTable('transactions') || Schema::hasColumn('transactions', 'invoice_id')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('invoice_id')
                ->nullable()
                ->constrained('documents')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('transactions') || ! Schema::hasColumn('transactions', 'invoice_id')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invoice_id');
        });
    }
};
