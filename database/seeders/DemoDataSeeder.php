<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\EntertainmentDetail;
use App\Models\Project;
use App\Models\Transaction;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(CategorySeeder::class);

        $customers = $this->seedCustomers();
        $projects = $this->seedProjects($customers);
        $invoices = $this->seedDocuments($customers, $projects);

        $this->seedTransactions($invoices, $projects);

        $this->command?->info(sprintf(
            'Demo data siap: %d customer, %d project, %d dokumen, %d transaksi kas.',
            count($customers),
            count($projects),
            count($invoices),
            Transaction::where('transaction_number', 'LIKE', 'DEMO-CASH-%')->count()
        ));
    }

    /**
     * @return array<string, Customer>
     */
    private function seedCustomers(): array
    {
        $rows = [
            ['name' => 'PT Garuda Teknologi Nusantara', 'email' => 'procurement@garudateknologi.example', 'phone' => '0215550101', 'address' => 'Jl. Jenderal Sudirman No. 10, Jakarta', 'tax_id_number' => '3174010101900001'],
            ['name' => 'CV Sinar Data Mandiri', 'email' => 'finance@sinardata.example', 'phone' => '0225550102', 'address' => 'Jl. Asia Afrika No. 25, Bandung', 'tax_id_number' => '3273010202900002'],
            ['name' => 'PT Karya Infrastruktur Digital', 'email' => 'admin@karyainfra.example', 'phone' => '0315550103', 'address' => 'Jl. Basuki Rahmat No. 18, Surabaya', 'tax_id_number' => '3578010303900003'],
            ['name' => 'PT Cakrawala Niaga Sejahtera', 'email' => 'ap@cakrawalaniaga.example', 'phone' => '0274550104', 'address' => 'Jl. Malioboro No. 45, Yogyakarta', 'tax_id_number' => '3471010404900004'],
            ['name' => 'CV Arunika Solusi Bisnis', 'email' => 'billing@arunikasolusi.example', 'phone' => '0245550105', 'address' => 'Jl. Pandanaran No. 30, Semarang', 'tax_id_number' => '3374010505900005'],
            ['name' => 'PT Lentera Komputasi Indonesia', 'email' => 'finance@lenterakomputasi.example', 'phone' => '0615550106', 'address' => 'Jl. Gatot Subroto No. 72, Medan', 'tax_id_number' => '1271010606900006'],
        ];

        $customers = [];
        foreach ($rows as $row) {
            $customers[$row['email']] = Customer::updateOrCreate(
                ['email' => $row['email']],
                $row + [
                    'tax_id_type' => 'NPWP16',
                    'nitku' => '0000000000000000000000',
                    'default_tax_transaction_code' => '040',
                ]
            );
        }

        return $customers;
    }

    /**
     * @param  array<string, Customer>  $customers
     * @return array<string, Project>
     */
    private function seedProjects(array $customers): array
    {
        $rows = [
            ['project_name' => 'Implementasi ERP Cabang Jakarta', 'client_email' => 'procurement@garudateknologi.example', 'status' => 'active'],
            ['project_name' => 'Portal Layanan Pelanggan', 'client_email' => 'finance@sinardata.example', 'status' => 'active'],
            ['project_name' => 'Infrastruktur Jaringan Surabaya', 'client_email' => 'admin@karyainfra.example', 'status' => 'completed'],
            ['project_name' => 'Migrasi Data dan Pelatihan', 'client_email' => 'ap@cakrawalaniaga.example', 'status' => 'on_hold'],
        ];

        $projects = [];
        foreach ($rows as $row) {
            $projects[$row['project_name']] = Project::updateOrCreate(
                ['project_name' => $row['project_name']],
                [
                    'client_id' => $customers[$row['client_email']]->id,
                    'status' => $row['status'],
                ]
            );
        }

        return $projects;
    }

    /**
     * @param  array<string, Customer>  $customers
     * @param  array<string, Project>  $projects
     * @return array<string, Document>
     */
    private function seedDocuments(array $customers, array $projects): array
    {
        $rows = [
            ['number' => 'INV-DEMO/001/2026', 'customer' => 'procurement@garudateknologi.example', 'project' => 'Implementasi ERP Cabang Jakarta', 'status' => 'PAID', 'days_ago' => 170, 'items' => [['Analisis proses dan konfigurasi ERP', 1, 'Paket', 18000000], ['Implementasi modul keuangan dan persediaan', 1, 'Paket', 32000000]]],
            ['number' => 'INV-DEMO/002/2026', 'customer' => 'procurement@garudateknologi.example', 'project' => 'Implementasi ERP Cabang Jakarta', 'status' => 'PAID', 'days_ago' => 125, 'items' => [['Migrasi data dan rekonsiliasi saldo', 1, 'Paket', 12500000], ['Pelatihan pengguna cabang', 3, 'Sesi', 2500000]]],
            ['number' => 'INV-DEMO/003/2026', 'customer' => 'procurement@garudateknologi.example', 'project' => 'Implementasi ERP Cabang Jakarta', 'status' => 'SENT', 'days_ago' => 45, 'items' => [['Dukungan pasca implementasi', 1, 'Paket', 8500000], ['Pemeliharaan sistem', 1, 'Tahun', 6500000]]],
            ['number' => 'INV-DEMO/004/2026', 'customer' => 'finance@sinardata.example', 'project' => 'Portal Layanan Pelanggan', 'status' => 'PAID', 'days_ago' => 110, 'items' => [['Desain UI/UX portal pelanggan', 1, 'Paket', 12000000], ['Pengembangan portal web', 1, 'Paket', 28000000]]],
            ['number' => 'INV-DEMO/005/2026', 'customer' => 'finance@sinardata.example', 'project' => 'Portal Layanan Pelanggan', 'status' => 'DRAFT', 'days_ago' => 12, 'items' => [['Integrasi notifikasi dan email', 1, 'Paket', 7500000], ['Pengujian keamanan aplikasi', 1, 'Paket', 4500000]]],
            ['number' => 'INV-DEMO/006/2026', 'customer' => 'admin@karyainfra.example', 'project' => 'Infrastruktur Jaringan Surabaya', 'status' => 'PAID', 'days_ago' => 210, 'items' => [['Perangkat switch managed 48 port', 4, 'Unit', 8500000], ['Instalasi dan konfigurasi jaringan', 1, 'Paket', 16000000]]],
            ['number' => 'INV-DEMO/007/2026', 'customer' => 'ap@cakrawalaniaga.example', 'project' => 'Migrasi Data dan Pelatihan', 'status' => 'SENT', 'days_ago' => 28, 'items' => [['Audit dan pembersihan data', 1, 'Paket', 9500000], ['Pelatihan admin aplikasi', 2, 'Sesi', 3000000]]],
            ['number' => 'INV-DEMO/008/2026', 'customer' => 'billing@arunikasolusi.example', 'project' => null, 'status' => 'PAID', 'days_ago' => 75, 'items' => [['Konsultasi proses bisnis', 8, 'Jam', 750000], ['Dokumentasi kebutuhan sistem', 1, 'Paket', 6000000]]],
            ['number' => 'INV-DEMO/009/2026', 'customer' => 'finance@lenterakomputasi.example', 'project' => null, 'status' => 'SENT', 'days_ago' => 18, 'items' => [['Jasa perbaikan server', 2, 'Unit', 4500000], ['Konfigurasi pencadangan data', 1, 'Paket', 3500000]]],
            ['number' => 'INV-DEMO/010/2026', 'customer' => 'billing@arunikasolusi.example', 'project' => null, 'status' => 'DRAFT', 'days_ago' => 5, 'items' => [['Pembuatan dashboard penjualan', 1, 'Paket', 10500000]]],
        ];

        $documents = [];
        foreach ($rows as $row) {
            $documentDate = now()->subDays($row['days_ago'])->toDateString();
            $totalAmount = collect($row['items'])->sum(fn ($item) => $item[1] * $item[3]);
            $document = Document::updateOrCreate(
                ['document_number' => $row['number']],
                [
                    'customer_id' => $customers[$row['customer']]->id,
                    'project_id' => $row['project'] ? $projects[$row['project']]->id : null,
                    'type' => 'INVOICE',
                    'date' => $documentDate,
                    'status' => $row['status'],
                    'total_amount' => $totalAmount,
                    'discount' => 0,
                    'tax_transaction_code' => '040',
                    'tax_invoice_date' => $documentDate,
                ]
            );

            foreach ($row['items'] as [$description, $qty, $unit, $price]) {
                DocumentItem::updateOrCreate(
                    ['document_id' => $document->id, 'description' => $description],
                    [
                        'notes' => 'Data demonstrasi',
                        'qty' => $qty,
                        'unit' => $unit,
                        'price' => $price,
                        'subtotal' => $qty * $price,
                    ]
                );
            }

            $documents[$row['number']] = $document;
        }

        return $documents;
    }

    /**
     * @param  array<string, Document>  $invoices
     * @param  array<string, Project>  $projects
     */
    private function seedTransactions(array $invoices, array $projects): void
    {
        $incomeCategory = Category::where('code', '4-100')->firstOrFail();
        $expenseRows = [
            ['category' => '5-105', 'amount' => 2800000, 'days_ago' => 168, 'description' => 'Transportasi tim implementasi dan kunjungan cabang', 'project' => 'Implementasi ERP Cabang Jakarta', 'invoice' => 'INV-DEMO/001/2026'],
            ['category' => '5-109', 'amount' => 4200000, 'days_ago' => 155, 'description' => 'Jasa konfigurasi dan integrasi ERP', 'project' => 'Implementasi ERP Cabang Jakarta', 'invoice' => 'INV-DEMO/001/2026'],
            ['category' => '5-108', 'amount' => 850000, 'days_ago' => 123, 'description' => 'Konsumsi pelatihan pengguna ERP', 'project' => 'Implementasi ERP Cabang Jakarta', 'invoice' => 'INV-DEMO/002/2026'],
            ['category' => '5-110', 'amount' => 1250000, 'days_ago' => 205, 'description' => 'Pengiriman perangkat jaringan ke Surabaya', 'project' => 'Infrastruktur Jaringan Surabaya', 'invoice' => 'INV-DEMO/006/2026'],
            ['category' => '5-107', 'amount' => 3600000, 'days_ago' => 202, 'description' => 'Instalasi rack dan penataan kabel jaringan', 'project' => 'Infrastruktur Jaringan Surabaya', 'invoice' => 'INV-DEMO/006/2026'],
            ['category' => '5-201', 'amount' => 1450000, 'days_ago' => 93, 'description' => 'Jamuan diskusi kebutuhan portal pelanggan', 'project' => 'Portal Layanan Pelanggan', 'invoice' => 'INV-DEMO/004/2026'],
            ['category' => '5-106', 'amount' => 2200000, 'days_ago' => 34, 'description' => 'Promosi layanan implementasi digital', 'project' => null, 'invoice' => null],
            ['category' => '5-102', 'amount' => 15000000, 'days_ago' => 30, 'description' => 'Sewa kantor bulan berjalan', 'project' => null, 'invoice' => null],
            ['category' => '5-103', 'amount' => 2750000, 'days_ago' => 21, 'description' => 'Tagihan listrik dan internet kantor', 'project' => null, 'invoice' => null],
            ['category' => '5-104', 'amount' => 675000, 'days_ago' => 14, 'description' => 'Pembelian alat tulis dan perlengkapan kantor', 'project' => null, 'invoice' => null],
            ['category' => '5-101', 'amount' => 38500000, 'days_ago' => 7, 'description' => 'Gaji dan tunjangan karyawan', 'project' => null, 'invoice' => null],
            ['category' => '6-102', 'amount' => 350000, 'days_ago' => 9, 'description' => 'Denda administrasi demonstrasi non-deductible', 'project' => null, 'invoice' => null],
        ];

        foreach ($invoices as $number => $invoice) {
            if ($invoice->status !== 'PAID') {
                continue;
            }

            Transaction::updateOrCreate(
                ['transaction_number' => 'DEMO-CASH-IN-'.str_pad((string) $invoice->id, 4, '0', STR_PAD_LEFT)],
                [
                    'type' => 'income',
                    'category_id' => $incomeCategory->id,
                    'invoice_id' => $invoice->id,
                    'project_id' => $invoice->project_id,
                    'amount' => $invoice->total_amount,
                    'transaction_date' => $invoice->date,
                    'payment_method' => 'Bank Transfer',
                    'description' => "Pelunasan invoice {$number}",
                ]
            );
        }

        foreach ($expenseRows as $index => $row) {
            $invoice = $row['invoice'] ? $invoices[$row['invoice']] : null;

            $transaction = Transaction::updateOrCreate(
                ['transaction_number' => 'DEMO-CASH-OUT-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT)],
                [
                    'type' => 'expense',
                    'category_id' => Category::where('code', $row['category'])->firstOrFail()->id,
                    'invoice_id' => $invoice?->id,
                    'project_id' => $row['project'] ? $projects[$row['project']]->id : null,
                    'amount' => $row['amount'],
                    'transaction_date' => now()->subDays($row['days_ago'])->toDateString(),
                    'payment_method' => $index % 2 === 0 ? 'Bank Transfer' : 'Cash',
                    'description' => $row['description'],
                ]
            );

            if ($row['category'] === '5-201') {
                EntertainmentDetail::updateOrCreate(
                    ['transaction_id' => $transaction->id],
                    [
                        'event_date' => $transaction->transaction_date,
                        'location' => 'Ruang rapat demo, Jakarta',
                        'attendee_name' => 'Raka Pratama',
                        'attendee_company' => 'CV Sinar Data Mandiri',
                        'attendee_position' => 'Manajer Operasional',
                        'purpose' => 'Diskusi kebutuhan dan rencana implementasi layanan digital.',
                    ]
                );
            }
        }

        Transaction::updateOrCreate(
            ['transaction_number' => 'DEMO-CASH-IN-MISC-001'],
            [
                'type' => 'income',
                'category_id' => Category::where('code', '4-900')->firstOrFail()->id,
                'invoice_id' => null,
                'project_id' => null,
                'amount' => 185000,
                'transaction_date' => now()->subDays(3)->toDateString(),
                'payment_method' => 'Bank Transfer',
                'description' => 'Jasa giro bank bulan berjalan',
            ]
        );
    }
}
