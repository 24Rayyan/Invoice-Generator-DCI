@extends('layouts.app')

@section('content')
<!-- Header Page & Breadcrumb -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('documents.index') }}" class="text-decoration-none text-muted small">Daftar Dokumen</a></li>
                <li class="breadcrumb-item active small fw-semibold" aria-current="page">Buat Dokumen</li>
            </ol>
        </nav>
        <h3 class="fw-bold text-dark mb-0 tracking-tight">Buat Dokumen Transaksi Baru</h3>
    </div>
    <div>
        <a href="{{ route('documents.index') }}" class="btn btn-light border btn-sm px-3">
            <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Daftar
        </a>
    </div>

</div>

<form action="{{ route('documents.store') }}" method="POST">
    @csrf

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4 p-lg-5">

            <!-- Validation Errors -->
            @if ($errors->any())
                <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger rounded-3 mb-4 p-3">
                    <h6 class="fw-bold mb-1"><i class="fa-solid fa-triangle-exclamation me-1"></i> Periksa kembali input formulir:</h6>
                    <ul class="mb-0 ps-3 small">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Section 1: Informasi Dokumen & Coretax -->
            <div class="mb-4">
                <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                    <span class="badge bg-primary bg-opacity-10 text-primary p-2 rounded-2"><i class="fa-solid fa-file-invoice"></i></span>
                    <h6 class="fw-bold text-dark mb-0">Informasi Utama Transaksi & Pajak (Coretax)</h6>
                </div>

                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-dark small">Jenis Dokumen <span class="text-danger">*</span></label>
                        <select name="type" id="document_type" class="form-select" required>
                            <option value="INVOICE" selected>Invoice (Faktur Penjualan)</option>
                            <option value="PO">Purchase Order (PO)</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-dark small">Pilih Customer <span class="text-danger">*</span></label>
                        <select name="customer_id" id="customer_select" class="form-select select2" required>
                            <option value="">-- Pilih Customer --</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" data-tax-code="{{ $c->default_tax_transaction_code ?? '040' }}">
                                    {{ $c->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-dark small">Nomor Dokumen <span class="badge bg-success bg-opacity-10 text-success ms-1 font-xs">Otomatis</span></label>
                        <input type="text" name="document_number" id="document_number" class="form-control fw-bold text-primary font-monospace" value="{{ $autoNumber }}" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-dark small">
                            <i class="fa-solid fa-tag text-primary me-1"></i> Kode Faktur PPN (Coretax)
                        </label>
                        <select name="tax_transaction_code" id="tax_transaction_code" class="form-select fw-semibold">
                            <option value="040">040 — DPP Nilai Lain</option>
                            <option value="020">020 — Pemungut Bendahara (WAPU)</option>
                            <option value="010">010 — Penyerahan Umum (BKP/JKP)</option>
                            <option value="030">030 — Pemungut BUMN</option>
                            <option value="050">050 — Besaran Tertentu</option>
                            <option value="070">070 — PPN Tidak Dipungut</option>
                            <option value="080">080 — PPN Dibebaskan</option>
                            <option value="090">090 — Penyerahan Aktiva (16D)</option>
                        </select>
                        <small class="text-muted font-xs">Otomatis inherit dari customer (dapat diubah).</small>
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <label for="project_id" class="form-label fw-semibold text-dark small">Project (Opsional)</label>
                        <select name="project_id" id="project_id" class="form-select select2">
                            <option value="">-- Tidak terkait project --</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}" {{ old('project_id') == $project->id ? 'selected' : '' }}>
                                    {{ $project->project_name }}{{ $project->client?->name ? ' — ' . $project->client->name : '' }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Invoice ini akan ikut menjadi bagian dari pendapatan project terpilih.</small>
                    </div>
                </div>
            </div>

            <!-- Section 2: Input Barang (Item) -->
            <div class="mb-4 mt-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-info bg-opacity-10 text-info p-2 rounded-2"><i class="fa-solid fa-list-check"></i></span>
                        <h6 class="fw-bold text-dark mb-0">Rincian Barang & Jasa</h6>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm fw-bold" id="add-row">
                        <i class="fa-solid fa-plus me-1"></i> Tambah Item
                    </button>
                </div>

                <div class="table-responsive rounded-3 border">
                    <table class="table table-hover align-middle mb-0" id="item-table">
                        <thead class="table-dark">
                            <tr class="small text-uppercase font-xs">
                                <th width="4%" class="text-center py-2.5">#</th>
                                <th width="28%" class="py-2.5">Nama Barang / Jasa</th>
                                <th width="18%" class="py-2.5">Keterangan</th>
                                <th width="10%" class="py-2.5">QTY</th>
                                <th width="10%" class="py-2.5">Satuan</th>
                                <th width="15%" class="py-2.5">Harga Satuan</th>
                                <th width="12%" class="py-2.5">Subtotal</th>
                                <th width="3%" class="text-center py-2.5"></th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            <tr>
                                <td class="text-center row-number fw-semibold text-muted small">1</td>
                                <td>
                                    <input type="text" name="item_desc[]" class="form-control form-control-sm border-0 bg-light" placeholder="Nama barang / jasa..." required>
                                </td>
                                <td>
                                    <input type="text" name="item_notes[]" class="form-control form-control-sm border-0 bg-light" placeholder="Catatan opsional...">
                                </td>
                                <td>
                                    <input type="number" name="item_qty[]" class="form-control form-control-sm border-0 bg-light item-qty" value="1" min="0" step="any" required>
                                </td>
                                <td>
                                    <input type="text" name="item_unit[]" class="form-control form-control-sm border-0 bg-light" placeholder="Pcs/Unit" value="Pcs" required>
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text border-0 bg-light text-muted">{{ $setting->currency_symbol ?? 'Rp' }}</span>
                                        <input type="number" name="item_price[]" class="form-control border-0 bg-light item-price" required min="0" step="any" placeholder="0">
                                    </div>
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text border-0 bg-light text-muted">{{ $setting->currency_symbol ?? 'Rp' }}</span>
                                        <input type="text" class="form-control border-0 bg-light fw-bold item-subtotal" readonly value="0">
                                    </div>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-link text-danger p-1 remove-row" title="Hapus Baris">
                                        <i class="fa-solid fa-xmark fs-6"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Section 3: Catatan Kwitansi & Diskon -->
            <div class="row g-4 mt-2">
                <div class="col-md-7">
                    <div class="card border bg-light h-100 p-3">
                        <label class="form-label fw-bold text-dark small mb-2">
                            <i class="fa-solid fa-receipt me-1 text-primary"></i> Keterangan Khusus Kwitansi (Opsional)
                        </label>
                        <textarea name="payment_note" class="form-control border-0 bg-white shadow-sm" rows="3" placeholder="{{ $setting->receipt_footer_notes ?? 'Contoh: Pembayaran Pelunasan Tagihan Dokumen Invoice' }}"></textarea>
                        <small class="text-muted mt-2 d-block font-xs">*Kosongkan jika ingin menggunakan format default otomatis.</small>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="card border bg-light h-100 p-3 d-flex flex-column justify-content-center">
                        <label class="form-label fw-bold text-danger small mb-2">
                            <i class="fa-solid fa-tag me-1"></i> Potongan Harga (Diskon)
                        </label>
                        <div class="input-group input-group-lg shadow-sm rounded-3">
                            <span class="input-group-text bg-white border-0 text-muted">{{ $setting->currency_symbol ?? 'Rp' }}</span>
                            <input type="number" name="discount" class="form-control border-0 fw-bold text-danger" value="0" min="0" step="any" placeholder="0">
                        </div>
                        <small class="text-muted mt-2 d-block font-xs">*Pengaturan PPN aktif: <strong>{{ ($setting->enable_tax ?? true) ? ($setting->default_tax_rate ?? 11) . '%' : 'Nonaktif (0%)' }}</strong>.</small>
                    </div>
                </div>
            </div>

            <div class="mt-5 pt-3 border-top d-flex justify-content-end align-items-center gap-2">
                <a href="{{ route('documents.index') }}" class="btn btn-light border px-4">Batal</a>
                <button type="submit" class="btn btn-primary px-5 shadow-sm fw-bold">
                    <i class="fa-solid fa-floppy-disk me-1.5"></i> Simpan Dokumen
                </button>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('.select2').select2({ theme: 'bootstrap-5', width: '100%' });

        // Auto-inherit default tax code from selected customer
        $('#customer_select').on('change', function() {
            let selectedOption = $(this).find('option:selected');
            let taxCode = selectedOption.data('tax-code');
            if (taxCode) {
                $('#tax_transaction_code').val(taxCode);
            }
        });

        function updateRowNumbers() {
            $('#item-table tbody tr').each(function(index) {
                $(this).find('.row-number').text(index + 1);
            });
        }

        function calculateRowSubtotal(row) {
            let qty = parseFloat(row.find('.item-qty').val()) || 0;
            let price = parseFloat(row.find('.item-price').val()) || 0;
            let subtotal = qty * price;
            
            row.find('.item-subtotal').val(new Intl.NumberFormat('id-ID').format(subtotal));
        }

        $('#item-table').on('input', '.item-qty, .item-price', function() {
            let row = $(this).closest('tr');
            calculateRowSubtotal(row);
        });

        $('#add-row').on('click', function() {
            let rowCount = $('#item-table tbody tr').length + 1;
            let newRow = `
                <tr>
                    <td class="text-center row-number fw-semibold text-muted small">${rowCount}</td>
                    <td>
                        <input type="text" name="item_desc[]" class="form-control form-control-sm border-0 bg-light" placeholder="Nama barang / jasa..." required>
                    </td>
                    <td>
                        <input type="text" name="item_notes[]" class="form-control form-control-sm border-0 bg-light" placeholder="Catatan opsional...">
                    </td>
                    <td>
                        <input type="number" name="item_qty[]" class="form-control form-control-sm border-0 bg-light item-qty" value="1" min="0" step="any" required>
                    </td>
                    <td>
                        <input type="text" name="item_unit[]" class="form-control form-control-sm border-0 bg-light" placeholder="Pcs/Unit" value="Pcs" required>
                    </td>
                    <td>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text border-0 bg-light text-muted">{{ $setting->currency_symbol ?? 'Rp' }}</span>
                            <input type="number" name="item_price[]" class="form-control border-0 bg-light item-price" required min="0" step="any" placeholder="0">
                        </div>
                    </td>
                    <td>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text border-0 bg-light text-muted">{{ $setting->currency_symbol ?? 'Rp' }}</span>
                            <input type="text" class="form-control border-0 bg-light fw-bold item-subtotal" readonly value="0">
                        </div>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-link text-danger p-1 remove-row" title="Hapus Baris"><i class="fa-solid fa-xmark fs-6"></i></button>
                    </td>
                </tr>
            `;
            $('#item-table tbody').append(newRow);
            updateRowNumbers();
        });

        $('#item-table').on('click', '.remove-row', function() {
            if($('#item-table tbody tr').length > 1) {
                $(this).closest('tr').remove();
                updateRowNumbers();
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Perhatian',
                    text: 'Minimal harus ada 1 baris item transaksi!',
                    confirmButtonColor: '#1e40af'
                });
            }
        });
    });
</script>
@endpush