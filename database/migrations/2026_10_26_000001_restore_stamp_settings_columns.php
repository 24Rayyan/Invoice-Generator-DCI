<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        Schema::table('settings', function (Blueprint $table) {
            if (! Schema::hasColumn('settings', 'stamp_invoice_enabled')) {
                $table->boolean('stamp_invoice_enabled')->default(true);
            }

            if (! Schema::hasColumn('settings', 'stamp_delivery_note_enabled')) {
                $table->boolean('stamp_delivery_note_enabled')->default(true);
            }

            if (! Schema::hasColumn('settings', 'stamp_receipt_enabled')) {
                $table->boolean('stamp_receipt_enabled')->default(false);
            }
        });
    }

    public function down(): void
    {
        // Keep these settings on rollback to avoid removing saved configuration.
    }
};
