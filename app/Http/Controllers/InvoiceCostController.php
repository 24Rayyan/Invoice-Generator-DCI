<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttachInvoiceExpensesRequest;
use App\Models\Document;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;

class InvoiceCostController extends Controller
{
    /**
     * Hubungkan beberapa kas keluar yang belum teralokasi ke invoice ini.
     */
    public function attach(AttachInvoiceExpensesRequest $request, Document $document): RedirectResponse
    {
        abort_unless($document->type === 'INVOICE', 404);

        $updated = Transaction::query()
            ->whereIn('id', $request->validated('transaction_ids'))
            ->where('type', 'expense')
            ->whereNull('invoice_id')
            ->update(['invoice_id' => $document->id]);

        if ($updated === 0) {
            return back()->with('error', 'Tidak ada transaksi kas keluar yang dapat dihubungkan. Pastikan item belum terikat invoice lain.');
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
