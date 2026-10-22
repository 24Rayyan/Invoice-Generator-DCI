<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('settings', function (Blueprint $table) {
        $table->id();
        // Detail Perusahaan
        $table->string('company_logo')->nullable();
        $table->text('company_address')->nullable();
        $table->string('company_email')->nullable();
        $table->string('company_phone')->nullable();
        $table->string('company_stamp')->nullable();
        $table->text('company_bank_account')->nullable();
        
        // Detail Lainnya
        $table->integer('term_of_payment')->default(0); // TOP dalam hitungan hari (misal: 30)
        
        // Monitoring Nomor Dokumen
        $table->string('po_prefix')->default('PO-');
        $table->integer('po_last_number')->default(0);
        $table->string('invoice_prefix')->default('INV');
        $table->integer('invoice_last_number')->default(0);
        
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
