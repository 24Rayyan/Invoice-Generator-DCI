<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Customer;
use App\Models\DocumentItem;
use App\Models\Setting;
use App\Models\Project;
use App\Models\Transaction;
use App\Services\TransactionService;
use App\Services\ProjectMarginService;
use App\Services\CoretaxXmlService;
use App\Exports\CoretaxFkExport;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DocumentController extends Controller
{
    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    // METHOD BARU: Untuk Halaman Super Dashboard Mini ERP
    public function dashboard()
    {
        $setting = Setting::getSetting();

        // 1. Total Omset (Invoice Lunas / PAID)
        $totalOmset = Document::where('type', 'INVOICE')
                              ->where('status', 'PAID')
                              ->sum('total_amount');

        // 2. Total Piutang (Invoice Terkirim Belum Dibayar / SENT)
        $totalPiutang = Document::where('type', 'INVOICE')
                                ->where('status', 'SENT')
                                ->sum('total_amount');

        // 3. Total Pengeluaran Kas (Expenses)
        $totalExpense = Transaction::where('type', 'expense')->sum('amount');

        // 4. Laba Bersih Komersial
        $totalIncome = Transaction::where('type', 'income')->sum('amount');
        $netProfit = $totalIncome - $totalExpense;

        // 5. Dokumen terbaru untuk ringkasan dashboard
        $recentDocuments = Document::with('customer')
            ->latest()
            ->take(4)
            ->get();

        // 6. Pengeluaran kas terbaru untuk ringkasan dashboard
        $recentTransactions = Transaction::with('category')
            ->where('type', 'expense')
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->take(5)
            ->get();

        return view('dashboard', compact(
            'setting',
            'totalOmset', 
            'totalPiutang', 
            'totalExpense',
            'netProfit',
            'recentDocuments',
            'recentTransactions'
        ));
    }

    // Helper privat konversi bulan ke Romawi
    private function getRomanMonth($month)
    {
        $romans = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];
        return $romans[(int)$month] ?? 'I';
    }

    // Helper Auto-Generate Nomor Dokumen
    private function generateDocumentNumber($type = 'INVOICE')
    {
        $setting = Setting::getSetting();
        $currentMonth = date('n'); // 1 - 12
        $currentYear = date('Y');  // Tahun Berjalan (2026)
        $romanMonth = $this->getRomanMonth($currentMonth);
        
        $prefix = ($type == 'PO') ? ($setting->po_prefix ?: 'PO') : ($setting->invoice_prefix ?: 'INV');
        $searchPattern = "%/{$prefix}/{$romanMonth}/{$currentYear}";

        $lastDoc = Document::where('document_number', 'LIKE', $searchPattern)
                           ->orderBy('id', 'desc')
                           ->first();

        if ($lastDoc) {
            $parts = explode('/', $lastDoc->document_number);
            $lastNumber = intval($parts[0]);
            $nextNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '001';
        }

        return "{$nextNumber}/{$prefix}/{$romanMonth}/{$currentYear}";
    }

    private function generateAuxiliaryDocumentNumber(string $column, string $prefix, string $date): string
    {
        $documentDate = Carbon::parse($date);
        $romanMonth = $this->getRomanMonth($documentDate->month);
        $searchPattern = "%/{$prefix}/{$romanMonth}/{$documentDate->year}";

        $lastDocument = Document::where($column, 'LIKE', $searchPattern)
            ->orderByDesc('id')
            ->first([$column]);

        $lastNumber = $lastDocument ? (int) explode('/', $lastDocument->{$column})[0] : 0;

        return str_pad((string) ($lastNumber + 1), 3, '0', STR_PAD_LEFT)
            . "/{$prefix}/{$romanMonth}/{$documentDate->year}";
    }

