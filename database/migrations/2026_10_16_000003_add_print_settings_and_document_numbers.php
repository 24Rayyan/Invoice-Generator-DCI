<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

            $columnsToDrop = [];
            foreach (['company_tagline', 'company_website'] as $column) {
                if (Schema::hasColumn('settings', $column)) {
                    $columnsToDrop[] = $column;
                }
            }
            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }
        });

        Schema::table('documents', function (Blueprint $table) {
            if (! Schema::hasColumn('documents', 'delivery_note_number')) {
                $table->string('delivery_note_number')->nullable()->unique();
            }
            if (! Schema::hasColumn('documents', 'receipt_number')) {
                $table->string('receipt_number')->nullable()->unique();
            }
        });

        DB::table('settings')->whereNull('stamp_invoice_enabled')->update(['stamp_invoice_enabled' => true]);
        DB::table('settings')->whereNull('stamp_delivery_note_enabled')->update(['stamp_delivery_note_enabled' => true]);
        DB::table('settings')->whereNull('stamp_receipt_enabled')->update(['stamp_receipt_enabled' => false]);
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $columnsToDrop = [];
            foreach (['delivery_note_number', 'receipt_number'] as $column) {
                if (Schema::hasColumn('documents', $column)) {
                    $columnsToDrop[] = $column;
                }
            }
            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }
        });

        Schema::table('settings', function (Blueprint $table) {
            $columnsToDrop = [];
            foreach (['stamp_invoice_enabled', 'stamp_delivery_note_enabled', 'stamp_receipt_enabled'] as $column) {
                if (Schema::hasColumn('settings', $column)) {
                    $columnsToDrop[] = $column;
                }
            }
            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }

            if (! Schema::hasColumn('settings', 'company_tagline')) {
                $table->string('company_tagline')->nullable();
            }
            if (! Schema::hasColumn('settings', 'company_website')) {
                $table->string('company_website')->nullable();
            }
        });
    }
};
