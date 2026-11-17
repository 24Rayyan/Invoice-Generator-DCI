@extends('layouts.app')

@section('content')
@php
    // Penanda field bermasalah: kelas + pesan galat per field.
    $inv = fn (string $f) => $errors->has($f) ? ' is-invalid' : '';
    $fb  = fn (string $f) => $errors->has($f) ? '<div class="invalid-feedback">' . e($errors->first($f)) . '</div>' : '';

    $sections = [
        'company'   => 'Profil perusahaan',
        'branding'  => 'Logo dan stempel',
        'numbering' => 'Penomoran dokumen',
        'finance'   => 'Pajak dan keuangan',
        'signatory' => 'Penandatangan',
        'notes'     => 'Catatan dokumen',
    ];
    // Field -> panel, untuk membuka panel yang berisi galat setelah validasi gagal.
    $fieldPanel = [
        'company_name' => 'company', 'company_npwp16' => 'company', 'company_nitku' => 'company', 'company_email' => 'company',
        'company_phone' => 'company', 'company_city' => 'company', 'company_address' => 'company',
        'company_logo' => 'branding', 'company_stamp' => 'branding',
        'invoice_prefix' => 'numbering', 'po_prefix' => 'numbering', 'delivery_note_prefix' => 'numbering', 'kwitansi_prefix' => 'numbering',
        'default_tax_rate' => 'finance', 'term_of_payment' => 'finance', 'currency_symbol' => 'finance', 'currency_code' => 'finance', 'company_bank_account' => 'finance',
        'signatory_name' => 'signatory', 'signatory_position' => 'signatory', 'signatory_city' => 'signatory',
        'invoice_footer_notes' => 'notes', 'receipt_footer_notes' => 'notes', 'delivery_note_footer_notes' => 'notes',
    ];
    $errorPanels = collect($errors->keys())->map(fn ($k) => $fieldPanel[$k] ?? null)->filter()->unique()->values();
    $firstErrorPanel = $errorPanels->first();

    $logoMissing  = $setting->company_logo && ! $setting->logoPath();
    $stampMissing = $setting->company_stamp && ! $setting->stampPath();
@endphp

