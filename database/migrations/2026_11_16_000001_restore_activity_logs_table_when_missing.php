<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('activity_logs')) {
            return;
        }

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name')->nullable();
            $table->string('action', 50);
            $table->string('module_name');
            $table->nullableMorphs('subject');
            $table->text('description');
            $table->json('properties')->nullable();
            $table->timestamps();
            $table->index(['module_name', 'action']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        // Keep audit logs when rolling back this schema repair.
    }
};