public function index(Request $request)
    {
        $setting = Setting::first(); // Sesuaikan jika menggunakan helper custom

        $query = Document::with(['customer', 'items'])->latest('date');

        // 1. Filter Berdasarkan Periode (YYYY-MM)
        if ($request->filled('period')) {
            $parts = explode('-', $request->period);
            if (count($parts) === 2) {
                $year = $parts[0];
                $month = sprintf('%02d', $parts[1]);

                $driver = DB::connection()->getDriverName();

                if ($driver === 'sqlite') {
                    $query->whereRaw("strftime('%Y', date) = ?", [$year])
                          ->whereRaw("strftime('%m', date) = ?", [$month]);
                } else {
                    $query->whereYear('date', $year)
                          ->whereMonth('date', $month);
                }
            }
        }

        // 2. Filter Berdasarkan Status (DRAFT, SENT, PAID)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $documents = $query->get();

        // 3. Mengambil Option Daftar Bulan Unik
        $availableMonths = Document::query()
            ->whereNotNull('date')
            ->select('date')
            ->orderBy('date', 'desc')
            ->get()
            ->map(function ($doc) {
                $date = $doc->date instanceof Carbon ? $doc->date : Carbon::parse($doc->date);
                return (object) [
                    'year'  => $date->format('Y'),
                    'month' => $date->format('m'),
                    'value' => $date->format('Y-m'),
                    'label' => $date->translatedFormat('F Y'),
                ];
            })
            ->unique('value')
            ->values();

        return view('documents.index', compact('documents', 'availableMonths', 'setting'));
    }

    public function show(Document $document)
    {
        $document->load(['customer', 'project', 'items']);
        $setting = Setting::getSetting();
        $canViewInvoiceMargin = $document->type === 'INVOICE'
            && auth()->user()->hasAccess('invoice_margin');
        if ($canViewInvoiceMargin) {
            $document->load('directExpenses.category');
        }

        $margin = $canViewInvoiceMargin
            ? app(ProjectMarginService::class)->forInvoice($document)
            : null;
        $unallocatedExpenses = $canViewInvoiceMargin
            ? Transaction::with('category')
                ->expense()
                ->unallocated()
                ->orderByDesc('transaction_date')
                ->orderByDesc('id')
                ->get()
            : collect();

        return view('documents.show', compact('document', 'setting', 'margin', 'unallocatedExpenses', 'canViewInvoiceMargin'));
    }

    public function create()
    {
        $setting = Setting::getSetting();
        $customers = Customer::all();
        $projects = auth()->user()->hasAccess('projects')
            ? Project::activeForSelection()
            : collect();
        $autoNumber = $this->generateDocumentNumber('INVOICE');

        return view('documents.create', compact('customers', 'projects', 'autoNumber', 'setting'));
    }

    public function store(Request $request)
    {
        $canManageProjects = auth()->user()->hasAccess('projects');
        $request->validate([
            'customer_id'          => 'required',
            'project_id'           => $canManageProjects ? 'nullable|integer|exists:projects,id' : 'prohibited',
            'type'                 => 'required',
            'document_number'      => 'required|unique:documents,document_number',
            'item_desc'            => 'required|array|min:1',
            'item_notes'           => 'nullable|array',
            'item_qty'             => 'required|array',
            'item_qty.*'           => 'required|numeric|min:0.01',
            'item_unit'            => 'required|array',
            'item_unit.*'          => 'required|string',
            'item_price'           => 'required|array',
            'item_price.*'         => 'required|numeric|min:0',
            'discount'             => 'nullable|numeric|min:0',
            'tax_transaction_code' => 'nullable|string|max:10',
            'tax_invoice_date'     => 'nullable|date',
            'payment_note'         => 'nullable|string',
        ]);

        $setting = Setting::getSetting();
        $customer = Customer::find($request->customer_id);

        $total_bruto = 0;
        $items_data = [];
        
        for ($i = 0; $i < count($request->item_desc); $i++) {
            $qty = $request->item_qty[$i];
            $price = $request->item_price[$i];
            $subtotal = $qty * $price;
            
            $total_bruto += $subtotal;

            $items_data[] = [
                'description' => $request->item_desc[$i],
                'notes'       => $request->item_notes[$i] ?? null,
                'qty'         => $qty,
                'unit'        => $request->item_unit[$i] ?? 'Pcs',
                'price'       => $price,
                'subtotal'    => $subtotal,
            ];
        }

        $discount = $request->discount ?? 0;
        $netto = $total_bruto - $discount;
        
        $taxRate = ($setting->enable_tax ?? true) ? (($setting->default_tax_rate ?? 11) / 100) : 0;
        $ppn = $netto * $taxRate;
        $total_amount_include_ppn = $netto + $ppn;

        $transactionCode = $request->tax_transaction_code 
            ?: ($customer?->default_tax_transaction_code ?: '040');

        $documentDate = date('Y-m-d');
        $documentData = [
            'customer_id'          => $request->customer_id,
            'project_id'           => $request->type === 'INVOICE' && $canManageProjects ? $request->project_id : null,
            'type'                 => $request->type,
            'document_number'      => $request->document_number,
            'date'                 => $documentDate,
            'total_amount'         => $total_amount_include_ppn,
            'discount'             => $discount,
            'tax_transaction_code' => $transactionCode,
            'tax_invoice_date'     => $request->tax_invoice_date ?? date('Y-m-d'),
            'payment_note'         => filled($request->payment_note)
                ? $request->payment_note
                : ($setting->receipt_footer_notes ?: null),
        ];
        if ($request->type === 'INVOICE') {
            $documentData['delivery_note_number'] = $this->generateAuxiliaryDocumentNumber(
                'delivery_note_number',
                $setting->delivery_note_prefix ?: 'SJ',
                $documentDate
            );
            $documentData['receipt_number'] = $this->generateAuxiliaryDocumentNumber(
                'receipt_number',
                $setting->kwitansi_prefix ?: 'KWT',
                $documentDate
            );
        }

        $document = Document::create($documentData);

        foreach ($items_data as $item) {
            DocumentItem::create(array_merge($item, ['document_id' => $document->id]));
        }

        return redirect()->route('documents.index')->with('success', 'Dokumen berhasil dibuat');
    }

    public function edit(Document $document)
    {
        // Jika status PAID, tolak akses edit dan redirect kembali
        if ($document->status === 'PAID') {
            return redirect()->route('documents.index')
                ->with('error', 'Dokumen berstatus PAID sudah terkunci dan tidak dapat diubah.');
        }

        // Ambil data customers untuk dropdown pilihan customer
        $customers = Customer::orderBy('name', 'asc')->get();
        $projects = auth()->user()->hasAccess('projects')
            ? Project::activeForSelection($document->project_id)
            : collect();

        // Pastikan relasi items ter-load jika dibutuhkan di form edit
        $document->load('items');

        return view('documents.edit', compact('document', 'customers', 'projects'));
    }

    public function update(Request $request, $id)
    {
        $document = Document::findOrFail($id);
        $canManageProjects = auth()->user()->hasAccess('projects');
        $request->validate([
            'customer_id'          => 'required',
            'project_id'           => $canManageProjects ? 'nullable|integer|exists:projects,id' : 'prohibited',
            'type'                 => 'required',
            'document_number'      => 'required|unique:documents,document_number,' . $id,
            'status'               => 'required',
            'item_desc'            => 'required|array|min:1',
            'item_notes'           => 'nullable|array',
            'item_qty'             => 'required|array',
            'item_qty.*'           => 'required|numeric|min:0.01',
            'item_unit'            => 'required|array',
            'item_unit.*'          => 'required|string',
            'item_price'           => 'required|array',
            'item_price.*'         => 'required|numeric|min:0',
            'discount'             => 'nullable|numeric|min:0',
            'tax_transaction_code' => 'nullable|string|max:10',
            'tax_invoice_date'     => 'nullable|date',
            'payment_note'         => 'nullable|string',
        ]);

        $setting = Setting::getSetting();
        $total_bruto = 0;
        
        $document->items()->delete();

        for ($i = 0; $i < count($request->item_desc); $i++) {
            $qty = $request->item_qty[$i];
            $price = $request->item_price[$i];
            $subtotal = $qty * $price;
            
            $total_bruto += $subtotal;

            DocumentItem::create([
                'document_id' => $document->id,
                'description' => $request->item_desc[$i],
                'notes'       => $request->item_notes[$i] ?? null,
                'qty'         => $qty,
                'unit'        => $request->item_unit[$i] ?? 'Pcs',
                'price'       => $price,
                'subtotal'    => $subtotal,
            ]);
        }

        $discount = $request->discount ?? 0;
        $netto = $total_bruto - $discount;
        
        $taxRate = ($setting->enable_tax ?? true) ? (($setting->default_tax_rate ?? 11) / 100) : 0;
        $ppn = $netto * $taxRate;
        $total_amount_include_ppn = $netto + $ppn;

        $documentData = [
            'customer_id'          => $request->customer_id,
            'project_id'           => $request->type === 'INVOICE'
                ? ($canManageProjects ? $request->project_id : $document->project_id)
                : null,
            'type'                 => $request->type,
            'document_number'      => $request->document_number,
            'status'               => $request->status,
            'total_amount'         => $total_amount_include_ppn,
            'discount'             => $discount,
            'tax_transaction_code' => $request->tax_transaction_code ?? $document->tax_transaction_code ?? '040',
            'tax_invoice_date'     => $request->tax_invoice_date ?? $document->tax_invoice_date ?? $document->date,
            'payment_note'         => filled($request->payment_note)
                ? $request->payment_note
                : ($setting->receipt_footer_notes ?: null),
        ];
        if ($request->type === 'INVOICE') {
            $documentData['delivery_note_number'] = $document->delivery_note_number
                ?: $this->generateAuxiliaryDocumentNumber('delivery_note_number', $setting->delivery_note_prefix ?: 'SJ', $document->date);
            $documentData['receipt_number'] = $document->receipt_number
                ?: $this->generateAuxiliaryDocumentNumber('receipt_number', $setting->kwitansi_prefix ?: 'KWT', $document->date);
        }
        $document->update($documentData);

        // Auto-sync transaksi kas jika status berubah menjadi PAID atau sebaliknya
        $this->transactionService->syncInvoicePaidTransaction($document);

        return redirect()->route('documents.index')->with('success', 'Dokumen berhasil diperbarui');
    }

    // Ubah Status Cepat via AJAX
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:DRAFT,SENT,PAID'
        ]);

        $document = Document::with('customer')->findOrFail($id);
        $document->update([
            'status' => $request->status
        ]);

        // Auto-sync transaksi kas jika status PAID
        $this->transactionService->syncInvoicePaidTransaction($document);

        return response()->json([
            'success' => true,
            'message' => 'Status dokumen ' . $document->document_number . ' berhasil diubah menjadi ' . $request->status
        ]);
    }

    // Duplicate / Copy Dokumen
    public function duplicate($id)
    {
        $setting = Setting::getSetting();
        $sourceDoc = Document::with('items')->findOrFail($id);
        $customers = Customer::all();
        
        $autoNumber = $this->generateDocumentNumber($sourceDoc->type);

        return view('documents.create_duplicate', compact('sourceDoc', 'customers', 'autoNumber', 'setting'));
    }

    public function destroy($id)
    {
        $document = Document::findOrFail($id);
        
        // Hapus transaksi kas terkait jika ada
        if ($document->transaction) {
            $this->transactionService->deleteTransaction($document->transaction);
        }

        $document->delete();
        
        return redirect()->route('documents.index')->with('success', 'Dokumen berhasil dihapus');
    }

    // Export Faktur Pajak Keluaran (FK) ke XML Coretax DJP (v1.6.1)
    public function exportCoretaxXml(Request $request, CoretaxXmlService $xmlService)
    {
        $request->validate([
            'document_ids'     => 'required|array|min:1',
            'document_ids.*'   => 'exists:documents,id',
            'tax_invoice_date' => 'nullable|date',
        ]);

        $setting = Setting::getSetting();
        $documents = Document::with(['customer', 'items'])
            ->whereIn('id', $request->document_ids)
            ->where('type', 'INVOICE')
            ->get();

        if ($documents->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada dokumen Invoice yang valid untuk diekspor ke Coretax.');
        }

        $customDate = $request->tax_invoice_date;
        $xmlContent = $xmlService->generateXml($documents, $setting, $customDate);
        $fileName = 'Coretax_FK_v1.6.1_' . date('Ymd_His') . '.xml';

        return response($xmlContent, 200, [
            'Content-Type'        => 'application/xml; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    // Alias method untuk exportXml sesuai spesifikasi requirement
    public function exportXml(Request $request, CoretaxXmlService $xmlService)
    {
        return $this->exportCoretaxXml($request, $xmlService);
    }

    // Export Faktur Pajak Keluaran (FK) - Mendukung XML v1.6.1 & Excel (.xlsx)
    public function exportCoretax(Request $request, CoretaxXmlService $xmlService)
    {
        $request->validate([
            'document_ids'     => 'required|array|min:1',
            'document_ids.*'   => 'exists:documents,id',
            'tax_invoice_date' => 'nullable|date',
            'export_type'      => 'nullable|string|in:xml,xlsx,excel',
        ]);

        // Jika user memilih format XML
        if ($request->input('export_type') === 'xml' || $request->input('format') === 'xml') {
            return $this->exportCoretaxXml($request, $xmlService);
        }

        $setting = Setting::getSetting();
        $documents = Document::with(['customer', 'items'])
            ->whereIn('id', $request->document_ids)
            ->where('type', 'INVOICE')
            ->get();

        if ($documents->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada dokumen Invoice yang valid untuk diekspor ke Coretax.');
        }

        $customDate = $request->tax_invoice_date;
        $fileName = 'Coretax_FK_Import_' . date('Ymd_His') . '.xlsx';

        return Excel::download(new CoretaxFkExport($documents, $setting, $customDate), $fileName);
    }

    // Download PDF Utama
    public function downloadPdf($id)
    {
        $document = Document::with(['customer', 'items'])->findOrFail($id);
        $setting = Setting::getSetting(); 
        
        if ($document->type == 'INVOICE') {
            $pdf = Pdf::loadView('exports.invoice_pdf', compact('document', 'setting'))
                      ->setPaper('a4', 'landscape');
        } else {
            $pdf = Pdf::loadView('exports.po_pdf', compact('document', 'setting'))
                      ->setPaper('a4', 'portrait');
        }
        
        $safeDocNumber = str_replace(['/', '\\'], '-', $document->document_number);
        $fileName = $document->type . '_' . $safeDocNumber . '.pdf';
        
        return $pdf->download($fileName);
    }

    // Download Surat Jalan PDF
    public function downloadDeliveryNote($id)
    {
        $document = Document::with(['customer', 'items'])->findOrFail($id);
        $setting = Setting::getSetting(); 
        
        $pdf = Pdf::loadView('exports.delivery_note_pdf', compact('document', 'setting'))
                  ->setPaper('a4', 'landscape');
        
        $pdfDocumentNumber = $document->delivery_note_number ?: $document->document_number;
        $safeDocNumber = str_replace(['/', '\\'], '-', $pdfDocumentNumber);
        $fileName = 'SJ_' . $safeDocNumber . '.pdf';
        
        return $pdf->download($fileName);
    }

    // Download Kwitansi PDF
    public function downloadKwitansi($id)
    {
        $document = Document::with(['customer', 'items'])->findOrFail($id);
        $setting = Setting::getSetting(); 
        
        $pdf = Pdf::loadView('exports.kwitansi_pdf', compact('document', 'setting'))
                  ->setPaper('a4', 'landscape');
        
        $pdfDocumentNumber = $document->receipt_number ?: $document->document_number;
        $safeDocNumber = str_replace(['/', '\\'], '-', $pdfDocumentNumber);
        $fileName = 'KWITANSI_' . $safeDocNumber . '.pdf';
        
        return $pdf->download($fileName);
    }
}