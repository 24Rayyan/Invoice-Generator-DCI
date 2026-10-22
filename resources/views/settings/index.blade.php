@extends('layouts.app')

@section('content')
<!-- Header Page & Breadcrumb -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none text-muted small">Dashboard</a></li>
                <li class="breadcrumb-item active small fw-semibold" aria-current="page">Pengaturan Sistem</li>
            </ol>
        </nav>
        <h3 class="fw-bold text-dark mb-0 tracking-tight">Pusat Konfigurasi Mini ERP</h3>
        <p class="text-muted small mb-0">Kelola identitas perusahaan, format nomor dokumen, perpajakan, pejabat penandatangan, dan template catatan penagihan.</p>
    </div>
</div>

<form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="card border-0 shadow-sm overflow-hidden mb-5">
        
        <!-- Tab Navigation Bar -->
        <div class="card-header bg-white border-bottom p-0">
            <ul class="nav nav-tabs nav-fill border-0 settings-nav-tabs" id="settingTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active py-3 px-3 fw-bold small text-start text-md-center" id="company-tab" data-bs-toggle="tab" data-bs-target="#tab-company" type="button" role="tab">
                        <i class="fa-solid fa-building me-2 text-primary"></i> 1. Profil & Identitas
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-3 px-3 fw-bold small text-start text-md-center" id="numbering-tab" data-bs-toggle="tab" data-bs-target="#tab-numbering" type="button" role="tab">
                        <i class="fa-solid fa-hashtag me-2 text-info"></i> 2. Penomoran Dokumen
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-3 px-3 fw-bold small text-start text-md-center" id="tax-tab" data-bs-toggle="tab" data-bs-target="#tab-tax" type="button" role="tab">
                        <i class="fa-solid fa-receipt me-2 text-success"></i> 3. Pajak & Finansial
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-3 px-3 fw-bold small text-start text-md-center" id="signatory-tab" data-bs-toggle="tab" data-bs-target="#tab-signatory" type="button" role="tab">
                        <i class="fa-solid fa-signature me-2 text-warning"></i> 4. Otorisasi & TTD
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-3 px-3 fw-bold small text-start text-md-center" id="notes-tab" data-bs-toggle="tab" data-bs-target="#tab-notes" type="button" role="tab">
                        <i class="fa-solid fa-file-lines me-2 text-secondary"></i> 5. Catatan & Syarat
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-4 p-lg-5">

            <!-- BLOK PESAN ERROR VALIDASI -->
            @if ($errors->any())
                <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger rounded-4 mb-4 p-4">
                    <div class="d-flex align-items-start">
                        <i class="fa-solid fa-triangle-exclamation fs-4 me-3 mt-1"></i>
                        <div>
                            <h6 class="fw-bold mb-1">Terdapat kesalahan pengisian formulir:</h6>
                            <ul class="mb-0 ps-3 small">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Tab Contents -->
            <div class="tab-content" id="settingTabsContent">
                
                <!-- TAB 1: Detail & Profil Perusahaan -->
                <div class="tab-pane fade show active" id="tab-company" role="tabpanel">
                    <div class="d-flex align-items-center gap-2 mb-4 pb-2 border-bottom">
                        <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-3">
                            <i class="fa-solid fa-building fs-5"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-0">Identitas & Legalitas Usaha</h5>
                            <small class="text-muted">Informasi resmi perusahaan yang tercantum pada seluruh dokumen transaksi dan laporan keuangan.</small>
                        </div>
                    </div>

                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark small">Nama Resmi Perusahaan <span class="text-danger">*</span></label>
                            <input type="text" name="company_name" class="form-control" value="{{ old('company_name', $setting->company_name ?? '') }}" required placeholder="Contoh: PT Sinar Abadi">
                            <small class="text-muted font-xs">Nama entitas bisnis resmi yang akan dicetak di Kop Surat & Laporan.</small>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-primary small">
                                <i class="fa-solid fa-file-invoice me-1"></i> NPWP 16 Digit Penjual (Coretax DJP)
                            </label>
                            <input type="text" name="company_npwp16" class="form-control font-monospace fw-bold text-primary" maxlength="16" value="{{ old('company_npwp16', $setting->company_npwp16 ?? '') }}" placeholder="16 digit angka (misal: 0123456789012345)">
                            <small class="text-muted font-xs">NPWP 16 digit format Coretax DJP terbaru.</small>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-primary small">
                                <i class="fa-solid fa-building-circle-check me-1"></i> NITKU 22 Digit Penjual (Coretax DJP)
                            </label>
                            <input type="text" name="company_nitku" class="form-control font-monospace fw-bold text-primary" maxlength="22" value="{{ old('company_nitku', $setting->company_nitku ?? '') }}" placeholder="22 digit NITKU">
                            <small class="text-muted font-xs">Isi sesuai NITKU resmi perusahaan.</small>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-dark small">Email Resmi Perusahaan</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-envelope"></i></span>
                                <input type="email" name="company_email" class="form-control" value="{{ old('company_email', $setting->company_email ?? '') }}" placeholder="finance@perusahaan.com">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-dark small">Telepon / WhatsApp</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-phone"></i></span>
                                <input type="text" name="company_phone" class="form-control" value="{{ old('company_phone', $setting->company_phone ?? '') }}" placeholder="08123456789 / (022) 123456">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark small">Kota Domisili Kantor</label>
                            <input type="text" name="company_city" class="form-control" value="{{ old('company_city', $setting->company_city ?? '') }}" placeholder="Contoh: Jakarta">
                            <small class="text-muted font-xs">Digunakan otomatis sebagai kota penanggalan kwitansi dan surat resmi.</small>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold text-dark small">Alamat Lengkap Kantor & Operasional</label>
                            <textarea name="company_address" class="form-control" rows="3" placeholder="Masukkan alamat lengkap kantor, jalan, nomor, kecamatan, kota, dan kode pos...">{{ old('company_address', $setting->company_address ?? '') }}</textarea>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2 mt-5 mb-4 pb-2 border-bottom">
                        <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-3">
                            <i class="fa-solid fa-image fs-5"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-0">Logo & Cap Stempel Resmi</h5>
                            <small class="text-muted">Logo perusahaan yang digunakan pada dokumen cetak.</small>
                        </div>
                    </div>

                    <div class="row g-4">
                        <!-- Upload Logo -->
                        <div class="col-md-6">
                            <div class="card h-100 bg-light border p-3">
                                <label class="form-label fw-bold text-dark small mb-1">Logo Utama Perusahaan</label>
                                <p class="text-muted font-xs mb-3">Format PNG/JPG transparan (Resolusi tinggi, maksimal 2MB).</p>
                                <input type="file" name="company_logo" class="form-control bg-white" accept=".png, .jpg, .jpeg, .webp">
                                
                                @if($setting && $setting->company_logo)
                                    <div class="mt-3 p-3 bg-white rounded-3 border d-flex align-items-center gap-3">
                                        <img src="{{ asset('storage/' . $setting->company_logo) }}" alt="Logo" class="img-fluid" style="max-height: 50px; max-width: 140px; object-fit: contain;">
                                        <div>
                                            <span class="badge bg-success bg-opacity-10 text-success fw-bold font-xs">Logo Terpasang</span>
                                            <small class="text-muted d-block font-xs mt-1">Muncul di Header PDF & Navbar</small>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: Penomoran Dokumen Otomatis -->
                <div class="tab-pane fade" id="tab-numbering" role="tabpanel">
                    <div class="d-flex align-items-center gap-2 mb-4 pb-2 border-bottom">
                        <div class="bg-info bg-opacity-10 text-info p-2 rounded-3">
                            <i class="fa-solid fa-hashtag fs-5"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-0">Format & Monitoring Penomoran Dokumen</h5>
                            <small class="text-muted">Sistem secara cerdas membuat nomor urut berurutan secara otomatis berdasarkan kode prefix yang Anda tetapkan.</small>
                        </div>
                    </div>

                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark small">Prefix Penjualan <span class="text-danger">*</span></label>
                            <input type="text" name="invoice_prefix" class="form-control fw-bold font-monospace text-primary" value="{{ old('invoice_prefix', $setting->invoice_prefix ?? 'INV') }}" required placeholder="INV">
                            <small class="text-muted font-xs">Contoh: <code>001/<strong>{{ $setting->invoice_prefix ?: 'INV' }}</strong>/{{ $romanMonth }}/{{ $currentYear }}</code></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark small">Prefix Purchase Order (PO) <span class="text-danger">*</span></label>
                            <input type="text" name="po_prefix" class="form-control fw-bold font-monospace text-info" value="{{ old('po_prefix', $setting->po_prefix ?? 'PO') }}" required placeholder="PO">
                            <small class="text-muted font-xs">Contoh: <code>001/<strong>PO</strong>/{{ $romanMonth }}/{{ $currentYear }}</code></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark small">Prefix Surat Jalan (Delivery Note)</label>
                            <input type="text" name="delivery_note_prefix" class="form-control fw-bold font-monospace text-dark" value="{{ old('delivery_note_prefix', $setting->delivery_note_prefix ?? 'SJ') }}" placeholder="SJ">
                            <small class="text-muted font-xs">Contoh: <code>001/<strong>{{ $setting->delivery_note_prefix ?: 'SJ' }}</strong>/{{ $romanMonth }}/{{ $currentYear }}</code></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark small">Prefix Kwitansi Pembayaran</label>
                            <input type="text" name="kwitansi_prefix" class="form-control fw-bold font-monospace text-dark" value="{{ old('kwitansi_prefix', $setting->kwitansi_prefix ?? 'KWT') }}" placeholder="KWT">
                            <small class="text-muted font-xs">Contoh: <code>001/<strong>{{ $setting->kwitansi_prefix ?: 'KWT' }}</strong>/{{ $romanMonth }}/{{ $currentYear }}</code></small>
                        </div>
                    </div>

                    <!-- Live Monitoring Box -->
                    <div class="row g-4 mt-2">
                        <div class="col-md-6">
                            <div class="card border-0 bg-light rounded-3 p-4 h-100">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="fw-bold text-success"><i class="fa-solid fa-file-invoice me-1"></i> MONITORING INVOICE</span>
                                    <span class="badge bg-success bg-opacity-10 text-success fw-bold font-xs">Auto Sequence</span>
                                </div>
                                <div class="p-3 bg-white rounded-3 border">
                                    <span class="text-muted d-block font-xs">Invoice Terakhir Bulan Ini ({{ $romanMonth }}/{{ $currentYear }}):</span>
                                    <strong class="text-dark fs-6 font-monospace">{{ $lastInvoice->document_number ?? 'Belum ada transaksi bulan ini' }}</strong>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card border-0 bg-light rounded-3 p-4 h-100">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="fw-bold text-info"><i class="fa-solid fa-cart-shopping me-1"></i> MONITORING PO</span>
                                    <span class="badge bg-info bg-opacity-10 text-info fw-bold font-xs">Auto Sequence</span>
                                </div>
                                <div class="p-3 bg-white rounded-3 border">
                                    <span class="text-muted d-block font-xs">PO Terakhir Bulan Ini ({{ $romanMonth }}/{{ $currentYear }}):</span>
                                    <strong class="text-dark fs-6 font-monospace">{{ $lastPO->document_number ?? 'Belum ada transaksi bulan ini' }}</strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0 bg-light rounded-3 p-4 h-100">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="fw-bold text-warning"><i class="fa-solid fa-truck-fast me-1"></i> MONITORING SURAT JALAN</span>
                                    <span class="badge bg-warning bg-opacity-10 text-dark fw-bold font-xs">Auto Sequence</span>
                                </div>
                                <div class="p-3 bg-white rounded-3 border">
                                    <span class="text-muted d-block font-xs">Surat Jalan Terakhir Bulan Ini ({{ $romanMonth }}/{{ $currentYear }}):</span>
                                    <strong class="text-dark fs-6 font-monospace">{{ $lastDeliveryNote->delivery_note_number ?? 'Belum ada transaksi bulan ini' }}</strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0 bg-light rounded-3 p-4 h-100">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="fw-bold text-secondary"><i class="fa-solid fa-receipt me-1"></i> MONITORING KWITANSI</span>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary fw-bold font-xs">Auto Sequence</span>
                                </div>
                                <div class="p-3 bg-white rounded-3 border">
                                    <span class="text-muted d-block font-xs">Kwitansi Terakhir Bulan Ini ({{ $romanMonth }}/{{ $currentYear }}):</span>
                                    <strong class="text-dark fs-6 font-monospace">{{ $lastReceipt->receipt_number ?? 'Belum ada transaksi bulan ini' }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert bg-primary bg-opacity-10 border-0 rounded-3 mt-4 p-3 d-flex align-items-center">
                        <i class="fa-solid fa-circle-info text-primary fs-5 me-3"></i>
                        <div class="small">
                            <strong>Aturan Siklus Penomoran Otomatis:</strong> Penomoran dokumen akan otomatis di-reset ke <code>001</code> pada awal setiap pergantian bulan kalender baru dengan format angka Romawi.
                        </div>
                    </div>
                </div>

                <!-- TAB 3: Pajak & Finansial -->
                <div class="tab-pane fade" id="tab-tax" role="tabpanel">
                    <div class="d-flex align-items-center gap-2 mb-4 pb-2 border-bottom">
                        <div class="bg-success bg-opacity-10 text-success p-2 rounded-3">
                            <i class="fa-solid fa-receipt fs-5"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-0">Perpajakan & Parameter Finansial</h5>
                            <small class="text-muted">Konfigurasi tarif PPN, mata uang default, masa jatuh tempo, dan rekening bank penagihan.</small>
                        </div>
                    </div>

                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-dark small">Tarif Default PPN (%) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="default_tax_rate" class="form-control fw-bold text-success" value="{{ old('default_tax_rate', $setting->default_tax_rate ?? 11) }}" required min="0" max="100">
                                <span class="input-group-text bg-light text-muted fw-bold">%</span>
                            </div>
                            <small class="text-muted font-xs">Tarif standar PPN (11% atau 12% sesuai regulasi DJP).</small>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-dark small">Masa Jatuh Tempo (Term of Payment)</label>
                            <div class="input-group">
                                <input type="number" name="term_of_payment" class="form-control fw-bold text-primary" value="{{ old('term_of_payment', $setting->term_of_payment ?? 30) }}" min="0">
                                <span class="input-group-text bg-light text-muted fw-bold">Hari</span>
                            </div>
                            <small class="text-muted font-xs">Menentukan otomatis tanggal <em>Due Date</em> pada invoice.</small>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label fw-semibold text-dark small">Simbol Mata Uang</label>
                            <input type="text" name="currency_symbol" class="form-control font-monospace fw-bold" value="{{ old('currency_symbol', $setting->currency_symbol ?? 'Rp') }}" placeholder="Rp">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label fw-semibold text-dark small">Kode Mata Uang</label>
                            <input type="text" name="currency_code" class="form-control font-monospace fw-bold" value="{{ old('currency_code', $setting->currency_code ?? 'IDR') }}" placeholder="IDR">
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch p-3 bg-light rounded-3 border">
                                <input class="form-check-input ms-0 me-3" type="checkbox" name="enable_tax" id="enable_tax" value="1" {{ old('enable_tax', $setting->enable_tax ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold text-dark" for="enable_tax">
                                    Aktifkan Perhitungan Pajak PPN Otomatis pada Faktur Baru
                                </label>
                                <small class="text-muted d-block mt-1">Jika dinonaktifkan, pembuatan dokumen faktur baru akan dihitung tanpa PPN (0%).</small>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold text-dark small">Informasi Rekening Bank & Petunjuk Transfer (Untuk Invoice)</label>
                            <textarea name="company_bank_account" class="form-control" rows="3" placeholder="Contoh:&#10;Bank: [Nama Bank]&#10;No. Rekening: [Nomor Rekening]&#10;Atas nama: [Nama Pemilik Rekening]">{{ old('company_bank_account', $setting->company_bank_account ?? '') }}</textarea>
                            <small class="text-muted font-xs">Instruksi ini akan dicetak di kotak <strong>Catatan Pembayaran</strong> pada PDF Invoice.</small>
                        </div>
                    </div>
                </div>

                <!-- TAB 4: Otorisasi & Pejabat Penandatangan -->
                <div class="tab-pane fade" id="tab-signatory" role="tabpanel">
                    <div class="d-flex align-items-center gap-2 mb-4 pb-2 border-bottom">
                        <div class="bg-warning bg-opacity-10 text-warning p-2 rounded-3">
                            <i class="fa-solid fa-signature fs-5"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-0">Pejabat Penandatangan & Otorisasi Dokumen</h5>
                            <small class="text-muted">Data pimpinan atau manajer yang namanya dicetak pada kolom pengesahan dokumen resmi.</small>
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-5">
                            <label class="form-label fw-semibold text-dark small">Nama Lengkap Pejabat Penandatangan</label>
                            <input type="text" name="signatory_name" class="form-control fw-bold" value="{{ old('signatory_name', $setting->signatory_name ?? '') }}" placeholder="Nama lengkap pejabat">
                            <small class="text-muted font-xs">Nama terang yang tertera di atas kolom tanda tangan.</small>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-dark small">Jabatan Resmi</label>
                            <input type="text" name="signatory_position" class="form-control" value="{{ old('signatory_position', $setting->signatory_position ?? '') }}" placeholder="Contoh: Direktur / Manajer Keuangan">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold text-dark small">Kota Tanda Tangan</label>
                            <input type="text" name="signatory_city" class="form-control" value="{{ old('signatory_city', $setting->signatory_city ?? '') }}" placeholder="Contoh: Jakarta">
                        </div>

                        <div class="col-12">
                            <div class="card border rounded-3 p-4">
                                <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-stamp text-primary me-2"></i> Stempel Perusahaan pada Dokumen Cetak</h6>
                                <p class="text-muted small">Unggah file stempel satu kali, lalu pilih pada dokumen mana stempel akan dicetak. Status awal: Invoice dan Surat Jalan aktif; Kwitansi nonaktif.</p>
                                <div class="row g-3 align-items-center mb-3">
                                    <div class="col-md-7">
                                        <label class="form-label fw-semibold text-dark small">File Stempel</label>
                                        <input type="file" name="company_stamp" class="form-control" accept=".png,.jpg,.jpeg,.webp">
                                        <small class="text-muted">PNG transparan disarankan; maksimal 2MB.</small>
                                    </div>
                                    <div class="col-md-5">
                                        @if($setting && $setting->company_stamp)
                                            <div class="p-2 bg-light rounded-3 d-flex align-items-center gap-3">
                                                <img src="{{ asset('storage/' . $setting->company_stamp) }}" alt="Pratinjau stempel perusahaan" style="max-width: 90px; max-height: 65px; object-fit: contain;">
                                                <span class="badge bg-success bg-opacity-10 text-success">Stempel tersimpan</span>
                                            </div>
                                        @else
                                            <span class="text-muted small">Belum ada file stempel.</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="row g-3">
                                    @foreach([
                                        ['stamp_invoice_enabled', 'stamp-invoice', 'Invoice', true],
                                        ['stamp_delivery_note_enabled', 'stamp-delivery-note', 'Surat Jalan', true],
                                        ['stamp_receipt_enabled', 'stamp-receipt', 'Kwitansi', false],
                                    ] as [$field, $id, $label, $default])
                                        <div class="col-md-4">
                                            <div class="form-check form-switch p-3 bg-light rounded-3 border">
                                                <input class="form-check-input ms-0 me-2" type="checkbox" name="{{ $field }}" id="{{ $id }}" value="1" {{ old($field, $setting->{$field} ?? $default) ? 'checked' : '' }}>
                                                <label class="form-check-label fw-semibold" for="{{ $id }}">Cetak stempel pada {{ $label }}</label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="card bg-light border-0 rounded-3 p-4 mt-3">
                                <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-circle-check text-success me-2"></i> Integrasi Tanda Tangan Otomatis</h6>
                                <p class="text-muted small mb-0">
                                    PDF Invoice, Kwitansi, dan Surat Jalan menggunakan nama, jabatan, dan kota tanda tangan yang Anda isi di atas. PO tidak menampilkan stempel perusahaan.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 5: Syarat Ketentuan & Catatan Standar -->
                <div class="tab-pane fade" id="tab-notes" role="tabpanel">
                    <div class="d-flex align-items-center gap-2 mb-4 pb-2 border-bottom">
                        <div class="bg-secondary bg-opacity-10 text-secondary p-2 rounded-3">
                            <i class="fa-solid fa-file-lines fs-5"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-0">Template Catatan & Syarat Ketentuan Standar</h5>
                            <small class="text-muted">Teks baku yang otomatis diisi ketika membuat invoice, kwitansi, atau surat jalan baru.</small>
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-12">
                            <label class="form-label fw-semibold text-dark small">Catatan Default Footer Invoice</label>
                            <textarea name="invoice_footer_notes" class="form-control" rows="2" placeholder="Contoh: Barang yang sudah dibeli tidak dapat dikembalikan. Pembayaran dianggap sah jika bukti transfer telah dikonfirmasi.">{{ old('invoice_footer_notes', $setting->invoice_footer_notes ?? '') }}</textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold text-dark small">Keterangan Default Kwitansi Pembayaran</label>
                            <textarea name="receipt_footer_notes" class="form-control" rows="2" placeholder="Contoh: Pembayaran Pelunasan Tagihan Dokumen Invoice">{{ old('receipt_footer_notes', $setting->receipt_footer_notes ?? '') }}</textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold text-dark small">Catatan Default Surat Jalan</label>
                            <textarea name="delivery_note_footer_notes" class="form-control" rows="2" placeholder="Contoh: Mohon periksa kembali kelengkapan barang sebelum menandatangani bukti penerimaan ini.">{{ old('delivery_note_footer_notes', $setting->delivery_note_footer_notes ?? '') }}</textarea>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Sticky/Action Footer -->
            <div class="mt-5 pt-4 border-top d-flex justify-content-between align-items-center">
                <span class="text-muted font-xs">
                    <i class="fa-solid fa-shield-halved text-success me-1"></i> Seluruh konfigurasi tersimpan aman di basis data ERP
                </span>
                <button type="submit" class="btn btn-primary px-4 py-2.5 shadow-sm fw-bold">
                    <i class="fa-solid fa-floppy-disk me-1.5"></i> Simpan Semua Konfigurasi
                </button>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<style>
    .settings-nav-tabs .nav-link {
        color: #64748b;
        border: none;
        border-bottom: 2px solid transparent;
        transition: all 0.2s ease;
    }
    .settings-nav-tabs .nav-link:hover {
        color: #1e40af;
        background-color: #f8fafc;
    }
    .settings-nav-tabs .nav-link.active {
        color: #1e40af !important;
        border-bottom: 2px solid #1e40af !important;
        background-color: transparent !important;
    }
    .font-xs {
        font-size: 0.775rem;
    }
</style>
@endpush