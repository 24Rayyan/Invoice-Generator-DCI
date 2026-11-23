<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_transaction_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->decimal('allocated_amount', 15, 2);
            $table->timestamps();
            $table->unique(['project_id', 'transaction_id']);
            $table->index('transaction_id');
        });

        DB::table('project_transaction_allocations')->insertUsing(
            ['project_id', 'transaction_id', 'allocated_amount', 'created_at', 'updated_at'],
            DB::table('transactions')
                ->selectRaw('project_id, id, amount, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP')
                ->whereNotNull('project_id')
                ->where('type', 'expense')
                ->whereNull('invoice_id')
        );

        DB::table('transactions')
            ->where('type', 'expense')
            ->whereNotNull('project_id')
            ->update(['project_id' => null]);
    }

    public function down(): void
    {
        Schema::dropIfExists('project_transaction_allocations');
    }
};
