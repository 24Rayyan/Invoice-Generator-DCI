<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ProjectMarginService;
use App\Services\TransactionService;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProjectCostTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CategorySeeder::class);

        $role = Role::where('name', 'super_admin')->firstOrFail();
        $this->user = User::create([
            'username' => 'margin_admin',
            'name' => 'Margin Admin',
            'email' => 'margin@example.test',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }

    protected function makeInvoice(float $total = 10000000, string $number = 'INV-MARGIN/X/2026'): Document
    {
        $customer = Customer::create([
            'name' => 'PT Proyek Sukses',
            'phone' => '08123456789',
        ]);

        return Document::create([
            'customer_id' => $customer->id,
            'type' => 'INVOICE',
            'document_number' => $number,
            'date' => '2026-10-01',
            'status' => 'SENT',
            'total_amount' => $total,
        ]);
    }

    protected function makeExpense(float $amount, ?int $invoiceId = null): Transaction
    {
        $category = Category::where('code', '5-101')->firstOrFail();

        return Transaction::create([
            'transaction_number' => Transaction::generateTransactionNumber('expense'),
            'type' => 'expense',
            'category_id' => $category->id,
            'invoice_id' => $invoiceId,
            'amount' => $amount,
            'transaction_date' => '2026-10-05',
            'payment_method' => 'Bank Transfer',
            'description' => 'Biaya proyek',
        ]);
    }

    public function test_margin_calculation_and_zero_revenue_guard(): void
    {
        $invoice = $this->makeInvoice(10000000);
        $this->makeExpense(2500000, $invoice->id);
        $this->makeExpense(1500000, $invoice->id);
        $this->makeExpense(999999); // operasional umum, tidak masuk margin

        $summary = app(ProjectMarginService::class)->forInvoice($invoice->fresh());

        $this->assertEquals(10000000, $summary['revenue']);
        $this->assertEquals(4000000, $summary['direct_expense']);
        $this->assertEquals(6000000, $summary['net_profit']);
        $this->assertEquals(60.0, $summary['net_margin']);

        $zeroInvoice = $this->makeInvoice(0, 'INV-ZERO/X/2026');
        $zeroSummary = app(ProjectMarginService::class)->forInvoice($zeroInvoice);
        $this->assertEquals(0.0, $zeroSummary['net_margin']);
        $this->assertEquals(0.0, $zeroSummary['net_profit']);
    }

    public function test_deleting_invoice_unlinks_attached_transactions(): void
    {
        $invoice = $this->makeInvoice();
        $expense = $this->makeExpense(250000, $invoice->id);

        $invoice->delete();

        $this->assertNull($expense->fresh()->invoice_id);
    }

    public function test_store_transaction_can_link_optional_invoice(): void
    {
        $invoice = $this->makeInvoice();
        $category = Category::where('code', '5-101')->firstOrFail();

        $this->actingAs($this->user)
            ->post(route('transactions.store'), [
                'type' => 'expense',
                'category_id' => $category->id,
                'amount' => 750000,
                'transaction_date' => '2026-10-06',
                'payment_method' => 'Cash',
                'description' => 'Transport site',
                'invoice_id' => $invoice->id,
            ])
            ->assertRedirect(route('transactions.index'));

        $this->assertDatabaseHas('transactions', [
            'description' => 'Transport site',
            'invoice_id' => $invoice->id,
            'amount' => 750000,
        ]);
    }

    public function test_transaction_forms_reject_purchase_orders_as_invoice_links(): void
    {
        $invoice = $this->makeInvoice(5000000, 'INV-LINK/X/2026');
        $purchaseOrder = $this->makeInvoice(3000000, 'PO-LINK/X/2026');
        $purchaseOrder->update(['type' => 'PO']);
        $expense = $this->makeExpense(100000, $invoice->id);
        $category = Category::where('code', '5-101')->firstOrFail();

        $this->actingAs($this->user)
            ->post(route('transactions.store'), [
                'type' => 'expense',
                'category_id' => $category->id,
                'amount' => 750000,
                'transaction_date' => '2026-10-06',
                'payment_method' => 'Cash',
                'invoice_id' => $purchaseOrder->id,
            ])
            ->assertSessionHasErrors('invoice_id');

        $this->assertDatabaseCount('transactions', 1);

        $this->actingAs($this->user)
            ->put(route('transactions.update', $expense), [
                'category_id' => $category->id,
                'amount' => 100000,
                'transaction_date' => '2026-10-05',
                'payment_method' => 'Cash',
                'invoice_id' => $purchaseOrder->id,
            ])
            ->assertSessionHasErrors('invoice_id');

        $this->assertEquals($invoice->id, $expense->fresh()->invoice_id);
    }

    public function test_bulk_attach_only_unallocated_expenses_and_detach(): void
    {
        $invoiceA = $this->makeInvoice(5000000, 'INV-A/X/2026');
        $invoiceB = $this->makeInvoice(8000000, 'INV-B/X/2026');

        $freeOne = $this->makeExpense(100000);
        $freeTwo = $this->makeExpense(200000);
        $alreadyLinked = $this->makeExpense(300000, $invoiceB->id);

        $this->actingAs($this->user)
            ->post(route('documents.attach-transactions', $invoiceA), [
                'transaction_ids' => [$freeOne->id, $freeTwo->id, $alreadyLinked->id],
            ])
            ->assertRedirect();

        $this->assertEquals($invoiceA->id, $freeOne->fresh()->invoice_id);
        $this->assertEquals($invoiceA->id, $freeTwo->fresh()->invoice_id);
        $this->assertEquals($invoiceB->id, $alreadyLinked->fresh()->invoice_id);

        $this->actingAs($this->user)
            ->post(route('documents.detach-transaction', [$invoiceA, $freeOne]))
            ->assertRedirect();

        $this->assertNull($freeOne->fresh()->invoice_id);
        $this->assertEquals($invoiceA->id, $freeTwo->fresh()->invoice_id);
    }

    public function test_paid_sync_does_not_mutate_attached_expenses(): void
    {
        $invoice = $this->makeInvoice(25000000, 'INV-PAID-SAFE/X/2026');
        $expense = $this->makeExpense(1000000, $invoice->id);

        $invoice->update(['status' => 'PAID']);
        $income = app(TransactionService::class)->syncInvoicePaidTransaction($invoice);

        $this->assertNotNull($income);
        $this->assertEquals('income', $income->type);
        $this->assertEquals($invoice->id, $income->invoice_id);
        $this->assertEquals('expense', $expense->fresh()->type);
        $this->assertEquals(1000000, (float) $expense->fresh()->amount);

        $invoice->update(['status' => 'SENT']);
        app(TransactionService::class)->syncInvoicePaidTransaction($invoice);

        $this->assertDatabaseMissing('transactions', [
            'id' => $income->id,
            'type' => 'income',
        ]);
        $this->assertDatabaseHas('transactions', [
            'id' => $expense->id,
            'invoice_id' => $invoice->id,
            'type' => 'expense',
        ]);
    }

    public function test_invoice_detail_and_margin_report_pages_render(): void
    {
        $invoice = $this->makeInvoice();
        $this->makeExpense(500000, $invoice->id);
        $this->makeExpense(250000);

        $this->actingAs($this->user)
            ->get(route('documents.show', $invoice))
            ->assertOk()
            ->assertSee('Net Margin')
            ->assertSee('Hubungkan Kas')
            ->assertSee('Tanggal Mulai')
            ->assertSee('Tanggal Akhir')
            ->assertSee('unallocatedSearch')
            ->assertSee($invoice->document_number);

        $this->actingAs($this->user)
            ->get(route('reports.project_margin'))
            ->assertOk()
            ->assertSee('Laporan Margin Proyek')
            ->assertSee($invoice->document_number)
            ->assertSee('PT Proyek Sukses');
    }

    public function test_financial_dashboard_shows_recent_documents_and_expenses(): void
    {
        $invoice = $this->makeInvoice(1000000, 'INV-DASHBOARD/X/2026');
        $purchaseOrder = $this->makeInvoice(500000, 'PO-DASHBOARD/X/2026');
        $purchaseOrder->update(['type' => 'PO']);
        $expense = $this->makeExpense(250000);
        $incomeCategory = Category::where('type', 'income')->firstOrFail();

        $income = Transaction::create([
            'transaction_number' => Transaction::generateTransactionNumber('income'),
            'type' => 'income',
            'category_id' => $incomeCategory->id,
            'amount' => 1000000,
            'transaction_date' => '2026-10-06',
            'payment_method' => 'Bank Transfer',
            'description' => 'Invoice payment',
        ]);

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Keuangan')
            ->assertSee('Executive Summary')
            ->assertSee('INV-DASHBOARD/X/2026')
            ->assertSee('PO-DASHBOARD/X/2026')
            ->assertSee($expense->transaction_number)
            ->assertDontSee($income->transaction_number);
    }

    public function test_cashflow_management_filters_searches_and_paginates_transactions(): void
    {
        $invoice = $this->makeInvoice(1000000, 'INV-CASHFLOW/X/2026');
        $firstExpense = null;
        $oldestOctoberExpense = null;

        for ($index = 0; $index < 12; $index++) {
            $expense = $this->makeExpense(100000 + $index);
            $expense->update([
                'transaction_date' => $index === 0 ? '2026-09-15' : '2026-10-05',
                'description' => $index === 0 ? 'September transport' : 'Biaya proyek',
            ]);

            $firstExpense ??= $expense;
            if ($index === 1) {
                $oldestOctoberExpense = $expense;
            }
        }

        $linkedExpense = $this->makeExpense(500000, $invoice->id);
        $linkedExpense->update(['description' => 'Pembayaran vendor khusus']);

        $this->actingAs($this->user)
            ->get(route('transactions.index', [
                'period' => '2026-10',
                'search' => 'Biaya proyek',
                'per_page' => 10,
            ]))
            ->assertOk()
            ->assertSee('Manajemen Arus Kas')
            ->assertSee('Riwayat Transaksi')
            ->assertSee('11 transaksi keluar')
            ->assertSee('dari 11 transaksi')
            ->assertDontSee($firstExpense->transaction_number);

        $this->actingAs($this->user)
            ->get(route('transactions.index', [
                'period' => '2026-10',
                'search' => 'Biaya proyek',
                'per_page' => 10,
                'page' => 2,
            ]))
            ->assertOk()
            ->assertSee($oldestOctoberExpense->transaction_number);

        $this->actingAs($this->user)
            ->get(route('transactions.index', [
                'period' => '',
                'search' => 'Pembayaran vendor khusus',
            ]))
            ->assertOk()
            ->assertSee($linkedExpense->transaction_number)
            ->assertSee('1 transaksi')
            ->assertDontSee($firstExpense->transaction_number);
    }

    public function test_professional_financial_report_renders_with_selected_period_and_exports(): void
    {
        $invoice = $this->makeInvoice(1000000, 'INV-REPORT/X/2026');
        $this->makeExpense(250000);

        $this->actingAs($this->user)
            ->get(route('reports.financial_statement', [
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-31',
            ]))
            ->assertOk()
            ->assertSee('Laporan Keuangan Profesional')
            ->assertSee('Filter Laporan')
            ->assertSee('Tanggal mulai')
            ->assertSee('Ringkasan Keuangan')
            ->assertSee('Rekap Penagihan')
            ->assertSee('Rincian Buku Kas Kronologis')
            ->assertSee('ledgerPerPage')
            ->assertSee('ledgerPagination')
            ->assertSee('Rekapitulasi per Akun Kategori')
            ->assertSee('250.000')
            ->assertSee('financial-statement/pdf?start_date=2026-10-01', false);
    }
}
