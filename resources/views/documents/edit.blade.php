@extends('layouts.app')

@section('content')
<!-- Header Page & Breadcrumb -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route($indexRoute) }}" class="text-decoration-none text-muted small">Daftar {{ $document->type === 'PO' ? 'PO' : 'Invoice' }}</a></li>
                <li class="breadcrumb-item active small fw-semibold" aria-current="page">Edit Dokumen</li>
            </ol>
        </nav>
        <h3 class="fw-bold text-dark mb-0 tracking-tight">Edit {{ $document->type }} <span class="text-primary font-monospace">#{{ $document->document_number }}</span></h3>
    </div>
    <div>
        <a href="{{ route($indexRoute) }}" class="btn btn-light border btn-sm px-3">
            <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Daftar
        </a>
    </div>
</div>

<form action="{{ route('documents.update', $document->id) }}" method="POST">
    @csrf
    @method('PUT')

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
                    <span class="badge bg-primary bg-opacity-10 text-primary p-2 rounded-2"><i class="fa-solid fa-file-pen"></i></span>
                    <h6 class="fw-bold text-dark mb-0">Informasi Utama Transaksi & Pajak (Coretax)</h6>
                </div>

                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-dark small">Jenis Dokumen <span class="text-danger">*</span></label>
                        <select name="type" id="document_type" class="form-select" required>
                            <option value="INVOICE" {{ $document->type == 'INVOICE' ? 'selected' : '' }}>Invoice (Faktur Penjualan)</option>
                            <option value="PO" {{ $document->type == 'PO' ? 'selected' : '' }}>Purchase Order (PO)</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-dark small">Status Dokumen <span class="text-danger">*</span></label>
                        <select name="status" class="form-select fw-semibold" required>
                            <option value="DRAFT" {{ $document->status == 'DRAFT' ? 'selected' : '' }}>DRAFT</option>
                            <option value="SENT" {{ $document->status == 'SENT' ? 'selected' : '' }}>SENT (Terkirim)</option>
                            <option value="PAID" {{ $document->status == 'PAID' ? 'selected' : '' }}>PAID (Lunas)</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="customer_select" class="form-label fw-semibold text-dark small">Pilih Customer <span class="text-danger">*</span></label>
                        <select name="customer_id" id="customer_select" class="form-select customer-select smooth-dropdown" data-placeholder="Pilih customer..." required>
                            <option value=""></option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" data-tax-code="{{ $c->default_tax_transaction_code ?? '040' }}" data-tax-id="{{ $c->tax_id_number }}" data-tax-type="{{ $c->tax_id_type ?? 'NPWP' }}" data-email="{{ $c->email }}" {{ $document->customer_id == $c->id ? 'selected' : '' }}>
                                    {{ $c->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-dark small">Nomor Dokumen <span class="text-danger">*</span></label>
                        <input type="text" name="document_number" class="form-control fw-bold text-primary font-monospace" value="{{ $document->document_number }}" required>
                    </div>

                    @if(auth()->user()->hasAccess('projects'))
                        <div class="col-md-6 invoice-only {{ $document->type === 'PO' ? 'd-none' : '' }}">
                            <label for="project_id" class="form-label fw-semibold text-dark small">Project (Opsional)</label>
                            <select name="project_id" id="project_id" class="form-select select2" {{ $document->type === 'PO' ? 'disabled' : '' }}>
                                <option value="">-- Tidak terkait project --</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}" {{ old('project_id', $document->project_id) == $project->id ? 'selected' : '' }}>
                                        {{ $project->project_name }}{{ $project->client?->name ? ' — ' . $project->client->name : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="col-md-4 invoice-only {{ $document->type === 'PO' ? 'd-none' : '' }}">
                        <label class="form-label fw-semibold text-dark small">
                            <i class="fa-solid fa-tag text-primary me-1"></i> Kode Faktur PPN (Coretax)
                        </label>
                        <select name="tax_transaction_code" id="tax_transaction_code" class="form-select fw-semibold" {{ $document->type === 'PO' ? 'disabled' : '' }}>
                            <option value="040" {{ ($document->tax_transaction_code ?? '040') == '040' ? 'selected' : '' }}>040 — DPP Nilai Lain</option>
                            <option value="020" {{ ($document->tax_transaction_code ?? '') == '020' ? 'selected' : '' }}>020 — Pemungut Bendahara (WAPU)</option>
                            <option value="010" {{ ($document->tax_transaction_code ?? '') == '010' ? 'selected' : '' }}>010 — Penyerahan Umum (BKP/JKP)</option>
                            <option value="030" {{ ($document->tax_transaction_code ?? '') == '030' ? 'selected' : '' }}>030 — Pemungut BUMN</option>
                            <option value="050" {{ ($document->tax_transaction_code ?? '') == '050' ? 'selected' : '' }}>050 — Besaran Tertentu</option>
                            <option value="070" {{ ($document->tax_transaction_code ?? '') == '070' ? 'selected' : '' }}>070 — PPN Tidak Dipungut</option>
                            <option value="080" {{ ($document->tax_transaction_code ?? '') == '080' ? 'selected' : '' }}>080 — PPN Dibebaskan</option>
                            <option value="090" {{ ($document->tax_transaction_code ?? '') == '090' ? 'selected' : '' }}>090 — Penyerahan Aktiva (16D)</option>
                        </select>
                    </div>

                    <div class="col-md-4 invoice-only {{ $document->type === 'PO' ? 'd-none' : '' }}">
                        <label class="form-label fw-semibold text-dark small">Tanggal Faktur Pajak Coretax</label>
                        <input type="date" name="tax_invoice_date" class="form-control" value="{{ $document->tax_invoice_date ?? $document->date }}" {{ $document->type === 'PO' ? 'disabled' : '' }}>
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
                                <th width="20%" class="py-2.5 item-description-heading">Nama Barang / Jasa</th>
                                <th width="16%" class="py-2.5 item-specification-heading">Keterangan</th>
                                <th width="7%" class="py-2.5 item-quantity-heading">QTY</th>
                                <th width="12%" class="py-2.5 item-type-column {{ $document->type === 'PO' ? 'd-none' : '' }}">Tipe</th>
                                <th width="9%" class="py-2.5 item-unit-column {{ $document->type === 'PO' ? 'd-none' : '' }}">Satuan</th>
                                <th width="14%" class="py-2.5 item-price-column {{ $document->type === 'PO' ? 'd-none' : '' }}">Harga Satuan</th>
                                <th width="16%" class="py-2.5 item-subtotal-column {{ $document->type === 'PO' ? 'd-none' : '' }}">Subtotal</th>
                                <th width="3%" class="text-center py-2.5"></th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            @foreach($document->items as $index => $item)
                            <tr>
                                <td class="text-center row-number fw-semibold text-muted small">{{ $index + 1 }}</td>
                                <td>
                                    <input type="text" name="item_desc[]" class="form-control form-control-sm border-0 bg-light" value="{{ $item->description }}" required>
                                </td>
                                <td>
                                    <input type="text" name="item_notes[]" class="form-control form-control-sm border-0 bg-light item-specification-input" value="{{ $item->notes }}" placeholder="Catatan opsional...">
                                </td>
                                <td>
                                    <input type="number" name="item_qty[]" class="form-control form-control-sm border-0 bg-light item-qty" value="{{ $item->qty }}" min="0" step="any" required>
                                </td>
                                <td class="item-type-column {{ $document->type === 'PO' ? 'd-none' : '' }}">
                                    <select name="item_type[]" class="form-select form-select-sm item-type-select" {{ $document->type === 'PO' ? 'disabled' : 'required' }}>
                                        <option value="" disabled {{ in_array($item->item_type, ['A', 'B'], true) ? '' : 'selected' }}>Pilih tipe Barang/Jasa...</option>
                                        <option value="A" {{ $item->item_type === 'A' ? 'selected' : '' }}>Barang (A)</option>
                                        <option value="B" {{ $item->item_type === 'B' ? 'selected' : '' }}>Jasa (B)</option>
                                    </select>
                                </td>
                                <td class="item-unit-column {{ $document->type === 'PO' ? 'd-none' : '' }}">
                                    <input type="text" name="item_unit[]" class="form-control form-control-sm border-0 bg-light" value="{{ $item->unit ?? 'Pcs' }}" {{ $document->type === 'PO' ? 'disabled' : 'required' }}>
                                </td>
                                <td class="item-price-column {{ $document->type === 'PO' ? 'd-none' : '' }}">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text border-0 bg-light text-muted">{{ $setting->currency_symbol ?? 'Rp' }}</span>
                                        <input type="number" name="item_price[]" class="form-control border-0 bg-light item-price" value="{{ $item->price }}" {{ $document->type === 'PO' ? 'disabled' : 'required' }} min="0" step="any">
                                    </div>
                                </td>
                                <td class="item-subtotal-column {{ $document->type === 'PO' ? 'd-none' : '' }}">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text border-0 bg-light text-muted">{{ $setting->currency_symbol ?? 'Rp' }}</span>
                                        <input type="text" class="form-control border-0 bg-light fw-bold item-subtotal" readonly value="{{ number_format($item->subtotal, 0, ',', '.') }}">
                                    </div>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-link text-danger p-1 remove-row" title="Hapus Baris">
                                        <i class="fa-solid fa-xmark fs-6"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Section 3: Catatan Kwitansi & Diskon -->
            <div class="row g-4 mt-2">
                <div class="col-md-7 invoice-only {{ $document->type === 'PO' ? 'd-none' : '' }}">
                    <div class="card border bg-light h-100 p-3">
                        <label class="form-label fw-bold text-dark small mb-2">
                            <i class="fa-solid fa-receipt me-1 text-primary"></i> Keterangan Khusus Kwitansi (Opsional)
                        </label>
                        <textarea name="payment_note" class="form-control border-0 bg-white shadow-sm" rows="3" placeholder="Keterangan khusus kwitansi..." {{ $document->type === 'PO' ? 'disabled' : '' }}>{{ $document->payment_note }}</textarea>
                    </div>
                </div>
                <div class="col-md-5 invoice-only {{ $document->type === 'PO' ? 'd-none' : '' }}">
                    <div class="card border bg-light h-100 p-3 d-flex flex-column justify-content-center">
                        <label class="form-label fw-bold text-danger small mb-2">
                            <i class="fa-solid fa-tag me-1"></i> Potongan Harga (Diskon)
                        </label>
                        <div class="input-group input-group-lg shadow-sm rounded-3">
                            <span class="input-group-text bg-white border-0 text-muted">{{ $setting->currency_symbol ?? 'Rp' }}</span>
                            <input type="number" name="discount" class="form-control border-0 fw-bold text-danger" value="{{ $document->discount ?? 0 }}" min="0" step="any">
                        </div>
                        <small class="text-muted mt-2 d-block font-xs">*Tarif PPN sistem: <strong>{{ ($setting->enable_tax ?? true) ? ($setting->default_tax_rate ?? 11) . '%' : 'Nonaktif (0%)' }}</strong>.</small>
                    </div>
                </div>
            </div>

            <div class="mt-5 pt-3 border-top d-flex justify-content-end align-items-center gap-2">
                <a href="{{ route($indexRoute) }}" class="btn btn-light border px-4">Batal</a>
                <button type="submit" class="btn btn-primary px-5 shadow-sm fw-bold">
                    <i class="fa-solid fa-floppy-disk me-1.5"></i> Perbarui Dokumen
                </button>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('.select2').not('#customer_select').select2({ theme: 'bootstrap-5', width: '100%' });

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

        function updateDocumentTypeFields() {
            let isPurchaseOrder = $('#document_type').val() === 'PO';
            $('.item-type-column').toggleClass('d-none', isPurchaseOrder);
            $('.item-type-column select').prop('disabled', isPurchaseOrder).prop('required', !isPurchaseOrder);
            $('.item-unit-column, .item-price-column, .item-subtotal-column').toggleClass('d-none', isPurchaseOrder);
            $('.item-unit-column input, .item-price-column input').prop('disabled', isPurchaseOrder);
            $('.item-unit-column input, .item-price-column input').prop('required', !isPurchaseOrder);
            $('.item-description-heading').text(isPurchaseOrder ? 'Nama Barang' : 'Nama Barang / Jasa');
            $('.item-specification-heading').text(isPurchaseOrder ? 'Spesifikasi' : 'Keterangan');
            $('.item-quantity-heading').text(isPurchaseOrder ? 'Kuantitas' : 'QTY');
            $('.item-specification-input').attr('placeholder', isPurchaseOrder ? 'Spesifikasi barang...' : 'Catatan opsional...');
            $('.invoice-only').toggleClass('d-none', isPurchaseOrder);
            $('.invoice-only input, .invoice-only select, .invoice-only textarea').prop('disabled', isPurchaseOrder);
        }

        $('#document_type').on('change', updateDocumentTypeFields);

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
                        <input type="text" name="item_notes[]" class="form-control form-control-sm border-0 bg-light item-specification-input" placeholder="Spesifikasi barang...">
                    </td>
                    <td>
                        <input type="number" name="item_qty[]" class="form-control form-control-sm border-0 bg-light item-qty" value="1" min="0" step="any" required>
                    </td>
                    <td class="item-type-column {{ $document->type === 'PO' ? 'd-none' : '' }}">
                        <select name="item_type[]" class="form-select form-select-sm item-type-select" {{ $document->type === 'PO' ? 'disabled' : 'required' }}>
                            <option value="A" selected>Barang (A)</option>
                            <option value="B">Jasa (B)</option>
                        </select>
                    </td>
                    <td class="item-unit-column">
                        <input type="text" name="item_unit[]" class="form-control form-control-sm border-0 bg-light" placeholder="Pcs/Unit" value="Pcs" required>
                    </td>
                    <td class="item-price-column">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text border-0 bg-light text-muted">{{ $setting->currency_symbol ?? 'Rp' }}</span>
                            <input type="number" name="item_price[]" class="form-control border-0 bg-light item-price" required min="0" step="any" placeholder="0">
                        </div>
                    </td>
                    <td class="item-subtotal-column">
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
            updateDocumentTypeFields();
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

        updateDocumentTypeFields();
    });
</script>
@endpush