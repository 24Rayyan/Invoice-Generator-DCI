@extends('layouts.app')

@section('content')
<!-- Header Page / Breadcrumb -->
<div class="d-flex justify-content-between align-items-end mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('customers.index') }}" class="text-decoration-none text-muted small">Data Pelanggan</a></li>
                <li class="breadcrumb-item active small fw-semibold" aria-current="page">Tambah Pelanggan</li>
            </ol>
        </nav>
        <h1 class="h3 fw-bold text-dark mb-0 tracking-tight">Tambah Pelanggan Baru</h1>
    </div>
    <div>
        <a href="{{ route('customers.index') }}" class="btn btn-light border btn-sm px-3">
            <i class="fa-solid fa-arrow-left me-1"></i> Batal & Kembali
        </a>
    </div>
</div>

<div class="row">
    <!-- Form Section -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-body p-lg-5 p-4">
                
                <!-- BLOK PESAN ERROR VALIDASI -->
                @if ($errors->any())
                    <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger rounded-3 mb-4 p-3">
                        <h6 class="fw-bold mb-1"><i class="fa-solid fa-triangle-exclamation me-1"></i> Terjadi kesalahan input:</h6>
                        <ul class="mb-0 ps-3 small">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('customers.store') }}" method="POST">
                    @csrf
                    
                    <!-- Section: Informasi Utama -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                            <span class="badge bg-primary bg-opacity-10 text-primary p-2 rounded-2"><i class="fa-solid fa-building"></i></span>
                            <h6 class="fw-bold text-dark mb-0">Informasi Profil & Kontak</h6>
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold text-dark small">Nama Perusahaan / Personal <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="Contoh: PT Mitra Solusi Global" value="{{ old('name') }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark small">Alamat Email</label>
                                <input type="email" name="email" class="form-control" placeholder="finance@customer.com" value="{{ old('email') }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark small">No. WhatsApp / Telepon</label>
                                <input type="text" name="phone" class="form-control" placeholder="081234567890" value="{{ old('phone') }}">
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold text-dark small">Alamat Lengkap Penagihan</label>
                                <textarea name="address" class="form-control" rows="2" placeholder="Masukkan nama jalan, gedung, kota, kode pos...">{{ old('address') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Data Perpajakan Coretax DJP -->
                    <div class="mb-4 mt-4">
                        <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                            <span class="badge bg-success bg-opacity-10 text-success p-2 rounded-2"><i class="fa-solid fa-receipt"></i></span>
                            <h6 class="fw-bold text-dark mb-0">Identitas Perpajakan Lawan Transaksi (Coretax DJP)</h6>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-dark small">Jenis ID Pembeli <span class="text-danger">*</span></label>
                                <select name="tax_id_type" class="form-select" required>
                                    <option value="NPWP16" {{ old('tax_id_type', 'NPWP16') == 'NPWP16' ? 'selected' : '' }}>NPWP 16 Digit (TIN)</option>
                                    <option value="NIK" {{ old('tax_id_type') == 'NIK' ? 'selected' : '' }}>NIK KTP (National ID)</option>
                                    <option value="PASPOR" {{ old('tax_id_type') == 'PASPOR' ? 'selected' : '' }}>Paspor (Passport)</option>
                                </select>
                                <small class="text-muted font-xs">Sesuai aturan Coretax DJP.</small>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-dark small">Nomor ID (NPWP16 / NIK / Paspor)</label>
                                <input type="text" name="tax_id_number" class="form-control font-monospace fw-bold" placeholder="16 digit angka / No Paspor" value="{{ old('tax_id_number') }}">
                                <small class="text-muted font-xs">NPWP 16 digit / NIK KTP.</small>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-dark small">NITKU (22 Digit)</label>
                                <input type="text" name="nitku" class="form-control font-monospace fw-bold" maxlength="22" placeholder="22 digit angka" value="{{ old('nitku', '0000000000000000000000') }}">
                                <small class="text-muted font-xs">Default: 22 digit 0 jika pusat.</small>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold text-dark small">Default Kode Transaksi Faktur Pajak PPN <span class="text-danger">*</span></label>
                                <select name="default_tax_transaction_code" class="form-select fw-semibold" required>
                                    <option value="040" {{ old('default_tax_transaction_code', '040') == '040' ? 'selected' : '' }}>040: DPP Nilai Lain (Default Utama Jasa/AC/Freight/Lainnya)</option>
                                    <option value="020" {{ old('default_tax_transaction_code') == '020' ? 'selected' : '' }}>020: Pemungut Bendaharawan Pemerintah (WAPU)</option>
                                    <option value="010" {{ old('default_tax_transaction_code') == '010' ? 'selected' : '' }}>010: Penyerahan BKP / JKP kepada Selain Pemungut PPN (Umum)</option>
                                    <option value="030" {{ old('default_tax_transaction_code') == '030' ? 'selected' : '' }}>030: Pemungut PPN BUMN / Badan Usaha Tertentu</option>
                                    <option value="050" {{ old('default_tax_transaction_code') == '050' ? 'selected' : '' }}>050: Penyerahan dengan Besaran Tertentu (Pasal 9A UU PPN)</option>
                                    <option value="070" {{ old('default_tax_transaction_code') == '070' ? 'selected' : '' }}>070: Penyerahan PPN Tidak Dipungut (Fasilitas Khusus/Kawasan Berikat)</option>
                                    <option value="080" {{ old('default_tax_transaction_code') == '080' ? 'selected' : '' }}>080: Penyerahan PPN Dibebaskan</option>
                                    <option value="090" {{ old('default_tax_transaction_code') == '090' ? 'selected' : '' }}>090: Penyerahan Aktiva (Pasal 16D UU PPN)</option>
                                </select>
                                <small class="text-muted font-xs">Setiap invoice baru untuk customer ini akan otomatis menggunakan kode transaksi ini (dapat diubah per invoice).</small>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                        <a href="{{ route('customers.index') }}" class="btn btn-light border px-4">Batal</a>
                        <button type="submit" class="btn btn-primary px-5 shadow-sm fw-bold">
                            <i class="fa-solid fa-floppy-disk me-1.5"></i> Simpan Pelanggan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection