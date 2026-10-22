@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">
                    <i class="fa-solid fa-pencil text-primary me-2"></i> Edit Transaksi: {{ $transaction->transaction_number }}
                </h3>
                <p class="text-muted mb-0 font-sm">
                    Perbarui data transaksi keuangan, rincian biaya, atau bukti nota pembayaran.
                </p>
            </div>
            <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary">
                <i class="fa-solid fa-arrow-left me-1.5"></i> Kembali
            </a>
        </div>

        @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
            <h6 class="fw-bold mb-1"><i class="fa-solid fa-circle-exclamation me-1.5"></i> Terjadi Kesalahan Input:</h6>
            <ul class="mb-0 ps-3 font-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <form action="{{ route('transactions.update', $transaction->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <!-- Section 1: Informasi Dasar Transaksi -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-dark text-white py-3 px-4 rounded-top-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold"><i class="fa-solid fa-file-invoice me-2 text-primary"></i> 1. Rincian Transaksi</span>
                        <span class="badge bg-secondary font-mono">{{ $transaction->transaction_number }}</span>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <!-- Tanggal Transaksi -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-dark font-sm">Tanggal Transaksi <span class="text-danger">*</span></label>
                            <input type="date" name="transaction_date" class="form-control" value="{{ old('transaction_date', $transaction->transaction_date->format('Y-m-d')) }}" required>
                        </div>

                        <!-- Metode Pembayaran -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-dark font-sm">Metode Pembayaran <span class="text-danger">*</span></label>
                            <select name="payment_method" class="form-select" required>
                                <option value="Bank Transfer" {{ old('payment_method', $transaction->payment_method) == 'Bank Transfer' ? 'selected' : '' }}>Bank Transfer (BCA / Mandiri)</option>
                                <option value="Cash" {{ old('payment_method', $transaction->payment_method) == 'Cash' ? 'selected' : '' }}>Cash / Kas Tunai</option>
                                <option value="e-Wallet" {{ old('payment_method', $transaction->payment_method) == 'e-Wallet' ? 'selected' : '' }}>e-Wallet / QRIS</option>
                            </select>
                        </div>

                        <!-- Kategori Akun (COA) -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-dark font-sm">Kategori Akun (COA) <span class="text-danger">*</span></label>
                            <select name="category_id" id="categorySelect" class="form-select" required onchange="onCategoryChange()">
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" 
                                        data-type="{{ $cat->type }}"
                                        data-code="{{ $cat->code }}"
                                        data-deductible="{{ $cat->is_tax_deductible ? '1' : '0' }}"
                                        {{ old('category_id', $transaction->category_id) == $cat->id ? 'selected' : '' }}>
                                        [{{ $cat->code }}] {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div id="categoryBadgeInfo" class="mt-2 font-xs"></div>
                        </div>

                        <!-- Nominal Transaksi -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark font-sm">Nominal Jumlah (Rp) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light fw-bold">Rp</span>
                                <input type="number" name="amount" id="amountInput" class="form-control form-control-lg fw-bold" min="1" step="any" value="{{ old('amount', $transaction->amount) }}" required>
                            </div>
                        </div>

                        <!-- Deskripsi / Keterangan -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark font-sm">Keterangan / Uraian Transaksi</label>
                            <textarea name="description" class="form-control" rows="2">{{ old('description', $transaction->description) }}</textarea>
                        </div>

                        <!-- Upload Bukti Struk/Nota -->
                        <div class="col-md-12">
                            <label class="form-label fw-bold text-dark font-sm">Ganti / Upload Bukti Struk / Nota</label>
                            @if($transaction->receipt_file_path)
                                <div class="mb-2 p-2 bg-light border rounded d-flex align-items-center justify-content-between">
                                    <span class="font-sm text-dark">
                                        <i class="fa-solid fa-file-invoice text-success me-1"></i> Bukti tersimpan: {{ basename($transaction->receipt_file_path) }}
                                    </span>
                                    <a href="{{ route('transactions.receipt', $transaction->id) }}" target="_blank" class="btn btn-sm btn-outline-primary py-0.5 px-2 font-xs">
                                        <i class="fa-solid fa-eye me-1"></i> Pratinjau
                                    </a>
                                </div>
                            @endif
                            <input type="file" name="receipt_file" class="form-control" accept="image/*,.pdf">
                            <small class="text-muted font-xs">Biarkan kosong jika tidak ingin mengubah berkas bukti nota.</small>
                        </div>

                        @if(auth()->user()->hasAccess('invoice_margin'))
                            @include('transactions.partials.invoice-select', [
                                'invoices' => $invoices,
                                'selectedInvoiceId' => old('invoice_id', $transaction->invoice_id),
                            ])
                        @endif
                        @if(auth()->user()->hasAccess('projects'))
                            @include('transactions.partials.project-select', [
                                'projects' => $projects,
                                'selectedProjectId' => old('project_id', $transaction->project_id),
                            ])
                        @endif
                    </div>
                </div>
            </div>

            <!-- Section 2: Khusus Lampiran Nominatif Entertainment (DJP SPT Tahunan) -->
            <div id="entertainmentSection" class="card border-0 shadow-sm mb-4 border-start border-4 border-warning" style="display: none;">
                <div class="card-header bg-warning bg-opacity-10 py-3 px-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-dark">
                            <i class="fa-solid fa-champagne-glasses text-warning me-2"></i> 2. Lampiran Khusus: Daftar Nominatif Entertainment (DJP)
                        </span>
                        <span class="badge bg-warning text-dark font-xs">Wajib Menurut Peraturan Dirjen Pajak</span>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-dark font-sm">Tanggal Acara / Kegiatan</label>
                            <input type="date" name="event_date" class="form-control" value="{{ old('event_date', $transaction->entertainmentDetail?->event_date ? $transaction->entertainmentDetail->event_date->format('Y-m-d') : date('Y-m-d')) }}">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold text-dark font-sm">Tempat / Lokasi Jamuan</label>
                            <input type="text" name="location" class="form-control" value="{{ old('location', $transaction->entertainmentDetail?->location) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-dark font-sm">Nama Relasi / Pihak Ketiga</label>
                            <input type="text" name="attendee_name" id="attendee_name" class="form-control" value="{{ old('attendee_name', $transaction->entertainmentDetail?->attendee_name) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-dark font-sm">Perusahaan / Instansi Relasi</label>
                            <input type="text" name="attendee_company" class="form-control" value="{{ old('attendee_company', $transaction->entertainmentDetail?->attendee_company) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-dark font-sm">Posisi / Jabatan</label>
                            <input type="text" name="attendee_position" class="form-control" value="{{ old('attendee_position', $transaction->entertainmentDetail?->attendee_position) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold text-dark font-sm">Tujuan Acara / Hubungan Bisnis</label>
                            <input type="text" name="purpose" class="form-control" value="{{ old('purpose', $transaction->entertainmentDetail?->purpose) }}">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="text-end mb-5">
                <a href="{{ route('transactions.index') }}" class="btn btn-secondary px-4 me-2">Batal</a>
                <button type="submit" class="btn btn-primary px-5 shadow-sm">
                    <i class="fa-solid fa-save me-1.5"></i> Perbarui Transaksi
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function onCategoryChange() {
        var selectedOpt = $('#categorySelect').find(':selected');
        var code = selectedOpt.data('code');
        var isDeductible = selectedOpt.data('deductible');
        var catType = selectedOpt.data('type');

        if (!selectedOpt.val()) {
            $('#categoryBadgeInfo').html('');
            $('#entertainmentSection').slideUp();
            return;
        }

        if (catType === 'expense') {
            if (isDeductible == '1') {
                $('#categoryBadgeInfo').html('<span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="fa-solid fa-check me-1"></i> Beban Fiskal (Deductible)</span>');
            } else {
                $('#categoryBadgeInfo').html('<span class="badge bg-warning-subtle text-dark border border-warning-subtle"><i class="fa-solid fa-triangle-exclamation me-1 text-warning"></i> Koreksi Fiskal Positif (Non-Deductible)</span>');
            }
        } else {
            $('#categoryBadgeInfo').html('<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fa-solid fa-arrow-down-left me-1"></i> Pendapatan Usaha</span>');
        }

        if (code === '5-201') {
            $('#entertainmentSection').slideDown();
        } else {
            $('#entertainmentSection').slideUp();
        }
    }

    $(document).ready(function() {
        onCategoryChange();
        $('.select2-invoice').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: '-- Umum / Operasional (tidak terikat invoice) --',
            allowClear: true
        });
        $('.select2-project').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: '-- Umum / Operasional (tidak terikat project) --',
            allowClear: true
        });
    });
</script>
@endpush
