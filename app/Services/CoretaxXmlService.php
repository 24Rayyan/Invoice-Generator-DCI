<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Setting;
use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Support\Collection;

class CoretaxXmlService
{
    /**
     * Map satuan teks umum ke kode satuan standar Coretax DJP (UM.xxxx).
     */
    protected static array $unitMapping = [
        'PCS'        => 'UM.0001',
        'PIECES'     => 'UM.0001',
        'BUAH'       => 'UM.0001',
        'BH'         => 'UM.0001',
        'PASANG'     => 'UM.0002',
        'PAIR'       => 'UM.0002',
        'UNIT'       => 'UM.0003',
        'UNT'        => 'UM.0003',
        'SET'        => 'UM.0004',
        'PAKET'      => 'UM.0005',
        'PACKAGE'    => 'UM.0005',
        'PKT'        => 'UM.0005',
        'BOX'        => 'UM.0006',
        'DUS'        => 'UM.0006',
        'BOTOL'      => 'UM.0007',
        'BOTTLE'     => 'UM.0007',
        'RIM'        => 'UM.0008',
        'REAM'       => 'UM.0008',
        'BULAN'      => 'UM.0017',
        'MONTH'      => 'UM.0017',
        'BLN'        => 'UM.0017',
        'TAHUN'      => 'UM.0018',
        'YEAR'       => 'UM.0018',
        'THN'        => 'UM.0018',
        'HARI'       => 'UM.0019',
        'DAY'        => 'UM.0019',
        'HR'         => 'UM.0019',
        'JAM'        => 'UM.0020',
        'HOUR'       => 'UM.0020',
        'LAYANAN'    => 'UM.0022',
        'JASA'       => 'UM.0022',
        'KEGIATAN'   => 'UM.0022',
        'SERVICE'    => 'UM.0022',
        'SERVICES'   => 'UM.0022',
        'ACTIVITY'   => 'UM.0022',
        'METER'      => 'UM.0033',
        'M'          => 'UM.0033',
        'KILOGRAM'   => 'UM.0034',
        'KG'         => 'UM.0034',
        'LITER'      => 'UM.0035',
        'LTR'        => 'UM.0035',
        'LEMBAR'     => 'UM.0036',
        'LBR'        => 'UM.0036',
        'SHEET'      => 'UM.0036',
        'ROLL'       => 'UM.0037',
        'ROL'        => 'UM.0037',
    ];

    /**
     * Generate Coretax DJP (v1.6.1) XML Faktur Pajak Keluaran (TaxInvoiceBulk).
     *
     * @param  Collection|array  $documents  Koleksi Invoice model Document
     * @param  Setting|null      $setting    Instance Setting perusahaan
     * @param  string|null       $customDate Tanggal faktur kustom (opsional, format YYYY-MM-DD)
     * @return string XML format terstruktur Coretax v1.6.1
     */
    public function generateXml($documents, ?Setting $setting = null, ?string $customDate = null): string
    {
        $setting = $setting ?? Setting::getSetting();

        if (is_array($documents)) {
            $documents = collect($documents);
        }

        // 1. Inisialisasi DOMDocument
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->formatOutput = true;
        $dom->preserveWhiteSpace = false;

        // 2. Root Element: TaxInvoiceBulk dengan XMLSchema namespaces
        $root = $dom->createElement('TaxInvoiceBulk');
        $root->setAttribute('xmlns:xsd', 'http://www.w3.org/2001/XMLSchema');
        $root->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $dom->appendChild($root);

        // 3. Data Penjual (Seller)
        $sellerNpwp16 = $this->sanitizeDigits($setting->company_npwp16 ?? Setting::get('company_tin') ?? '0123456789012345');
        $sellerTin = str_pad(substr($sellerNpwp16, 0, 16), 16, '0', STR_PAD_LEFT);

        $sellerRawNitku = $this->sanitizeDigits($setting->company_nitku ?? Setting::get('company_nitku') ?? '000000');
        $sellerNitku6 = strlen($sellerRawNitku) >= 6 ? substr($sellerRawNitku, -6) : str_pad($sellerRawNitku, 6, '0', STR_PAD_LEFT);
        $sellerIdtku = $sellerTin . $sellerNitku6;

        // Single root <TIN> tag
        $this->appendElement($dom, $root, 'TIN', $sellerTin);

        // 4. Wrapper <ListOfTaxInvoice>
        $listOfTaxInvoice = $dom->createElement('ListOfTaxInvoice');
        $root->appendChild($listOfTaxInvoice);

        // 5. Render setiap faktur pajak (<TaxInvoice>)
        foreach ($documents as $invoice) {
            $this->buildTaxInvoiceNode($dom, $listOfTaxInvoice, $invoice, $setting, $sellerIdtku, $customDate);
        }

        return $dom->saveXML();
    }