<div class="st-page">

    <header class="st-head">
        <div>
            <h1>Pusat Konfigurasi Mini ERP</h1>
            <p>Satu sumber data untuk identitas perusahaan, penomoran, pajak, dan tampilan dokumen cetak. Perubahan berlaku pada dokumen yang dibuat atau dicetak setelah disimpan.</p>
        </div>
    </header>

    @if(session('success'))
        <div class="alert alert-success d-flex justify-content-between align-items-start gap-3" role="status">
            <span>{{ session('success') }}</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger" role="alert" id="stErrors">
            <strong>Pengaturan belum tersimpan. Periksa {{ $errors->count() }} isian berikut:</strong>
            <ul class="mb-0 mt-1 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data" id="settingsForm" novalidate>
        @csrf

        <div class="st-layout">

            {{-- Navigasi seksi: kolom tetap di desktop, baris yang bisa digeser di layar sempit --}}
            <nav class="st-nav" aria-label="Seksi pengaturan">
                <div class="nav flex-lg-column" id="settingTabs" role="tablist" aria-orientation="vertical">
                    @foreach($sections as $key => $label)
                        <button type="button" role="tab"
                                class="st-nav-item {{ ($firstErrorPanel ?? 'company') === $key ? 'active' : '' }} {{ $errorPanels->contains($key) ? 'has-error' : '' }}"
                                id="tab-btn-{{ $key }}" data-bs-toggle="tab" data-bs-target="#panel-{{ $key }}"
                                aria-controls="panel-{{ $key }}" aria-selected="{{ ($firstErrorPanel ?? 'company') === $key ? 'true' : 'false' }}">
                            <span>{{ $label }}</span>
                            @if($errorPanels->contains($key))<span class="st-nav-flag" title="Ada isian yang perlu diperbaiki">Perbaiki</span>@endif
                        </button>
                    @endforeach
                </div>
            </nav>

            <div class="st-main">
                <div class="tab-content">

                    {{-- 1. PROFIL --}}
                    <section class="tab-pane fade {{ ($firstErrorPanel ?? 'company') === 'company' ? 'show active' : '' }}" id="panel-company" role="tabpanel" aria-labelledby="tab-btn-company" tabindex="0">
                        <div class="st-panel-head">
                            <h2>Profil perusahaan</h2>
                            <p>Dicetak pada kop seluruh dokumen (Invoice, PO, Surat Jalan, Kwitansi) dan laporan PDF.</p>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="company_name" class="form-label">Nama resmi perusahaan <span class="req" aria-hidden="true">*</span></label>
                                <input type="text" id="company_name" name="company_name" class="form-control{{ $inv('company_name') }}" value="{{ old('company_name', $setting->company_name ?? '') }}" required aria-required="true" placeholder="Contoh: PT Sinar Abadi">
                                {!! $fb('company_name') !!}
                                <div class="form-text">Juga tampil di halaman login dan laporan keuangan.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="company_city" class="form-label">Kota kantor</label>
                                <input type="text" id="company_city" name="company_city" class="form-control{{ $inv('company_city') }}" value="{{ old('company_city', $setting->company_city ?? '') }}" placeholder="Contoh: Jakarta">
                                {!! $fb('company_city') !!}
                                <div class="form-text">Cadangan kota tanda tangan bila kota penandatangan kosong.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="company_email" class="form-label">Email</label>
                                <input type="email" id="company_email" name="company_email" class="form-control{{ $inv('company_email') }}" value="{{ old('company_email', $setting->company_email ?? '') }}" placeholder="finance@perusahaan.com" autocomplete="off">
                                {!! $fb('company_email') !!}
                            </div>
                            <div class="col-md-6">
                                <label for="company_phone" class="form-label">Telepon / WhatsApp</label>
                                <input type="text" id="company_phone" name="company_phone" class="form-control{{ $inv('company_phone') }}" value="{{ old('company_phone', $setting->company_phone ?? '') }}" placeholder="08123456789 / (022) 123456" inputmode="tel" autocomplete="off">
                                {!! $fb('company_phone') !!}
                            </div>
                            <div class="col-12">
                                <label for="company_address" class="form-label">Alamat lengkap</label>
                                <textarea id="company_address" name="company_address" class="form-control{{ $inv('company_address') }}" rows="3" placeholder="Jalan, nomor, kecamatan, kota, kode pos">{{ old('company_address', $setting->company_address ?? '') }}</textarea>
                                {!! $fb('company_address') !!}
                            </div>
                        </div>

                        <div class="st-sub">
                            <h3>Identitas pajak (Coretax DJP)</h3>
                            <p>Dipakai pada ekspor faktur Coretax dan dicetak di Invoice, Surat Jalan, Kwitansi, serta laporan.</p>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="company_npwp16" class="form-label">NPWP 16 Digit Penjual</label>
                                <input type="text" id="company_npwp16" name="company_npwp16" class="form-control font-monospace{{ $inv('company_npwp16') }}" maxlength="16" inputmode="numeric" value="{{ old('company_npwp16', $setting->company_npwp16 ?? '') }}" placeholder="16 digit angka">
                                {!! $fb('company_npwp16') !!}
                            </div>
                            <div class="col-md-6">
                                <label for="company_nitku" class="form-label">NITKU 22 digit</label>
                                <input type="text" id="company_nitku" name="company_nitku" class="form-control font-monospace{{ $inv('company_nitku') }}" maxlength="22" inputmode="numeric" value="{{ old('company_nitku', $setting->company_nitku ?? '') }}" placeholder="22 digit NITKU">
                                {!! $fb('company_nitku') !!}
                                <div class="form-text">Bila dikosongkan, sistem memakai 22 digit nol.</div>
                            </div>
                        </div>
                    </section>

                    {{-- 2. LOGO & STEMPEL --}}
                    <section class="tab-pane fade {{ $firstErrorPanel === 'branding' ? 'show active' : '' }}" id="panel-branding" role="tabpanel" aria-labelledby="tab-btn-branding" tabindex="0">
                        <div class="st-panel-head">
                            <h2>Logo dan stempel</h2>
                            <p>Gambar PNG, JPG, atau WEBP, maksimal 2 MB. PNG transparan memberi hasil cetak terbaik. Tanpa file, dokumen tetap tercetak normal dengan nama perusahaan.</p>
                        </div>

                        <div class="st-asset">
                            <div class="st-asset-preview">
                                @if($setting->company_logo && ! $logoMissing)
                                    <img src="{{ asset('storage/' . $setting->company_logo) }}" alt="Logo perusahaan saat ini" width="140" height="50">
                                @else
                                    <span>Belum ada logo</span>
                                @endif
                            </div>
                            <div class="st-asset-body">
                                <label for="company_logo" class="form-label">Logo perusahaan</label>
                                <input type="file" id="company_logo" name="company_logo" class="form-control{{ $inv('company_logo') }}" accept=".png,.jpg,.jpeg,.webp">
                                {!! $fb('company_logo') !!}
                                <div class="form-text">Tampil di navbar, halaman login, kop PDF dokumen, dan PDF laporan.</div>
                                @if($logoMissing)
                                    <div class="form-text text-danger">File logo yang tercatat tidak ditemukan di penyimpanan. Dokumen dicetak tanpa logo sampai logo diunggah ulang.</div>
                                @endif
                                @if($setting->company_logo)
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" name="remove_company_logo" id="remove_company_logo" value="1">
                                        <label class="form-check-label" for="remove_company_logo">Hapus logo saat ini</label>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="st-asset">
                            <div class="st-asset-preview">
                                @if($setting->company_stamp && ! $stampMissing)
                                    <img src="{{ asset('storage/' . $setting->company_stamp) }}" alt="Stempel perusahaan saat ini" width="90" height="65">
                                @else
                                    <span>Belum ada stempel</span>
                                @endif
                            </div>
                            <div class="st-asset-body">
                                <label for="company_stamp" class="form-label">Stempel Perusahaan</label>
                                <input type="file" id="company_stamp" name="company_stamp" class="form-control{{ $inv('company_stamp') }}" accept=".png,.jpg,.jpeg,.webp">
                                {!! $fb('company_stamp') !!}
                                <div class="form-text">Unggah satu kali, lalu pilih dokumen yang mencetaknya. PO tidak memakai stempel.</div>
                                @if($stampMissing)
                                    <div class="form-text text-danger">File stempel yang tercatat tidak ditemukan di penyimpanan.</div>
                                @endif
                                @if($setting->company_stamp)
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" name="remove_company_stamp" id="remove_company_stamp" value="1">
                                        <label class="form-check-label" for="remove_company_stamp">Hapus stempel saat ini</label>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <fieldset class="st-sub">
                            <legend>Cetak stempel pada</legend>
                            <div class="st-toggles">
                                @foreach([
                                    ['stamp_invoice_enabled', 'stamp-invoice', 'Invoice', true],
                                    ['stamp_delivery_note_enabled', 'stamp-delivery-note', 'Surat Jalan', true],
                                    ['stamp_receipt_enabled', 'stamp-receipt', 'Kwitansi', false],
                                ] as [$field, $id, $label, $default])
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" name="{{ $field }}" id="{{ $id }}" value="1" {{ old($field, $setting->{$field} ?? $default) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="{{ $id }}">{{ $label }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </fieldset>
                    </section>

                    {{-- 3. PENOMORAN --}}
                    <section class="tab-pane fade {{ $firstErrorPanel === 'numbering' ? 'show active' : '' }}" id="panel-numbering" role="tabpanel" aria-labelledby="tab-btn-numbering" tabindex="0">
                        <div class="st-panel-head">
                            <h2>Penomoran dokumen</h2>
                            <p>Format nomor: <span class="font-monospace">urut/PREFIX/bulan romawi/tahun</span>. Urutan kembali ke 001 setiap pergantian bulan. Mengganti prefix memulai urutan baru untuk prefix tersebut; nomor lama tidak berubah.</p>
                        </div>

                        <div class="table-responsive">
                            <table class="st-table">
                                <thead>
                                    <tr>
                                        <th scope="col">Dokumen</th>
                                        <th scope="col" style="width: 190px;">Prefix</th>
                                        <th scope="col">Contoh nomor</th>
                                        <th scope="col">Terakhir bulan ini ({{ $romanMonth }}/{{ $currentYear }})</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach([
                                        ['invoice_prefix', 'Invoice', 'INV', true,  $lastInvoice?->document_number],
                                        ['po_prefix', 'Purchase Order', 'PO', true, $lastPO?->document_number],
                                        ['delivery_note_prefix', 'Surat Jalan', 'SJ', false, $lastDeliveryNote?->delivery_note_number],
                                        ['kwitansi_prefix', 'Kwitansi', 'KWT', false, $lastReceipt?->receipt_number],
                                    ] as [$field, $label, $fallback, $required, $last])
                                        <tr>
                                            <td><label for="{{ $field }}" class="mb-0">{{ $label }} @if($required)<span class="req" aria-hidden="true">*</span>@endif</label></td>
                                            <td>
                                                <input type="text" id="{{ $field }}" name="{{ $field }}" class="form-control form-control-sm font-monospace{{ $inv($field) }}" value="{{ old($field, $setting->{$field} ?? $fallback) }}" @if($required) required aria-required="true" @endif placeholder="{{ $fallback }}" data-prefix-input data-fallback="{{ $fallback }}">
                                                {!! $fb($field) !!}
                                            </td>
                                            <td class="font-monospace small">001/<span data-prefix-example="{{ $field }}">{{ old($field, $setting->{$field} ?? $fallback) ?: $fallback }}</span>/{{ $romanMonth }}/{{ $currentYear }}</td>
                                            <td class="font-monospace small">{{ $last ?? 'Belum ada bulan ini' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>

                    {{-- 4. PAJAK & KEUANGAN --}}
                    <section class="tab-pane fade {{ $firstErrorPanel === 'finance' ? 'show active' : '' }}" id="panel-finance" role="tabpanel" aria-labelledby="tab-btn-finance" tabindex="0">
                        <div class="st-panel-head">
                            <h2>Pajak dan keuangan</h2>
                            <p>Berlaku untuk dokumen baru. Invoice dan Kwitansi yang sudah dibuat tetap memakai total yang tersimpan, jadi perubahan tarif tidak mengubah dokumen lama.</p>
                        </div>
                        <div class="row g-3">
                            <div class="col-sm-6 col-lg-4">
                                <label for="default_tax_rate" class="form-label">Tarif PPN <span class="req" aria-hidden="true">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.01" id="default_tax_rate" name="default_tax_rate" class="form-control{{ $inv('default_tax_rate') }}" value="{{ old('default_tax_rate', $setting->default_tax_rate ?? 11) }}" required aria-required="true" min="0" max="100">
                                    <span class="input-group-text">%</span>
                                    {!! $fb('default_tax_rate') !!}
                                </div>
                                <div class="form-text">Dipakai pada form Invoice dan ekspor Coretax.</div>
                            </div>
                            <div class="col-sm-6 col-lg-4">
                                <label for="term_of_payment" class="form-label">Jatuh tempo (term of payment)</label>
                                <div class="input-group">
                                    <input type="number" id="term_of_payment" name="term_of_payment" class="form-control{{ $inv('term_of_payment') }}" value="{{ old('term_of_payment', $setting->term_of_payment ?? 30) }}" min="0">
                                    <span class="input-group-text">hari</span>
                                    {!! $fb('term_of_payment') !!}
                                </div>
                                <div class="form-text">Tanggal jatuh tempo pada PDF Invoice dihitung dari tanggal invoice.</div>
                            </div>
                            <div class="col-sm-6 col-lg-2">
                                <label for="currency_symbol" class="form-label">Simbol mata uang</label>
                                <input type="text" id="currency_symbol" name="currency_symbol" class="form-control font-monospace{{ $inv('currency_symbol') }}" value="{{ old('currency_symbol', $setting->currency_symbol ?? 'Rp') }}" placeholder="Rp">
                                {!! $fb('currency_symbol') !!}
                            </div>
                            <div class="col-sm-6 col-lg-2">
                                <label for="currency_code" class="form-label">Kode</label>
                                <input type="text" id="currency_code" name="currency_code" class="form-control font-monospace{{ $inv('currency_code') }}" value="{{ old('currency_code', $setting->currency_code ?? 'IDR') }}" placeholder="IDR">
                                {!! $fb('currency_code') !!}
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch st-switch-row">
                                    <input class="form-check-input" type="checkbox" role="switch" name="enable_tax" id="enable_tax" value="1" {{ old('enable_tax', $setting->enable_tax ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="enable_tax">
                                        Hitung PPN otomatis pada dokumen baru
                                        <span class="d-block form-text mt-0">Bila nonaktif, dokumen baru dihitung tanpa PPN (0%).</span>
                                    </label>
                                </div>
                            </div>
                            <div class="col-12">
                                <label for="company_bank_account" class="form-label">Rekening bank dan petunjuk transfer</label>
                                <textarea id="company_bank_account" name="company_bank_account" class="form-control{{ $inv('company_bank_account') }}" rows="3" placeholder="Bank: [Nama Bank]&#10;No. Rekening: [Nomor Rekening]&#10;Atas nama: [Nama Pemilik Rekening]">{{ old('company_bank_account', $setting->company_bank_account ?? '') }}</textarea>
                                {!! $fb('company_bank_account') !!}
                                <div class="form-text">Dicetak pada kotak Catatan Pembayaran di PDF Invoice.</div>
                            </div>
                        </div>
                    </section>

                    {{-- 5. PENANDATANGAN --}}
                    <section class="tab-pane fade {{ $firstErrorPanel === 'signatory' ? 'show active' : '' }}" id="panel-signatory" role="tabpanel" aria-labelledby="tab-btn-signatory" tabindex="0">
                        <div class="st-panel-head">
                            <h2>Penandatangan</h2>
                            <p>Dicetak pada kolom pengesahan Invoice, PO, Surat Jalan, Kwitansi, dan lampiran laporan PDF. Bila kosong, kolom nama dibiarkan untuk diisi manual atau memakai teks bawaan dokumen.</p>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label for="signatory_name" class="form-label">Nama pejabat</label>
                                <input type="text" id="signatory_name" name="signatory_name" class="form-control{{ $inv('signatory_name') }}" value="{{ old('signatory_name', $setting->signatory_name ?? '') }}" placeholder="Nama lengkap pejabat">
                                {!! $fb('signatory_name') !!}
                            </div>
                            <div class="col-md-4">
                                <label for="signatory_position" class="form-label">Jabatan</label>
                                <input type="text" id="signatory_position" name="signatory_position" class="form-control{{ $inv('signatory_position') }}" value="{{ old('signatory_position', $setting->signatory_position ?? '') }}" placeholder="Contoh: Direktur / Manajer Keuangan">
                                {!! $fb('signatory_position') !!}
                            </div>
                            <div class="col-md-3">
                                <label for="signatory_city" class="form-label">Kota tanda tangan</label>
                                <input type="text" id="signatory_city" name="signatory_city" class="form-control{{ $inv('signatory_city') }}" value="{{ old('signatory_city', $setting->signatory_city ?? '') }}" placeholder="Contoh: Jakarta">
                                {!! $fb('signatory_city') !!}
                            </div>
                        </div>
                    </section>

                    {{-- 6. CATATAN --}}
                    <section class="tab-pane fade {{ $firstErrorPanel === 'notes' ? 'show active' : '' }}" id="panel-notes" role="tabpanel" aria-labelledby="tab-btn-notes" tabindex="0">
                        <div class="st-panel-head">
                            <h2>Catatan dokumen</h2>
                            <p>Teks baku yang menyertai dokumen. Bedanya: catatan Invoice dan Surat Jalan dicetak langsung dari sini, sedangkan keterangan Kwitansi disalin ke Invoice baru saat dibuat.</p>
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="invoice_footer_notes" class="form-label">Catatan footer Invoice</label>
                                <textarea id="invoice_footer_notes" name="invoice_footer_notes" class="form-control{{ $inv('invoice_footer_notes') }}" rows="3" placeholder="Contoh: Barang yang sudah dibeli tidak dapat dikembalikan. Pembayaran dianggap sah jika bukti transfer telah dikonfirmasi.">{{ old('invoice_footer_notes', $setting->invoice_footer_notes ?? '') }}</textarea>
                                {!! $fb('invoice_footer_notes') !!}
                                <div class="form-text">Dicetak di PDF Invoice.</div>
                            </div>
                            <div class="col-12">
                                <label for="receipt_footer_notes" class="form-label">Keterangan Kwitansi</label>
                                <textarea id="receipt_footer_notes" name="receipt_footer_notes" class="form-control{{ $inv('receipt_footer_notes') }}" rows="2" placeholder="Contoh: Pembayaran Pelunasan Tagihan Dokumen Invoice">{{ old('receipt_footer_notes', $setting->receipt_footer_notes ?? '') }}</textarea>
                                {!! $fb('receipt_footer_notes') !!}
                                <div class="form-text">Diisi otomatis sebagai catatan pembayaran pada Invoice baru, lalu tampil di Kwitansi.</div>
                            </div>
                            <div class="col-12">
                                <label for="delivery_note_footer_notes" class="form-label">Catatan Surat Jalan</label>
                                <textarea id="delivery_note_footer_notes" name="delivery_note_footer_notes" class="form-control{{ $inv('delivery_note_footer_notes') }}" rows="2" placeholder="Contoh: Mohon periksa kembali kelengkapan barang sebelum menandatangani bukti penerimaan ini.">{{ old('delivery_note_footer_notes', $setting->delivery_note_footer_notes ?? '') }}</textarea>
                                {!! $fb('delivery_note_footer_notes') !!}
                                <div class="form-text">Dicetak di PDF Surat Jalan.</div>
                            </div>
                        </div>
                    </section>

                </div>

                {{-- Bilah simpan: selalu terjangkau; satu tombol menyimpan semua seksi --}}
                <div class="st-savebar">
                    <span class="st-savebar-note"><span class="req" aria-hidden="true">*</span> wajib diisi. Satu tombol menyimpan semua seksi.</span>
                    <button type="submit" class="btn btn-primary" id="settingsSubmit">
                        <span class="erp-spinner" aria-hidden="true"></span>Simpan pengaturan
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('styles')
<style>
    /* Token --erp-* dari erp.css. Satu aksen biru; merah hanya untuk galat. */
    .st-page { color: var(--erp-text); }
    .st-head { margin-bottom: 1rem; }
    .st-head h1 { font-size: clamp(1.3rem, 1.1rem + .8vw, 1.65rem); font-weight: 700; letter-spacing: -.03em; margin: 0 0 .2rem; }
    .st-head p { margin: 0; font-size: .86rem; color: var(--erp-text-3); max-width: 72ch; }

    .st-layout { display: grid; grid-template-columns: 220px minmax(0, 1fr); gap: 1.25rem; align-items: start; }

    .st-nav { position: sticky; top: 92px; }
    .st-nav .nav { gap: .15rem; }
    .st-nav-item { appearance: none; background: transparent; border: 0; text-align: left; font: inherit; font-size: .86rem; font-weight: 600; color: var(--erp-text-2); padding: .55rem .75rem; min-height: 40px; border-radius: var(--erp-radius-sm); display: flex; align-items: center; justify-content: space-between; gap: .5rem; border-left: 3px solid transparent; transition: background-color .15s ease, color .15s ease; }
    .st-nav-item:hover { background: var(--erp-surface); color: var(--erp-ink); }
    .st-nav-item.active { background: var(--erp-primary-soft); color: var(--erp-primary-hover); border-left-color: var(--erp-primary); }
    .st-nav-item:focus-visible { outline: 2px solid var(--erp-primary); outline-offset: 2px; }
    .st-nav-flag { font-size: .7rem; font-weight: 700; color: var(--erp-danger); background: var(--erp-danger-soft); border-radius: 5px; padding: .05rem .4rem; }

    .st-main { background: var(--erp-surface); border: 1px solid var(--erp-line); border-radius: var(--erp-radius); min-width: 0; overflow: hidden; }
    .tab-pane { padding: 1.25rem 1.35rem 1.5rem; }
    .st-panel-head { margin-bottom: 1.1rem; padding-bottom: .8rem; border-bottom: 1px solid var(--erp-line); }
    .st-panel-head h2 { font-size: 1.05rem; font-weight: 700; margin: 0 0 .2rem; }
    .st-panel-head p, .st-sub p { margin: 0; font-size: .84rem; color: var(--erp-text-3); max-width: 78ch; }
    .st-sub { margin-top: 1.6rem; margin-bottom: .85rem; padding: 0; border: 0; }
    .st-sub h3, .st-sub legend { font-size: .92rem; font-weight: 700; color: var(--erp-ink); margin: 0 0 .2rem; float: none; width: auto; }

    .req { color: var(--erp-danger); font-weight: 700; }
    .st-page .form-label { font-size: .8rem; font-weight: 600; color: var(--erp-text); margin-bottom: .3rem; }
    .st-page .form-text { margin-top: .3rem; font-size: .76rem; color: var(--erp-text-3); }
    .st-page .form-text.text-danger { color: var(--erp-danger) !important; }

    .st-asset { display: grid; grid-template-columns: 170px minmax(0, 1fr); gap: 1rem; padding: 1rem 0; border-top: 1px solid var(--erp-line); }
    .st-asset:first-of-type { border-top: 0; padding-top: 0; }
    .st-asset-preview { display: grid; place-items: center; min-height: 84px; border: 1px dashed var(--erp-line-strong); border-radius: var(--erp-radius-sm); background: var(--erp-surface-2); padding: .5rem; font-size: .78rem; color: var(--erp-text-3); text-align: center; }
    .st-asset-preview img { max-width: 100%; max-height: 64px; object-fit: contain; }

    .st-toggles { display: flex; flex-wrap: wrap; gap: .6rem 1.75rem; margin-top: .6rem; }
    .st-toggles .form-check { margin: 0; min-height: 40px; display: flex; align-items: center; gap: .6rem; padding-left: 0; }
    .st-toggles .form-check-input, .st-switch-row .form-check-input { margin: 0; float: none; flex-shrink: 0; }
    .st-switch-row { display: flex; align-items: flex-start; gap: .7rem; padding: .75rem .9rem; background: var(--erp-surface-2); border: 1px solid var(--erp-line); border-radius: var(--erp-radius-sm); margin: 0; }
    .st-switch-row .form-check-input { margin-top: .2rem; }
    .st-switch-row .form-check-label { font-weight: 600; font-size: .86rem; }

    .st-table { width: 100%; min-width: 680px; border-collapse: collapse; font-size: .85rem; }
    .st-table thead th { background: var(--erp-surface-2); color: var(--erp-text-3); font-size: .72rem; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; padding: .6rem .8rem; text-align: left; border-bottom: 1px solid var(--erp-line-strong); white-space: nowrap; }
    .st-table tbody td { padding: .65rem .8rem; border-bottom: 1px solid var(--erp-line); vertical-align: middle; }
    .st-table tbody tr:last-child td { border-bottom: 0; }
    .st-table label { font-weight: 600; color: var(--erp-ink); }

    .st-savebar { position: sticky; bottom: 0; z-index: 5; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem 1rem; padding: .8rem 1.35rem; background: var(--erp-surface); border-top: 1px solid var(--erp-line-strong); }
    .st-savebar-note { font-size: .8rem; color: var(--erp-text-3); }

    @media (max-width: 991.98px) {
        .st-layout { grid-template-columns: minmax(0, 1fr); gap: .9rem; }
        .st-nav { position: static; margin: 0 -.15rem; }
        .st-nav .nav { flex-wrap: nowrap; overflow-x: auto; gap: .25rem; padding-bottom: .25rem; scrollbar-width: thin; }
        .st-nav-item { flex: 0 0 auto; white-space: nowrap; border-left: 0; border-bottom: 2px solid transparent; min-height: 44px; background: var(--erp-surface); border: 1px solid var(--erp-line); }
        .st-nav-item.active { border-color: var(--erp-primary); }
    }
    @media (max-width: 767.98px) {
        /* Tabel penomoran: tiap dokumen menjadi blok bertumpuk, prefix tetap dekat labelnya */
        .st-table, .st-table tbody { display: block; min-width: 0; }
        .st-table thead { display: none; }
        .st-table tbody tr { display: grid; gap: .45rem; padding: .9rem 0; border-bottom: 1px solid var(--erp-line); }
        .st-table tbody tr:last-child { border-bottom: 0; }
        .st-table tbody td { display: block; padding: 0; border: 0; overflow-wrap: anywhere; }
        .st-table tbody td:nth-child(3)::before { content: "Contoh nomor"; display: block; font: 600 .7rem/1.4 var(--bs-body-font-family); color: var(--erp-text-3); text-transform: uppercase; letter-spacing: .04em; }
        .st-table tbody td:nth-child(4)::before { content: "Terakhir bulan ini"; display: block; font: 600 .7rem/1.4 var(--bs-body-font-family); color: var(--erp-text-3); text-transform: uppercase; letter-spacing: .04em; }
    }
    @media (max-width: 575.98px) {
        .tab-pane { padding: 1rem .95rem 1.25rem; }
        .st-asset { grid-template-columns: minmax(0, 1fr); }
        .st-savebar { padding: .75rem .95rem; }
        .st-savebar .btn { width: 100%; }
        .st-toggles { flex-direction: column; gap: .25rem; }
    }
</style>
@endpush

@push('scripts')
<script>
    (function () {
        // Pratinjau contoh nomor mengikuti prefix yang sedang diketik.
        document.querySelectorAll('[data-prefix-input]').forEach(function (input) {
            input.addEventListener('input', function () {
                var target = document.querySelector('[data-prefix-example="' + input.name + '"]');
                if (target) target.textContent = input.value.trim() || input.dataset.fallback;
            });
        });

        // Ingat seksi aktif lewat hash, kecuali ada galat (seksi galat sudah dibuka server).
        var hasErrors = {{ $errors->any() ? 'true' : 'false' }};
        if (!hasErrors && location.hash.indexOf('#panel-') === 0) {
            var btn = document.querySelector('[data-bs-target="' + location.hash + '"]');
            if (btn && window.bootstrap) bootstrap.Tab.getOrCreateInstance(btn).show();
        }
        document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function (btn) {
            btn.addEventListener('shown.bs.tab', function (e) {
                history.replaceState(null, '', e.target.getAttribute('data-bs-target'));
            });
        });

        // Validasi klien ringan: seksi yang berisi field wajib kosong dibuka dan difokuskan.
        document.getElementById('settingsForm').addEventListener('submit', function (e) {
            var invalid = this.querySelector('[required]:invalid');
            if (!invalid) return;
            e.preventDefault();
            e.stopImmediatePropagation();
            var pane = invalid.closest('.tab-pane');
            var btn = pane && document.querySelector('[data-bs-target="#' + pane.id + '"]');
            if (btn && window.bootstrap) bootstrap.Tab.getOrCreateInstance(btn).show();
            invalid.classList.add('is-invalid');
            setTimeout(function () { invalid.focus(); invalid.reportValidity(); }, 150);
        }, true);
    })();
</script>
@endpush
