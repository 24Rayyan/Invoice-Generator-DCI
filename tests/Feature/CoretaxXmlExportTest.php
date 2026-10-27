<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\Setting;
use App\Services\CoretaxXmlService;
use DOMDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoretaxXmlExportTest extends TestCase
{
    use RefreshDatabase;

    protected Setting $setting;
    protected Customer $customerWithNpwp;
    protected Customer $customerWithNik;
    protected Customer $customerSpecialChars;
    protected Document $invoice1;
    protected Document $invoice2;
    protected Document $invoiceSpecial;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setting Penjual
        $this->setting = Setting::create([
            'company_name'       => 'PT DWITAMA CIPTA INTERNUSA',
            'company_npwp'       => '01.234.567.8-901.000',
            'company_npwp16'     => '0123456789012345',
            'company_nitku'      => '0123456789012345000000',
            'company_address'    => 'Jl. Buah Batu No. 123, Bandung',
            'company_email'      => 'finance@dwitama.co.id',
            'company_phone'      => '0227123456',
            'signatory_name'     => 'Fauzan Septiana',
            'signatory_position' => 'Direktur Utama',
            'currency_symbol'    => 'Rp',
            'default_tax_rate'   => 11.00,
            'enable_tax'         => true,
        ]);

        // 2. Customer 1: Badan Usaha dengan NPWP 16 Digit
        $this->customerWithNpwp = Customer::create([
            'name'                         => 'PT MITRA SOLUSI DIGITAL',
            'email'                        => 'billing@mitrasolusi.com',
            'phone'                        => '081122334455',
            'address'                      => 'Jl. Sudirman Kav. 21, Jakarta Selatan',
            'tax_id_type'                  => 'NPWP16',
            'tax_id_number'                => '9876543210987654',
            'nitku'                        => '9876543210987654000000',
            'default_tax_transaction_code' => '040',
        ]);

        // 3. Customer 2: Orang Pribadi dengan NIK KTP
        $this->customerWithNik = Customer::create([
            'name'                         => 'Ahmad Suryana',
            'email'                        => 'ahmad.suryana@gmail.com',
            'phone'                        => '081987654321',
            'address'                      => 'Jl. Dago Asri No. 45, Bandung',
            'tax_id_type'                  => 'NIK',
            'tax_id_number'                => '3273012345670001',
            'nitku'                        => '000000',
            'default_tax_transaction_code' => '010',
        ]);

        // 4. Customer 3: Karakter Khusus XML (&, <, >, ", ')
        $this->customerSpecialChars = Customer::create([
            'name'                         => 'CV & PT "Bintang <Kreatif>" & Co.',
            'email'                        => 'info@bintang-kreatif.com',
            'phone'                        => '081234567890',
            'address'                      => 'Jl. Riau & Merdeka <No. 10>, Bandung',
            'tax_id_type'                  => 'NPWP16',
            'tax_id_number'                => '1122334455667788',
            'nitku'                        => '000000',
            'default_tax_transaction_code' => '040',
        ]);

        // 5. Invoice 1 (Jasa / 040)
        $this->invoice1 = Document::create([
            'customer_id'          => $this->customerWithNpwp->id,
            'type'                 => 'INVOICE',
            'document_number'      => 'INV-DCI/2026/09/001',
            'date'                 => '2026-09-07',
            'tax_transaction_code' => '040',
            'tax_invoice_date'     => '2026-09-07',
            'status'               => 'SENT',
            'total_amount'         => 27750000,
            'discount'             => 0,
        ]);

        DocumentItem::create([
            'document_id' => $this->invoice1->id,
            'description' => 'Jasa Maintenance Sistem Server & Cloud',
            'item_type'   => 'B',
            'qty'         => 1,
            'unit'        => 'Bulan',
            'price'       => 25000000,
            'subtotal'    => 25000000,
        ]);

        // 6. Invoice 2 (Barang / 010)
        $this->invoice2 = Document::create([
            'customer_id'          => $this->customerWithNik->id,
            'type'                 => 'INVOICE',
            'document_number'      => 'INV-DCI/2026/09/002',
            'date'                 => '2026-09-10',
            'tax_transaction_code' => '010',
            'tax_invoice_date'     => '2026-09-10',
            'status'               => 'PAID',
            'total_amount'         => 5550000,
            'discount'             => 0,
        ]);

        DocumentItem::create([
            'document_id' => $this->invoice2->id,
            'description' => 'Router Wireless Gigabit Dual Band',
            'item_type'   => 'A',
            'qty'         => 2,
            'unit'        => 'Pcs',
            'price'       => 2500000,
            'subtotal'    => 5000000,
        ]);

        // 7. Invoice Special Chars
        $this->invoiceSpecial = Document::create([
            'customer_id'          => $this->customerSpecialChars->id,
            'type'                 => 'INVOICE',
            'document_number'      => 'INV-DCI/2026/09/003',
            'date'                 => '2026-09-15',
            'tax_transaction_code' => '040',
            'tax_invoice_date'     => '2026-09-15',
            'status'               => 'SENT',
            'total_amount'         => 11100000,
            'discount'             => 0,
        ]);

        DocumentItem::create([
            'document_id' => $this->invoiceSpecial->id,
            'description' => 'Instalasi Jaringan & Web Server <Production> Ver "2.0"',
            'item_type'   => 'B',
            'qty'         => 1,
            'unit'        => 'Paket',
            'price'       => 10000000,
            'subtotal'    => 10000000,
        ]);
    }

    /**
     * Test Setting model static get helper
     */
    public function test_setting_static_get_helper(): void
    {
        $this->assertEquals('0123456789012345', Setting::get('company_tin'));
        $this->assertEquals('0123456789012345', Setting::get('tin'));
        $this->assertEquals('0123456789012345000000', Setting::get('company_nitku'));
        $this->assertEquals('Fauzan Septiana', Setting::get('signatory_name'));
        $this->assertEquals('Default Value', Setting::get('non_existent_key', 'Default Value'));
    }

    /**
     * Test Customer dynamic accessors
     */
    public function test_customer_dynamic_accessors(): void
    {
        // Customer 1: NPWP16
        $this->assertEquals('9876543210987654', $this->customerWithNpwp->tin);
        $this->assertEquals('TIN', $this->customerWithNpwp->document_type);
        $this->assertEquals('9876543210987654', $this->customerWithNpwp->document_number);
        $this->assertEquals('IDN', $this->customerWithNpwp->country_code);

        // Customer 2: NIK
        $this->assertNull($this->customerWithNik->tin);
        $this->assertEquals('National ID', $this->customerWithNik->document_type);
        $this->assertEquals('3273012345670001', $this->customerWithNik->document_number);
        $this->assertEquals('IDN', $this->customerWithNik->country_code);
    }

    /**
     * Test XML generation directly via CoretaxXmlService
     */
    public function test_coretax_xml_service_structure_and_schema(): void
    {
        $service = new CoretaxXmlService();
        $xmlString = $service->generateXml([$this->invoice1, $this->invoice2], $this->setting);

        $this->assertNotEmpty($xmlString);

        // Validasi struktur XML DOM
        $dom = new DOMDocument();
        $loaded = $dom->loadXML($xmlString);
        $this->assertTrue($loaded, 'XML must be valid XML Document');

        // Root element
        $root = $dom->documentElement;
        $this->assertEquals('TaxInvoiceBulk', $root->tagName);
        $this->assertEquals('http://www.w3.org/2001/XMLSchema', $root->getAttribute('xmlns:xsd'));
        $this->assertEquals('http://www.w3.org/2001/XMLSchema-instance', $root->getAttribute('xmlns:xsi'));

        // Single root <TIN> Penjual
        $tinElements = $root->getElementsByTagName('TIN');
        $this->assertEquals(1, $tinElements->length);
        $this->assertEquals('0123456789012345', $tinElements->item(0)->textContent);

        // ListOfTaxInvoice
        $listOfTaxInvoice = $root->getElementsByTagName('ListOfTaxInvoice');
        $this->assertEquals(1, $listOfTaxInvoice->length);

        $taxInvoices = $listOfTaxInvoice->item(0)->getElementsByTagName('TaxInvoice');
        $this->assertEquals(2, $taxInvoices->length);

        // Faktur 1 (NPWP Pembeli, TrxCode 04, Unit UM.0017 / Bulan)
        $inv1Node = $taxInvoices->item(0);
        $this->assertEquals('2026-09-07', $inv1Node->getElementsByTagName('TaxInvoiceDate')->item(0)->textContent);
        $this->assertEquals('Normal', $inv1Node->getElementsByTagName('TaxInvoiceOpt')->item(0)->textContent);
        $this->assertEquals('04', $inv1Node->getElementsByTagName('TrxCode')->item(0)->textContent);
        $this->assertEquals('INV-DCI/2026/09/001', $inv1Node->getElementsByTagName('RefDesc')->item(0)->textContent);
        $this->assertEquals('0123456789012345000000', $inv1Node->getElementsByTagName('SellerIDTKU')->item(0)->textContent);
        $this->assertEquals('9876543210987654', $inv1Node->getElementsByTagName('BuyerTin')->item(0)->textContent);
        $this->assertEquals('TIN', $inv1Node->getElementsByTagName('BuyerDocument')->item(0)->textContent);
        $this->assertEquals('IDN', $inv1Node->getElementsByTagName('BuyerCountry')->item(0)->textContent);
        $this->assertEquals('', $inv1Node->getElementsByTagName('BuyerDocumentNumber')->item(0)->textContent);
        $this->assertEquals('PT MITRA SOLUSI DIGITAL', $inv1Node->getElementsByTagName('BuyerName')->item(0)->textContent);
        $this->assertEquals('Jl. Sudirman Kav. 21, Jakarta Selatan', $inv1Node->getElementsByTagName('BuyerAdress')->item(0)->textContent);
        $this->assertEquals('billing@mitrasolusi.com', $inv1Node->getElementsByTagName('BuyerEmail')->item(0)->textContent);
        $this->assertEquals('9876543210987654000000', $inv1Node->getElementsByTagName('BuyerIDTKU')->item(0)->textContent);

        // Detail GoodService Faktur 1 (Jasa: Opt B, Code 000000, Unit UM.0030, TrxCode 04: OtherTaxBase = 11/12 * 25000000 = 22916666.67)
        $goodServices1 = $inv1Node->getElementsByTagName('GoodService');
        $this->assertEquals(1, $goodServices1->length);
        $gs1 = $goodServices1->item(0);
        $this->assertEquals('B', $gs1->getElementsByTagName('Opt')->item(0)->textContent);
        $this->assertEquals('000000', $gs1->getElementsByTagName('Code')->item(0)->textContent);
        $this->assertEquals('Jasa Maintenance Sistem Server & Cloud', $gs1->getElementsByTagName('Name')->item(0)->textContent);
        $this->assertEquals('UM.0030', $gs1->getElementsByTagName('Unit')->item(0)->textContent);
        $this->assertEquals('25000000', $gs1->getElementsByTagName('Price')->item(0)->textContent);
        $this->assertEquals('1', $gs1->getElementsByTagName('Qty')->item(0)->textContent);
        $this->assertEquals('0', $gs1->getElementsByTagName('TotalDiscount')->item(0)->textContent);
        $this->assertEquals('25000000', $gs1->getElementsByTagName('TaxBase')->item(0)->textContent);
        $this->assertEquals('22916666.67', $gs1->getElementsByTagName('OtherTaxBase')->item(0)->textContent);
        $this->assertEquals('12', $gs1->getElementsByTagName('VATRate')->item(0)->textContent);
        $this->assertEquals('2750000', $gs1->getElementsByTagName('VAT')->item(0)->textContent);

        // Faktur 2 (NIK Pembeli, TrxCode 01, Barang: Opt A, Code 000000, Unit UM.0033, TrxCode 01: OtherTaxBase 0)
        $inv2Node = $taxInvoices->item(1);
        $this->assertEquals('01', $inv2Node->getElementsByTagName('TrxCode')->item(0)->textContent);
        $this->assertEquals('0000000000000000', $inv2Node->getElementsByTagName('BuyerTin')->item(0)->textContent);
        $this->assertEquals('National ID', $inv2Node->getElementsByTagName('BuyerDocument')->item(0)->textContent);
        $this->assertEquals('3273012345670001', $inv2Node->getElementsByTagName('BuyerDocumentNumber')->item(0)->textContent);
        $this->assertEquals('0000000000000000000000', $inv2Node->getElementsByTagName('BuyerIDTKU')->item(0)->textContent);

        $goodServices2 = $inv2Node->getElementsByTagName('GoodService');
        $this->assertEquals(1, $goodServices2->length);
        $gs2 = $goodServices2->item(0);
        $this->assertEquals('A', $gs2->getElementsByTagName('Opt')->item(0)->textContent);
        $this->assertEquals('000000', $gs2->getElementsByTagName('Code')->item(0)->textContent);
        $this->assertEquals('UM.0033', $gs2->getElementsByTagName('Unit')->item(0)->textContent);
        $this->assertEquals('2500000', $gs2->getElementsByTagName('Price')->item(0)->textContent);
        $this->assertEquals('2', $gs2->getElementsByTagName('Qty')->item(0)->textContent);
        $this->assertEquals('5000000', $gs2->getElementsByTagName('TaxBase')->item(0)->textContent);
        $this->assertEquals('0', $gs2->getElementsByTagName('OtherTaxBase')->item(0)->textContent);
        $this->assertEquals('550000', $gs2->getElementsByTagName('VAT')->item(0)->textContent);
    }

    /**
     * Test XML escaping special characters (&, <, >, ", ')
     */
    public function test_coretax_xml_auto_escaping_special_characters(): void
    {
        $service = new CoretaxXmlService();
        $xmlString = $service->generateXml([$this->invoiceSpecial], $this->setting);

        $this->assertNotEmpty($xmlString);

        // Verifikasi raw string ter-escape karakter khusus XML (&, <, >)
        $this->assertStringContainsString('CV &amp; PT "Bintang &lt;Kreatif&gt;" &amp; Co.', $xmlString);
        $this->assertStringContainsString('Jl. Riau &amp; Merdeka &lt;No. 10&gt;, Bandung', $xmlString);
        $this->assertStringContainsString('Instalasi Jaringan &amp; Web Server &lt;Production&gt; Ver "2.0"', $xmlString);

        // Verifikasi DOM parsing mengembalikan teks asli tanpa korupsi XML
        $dom = new DOMDocument();
        $dom->loadXML($xmlString);

        $buyerName = $dom->getElementsByTagName('BuyerName')->item(0)->textContent;
        $this->assertEquals('CV & PT "Bintang <Kreatif>" & Co.', $buyerName);

        $buyerAddress = $dom->getElementsByTagName('BuyerAdress')->item(0)->textContent;
        $this->assertEquals('Jl. Riau & Merdeka <No. 10>, Bandung', $buyerAddress);

        $itemName = $dom->getElementsByTagName('Name')->item(0)->textContent;
        $this->assertEquals('Instalasi Jaringan & Web Server <Production> Ver "2.0"', $itemName);
    }

    public function test_stored_item_type_takes_precedence_over_description_keywords(): void
    {
        $this->assertSame('A', CoretaxXmlService::determineItemOpt((object) [
            'item_type' => 'A',
            'description' => 'Jasa konsultasi',
        ]));
        $this->assertSame('B', CoretaxXmlService::determineItemOpt((object) [
            'item_type' => 'B',
            'description' => 'Router wireless',
        ]));
        $this->assertSame('UM.0033', CoretaxXmlService::getUnitCode('A'));
        $this->assertSame('UM.0030', CoretaxXmlService::getUnitCode('B'));
    }

    /**
     * Test HTTP endpoint POST /documents/export-coretax-xml
     */
    public function test_export_coretax_xml_endpoint_download(): void
    {
        $response = $this->post(route('documents.export_coretax_xml'), [
            'document_ids'     => [$this->invoice1->id, $this->invoice2->id],
            'tax_invoice_date' => '2026-09-17',
        ]);

        $response->assertSuccessful();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $this->assertStringContainsString('attachment; filename="Coretax_FK_v1.6.1_', $response->headers->get('Content-Disposition'));

        $content = $response->getContent();
        $this->assertStringContainsString('<TaxInvoiceBulk', $content);
        $this->assertStringContainsString('0123456789012345', $content);
        $this->assertStringContainsString('INV-DCI/2026/09/001', $content);
        $this->assertStringContainsString('2026-09-17', $content);
    }

    /**
     * Test HTTP endpoint POST /documents/export-xml alias
     */
    public function test_export_xml_alias_endpoint(): void
    {
        $response = $this->post(route('documents.export_xml'), [
            'document_ids' => [$this->invoice1->id],
        ]);

        $response->assertSuccessful();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * Test POST /documents/export-coretax with export_type=xml returns XML file
     */
    public function test_export_coretax_with_xml_type(): void
    {
        $response = $this->post(route('documents.export_coretax'), [
            'document_ids' => [$this->invoice1->id],
            'export_type'  => 'xml',
        ]);

        $response->assertSuccessful();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $this->assertStringContainsString('<TaxInvoiceBulk', $response->getContent());
    }

    /**
     * Test validation error when no document_ids provided
     */
    public function test_export_xml_validation_fails_without_documents(): void
    {
        $response = $this->post(route('documents.export_coretax_xml'), []);
        $response->assertSessionHasErrors(['document_ids']);
    }

    /**
     * Test calculateOtherTaxBase formula 11/12 x DPP
     */
    public function test_calculate_other_tax_base_with_11_per_12_formula(): void
    {
        // 11/12 x 12.000.000 = 11.000.000
        $this->assertEquals(11000000.0, CoretaxXmlService::calculateOtherTaxBase(12000000, '04'));

        // 11/12 x 25.000.000 = 22.916.666,67
        $this->assertEquals(22916666.67, CoretaxXmlService::calculateOtherTaxBase(25000000, '04'));

        // Non-04 trxCode returns 0.0
        $this->assertEquals(0.0, CoretaxXmlService::calculateOtherTaxBase(12000000, '01'));
        $this->assertEquals(0.0, CoretaxXmlService::calculateOtherTaxBase(12000000, '02'));
    }
}
