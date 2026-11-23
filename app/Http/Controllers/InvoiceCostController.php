<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttachInvoiceExpensesRequest;
use App\Models\Document;
use App\Models\Transaction;
use App\Services\ActivityLogger;
use App\Models\ActivityLog;
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

        $transactions = collect();
        $updated = DB::transaction(function () use ($request, $document, &$transactions) {
            $transactions = Transaction::query()
                ->whereIn('id', $request->validated('transaction_ids'))
                ->where('type', 'expense')
                ->whereNull('invoice_id')
                ->whereNull('project_id')
                ->whereDoesntHave('projects')
                ->lockForUpdate()
                ->get();

            if ($transactions->isEmpty()) {
                return 0;
            }

            $ids = Transaction::query()
                ->whereIn('id', $transactions->modelKeys())
                ->where('type', 'expense')
                ->whereNull('invoice_id')
                ->whereNull('project_id')
                ->whereDoesntHave('projects')
                ->pluck('id');

            Transaction::query()->whereIn('id', $ids)->update(['invoice_id' => $document->id]);

            return $ids->count();
        });

        if ($updated === 0) {
            return back()->with('error', 'Tidak ada transaksi kas keluar yang dapat dihubungkan. Pastikan item belum terikat invoice atau project.');
        }

        ActivityLogger::logForModel(
            ActivityLog::ACTION_ATTACH,
            'Buku Kas & Transaksi',
            'User '.(auth()->user()->username ?? 'Sistem')." menghubungkan {$updated} transaksi kas ke invoice \"{$document->document_number}\".",
            $document,
            ['transactions' => $transactions->map(fn (Transaction $transaction) => [
                'id' => $transaction->id,
                'transaction_number' => $transaction->transaction_number,
            ])->values()->all()]
        );

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

        $transaction->updateQuietly(['invoice_id' => null]);
        ActivityLogger::logForModel(
            ActivityLog::ACTION_DETACH,
            'Buku Kas & Transaksi',
            'User '.(auth()->user()->username ?? 'Sistem')." melepas transaksi kas \"{$transaction->transaction_number}\" dari invoice \"{$document->document_number}\".",
            $document,
            ['transaction_id' => $transaction->id, 'transaction_number' => $transaction->transaction_number]
        );

        return back()->with('success', "Transaksi {$transaction->transaction_number} berhasil dilepas dari invoice.");
    }
};
