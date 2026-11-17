<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        // Identitas & Legalitas Usaha
        'company_name',
        'app_title',
        'app_favicon',
        'company_npwp',
        'company_npwp16',
        'company_nitku',
        'company_logo',
        'company_stamp',
        'company_email',
        'company_phone',
        'company_address',
        'company_city',
        'company_bank_account',
        'stamp_invoice_enabled',
        'stamp_delivery_note_enabled',
        'stamp_receipt_enabled',

        // Penomoran Dokumen
        'po_prefix',
        'invoice_prefix',
        'delivery_note_prefix',
        'kwitansi_prefix',

        // Perpajakan & Finansial
        'term_of_payment',
        'default_tax_rate',
        'enable_tax',
        'currency_symbol',
        'currency_code',

        // Otorisasi & Pejabat Penandatangan
        'signatory_name',
        'signatory_position',
        'signatory_city',

        // Catatan Footer & Syarat Ketentuan Standar
        'invoice_footer_notes',
        'receipt_footer_notes',
        'delivery_note_footer_notes',
    ];

    protected $casts = [
        'enable_tax' => 'boolean',
        'stamp_invoice_enabled' => 'boolean',
        'stamp_delivery_note_enabled' => 'boolean',
        'stamp_receipt_enabled' => 'boolean',
        'default_tax_rate' => 'float',
        'term_of_payment' => 'integer',
    ];

    /**
     * Helper Singleton untuk mengambil atau membuat record setting sistem.
     */
    public static function getSetting(): self
    {
        $setting = self::first();
        if (!$setting) {
            $setting = self::create([
                'company_name' => null,
                'company_npwp16' => null,
                'company_nitku' => '0000000000000000000000',
                'company_city' => null,
                'invoice_prefix' => 'INV',
                'po_prefix' => 'PO',
                'delivery_note_prefix' => 'SJ',
                'kwitansi_prefix' => 'KWT',
                'default_tax_rate' => 11.00,
                'enable_tax' => true,
                'stamp_invoice_enabled' => true,
                'stamp_delivery_note_enabled' => true,
                'stamp_receipt_enabled' => false,
                'currency_symbol' => 'Rp',
                'currency_code' => 'IDR',
                'term_of_payment' => 30,
                'signatory_name' => null,
                'signatory_position' => null,
                'signatory_city' => null,
            ]);
        }
        return $setting;
    }

    /**
     * Path absolut file logo untuk dokumen cetak, atau null bila belum diunggah / file hilang.
     * Satu sumber untuk seluruh PDF supaya dokumen tidak pecah saat logo belum ada.
     */
    public function logoPath(): ?string
    {
        return $this->existingPublicFile($this->company_logo);
    }

    /**
     * Path absolut file stempel untuk dokumen cetak, atau null.
     */
    public function stampPath(): ?string
    {
        return $this->existingPublicFile($this->company_stamp);
    }

    public function faviconUrl(): ?string
    {
        if (blank($this->app_favicon)) {
            return null;
        }

        $disk = \Illuminate\Support\Facades\Storage::disk('public');

        return $disk->exists($this->app_favicon) ? $disk->url($this->app_favicon) : null;
    }

    private function existingPublicFile(?string $relative): ?string
    {
        if (blank($relative)) {
            return null;
        }
        $disk = \Illuminate\Support\Facades\Storage::disk('public');

        return $disk->exists($relative) ? $disk->path($relative) : null;
    }

    /**
     * Helper dinamis untuk mengambil nilai konfigurasi berdasarkan key alias atau atribut.
     */
    public static function get(string $key, $default = null)
    {
        $setting = self::getSetting();

        $aliasMap = [
            'company_tin'   => 'company_npwp16',
            'tin'           => 'company_npwp16',
            'npwp'          => 'company_npwp16',
            'npwp16'        => 'company_npwp16',
            'company_nitku' => 'company_nitku',
            'nitku'         => 'company_nitku',
        ];

        $attribute = $aliasMap[$key] ?? $key;

        return $setting->{$attribute} ?? $default;
    }
}