    /**
     * Membangun node <TaxInvoice> untuk sebuah invoice.
     */
    protected function buildTaxInvoiceNode(
        DOMDocument $dom,
        DOMElement $parent,
        Document $invoice,
        Setting $setting,
        string $sellerIdtku,
        ?string $customDate = null
    ): DOMElement {
        $customer = $invoice->customer;

        $taxInvoice = $dom->createElement('TaxInvoice');
        $parent->appendChild($taxInvoice);

        // 1. TaxInvoiceDate (YYYY-MM-DD)
        $taxDate = $this->resolveInvoiceDate($invoice, $customDate);
        $this->appendElement($dom, $taxInvoice, 'TaxInvoiceDate', $taxDate);

        // 2. TaxInvoiceOpt ('Normal' / 'Pengganti')
        $this->appendElement($dom, $taxInvoice, 'TaxInvoiceOpt', 'Normal');

        // 3. TrxCode – Prioritas: (1) Kode FP di detail Customer, (2) Kode di Dokumen, (3) Default 040
        $customerFpCode = $customer?->default_tax_transaction_code ?? null;
        $rawTrxCode = !empty($customerFpCode)
            ? $customerFpCode
            : (!empty($invoice->tax_transaction_code) ? $invoice->tax_transaction_code : '040');
        $trxCode = $this->formatTrxCode($rawTrxCode);
        $this->appendElement($dom, $taxInvoice, 'TrxCode', $trxCode);

        // 4. Structural Self-closing / Optional Tags
        $this->appendElement($dom, $taxInvoice, 'AddInfo', null);
        $this->appendElement($dom, $taxInvoice, 'CustomDoc', null);
        $this->appendElement($dom, $taxInvoice, 'CustomDocMonthYear', null);

        // 5. RefDesc (Nomor Invoice Referensi)
        $refDesc = $invoice->document_number ?? $invoice->number ?? 'INV-' . $invoice->id;
        $this->appendElement($dom, $taxInvoice, 'RefDesc', $refDesc);

        // 6. FacilityStamp
        $this->appendElement($dom, $taxInvoice, 'FacilityStamp', null);

        // 7. SellerIDTKU (22 digit)
        $this->appendElement($dom, $taxInvoice, 'SellerIDTKU', $sellerIdtku);

        // 8. Data Identitas Pembeli (Buyer)
        $buyerData = $this->resolveBuyerData($customer);

        $this->appendElement($dom, $taxInvoice, 'BuyerTin', $buyerData['tin']);
        $this->appendElement($dom, $taxInvoice, 'BuyerDocument', $buyerData['document_type']);
        $this->appendElement($dom, $taxInvoice, 'BuyerCountry', $buyerData['country_code']);
        $this->appendElement($dom, $taxInvoice, 'BuyerDocumentNumber', $buyerData['document_number']);
        $this->appendElement($dom, $taxInvoice, 'BuyerName', $buyerData['name']);
        $this->appendElement($dom, $taxInvoice, 'BuyerAdress', $buyerData['address']);
        $this->appendElement($dom, $taxInvoice, 'BuyerEmail', $buyerData['email']);
        $this->appendElement($dom, $taxInvoice, 'BuyerIDTKU', $buyerData['idtku']);

        // 9. ListOfGoodService -> GoodService items
        $listOfGoodService = $dom->createElement('ListOfGoodService');
        $taxInvoice->appendChild($listOfGoodService);

        $taxRate = (float) ($invoice->tax_percent ?? (($setting->enable_tax ?? true) ? ($setting->default_tax_rate ?? 11.00) : 0.00));

        $items = $invoice->items;
        if ($items && $items->isNotEmpty()) {
            foreach ($items as $idx => $item) {
                $this->buildGoodServiceNode($dom, $listOfGoodService, $item, $idx + 1, $taxRate, $trxCode);
            }
        } else {
            // Fallback jika tidak ada item detail
            $this->buildFallbackGoodServiceNode($dom, $listOfGoodService, $invoice, $taxRate, $trxCode);
        }

        return $taxInvoice;
    }

