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
            // Profil & Identitas Bisnis
            if (!Schema::hasColumn('settings', 'company_name')) {
                $table->string('company_name')->nullable()->after('id');
            }
            if (!Schema::hasColumn('settings', 'company_tagline')) {
                $table->string('company_tagline')->default('Business & IT Solutions')->nullable()->after('company_name');
            }
            if (!Schema::hasColumn('settings', 'company_npwp')) {
                $table->string('company_npwp')->nullable()->after('company_tagline');
            }
            if (!Schema::hasColumn('settings', 'company_website')) {
                $table->string('company_website')->nullable()->after('company_phone');
            }
            if (!Schema::hasColumn('settings', 'company_city')) {
                $table->string('company_city')->nullable()->after('company_address');
            }

            // Penomoran Dokumen
            if (!Schema::hasColumn('settings', 'delivery_note_prefix')) {
                $table->string('delivery_note_prefix')->default('SJ')->after('invoice_last_number');
            }
            if (!Schema::hasColumn('settings', 'kwitansi_prefix')) {
                $table->string('kwitansi_prefix')->default('KWT')->after('delivery_note_prefix');
            }

            // Pajak & Finansial
            if (!Schema::hasColumn('settings', 'default_tax_rate')) {
                $table->decimal('default_tax_rate', 5, 2)->default(11.00)->after('term_of_payment');
            }
            if (!Schema::hasColumn('settings', 'enable_tax')) {
                $table->boolean('enable_tax')->default(true)->after('default_tax_rate');
            }
            if (!Schema::hasColumn('settings', 'currency_symbol')) {
                $table->string('currency_symbol', 10)->default('Rp')->after('enable_tax');
            }
            if (!Schema::hasColumn('settings', 'currency_code')) {
                $table->string('currency_code', 10)->default('IDR')->after('currency_symbol');
            }

            // Otorisasi & Pejabat Penandatangan
            if (!Schema::hasColumn('settings', 'signatory_name')) {
                $table->string('signatory_name')->nullable()->after('currency_code');
            }
            if (!Schema::hasColumn('settings', 'signatory_position')) {
                $table->string('signatory_position')->nullable()->after('signatory_name');
            }
            if (!Schema::hasColumn('settings', 'signatory_city')) {
                $table->string('signatory_city')->nullable()->after('signatory_position');
            }

            // Catatan Footer & Syarat Ketentuan Standar
            if (!Schema::hasColumn('settings', 'invoice_footer_notes')) {
                $table->text('invoice_footer_notes')->nullable()->after('signatory_city');
            }
            if (!Schema::hasColumn('settings', 'receipt_footer_notes')) {
                $table->text('receipt_footer_notes')->nullable()->after('invoice_footer_notes');
            }
            if (!Schema::hasColumn('settings', 'delivery_note_footer_notes')) {
                $table->text('delivery_note_footer_notes')->nullable()->after('receipt_footer_notes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'company_name',
                'company_tagline',
                'company_npwp',
                'company_website',
                'company_city',
                'delivery_note_prefix',
                'kwitansi_prefix',
                'default_tax_rate',
                'enable_tax',
                'currency_symbol',
                'currency_code',
                'signatory_name',
                'signatory_position',
                'signatory_city',
                'invoice_footer_notes',
                'receipt_footer_notes',
                'delivery_note_footer_notes',
            ]);
        });
    }
};
