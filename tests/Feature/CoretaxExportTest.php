<?php

namespace Tests\Feature;

use App\Exports\CoretaxFkExport;
use App\Models\Customer;
use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoretaxExportTest extends TestCase
{
    use RefreshDatabase;

    protected Setting $setting;
    protected Customer $customer;
    protected Document $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setting = Setting::create([
            'company_name'      => 'PT GLOBAL TEKNOLOGI NUSANTARA',
            'company_npwp'      => '012345678901000',
            'company_npwp16'    => '0123456789012345',
            'company_nitku'     => '0123456789012345000000',
            'company_address'   => 'Jl. Sudirman No. 100, Jakarta Pusat',
            'company_email'     => 'finance@globaltekno.id',
            'signatory_name'    => 'Budi Santoso',
            'signatory_position'=> 'Direktur Utama',
            'currency_symbol'   => 'Rp',
            'default_tax_rate'  => 12.00,
            'enable_tax'        => true,
        ]);

        $this->customer = Customer::create([
            'name'                       => 'PT MITRA ABADI MAKMUR',
            'email'                      => 'procurement@mitraabadi.co.id',
            'phone'                      => '08123456789',
            'address'                    => 'Jl. Gatot Subroto Kav. 50, Jakarta Selatan',
            'tax_id_type'                => 'NPWP16',
            'tax_id_number'              => '9876543210987654',
            'nitku'                      => '9876543210987654000000',
            'default_tax_transaction_code' => '040',
        ]);

        $this->invoice = Document::create([
            'customer_id'          => $this->customer->id,
            'type'                 => 'INVOICE',
            'document_number'      => 'INV-2026-09-001',
            'date'                 => '2026-09-07',
            'tax_transaction_code' => '040',
            'tax_invoice_date'     => '2026-09-07',
            'status'               => 'SENT',
            'total_amount'         => 11200000,
            'discount'             => 0,
        ]);

        DocumentItem::create([
            'document_id' => $this->invoice->id,
            'description' => 'Jasa Maintenance Server Bulanan',
            'item_type'   => 'B',
            'qty'         => 1,
            'unit'        => 'Bulan',
            'price'       => 10000000,
            'subtotal'    => 10000000,
        ]);
    }

    /**
     * Test Setting model stores Coretax fields properly
     */
    public function test_setting_persists_coretax_fields(): void
    {
        $this->assertEquals('0123456789012345', $this->setting->company_npwp16);
        $this->assertEquals('0123456789012345000000', $this->setting->company_nitku);
        $this->assertEquals('Budi Santoso', $this->setting->signatory_name);
        $this->assertEquals('Direktur Utama', $this->setting->signatory_position);
    }

    /**
     * Test Customer model stores Coretax fields properly
     */
    public function test_customer_persists_coretax_fields(): void
    {
        $this->assertEquals('NPWP16', $this->customer->tax_id_type);
        $this->assertEquals('9876543210987654', $this->customer->tax_id_number);
        $this->assertEquals('9876543210987654000000', $this->customer->nitku);
        $this->assertEquals('040', $this->customer->default_tax_transaction_code);
    }

    /**
     * Test Document model stores Coretax transaction code and invoice date (Carbon cast)
     */
    public function test_document_persists_coretax_fields(): void
    {
        $doc = Document::find($this->invoice->id);
        $this->assertEquals('040', $doc->tax_transaction_code);
        // tax_invoice_date is cast to Carbon by $casts
        $this->assertEquals('2026-09-07', $doc->tax_invoice_date->format('Y-m-d'));
    }

    /**
     * Test Export validation fails when no documents selected
     */
    public function test_export_coretax_validation_requires_document_ids(): void
    {
        $response = $this->post(route('documents.export_coretax'), []);
        $response->assertSessionHasErrors(['document_ids']);
    }

    /**
     * Test Export Coretax downloads valid Excel file
     */
    public function test_export_coretax_successful_download(): void
    {
        $response = $this->post(route('documents.export_coretax'), [
            'document_ids'     => [$this->invoice->id],
            'tax_invoice_date' => '2026-09-07',
        ]);

        $response->assertSuccessful();
        $response->assertDownload();
    }

    /**
     * Test Coretax Header and Detail Sheet structure and rendered HTML
     */
    public function test_coretax_export_sheets_structure(): void
    {
        $documents = Document::with(['customer', 'items'])->where('id', $this->invoice->id)->get();
        $export = new CoretaxFkExport($documents, $this->setting, '2026-09-07');
        $sheets = $export->sheets();

        $this->assertCount(2, $sheets);
        $this->assertEquals('FAKTUR_KELUARAN', $sheets[0]->title());
        $this->assertEquals('DETAIL_OBJEK_FAKTUR', $sheets[1]->title());

        // Render header sheet and verify key data
        $headerHtml = $sheets[0]->view()->render();
        $this->assertStringContainsString('NPWP_PENJUAL', $headerHtml);
        $this->assertStringContainsString('0123456789012345', $headerHtml);
        $this->assertStringContainsString('INV-2026-09-001', $headerHtml);

        // Render detail sheet and verify item data
        $detailHtml = $sheets[1]->view()->render();
        $this->assertStringContainsString('NOMOR_DOKUMEN_REFERENSI', $detailHtml);
        $this->assertStringContainsString('<td>B</td>', $detailHtml);
        $this->assertStringContainsString('Jasa Maintenance Server Bulanan', $detailHtml);
        $this->assertStringContainsString('000000', $detailHtml);
        $this->assertStringContainsString('UM.0030', $detailHtml);
    }
}
