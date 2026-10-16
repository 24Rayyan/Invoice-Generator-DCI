<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Exports\FinancialStatementExport;
use App\Services\FinancialReportService;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class FinancialStatementPdfTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CategorySeeder::class);

        $role = Role::where('name', 'super_admin')->firstOrFail();
        $this->admin = User::create([
            'username' => 'report_admin',
            'name' => 'Report Admin',
            'email' => 'report-admin@example.test',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }

    public function test_financial_statement_pdf_renders_professional_ledger_and_downloads_landscape_report(): void
    {
        Setting::getSetting();
        $incomeCategory = Category::where('type', 'income')->firstOrFail();
        $expenseCategory = Category::where('type', 'expense')->firstOrFail();

        Transaction::create([
            'transaction_number' => 'TRX-IN/2026/10/0001',
            'type' => 'income',
            'category_id' => $incomeCategory->id,
            'amount' => 2500000,
            'transaction_date' => '2026-10-05',
            'payment_method' => 'Bank Transfer',
            'description' => 'Pembayaran proyek utama',
        ]);
        Transaction::create([
            'transaction_number' => 'TRX-OUT/2026/10/0001',
            'type' => 'expense',
            'category_id' => $expenseCategory->id,
            'amount' => 750000,
            'transaction_date' => '2026-10-06',
            'payment_method' => 'Cash',
            'description' => 'Pembelian perlengkapan kantor',
        ]);

        $reportData = app(FinancialReportService::class)->getDetailedFinancialReport(
            '2026-10-01',
            '2026-10-31'
        );
        $html = view('exports.financial_statement_pdf', [
            'reportData' => $reportData,
            'setting' => Setting::first(),
        ])->render();

        $this->assertStringContainsString('size: A4 landscape', $html);
        $this->assertStringContainsString('Ikhtisar Arus Kas', $html);
        $this->assertStringContainsString('Surplus / (Defisit) Kas', $html);
        $this->assertStringContainsString('Ringkasan Mutasi per Akun', $html);
        $this->assertStringContainsString('Rincian Buku Kas', $html);
        $this->assertStringContainsString('table-header-group', $html);
        $this->assertStringContainsString('Pembayaran proyek utama', $html);
        $this->assertStringContainsString('2.500.000', $html);
        $this->assertStringContainsString('750.000', $html);

        $this->actingAs($this->admin)
            ->get(route('reports.financial_statement.pdf', [
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-31',
            ]))
            ->assertOk()
            ->assertDownload('Laporan_Keuangan_DCI_2026-10-01_sd_2026-10-31.pdf');
    }

    public function test_financial_statement_excel_export_downloads_xlsx_file(): void
    {
        Setting::getSetting();

        $reportData = app(FinancialReportService::class)->getDetailedFinancialReport(
            '2026-10-01',
            '2026-10-31'
        );
        $xlsx = Excel::raw(
            new FinancialStatementExport($reportData, Setting::first()),
            \Maatwebsite\Excel\Excel::XLSX
        );
        $this->assertNotEmpty($xlsx);

        $this->actingAs($this->admin)
            ->get(route('reports.financial_statement.excel', [
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-31',
            ]))
            ->assertOk()
            ->assertDownload('Laporan_Keuangan_DCI_2026-10-01_sd_2026-10-31.xlsx');
    }
}
