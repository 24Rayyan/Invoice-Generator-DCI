<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        DB::transaction(function (): void {
            $categories = $this->seedCategories();
            $customers = $this->seedCustomers();
            $documents = $this->seedInvoices($customers);

            $this->seedTransactions($categories, $documents);
        });

        $this->command?->info('Data demo berhasil disiapkan: 15 customer, 10 invoice, 10 akun kategori, dan 13 transaksi kas.');
        $this->command?->warn('Seluruh data customer dan transaksi ini sintetis untuk demonstrasi, bukan catatan perusahaan sungguhan.');
    }

    /**
     * @return array<string, Category>
     */
    private function seedCategories(): array
    {
        $definitions = [
            ['code' => '4-100', 'name' => 'Pendapatan Jasa Implementasi dan Konsultasi', 'type' => 'income', 'is_tax_deductible' => false, 'description' => 'Pendapatan jasa analisis, implementasi, konfigurasi, dan konsultasi teknologi informasi.'],
            ['code' => '4-110', 'name' => 'Pendapatan Pemeliharaan Sistem', 'type' => 'income', 'is_tax_deductible' => false, 'description' => 'Pendapatan kontrak pemeliharaan dan dukungan sistem berkala.'],
            ['code' => '4-120', 'name' => 'Pendapatan Pengadaan Perangkat', 'type' => 'income', 'is_tax_deductible' => false, 'description' => 'Pendapatan penjualan atau pengadaan perangkat jaringan dan server.'],
            ['code' => '5-101', 'name' => 'Harga Pokok Pengadaan Perangkat', 'type' => 'expense', 'is_tax_deductible' => true, 'description' => 'Biaya pembelian perangkat keras yang langsung terkait pesanan pelanggan.'],
            ['code' => '5-102', 'name' => 'Jasa Subkontraktor Instalasi', 'type' => 'expense', 'is_tax_deductible' => true, 'description' => 'Biaya tenaga subkontraktor untuk pemasangan kabel, jaringan, atau perangkat.'],
            ['code' => '6-101', 'name' => 'Langganan Cloud dan Internet', 'type' => 'expense', 'is_tax_deductible' => true, 'description' => 'Biaya layanan cloud, hosting, pemantauan, dan konektivitas usaha.'],
            ['code' => '6-201', 'name' => 'Transportasi dan Perjalanan Dinas', 'type' => 'expense', 'is_tax_deductible' => true, 'description' => 'Biaya perjalanan untuk survei lokasi dan pelaksanaan pekerjaan pelanggan.'],
            ['code' => '6-301', 'name' => 'Sewa dan Utilitas Kantor', 'type' => 'expense', 'is_tax_deductible' => true, 'description' => 'Biaya sewa ruang kerja, listrik, air, dan utilitas kantor.'],
            ['code' => '6-401', 'name' => 'Perlengkapan dan Administrasi Kantor', 'type' => 'expense', 'is_tax_deductible' => true, 'description' => 'Biaya perlengkapan kerja dan kebutuhan administrasi kantor.'],
            ['code' => '6-501', 'name' => 'Biaya Administrasi Bank', 'type' => 'expense', 'is_tax_deductible' => true, 'description' => 'Biaya administrasi rekening dan transfer bank perusahaan.'],
        ];

        $categories = [];

        foreach ($definitions as $definition) {
            $categories[$definition['code']] = Category::firstOrCreate(
                ['code' => $definition['code']],
                $definition
            );
        }

        return $categories;
    }

    /**
     * @return array<int, Customer>
     */
    private function seedCustomers(): array
    {
        $records = [
            ['PT Arunika Teknologi Nusantara [DEMO]', 'Kuningan, Jakarta Selatan'],
            ['CV Pilar Data Mandiri [DEMO]', 'Coblong, Bandung'],
            ['PT Sagara Sistem Integrasi [DEMO]', 'BSD City, Tangerang Selatan'],
            ['PT Karya Niaga Digital [DEMO]', 'Tegalsari, Surabaya'],
            ['CV Lentera Komputasi [DEMO]', 'Depok, Yogyakarta'],
            ['PT Cakrawala Medika Pratama [DEMO]', 'Menteng, Jakarta Pusat'],
            ['PT Reka Industri Otomasi [DEMO]', 'Cikarang, Bekasi'],
            ['CV Solusi Jaringan Nusantara [DEMO]', 'Tembalang, Semarang'],
            ['PT Bumi Logistik Digital [DEMO]', 'Kelapa Gading, Jakarta Utara'],
            ['PT Nusa Edukasi Teknologi [DEMO]', 'Buahbatu, Bandung'],
            ['CV Andalan Infrastruktur [DEMO]', 'Panakkukang, Makassar'],
            ['PT Mitra Karya Energi [DEMO]', 'Balikpapan Selatan, Balikpapan'],
            ['PT Koneksi Prima Indonesia [DEMO]', 'Beji, Depok'],
            ['CV Griya Data Konsultan [DEMO]', 'Cibinong, Bogor'],
            ['PT Awan Cerdas Sistem [DEMO]', 'Denpasar Barat, Denpasar'],
        ];

        $customers = [];

        foreach ($records as $index => [$name, $address]) {
            $number = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
            $email = "demo.customer.{$number}@example.test";

            $customers[$index + 1] = Customer::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'tax_id_type' => 'OTHER',
                    'tax_id_number' => null,
                    'nitku' => '0000000000000000000000',
                    'default_tax_transaction_code' => '040',
                    'phone' => null,
                    'address' => $address,
                ]
            );
        }

        return $customers;
    }

    /**
     * @param  array<int, Customer>  $customers
     * @return array<string, array{document: Document, revenue_category: string}>
     */
    private function seedInvoices(array $customers): array
    {
        $records = [
            ['customer' => 1, 'date' => '2026-04-08', 'status' => 'PAID', 'revenue_code' => '4-100', 'items' => [
                ['description' => 'Audit kesiapan infrastruktur server', 'qty' => 1, 'unit' => 'Paket', 'price' => 18500000],
                ['description' => 'Dokumentasi arsitektur jaringan', 'qty' => 1, 'unit' => 'Paket', 'price' => 7500000],
            ]],
            ['customer' => 2, 'date' => '2026-05-05', 'status' => 'PAID', 'revenue_code' => '4-120', 'items' => [
                ['description' => 'Pengadaan switch jaringan managed', 'qty' => 3, 'unit' => 'Unit', 'price' => 8250000],
                ['description' => 'Konfigurasi VLAN dan failover', 'qty' => 1, 'unit' => 'Paket', 'price' => 6500000],
            ]],
            ['customer' => 3, 'date' => '2026-05-28', 'status' => 'SENT', 'revenue_code' => '4-100', 'items' => [
                ['description' => 'Jasa migrasi data ke cloud', 'qty' => 2, 'unit' => 'Paket', 'price' => 12500000],
            ]],
            ['customer' => 4, 'date' => '2026-06-10', 'status' => 'PAID', 'revenue_code' => '4-110', 'items' => [
                ['description' => 'Pemeliharaan sistem ERP', 'qty' => 3, 'unit' => 'Bulan', 'price' => 5500000],
                ['description' => 'Dukungan teknis dan pembaruan minor', 'qty' => 1, 'unit' => 'Paket', 'price' => 3000000],
            ]],
            ['customer' => 5, 'date' => '2026-07-03', 'status' => 'SENT', 'revenue_code' => '4-120', 'items' => [
                ['description' => 'Pengadaan server rack', 'qty' => 2, 'unit' => 'Unit', 'price' => 24500000],
                ['description' => 'Instalasi dan konfigurasi server', 'qty' => 1, 'unit' => 'Paket', 'price' => 9500000],
            ]],
            ['customer' => 6, 'date' => '2026-07-22', 'status' => 'PAID', 'revenue_code' => '4-100', 'items' => [
                ['description' => 'Implementasi backup dan disaster recovery', 'qty' => 1, 'unit' => 'Paket', 'price' => 32000000],
                ['description' => 'Simulasi pemulihan data', 'qty' => 1, 'unit' => 'Paket', 'price' => 8500000],
            ]],
            ['customer' => 7, 'date' => '2026-08-15', 'status' => 'SENT', 'revenue_code' => '4-120', 'items' => [
                ['description' => 'Pengadaan access point kantor', 'qty' => 8, 'unit' => 'Unit', 'price' => 2150000],
                ['description' => 'Pemasangan dan pengujian jaringan Wi-Fi', 'qty' => 1, 'unit' => 'Paket', 'price' => 6800000],
            ]],
            ['customer' => 8, 'date' => '2026-09-08', 'status' => 'SENT', 'revenue_code' => '4-110', 'items' => [
                ['description' => 'Kontrak helpdesk dan managed service', 'qty' => 3, 'unit' => 'Bulan', 'price' => 6250000],
            ]],
            ['customer' => 9, 'date' => '2026-09-24', 'status' => 'DRAFT', 'revenue_code' => '4-100', 'items' => [
                ['description' => 'Upgrade storage dan konfigurasi sistem', 'qty' => 1, 'unit' => 'Paket', 'price' => 42000000],
            ]],
            ['customer' => 10, 'date' => '2026-10-12', 'status' => 'DRAFT', 'revenue_code' => '4-100', 'items' => [
                ['description' => 'Integrasi API sistem logistik', 'qty' => 1, 'unit' => 'Paket', 'price' => 27500000],
            ]],
        ];

        $setting = Setting::query()->first();
        $taxRate = (float) ($setting?->default_tax_rate ?? 11);
        $applyTax = (bool) ($setting?->enable_tax ?? true);
        $documents = [];

        foreach ($records as $index => $record) {
            $documentNumber = sprintf('INV-DEMO/2026/%03d', $index + 1);
            $subtotal = array_sum(array_map(
                fn (array $item): float => $item['qty'] * $item['price'],
                $record['items']
            ));
            $total = $applyTax
                ? $subtotal * (1 + ($taxRate / 100))
                : $subtotal;

            $document = Document::updateOrCreate(
                ['document_number' => $documentNumber],
                [
                    'customer_id' => $customers[$record['customer']]->id,
                    'project_id' => null,
                    'type' => 'INVOICE',
                    'date' => $record['date'],
                    'status' => $record['status'],
                    'total_amount' => $total,
                    'discount' => 0,
                    'tax_transaction_code' => '040',
                    'tax_invoice_date' => $record['date'],
                    'payment_note' => 'Data DEMO untuk pengujian alur penagihan.',
                ]
            );

            foreach ($record['items'] as $item) {
                DocumentItem::updateOrCreate(
                    [
                        'document_id' => $document->id,
                        'description' => $item['description'],
                    ],
                    [
                        'notes' => 'Item simulasi layanan dan pengadaan TI.',
                        'qty' => $item['qty'],
                        'unit' => $item['unit'],
                        'price' => $item['price'],
                        'subtotal' => $item['qty'] * $item['price'],
                    ]
                );
            }

            $documents[$documentNumber] = [
                'document' => $document,
                'revenue_category' => $record['revenue_code'],
            ];
        }

        return $documents;
    }

    /**
     * @param  array<string, Category>  $categories
     * @param  array<string, array{document: Document, revenue_category: string}>  $documents
     */
    private function seedTransactions(array $categories, array $documents): void
    {
        $paymentDates = [
            'INV-DEMO/2026/001' => '2026-04-28',
            'INV-DEMO/2026/002' => '2026-05-20',
            'INV-DEMO/2026/004' => '2026-06-25',
            'INV-DEMO/2026/006' => '2026-08-05',
        ];

        $incomeNumber = 1;

        foreach ($paymentDates as $documentNumber => $paymentDate) {
            $seededDocument = $documents[$documentNumber];
            $document = $seededDocument['document'];

            Transaction::updateOrCreate(
                ['transaction_number' => sprintf('DEMO-CASH-IN-2026-%04d', $incomeNumber++)],
                [
                    'type' => 'income',
                    'category_id' => $categories[$seededDocument['revenue_category']]->id,
                    'invoice_id' => $document->id,
                    'project_id' => null,
                    'amount' => $document->total_amount,
                    'transaction_date' => $paymentDate,
                    'payment_method' => 'Bank Transfer',
                    'description' => "Pelunasan {$documentNumber} - {$document->customer->name}",
                ]
            );
        }

        $expenses = [
            ['date' => '2026-04-05', 'code' => '6-101', 'amount' => 1350000, 'method' => 'Bank Transfer', 'description' => 'Langganan internet kantor April 2026'],
            ['date' => '2026-04-25', 'code' => '6-101', 'amount' => 1800000, 'method' => 'Bank Transfer', 'description' => 'Layanan cloud untuk lingkungan produksi April 2026'],
            ['date' => '2026-05-12', 'code' => '5-101', 'amount' => 18000000, 'method' => 'Bank Transfer', 'description' => 'Pembelian perangkat jaringan untuk pekerjaan pelanggan'],
            ['date' => '2026-05-30', 'code' => '6-201', 'amount' => 1150000, 'method' => 'Cash', 'description' => 'Transportasi survei lokasi pelanggan di Bandung'],
            ['date' => '2026-06-15', 'code' => '5-102', 'amount' => 6750000, 'method' => 'Bank Transfer', 'description' => 'Pembayaran subkontraktor penarikan kabel jaringan'],
            ['date' => '2026-07-05', 'code' => '6-101', 'amount' => 2100000, 'method' => 'Bank Transfer', 'description' => 'Langganan cloud dan pemantauan server Juli 2026'],
            ['date' => '2026-08-18', 'code' => '6-401', 'amount' => 485000, 'method' => 'Cash', 'description' => 'Perlengkapan administrasi dan label aset'],
            ['date' => '2026-09-21', 'code' => '6-501', 'amount' => 95000, 'method' => 'Bank Transfer', 'description' => 'Biaya administrasi transfer vendor'],
            ['date' => '2026-10-03', 'code' => '6-201', 'amount' => 750000, 'method' => 'e-Wallet', 'description' => 'Transportasi teknisi untuk kunjungan pemeliharaan'],
        ];

        foreach ($expenses as $index => $expense) {
            Transaction::updateOrCreate(
                ['transaction_number' => sprintf('DEMO-CASH-OUT-2026-%04d', $index + 1)],
                [
                    'type' => 'expense',
                    'category_id' => $categories[$expense['code']]->id,
                    'invoice_id' => null,
                    'project_id' => null,
                    'amount' => $expense['amount'],
                    'transaction_date' => $expense['date'],
                    'payment_method' => $expense['method'],
                    'description' => $expense['description'],
                ]
            );
        }
    }
}