    /**
     * Membangun node <GoodService> untuk satu baris item barang/jasa.
     */
    protected function buildGoodServiceNode(
        DOMDocument $dom,
        DOMElement $parent,
        $item,
        int $index,
        float $taxRate,
        string $trxCode
    ): DOMElement {
        $goodService = $dom->createElement('GoodService');
        $parent->appendChild($goodService);

        // <Opt>: 'A' (Barang) / 'B' (Jasa)
        $opt = $this->determineItemOpt($item);
        $this->appendElement($dom, $goodService, 'Opt', $opt);

        // <Code>: Kode Barang / Jasa (Default '000000' dummy code Coretax)
        $code = $item->coretax_code ?? '000000';
        $this->appendElement($dom, $goodService, 'Code', $code);

        // <Name>: Nama Barang / Jasa
        $name = $item->description ?? ('Item ' . $index);
        $this->appendElement($dom, $goodService, 'Name', $name);

        // <Unit>: Satuan Coretax (Jika Jasa/Opt B selalu UM.0022, jika Barang sesuai unit barang)
        $unit = $this->formatUnit($item->unit ?? 'Unit', $opt);
        $this->appendElement($dom, $goodService, 'Unit', $unit);

        // Perhitungan Finansial & DPP Nilai Lain (Rumus 11/12 x DPP untuk Transaksi 04)
        $price = (float) $item->price;
        $qty = (float) $item->qty;
        $discount = 0.0;
        $taxBase = (float) ($item->subtotal ?? ($price * $qty));

        // Khusus Kode Transaksi 04 (DPP Nilai Lain), OtherTaxBase = 11/12 x DPP
        if ($trxCode === '04') {
            $otherTaxBase = isset($item->other_tax_base) ? (float) $item->other_tax_base : round(($taxBase * 11) / 12, 2);
            $vatRate = $taxRate > 0 ? 12.0 : 0.0;
            $vat = round($otherTaxBase * ($vatRate / 100), 2);
        } else {
            $otherTaxBase = 0.0;
            $vatRate = $taxRate;
            $vat = round($taxBase * ($taxRate / 100), 2);
        }

        $this->appendElement($dom, $goodService, 'Price', $this->formatNumber($price));
        $this->appendElement($dom, $goodService, 'Qty', $this->formatNumber($qty));
        $this->appendElement($dom, $goodService, 'TotalDiscount', $this->formatNumber($discount));
        $this->appendElement($dom, $goodService, 'TaxBase', $this->formatNumber($taxBase));
        $this->appendElement($dom, $goodService, 'OtherTaxBase', $this->formatNumber($otherTaxBase));
        $this->appendElement($dom, $goodService, 'VATRate', $this->formatNumber($vatRate));
        $this->appendElement($dom, $goodService, 'VAT', $this->formatNumber($vat));
        $this->appendElement($dom, $goodService, 'STLGRate', '0');
        $this->appendElement($dom, $goodService, 'STLG', '0');

        return $goodService;
    }

    /**
     * Membangun fallback node <GoodService> bila invoice tidak memiliki relasi item.
     */
    protected function buildFallbackGoodServiceNode(
        DOMDocument $dom,
        DOMElement $parent,
        Document $invoice,
        float $taxRate,
        string $trxCode
    ): DOMElement {
        $goodService = $dom->createElement('GoodService');
        $parent->appendChild($goodService);

        $this->appendElement($dom, $goodService, 'Opt', 'B');
        $this->appendElement($dom, $goodService, 'Code', '000000');
        $this->appendElement($dom, $goodService, 'Name', 'Penyerahan Jasa / Barang (' . ($invoice->document_number ?? 'Invoice') . ')');
        $this->appendElement($dom, $goodService, 'Unit', 'UM.0030');

        $taxBase = (float) ($invoice->subtotal ?? ($invoice->total_amount ?? 0));
        if ($trxCode === '04') {
            $otherTaxBase = round(($taxBase * 11) / 12, 2);
            $vatRate = $taxRate > 0 ? 12.0 : 0.0;
            $vat = round($otherTaxBase * ($vatRate / 100), 2);
        } else {
            $otherTaxBase = 0.0;
            $vatRate = $taxRate;
            $vat = round($taxBase * ($taxRate / 100), 2);
        }

        $this->appendElement($dom, $goodService, 'Price', $this->formatNumber($taxBase));
        $this->appendElement($dom, $goodService, 'Qty', '1');
        $this->appendElement($dom, $goodService, 'TotalDiscount', '0');
        $this->appendElement($dom, $goodService, 'TaxBase', $this->formatNumber($taxBase));
        $this->appendElement($dom, $goodService, 'OtherTaxBase', $this->formatNumber($otherTaxBase));
        $this->appendElement($dom, $goodService, 'VATRate', $this->formatNumber($vatRate));
        $this->appendElement($dom, $goodService, 'VAT', $this->formatNumber($vat));
        $this->appendElement($dom, $goodService, 'STLGRate', '0');
        $this->appendElement($dom, $goodService, 'STLG', '0');

        return $goodService;
    }

