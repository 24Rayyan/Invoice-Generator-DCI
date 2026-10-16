<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionRequest;
use App\Models\Category;
use App\Models\Document;
use App\Models\Transaction;
use App\Services\TransactionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TransactionController extends Controller
{
    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'period'         => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'type'           => ['nullable', 'in:income,expense'],
            'category_id'    => ['nullable', 'integer', 'exists:categories,id'],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'search'         => ['nullable', 'string', 'max:150'],
            'per_page'       => ['nullable', 'integer', 'in:10,25,50,100'],
        ]);

        $query = Transaction::with(['category', 'document.customer', 'entertainmentDetail'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc');

        $period = $validated['period'] ?? '';
        $selectedYear = null;
        $selectedMonth = null;

        if ($request->exists('period')) {
            if ($period !== null && $period !== '') {
                [$selectedYear, $selectedMonth] = explode('-', $period);
                $query->whereYear('transaction_date', $selectedYear)
                    ->whereMonth('transaction_date', $selectedMonth);
            }
        } elseif ($request->filled('year') || $request->filled('month')) {
            $selectedYear = $request->filled('year') ? (int) $request->year : (int) date('Y');
            $selectedMonth = $request->filled('month') ? (int) $request->month : null;
            $query->whereYear('transaction_date', $selectedYear);
            if ($selectedMonth) {
                $query->whereMonth('transaction_date', $selectedMonth);
            }
        }

        // Filter Tipe (income / expense)
        if (! empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        // Filter Kategori
        if (! empty($validated['category_id'])) {
            $query->where('category_id', $validated['category_id']);
        }

        // Filter Metode Pembayaran
        if (! empty($validated['payment_method'])) {
            $query->where('payment_method', $validated['payment_method']);
        }

        // Search text
        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('transaction_number', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhereHas('category', function ($catQ) use ($search) {
                      $catQ->where('name', 'LIKE', "%{$search}%")
                           ->orWhere('code', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('document', function ($documentQuery) use ($search) {
                      $documentQuery->where('document_number', 'LIKE', "%{$search}%");
                  });
            });
        }

        $totalIncome = (clone $query)->where('type', 'income')->sum('amount');
        $totalExpense = (clone $query)->where('type', 'expense')->sum('amount');
        $incomeCount = (clone $query)->where('type', 'income')->count();
        $expenseCount = (clone $query)->where('type', 'expense')->count();
        $totalTransactions = (clone $query)->count();
        $netCashflow = $totalIncome - $totalExpense;
        $perPage = (int) ($validated['per_page'] ?? 10);
        $transactions = $query->paginate($perPage)->withQueryString();

        $categories = Category::orderBy('code', 'asc')->get();

        // Daftar Periode Unik untuk Filter
        $availableMonths = Transaction::select('transaction_date')
            ->whereNotNull('transaction_date')
            ->orderBy('transaction_date', 'desc')
            ->get()
            ->map(function ($trx) {
                $date = Carbon::parse($trx->transaction_date);
                return (object) [
                    'year'  => $date->format('Y'),
                    'month' => $date->format('m'),
                    'value' => $date->format('Y-m'),
                    'label' => $date->translatedFormat('F Y'),
                ];
            })
            ->unique('value');

        return view('transactions.index', compact(
            'transactions',
            'categories',
            'totalIncome',
            'totalExpense',
            'netCashflow',
            'incomeCount',
            'expenseCount',
            'totalTransactions',
            'selectedYear',
            'selectedMonth',
            'period',
            'perPage',
            'availableMonths'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $defaultType = $request->get('type', 'expense');
        $categories = Category::orderBy('code', 'asc')->get();
        $autoNumber = Transaction::generateTransactionNumber($defaultType, date('Y-m-d'));
        $invoices = Document::linkableInvoices();

        return view('transactions.create', compact('categories', 'defaultType', 'autoNumber', 'invoices'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TransactionRequest $request)
    {
        $invoiceId = $request->validated('invoice_id');

        $category = Category::findOrFail($request->category_id);

        // Jika kategori adalah 5-201 (Jamuan/Entertainment), data nominatif wajib diisi
        if ($category->code === '5-201' && empty($request->attendee_name)) {
            return back()->withInput()->withErrors([
                'attendee_name' => 'Kategori Biaya Entertainment (5-201) wajib melengkapi rincian Daftar Nominatif (Nama Relasi, Instansi, dsb) untuk keperluan SPT Tahunan.'
            ]);
        }

        $entertainmentData = null;
        if (!empty($request->attendee_name)) {
            $entertainmentData = [
                'event_date'        => $request->event_date ?? $request->transaction_date,
                'location'          => $request->location ?? '-',
                'attendee_name'     => $request->attendee_name,
                'attendee_company'  => $request->attendee_company ?? '-',
                'attendee_position' => $request->attendee_position ?? '-',
                'purpose'           => $request->purpose ?? 'Hubungan Bisnis / Entertainment Klien',
            ];
        }

        $transactionData = [
            'type'             => $request->type,
            'category_id'      => $request->category_id,
            'invoice_id'       => $invoiceId,
            'amount'           => $request->amount,
            'transaction_date' => $request->transaction_date,
            'payment_method'   => $request->payment_method,
            'description'      => $request->description,
        ];

        $this->transactionService->createTransaction(
            $transactionData,
            $request->file('receipt_file'),
            $entertainmentData
        );

        return redirect()->route('transactions.index')->with('success', 'Transaksi berhasil dicatat ke dalam jurnal keuangan.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Transaction $transaction)
    {
        $transaction->load(['category', 'entertainmentDetail', 'document']);
        $categories = Category::orderBy('code', 'asc')->get();
        $invoices = Document::linkableInvoices($transaction->invoice_id);

        return view('transactions.edit', compact('transaction', 'categories', 'invoices'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TransactionRequest $request, Transaction $transaction)
    {
        $invoiceId = $request->validated('invoice_id');

        $category = Category::findOrFail($request->category_id);

        if ($category->code === '5-201' && empty($request->attendee_name)) {
            return back()->withInput()->withErrors([
                'attendee_name' => 'Kategori Biaya Entertainment (5-201) wajib melengkapi rincian Daftar Nominatif (Nama Relasi, Instansi, dsb) untuk keperluan SPT Tahunan.'
            ]);
        }

        $entertainmentData = null;
        if (!empty($request->attendee_name)) {
            $entertainmentData = [
                'event_date'        => $request->event_date ?? $request->transaction_date,
                'location'          => $request->location ?? '-',
                'attendee_name'     => $request->attendee_name,
                'attendee_company'  => $request->attendee_company ?? '-',
                'attendee_position' => $request->attendee_position ?? '-',
                'purpose'           => $request->purpose ?? 'Hubungan Bisnis / Entertainment Klien',
            ];
        }

        $transactionData = [
            'type'             => $category->type,
            'category_id'      => $request->category_id,
            'invoice_id'       => $invoiceId,
            'amount'           => $request->amount,
            'transaction_date' => $request->transaction_date,
            'payment_method'   => $request->payment_method,
            'description'      => $request->description,
        ];

        $this->transactionService->updateTransaction(
            $transaction,
            $transactionData,
            $request->file('receipt_file'),
            $entertainmentData
        );

        return redirect()->route('transactions.index')->with('success', 'Data transaksi berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaction $transaction)
    {
        $this->transactionService->deleteTransaction($transaction);

        return redirect()->route('transactions.index')->with('success', 'Transaksi berhasil dihapus dari sistem.');
    }

    /**
     * Download or view receipt file
     */
    public function downloadReceipt(Transaction $transaction)
    {
        if (!$transaction->receipt_file_path || !Storage::disk('public')->exists($transaction->receipt_file_path)) {
            abort(404, 'Bukti nota/struk tidak ditemukan.');
        }

        return Storage::disk('public')->response($transaction->receipt_file_path);
    }

}
