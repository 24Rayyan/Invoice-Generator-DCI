<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (! Schema::hasColumn('documents', 'delivery_note_number')) {
                $table->string('delivery_note_number')->nullable()->unique();
            }

            if (! Schema::hasColumn('documents', 'receipt_number')) {
                $table->string('receipt_number')->nullable()->unique();
            }
        });
    }

    public function down(): void
    {
        // Keep these columns on rollback: an earlier migration may have created them.
    }
};
