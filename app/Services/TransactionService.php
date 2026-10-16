<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Document;
use App\Models\EntertainmentDetail;
use App\Models\Transaction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TransactionService
{
    /**
     * Membuat Transaksi Baru (Pemasukan / Pengeluaran)
     *
     * @param array $data
     * @param UploadedFile|null $receiptFile
     * @param array|null $entertainmentData
     * @return Transaction
     */
    public function createTransaction(array $data, ?UploadedFile $receiptFile = null, ?array $entertainmentData = null): Transaction
    {
        return DB::transaction(function () use ($data, $receiptFile, $entertainmentData) {
            // 1. Generate Nomor Transaksi Otomatis jika belum disediakan
            if (empty($data['transaction_number'])) {
                $data['transaction_number'] = Transaction::generateTransactionNumber(
                    $data['type'] ?? 'expense',
                    $data['transaction_date'] ?? date('Y-m-d')
                );
            }

            // 2. Upload Bukti Struk/Nota jika ada
            if ($receiptFile) {
                $data['receipt_file_path'] = $receiptFile->store('receipts', 'public');
            }

            // 3. Simpan Record Transaksi
            $transaction = Transaction::create($data);

            // 4. Simpan Detail Entertainment jika kategori adalah Jamuan/Entertainment
            if ($entertainmentData && !empty($entertainmentData['attendee_name'])) {
                $entertainmentData['transaction_id'] = $transaction->id;
                $entertainmentData['event_date'] = $entertainmentData['event_date'] ?? $transaction->transaction_date;
                EntertainmentDetail::create($entertainmentData);
            }

            return $transaction;
        });
    }

    /**
     * Memperbarui Data Transaksi
     *
     * @param Transaction $transaction
     * @param array $data
     * @param UploadedFile|null $receiptFile
     * @param array|null $entertainmentData
     * @return Transaction
     */
    public function updateTransaction(Transaction $transaction, array $data, ?UploadedFile $receiptFile = null, ?array $entertainmentData = null): Transaction
    {
        return DB::transaction(function () use ($transaction, $data, $receiptFile, $entertainmentData) {
            // 1. Handle pergantian file struk jika diupload baru
            if ($receiptFile) {
                if ($transaction->receipt_file_path && Storage::disk('public')->exists($transaction->receipt_file_path)) {
                    Storage::disk('public')->delete($transaction->receipt_file_path);
                }
                $data['receipt_file_path'] = $receiptFile->store('receipts', 'public');
            }

            // 2. Update data transaksi
            $transaction->update($data);

            // 3. Sync detail entertainment
            if ($entertainmentData && !empty($entertainmentData['attendee_name'])) {
                $entertainmentData['event_date'] = $entertainmentData['event_date'] ?? $transaction->transaction_date;
                $transaction->entertainmentDetail()->updateOrCreate(
                    ['transaction_id' => $transaction->id],
                    $entertainmentData
                );
            } else {
                // Jika data entertainment dikosongkan saat edit, hapus relasi jika ada
                if ($transaction->entertainmentDetail) {
                    $transaction->entertainmentDetail->delete();
                }
            }

            return $transaction->fresh(['category', 'entertainmentDetail', 'document']);
        });
    }

    /**
     * Menghapus Transaksi Beserta Bukti File & Relasinya
     *
     * @param Transaction $transaction
     * @return bool
     */
    public function deleteTransaction(Transaction $transaction): bool
    {
        return DB::transaction(function () use ($transaction) {
            if ($transaction->receipt_file_path && Storage::disk('public')->exists($transaction->receipt_file_path)) {
                Storage::disk('public')->delete($transaction->receipt_file_path);
            }

            return $transaction->delete();
        });
    }

    /**
     * Sinkronisasi Otomatis Pencatatan Transaksi Arus Kas dari Dokumen Invoice Lunas (PAID)
     *
     * @param Document $document
     * @param string $paymentMethod
     * @return Transaction|null
     */
    public function syncInvoicePaidTransaction(Document $document, string $paymentMethod = 'Bank Transfer'): ?Transaction
    {
        if ($document->type !== 'INVOICE') {
            return null;
        }

        // Hanya sinkronkan kas MASUK pelunasan. Kas keluar terikat proyek tidak ikut diubah/dihapus.
        $existingTransaction = Transaction::where('invoice_id', $document->id)
            ->where('type', 'income')
            ->first();

        // JIKA STATUS PAID -> BUAT / UPDATE TRANSAKSI PEMASUKAN
        if ($document->status === 'PAID') {
            // Ambil kategori pendapatan invoice (4-100) atau kategori income pertama
            $category = Category::where('code', '4-100')->first() 
                        ?? Category::where('type', 'income')->first();

            $customerName = $document->customer ? $document->customer->name : 'Customer';
            $description = "Pelunasan Invoice #{$document->document_number} - {$customerName}";

            if ($existingTransaction) {
                $existingTransaction->update([
                    'amount'           => $document->total_amount,
                    'transaction_date' => $document->date ?? date('Y-m-d'),
                    'description'      => $description,
                    'category_id'      => $category?->id ?? $existingTransaction->category_id,
                ]);
                return $existingTransaction;
            }

            return Transaction::create([
                'transaction_number' => Transaction::generateTransactionNumber('income', $document->date),
                'type'               => 'income',
                'category_id'        => $category?->id ?? 1,
                'invoice_id'         => $document->id,
                'amount'             => $document->total_amount,
                'transaction_date'   => $document->date ?? date('Y-m-d'),
                'payment_method'     => $paymentMethod,
                'description'        => $description,
            ]);
        }

        // JIKA STATUS BUKAN PAID (DRAFT / SENT) -> HAPUS TRANSAKSI AGAR LAPORAN KEUANGAN TETAP AKURAT
        if ($existingTransaction) {
            $this->deleteTransaction($existingTransaction);
        }

        return null;
    }
}
