<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttachInvoiceExpensesRequest;
use App\Models\Document;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class InvoiceCostController extends Controller
{
    /**
     * Hubungkan beberapa kas keluar yang belum teralokasi ke invoice ini.
     */
    public function attach(AttachInvoiceExpensesRequest $request, Document $document): RedirectResponse
    {
        abort_unless($document->type === 'INVOICE', 404);

        $updated = DB::transaction(function () use ($request, $document) {
            $transactions = Transaction::query()
                ->whereIn('id', $request->validated('transaction_ids'))
                ->where('type', 'expense')
                ->whereNull('invoice_id')
                ->whereNull('project_id')
                ->lockForUpdate()
                ->get();

            if ($transactions->isEmpty()) {
                return 0;
            }

            return Transaction::query()
                ->whereIn('id', $transactions->modelKeys())
                ->where('type', 'expense')
                ->whereNull('invoice_id')
                ->whereNull('project_id')
                ->update(['invoice_id' => $document->id]);
        });

        if ($updated === 0) {
            return back()->with('error', 'Tidak ada transaksi kas keluar yang dapat dihubungkan. Pastikan item belum terikat invoice atau project.');
        }

        return back()->with('success', "{$updated} transaksi kas keluar berhasil dihubungkan ke invoice {$document->document_number}.");
    }

    /**
     * Lepas relasi satu transaksi kas dari invoice (invoice_id = NULL).
     */
    public function detach(Document $document, Transaction $transaction): RedirectResponse
    {
        abort_unless($document->type === 'INVOICE', 404);

        if ((int) $transaction->invoice_id !== (int) $document->id) {
            return back()->with('error', 'Transaksi ini tidak terhubung ke invoice tersebut.');
        }

        $transaction->update(['invoice_id' => null]);

        return back()->with('success', "Transaksi {$transaction->transaction_number} berhasil dilepas dari invoice.");
    }
};
