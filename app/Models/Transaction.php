<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Transaction extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'transaction_number',
        'type',
        'category_id',
        'invoice_id',
        'project_id',
        'amount',
        'transaction_date',
        'payment_method',
        'description',
        'receipt_file_path',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    /**
     * Relasi: Transaksi terhubung ke 1 Kategori (COA)
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relasi: Transaksi terhubung ke 1 Invoice / Dokumen (Opsional).
     * Satu transaksi kas hanya boleh terikat ke satu invoice; NULL = biaya operasional umum.
     */
    public function document()
    {
        return $this->belongsTo(Document::class, 'invoice_id');
    }

    public function invoice()
    {
        return $this->document();
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function scopeUnallocated($query)
    {
        return $query->whereNull('invoice_id')->whereNull('project_id');
    }

    public function scopeUnallocatedToProject($query)
    {
        return $query->whereNull('invoice_id')->whereNull('project_id');
    }

    /**
     * Relasi: Transaksi Jamuan/Entertainment memiliki 1 Detail Nominatif SPT
     */
    public function entertainmentDetail()
    {
        return $this->hasOne(EntertainmentDetail::class);
    }

    public function activityModuleName(): string
    {
        return 'Buku Kas & Transaksi';
    }

    public function activitySubjectName(): string
    {
        return $this->type === 'income' ? 'Transaksi Pemasukan' : 'Transaksi Pengeluaran';
    }

    public function activityLabel(): string
    {
        return $this->transaction_number ?: ('#' . $this->getKey());
    }

    /**
     * Scope Income
     */
    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    /**
     * Scope Expense
     */
    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }

    /**
     * Scope Filter Periode (Tahun & Bulan)
     */
    public function scopePeriod($query, $year, $month = null)
    {
        $query->whereYear('transaction_date', $year);
        if ($month) {
            $query->whereMonth('transaction_date', $month);
        }
        return $query;
    }

    /**
     * Helper Generator Nomor Transaksi Otomatis: TRX/YYYY/MM/XXXX
     */
    public static function generateTransactionNumber($type = 'expense', $date = null): string
    {
        $carbonDate = $date ? Carbon::parse($date) : Carbon::now();
        $year = $carbonDate->format('Y');
        $month = $carbonDate->format('m');
        
        $prefix = ($type === 'income') ? 'TRX-IN' : 'TRX-OUT';
        $searchPattern = "{$prefix}/{$year}/{$month}/%";

        $lastTransaction = self::where('transaction_number', 'LIKE', $searchPattern)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastTransaction) {
            $parts = explode('/', $lastTransaction->transaction_number);
            $lastIndex = end($parts);
            $nextNumber = str_pad(intval($lastIndex) + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }

        return "{$prefix}/{$year}/{$month}/{$nextNumber}";
    }
}
