<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Setting;
use App\Models\Transaction;
use App\Services\FinancialReportService;
use App\Services\TransactionService;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialErpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CategorySeeder::class);
    }

    /**
     * Test Chart of Accounts seeding & scopes
     */
    public function test_categories_seeded_and_scopes_work(): void
    {
        $this->assertGreaterThan(0, Category::count());
        
        $deductibleExpense = Category::expense()->taxDeductible()->first();
        $this->assertNotNull($deductibleExpense);
        $this->assertTrue((bool) $deductibleExpense->is_tax_deductible);

        $nonDeductibleExpense = Category::expense()->nonDeductible()->first();
        $this->assertNotNull($nonDeductibleExpense);
        $this->assertFalse((bool) $nonDeductibleExpense->is_tax_deductible);
    }

    /**
     * Test Transaction creation with Entertainment Nominative details
     */
    public function test_transaction_with_entertainment_detail_creation(): void
    {
        $category = Category::where('code', '5-201')->firstOrFail();
        $service = app(TransactionService::class);

        $transaction = $service->createTransaction(
            [
                'type' => 'expense',
                'category_id' => $category->id,
                'amount' => 5000000,
                'transaction_date' => '2026-09-02',
                'payment_method' => 'Bank Transfer',
                'description' => 'Business Dinner Klien',
            ],
            null,
            [
                'event_date' => '2026-09-02',
                'location' => 'Hotel Indonesia Kempinski Jakarta',
                'attendee_name' => 'Dr. Ir. Suryadi, M.M.',
                'attendee_company' => 'PT Astra International Tbk',
                'attendee_position' => 'Direktur Pengadaan',
                'purpose' => 'Diskusi Kerjasama Pengadaan Server Cloud',
            ]
        );

        $this->assertNotNull($transaction);
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'amount' => 5000000,
        ]);

        $this->assertDatabaseHas('entertainment_details', [
            'transaction_id' => $transaction->id,
            'attendee_name' => 'Dr. Ir. Suryadi, M.M.',
            'attendee_company' => 'PT Astra International Tbk',
        ]);
    }

    /**
     * Test Auto-sync when Invoice is marked as PAID
     */
    public function test_invoice_paid_auto_syncs_transaction(): void
    {
        $customer = Customer::create([
            'name' => 'PT Mitra Global Test',
            'phone' => '08123456789',
            'address' => 'Jakarta Barat',
        ]);

        $invoice = Document::create([
            'customer_id' => $customer->id,
            'type' => 'INVOICE',
            'document_number' => 'INV-TEST/IX/2026',
            'date' => '2026-09-03',
            'status' => 'SENT',
            'total_amount' => 25000000,
        ]);

        // When status updated to PAID
        $invoice->update(['status' => 'PAID']);
        $transactionService = app(TransactionService::class);
        $transaction = $transactionService->syncInvoicePaidTransaction($invoice);

        $this->assertNotNull($transaction);
        $this->assertEquals('income', $transaction->type);
        $this->assertEquals(25000000, $transaction->amount);
        $this->assertEquals($invoice->id, $transaction->invoice_id);

        // When status updated back to SENT -> transaction removed
        $invoice->update(['status' => 'SENT']);
        $transactionService->syncInvoicePaidTransaction($invoice);
        $this->assertDatabaseMissing('transactions', ['invoice_id' => $invoice->id]);
    }

    /**
     * Test FinancialReportService P&L and Fiscal calculations
     */
    public function test_financial_report_service_fiscal_calculations(): void
    {
        $reportService = app(FinancialReportService::class);
        $report = $reportService->getProfitAndLossReport(2026);

        $this->assertArrayHasKey('total_income', $report);
        $this->assertArrayHasKey('total_deductible_expense', $report);
        $this->assertArrayHasKey('total_non_deductible_expense', $report);
        $this->assertArrayHasKey('commercial_net_profit', $report);
        $this->assertArrayHasKey('fiscal_net_profit', $report);

        // Fiscal Net Profit = Total Income - Total Deductible Expense
        $expectedFiscalProfit = $report['total_income'] - $report['total_deductible_expense'];
        $this->assertEquals($expectedFiscalProfit, $report['fiscal_net_profit']);

        // Commercial Net Profit = Total Income - Total Expense
        $expectedCommercialProfit = $report['total_income'] - $report['total_expense'];
        $this->assertEquals($expectedCommercialProfit, $report['commercial_net_profit']);
    }

    /**
     * Test Centralized ERP Settings Page & Configuration Updates
     */
    public function test_centralized_erp_settings_update(): void
    {
        $response = $this->get(route('settings.index'));
        $response->assertStatus(200);
        $response->assertSee('Pusat Konfigurasi Mini ERP');

        $postData = [
            'company_name' => 'PT Solusi Teknologi Nusantara',
            'company_npwp16' => '0123456789012345',
            'company_email' => 'contact@solusinusantara.com',
            'company_phone' => '081299887766',
            'company_address' => 'Jl. Asia Afrika No. 100, Bandung',
            'company_city' => 'Bandung',
            'company_bank_account' => 'BCA 1234567890 a.n PT Solusi Teknologi Nusantara',
            'invoice_prefix' => 'INV-STN',
            'po_prefix' => 'PO-STN',
            'delivery_note_prefix' => 'SJ-STN',
            'kwitansi_prefix' => 'KWT-STN',
            'default_tax_rate' => 12.00,
            'enable_tax' => '1',
            'term_of_payment' => 45,
            'currency_symbol' => 'Rp',
            'currency_code' => 'IDR',
            'signatory_name' => 'Ahmad Fauzi, M.T.',
            'signatory_position' => 'Chief Executive Officer',
            'signatory_city' => 'Bandung',
            'invoice_footer_notes' => 'Pembayaran via transfer bank sesuai invoice.',
            'receipt_footer_notes' => 'Pembayaran lunas tagihan.',
            'delivery_note_footer_notes' => 'Periksa barang saat serah terima.',
        ];

        $postResponse = $this->post(route('settings.update'), $postData);
        $postResponse->assertSessionHas('success');

        $setting = Setting::first();
        $this->assertEquals('PT Solusi Teknologi Nusantara', $setting->company_name);
        $this->assertEquals('INV-STN', $setting->invoice_prefix);
        $this->assertEquals(12.00, $setting->default_tax_rate);
        $this->assertEquals(45, $setting->term_of_payment);
        $this->assertEquals('Ahmad Fauzi, M.T.', $setting->signatory_name);
    }

    /**
     * Test web routes accessibility
     */
    public function test_erp_web_routes(): void
    {
        $this->get(route('dashboard'))->assertStatus(200);
        $this->get(route('documents.index'))->assertStatus(200);
        $this->get(route('customers.index'))->assertStatus(200);
        $this->get(route('categories.index'))->assertStatus(200);
        $this->get(route('transactions.index'))->assertStatus(200);
        $this->get(route('reports.financial_statement'))->assertStatus(200);
        $this->get(route('reports.profit_loss'))->assertStatus(200);
        $this->get(route('reports.profit_loss.pdf', ['year' => 2026]))->assertStatus(200);
        $this->get(route('reports.profit_loss.excel', ['year' => 2026]))->assertStatus(200);
        $this->get(route('reports.entertainment_nominative'))->assertStatus(200);
        $this->get(route('reports.entertainment_nominative.pdf', ['year' => 2026]))->assertStatus(200);
        $this->get(route('reports.entertainment_nominative.excel', ['year' => 2026]))->assertStatus(200);
        $this->get(route('settings.index'))->assertStatus(200);
    }
}
