<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'code',
        'name',
        'type',
        'is_tax_deductible',
        'description',
    ];

    protected $casts = [
        'is_tax_deductible' => 'boolean',
    ];

    /**
     * Relasi: 1 Kategori memiliki banyak Transaksi
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function activityModuleName(): string
    {
        return 'Buku Kas & Transaksi';
    }

    public function activitySubjectName(): string
    {
        return 'Kategori Akun';
    }

    public function activityLabel(): string
    {
        return trim(($this->code ? "[{$this->code}] " : '') . ($this->name ?? '#' . $this->getKey()));
    }

    /**
     * Scope untuk kategori pendapatan (income)
     */
    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    /**
     * Scope untuk kategori pengeluaran (expense)
     */
    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }

    /**
     * Scope untuk pengeluaran yang dapat dikurangkan dari pajak (Deductible)
     */
    public function scopeTaxDeductible($query)
    {
        return $query->where('is_tax_deductible', true);
    }

    /**
     * Scope untuk pengeluaran non-deductible (Koreksi Fiskal Positif)
     */
    public function scopeNonDeductible($query)
    {
        return $query->where('is_tax_deductible', false);
    }

}
