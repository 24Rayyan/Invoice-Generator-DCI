<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name',
        'tax_id_type',
        'tax_id_number',
        'nitku',
        'default_tax_transaction_code',
        'email',
        'phone',
        'address',
    ];

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function activityModuleName(): string
    {
        return 'Invoicing & PO';
    }

    public function activitySubjectName(): string
    {
        return 'Customer';
    }

    /**
     * Accessor untuk NPWP 16 digit (TIN) pembeli
     */
    public function getTinAttribute(): ?string
    {
        $idType = strtoupper($this->tax_id_type ?? 'NPWP16');
        if ($idType === 'NPWP16' || $idType === 'TIN' || $idType === 'NPWP') {
            return $this->tax_id_number ? preg_replace('/[^0-9]/', '', $this->tax_id_number) : null;
        }
        return null;
    }

    /**
     * Accessor untuk jenis dokumen Coretax ('TIN', 'National ID', 'Passport', 'Other')
     */
    public function getDocumentTypeAttribute(): string
    {
        $type = strtoupper($this->tax_id_type ?? 'NPWP16');

        if ($type === 'NIK' || $type === 'NATIONAL ID' || $type === 'NATIONAL_ID' || $type === 'KTP') {
            return 'National ID';
        }

        if ($type === 'PASPOR' || $type === 'PASSPORT') {
            return 'Passport';
        }

        if ($type === 'OTHER' || $type === 'LAINNYA') {
            return 'Other';
        }

        return 'TIN';
    }

    /**
     * Accessor untuk nomor dokumen identitas pembeli
     */
    public function getDocumentNumberAttribute(): ?string
    {
        return $this->tax_id_number;
    }

    /**
     * Accessor untuk kode negara pembeli (default 'IDN')
     */
    public function getCountryCodeAttribute(): string
    {
        return $this->attributes['country_code'] ?? 'IDN';
    }
}