    /**
     * Resolusi dan normalisasi data pembeli (Buyer) untuk skema Coretax v1.6.1.
     */
    public function resolveBuyerData($customer): array
    {
        if (!$customer) {
            return [
                'tin'             => '0000000000000000',
                'document_type'   => 'National ID',
                'country_code'    => 'IDN',
                'document_number' => '0000000000000000',
                'name'            => 'Customer Umum',
                'address'         => '-',
                'email'           => null,
                'nitku'           => '000000',
                'idtku'           => '0000000000000000000000',
            ];
        }

        $rawDocType = strtoupper($customer->tax_id_type ?? $customer->document_type ?? 'NPWP16');
        $rawTaxId = (string) ($customer->tax_id_number ?? $customer->document_number ?? $customer->tin ?? '');
        $digitsTaxId = $this->sanitizeDigits($rawTaxId);

        // Resolusi Buyer NITKU (6 digit sub-unit / branch)
        $rawNitku = (string) ($customer->nitku ?? '');
        $digitsNitku = $this->sanitizeDigits($rawNitku);
        $nitku6 = strlen($digitsNitku) >= 6 ? substr($digitsNitku, -6) : str_pad($digitsNitku, 6, '0', STR_PAD_LEFT);

        $name = trim($customer->name ?? 'Customer Umum');
        $address = trim($customer->address ?? '-');
        if (empty($address)) {
            $address = '-';
        }
        $email = !empty($customer->email) ? trim($customer->email) : null;
        $countryCode = !empty($customer->country_code) ? strtoupper(substr($customer->country_code, 0, 3)) : 'IDN';

        // Penentuan jenis dokumen & nomor
        if ($rawDocType === 'NIK' || $rawDocType === 'NATIONAL ID' || $rawDocType === 'NATIONAL_ID' || $rawDocType === 'KTP') {
            $documentType = 'National ID';
            $tin = '0000000000000000';
            $documentNumber = !empty($digitsTaxId) ? $digitsTaxId : ($this->sanitizeDigits($customer->phone ?? '') ?: '0000000000000000');
            $idtku = '0000000000000000' . $nitku6;
        } elseif ($rawDocType === 'PASPOR' || $rawDocType === 'PASSPORT') {
            $documentType = 'Passport';
            $tin = '0000000000000000';
            $documentNumber = !empty($rawTaxId) ? trim($rawTaxId) : 'PASSPORT';
            $idtku = '0000000000000000' . $nitku6;
        } elseif (!empty($digitsTaxId) && strlen($digitsTaxId) >= 15) {
            $documentType = 'TIN';
            $tin = str_pad(substr($digitsTaxId, 0, 16), 16, '0', STR_PAD_LEFT);
            $documentNumber = null; // Self-closing pada Coretax jika TIN
            $idtku = $tin . $nitku6;
        } else {
            // Non-NPWP / Pelanggan tanpa Tax ID spesifik
            $documentType = 'National ID';
            $tin = '0000000000000000';
            $documentNumber = !empty($digitsTaxId) ? $digitsTaxId : ($this->sanitizeDigits($customer->phone ?? '') ?: '0000000000000000');
            $idtku = '0000000000000000' . $nitku6;
        }

        return [
            'tin'             => $tin,
            'document_type'   => $documentType,
            'country_code'    => $countryCode,
            'document_number' => $documentNumber,
            'name'            => $name,
            'address'         => $address,
            'email'           => $email,
            'nitku'           => $nitku6,
            'idtku'           => $idtku,
        ];
    }

