<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('projects')) {
            Schema::create('projects', function (Blueprint $table) {
                $table->id();
                $table->string('project_name');
                $table->foreignId('client_id')->nullable()->constrained('customers')->nullOnDelete();
                $table->string('status')->default('active');
                $table->timestamps();
            });
        }

        if (Schema::hasTable('documents') && ! Schema::hasColumn('documents', 'project_id')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            });
        }

        if (Schema::hasTable('transactions') && ! Schema::hasColumn('transactions', 'project_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Keep restored project data when rolling back this repair migration.
    }
};
