<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use LogsActivity;

    protected $fillable = [
        'customer_id',
        'project_id',
        'type',
        'document_number',
        'delivery_note_number',
        'receipt_number',
        'date',
        'status',
        'total_amount',
        'discount',
        'tax_transaction_code',
        'tax_invoice_date',
        'payment_note',
    ];

    protected $casts = [
        'date'             => 'date',
        'tax_invoice_date' => 'date',
        'total_amount'     => 'decimal:2',
        'discount'         => 'decimal:2',
    ];

    // Relasi: 1 Document punya banyak DocumentItem
    public function items()
    {
        return $this->hasMany(DocumentItem::class);
    }

    // Relasi: 1 Document milik 1 Customer
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Semua transaksi kas yang terikat ke invoice ini (income pelunasan + expense proyek).
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'invoice_id');
    }

    /**
     * Transaksi pemasukan pelunasan otomatis (satu invoice = satu kas masuk PAID).
     */
    public function transaction()
    {
        return $this->hasOne(Transaction::class, 'invoice_id')->where('type', 'income');
    }

    /**
     * Kas keluar terikat proyek — dipakai untuk hitung direct expense & net margin.
     */
    public function directExpenses()
    {
        return $this->hasMany(Transaction::class, 'invoice_id')->where('type', 'expense');
    }

    /**
     * Invoice yang relevan untuk dropdown relasi kas (bukan PO).
     */
    public static function linkableInvoices(?int $includeId = null)
    {
        return static::query()
            ->with('customer')
            ->where('type', 'INVOICE')
            ->when($includeId, function ($query) use ($includeId) {
                $query->where(function ($inner) use ($includeId) {
                    $inner->whereIn('status', ['DRAFT', 'SENT', 'PAID'])
                          ->orWhere('id', $includeId);
                });
            }, function ($query) {
                $query->whereIn('status', ['DRAFT', 'SENT', 'PAID']);
            })
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();
    }

    public function activityModuleName(): string
    {
        return 'Invoicing & PO';
    }

    public function activitySubjectName(): string
    {
        return $this->type === 'PO' ? 'Purchase Order' : 'Invoice';
    }

    public function activityLabel(): string
    {
        return $this->document_number ?: ('#' . $this->getKey());
    }
}