    /**
     * Resolusi tanggal faktur pajak (YYYY-MM-DD).
     */
    protected function resolveInvoiceDate(Document $invoice, ?string $customDate = null): string
    {
        if (!empty($customDate)) {
            return date('Y-m-d', strtotime($customDate));
        }

        if (!empty($invoice->tax_invoice_date)) {
            return is_string($invoice->tax_invoice_date)
                ? date('Y-m-d', strtotime($invoice->tax_invoice_date))
                : $invoice->tax_invoice_date->format('Y-m-d');
        }

        if (!empty($invoice->date)) {
            return is_string($invoice->date)
                ? date('Y-m-d', strtotime($invoice->date))
                : $invoice->date->format('Y-m-d');
        }

        return date('Y-m-d');
    }

    /**
     * Format TrxCode menjadi 2 digit string ('01', '04', '07', dll).
     */
    protected function formatTrxCode(?string $code): string
    {
        if (empty($code)) {
            return '04';
        }

        $digits = $this->sanitizeDigits($code);
        if (empty($digits)) {
            return '04';
        }

        if (strlen($digits) >= 2) {
            return substr($digits, 0, 2);
        }

        return str_pad($digits, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Menentukan apakah item bertipe 'A' (Barang) atau 'B' (Jasa).
     */
    public static function determineItemOpt($item): string
    {
        if (isset($item->item_type) && in_array(strtoupper((string) $item->item_type), ['A', 'B'], true)) {
            return strtoupper((string) $item->item_type);
        }

        if (isset($item->opt) && in_array(strtoupper((string)$item->opt), ['A', 'B'])) {
            return strtoupper((string)$item->opt);
        }

        if (isset($item->type)) {
            $type = strtoupper((string)$item->type);
            if (in_array($type, ['JASA', 'SERVICE', 'SERVICES'])) {
                return 'B';
            }
            if (in_array($type, ['BARANG', 'GOODS', 'PRODUCT'])) {
                return 'A';
            }
        }

        $desc = strtolower($item->description ?? '');
        $serviceKeywords = [
            'jasa', 'service', 'maintenance', 'konsultasi', 'sewa',
            'instalasi', 'pengembangan', 'pemeliharaan', 'dukungan',
            'support', 'perbaikan', 'langganan', 'subscription', 'ongkir', 'freight'
        ];

        foreach ($serviceKeywords as $keyword) {
            if (str_contains($desc, $keyword)) {
                return 'B';
            }
        }

        return 'A';
    }

    /**
     * Mengambil kode satuan Coretax:
     * - Opt B (Jasa)   => 'UM.0030'
     * - Opt A (Barang) => 'UM.0033'
     */
    public static function getUnitCode(string $opt = 'A'): string
    {
        return strtoupper($opt) === 'B' ? 'UM.0030' : 'UM.0033';
    }

    /**
     * Hitung DPP Nilai Lain menggunakan rumus resmi: 11/12 x DPP.
     */
    public static function calculateOtherTaxBase(float $taxBase, string $trxCode = '04'): float
    {
        if ($trxCode === '04') {
            return round(($taxBase * 11) / 12, 2);
        }

        return 0.0;
    }

    /**
     * Konversi satuan ke kode Coretax UM.xxxx.
     * Sesuai aturan:
     * - Jasa (Opt B) langsung di-set ke 'UM.0030'
     * - Barang (Opt A) langsung di-set ke 'UM.0033'
     */
    protected function formatUnit(?string $rawUnit, string $opt = 'A'): string
    {
        return self::getUnitCode($opt);
    }

    /**
     * Format angka desimal tanpa pemisah ribuan.
     */
    protected function formatNumber(float|int|string $number): string
    {
        $val = (float) $number;
        if (floor($val) == $val) {
            return (string) (int) $val;
        }

        return rtrim(rtrim(number_format($val, 4, '.', ''), '0'), '.');
    }

    /**
     * Hapus karakter non-digit (titik, strip, spasi, huruf).
     */
    protected function sanitizeDigits(?string $input): string
    {
        if ($input === null) {
            return '';
        }

        return preg_replace('/[^0-9]/', '', (string)$input);
    }

    /**
     * Helper pembuatan elemen XML dengan auto-escaping text node atau self-closing bila kosong.
     */
    protected function appendElement(DOMDocument $dom, DOMNode $parent, string $name, ?string $value = null): DOMElement
    {
        $element = $dom->createElement($name);

        if ($value !== null && $value !== '') {
            $element->appendChild($dom->createTextNode($value));
        }

        $parent->appendChild($element);
        return $element;
    }
}
