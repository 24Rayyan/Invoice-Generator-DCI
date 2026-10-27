@extends('layouts.app')

@section('content')
<!-- Header Page & Breadcrumb -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route($indexRoute) }}" class="text-decoration-none text-muted small">Daftar {{ $sourceDoc->type === 'PO' ? 'PO' : 'Invoice' }}</a></li>
                <li class="breadcrumb-item active small fw-semibold" aria-current="page">Duplikasi Dokumen</li>
            </ol>
        </nav>
        <h3 class="fw-bold text-dark mb-0 tracking-tight">Duplikasi Dokumen Transaksi</h3>
    </div>
    <div>
        <a href="{{ route($indexRoute) }}" class="btn btn-light border btn-sm px-3">
            <i class="fa-solid fa-arrow-left me-1"></i> Batal & Kembali
        </a>
    </div>
</div>

<!-- Banner Referensi Salinan -->
<div class="alert border-0 bg-info bg-opacity-10 text-info rounded-3 mb-4 p-3 d-flex align-items-center shadow-sm">
    <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 36px; height: 36px;">
        <i class="fa-solid fa-copy fs-6"></i>
    </div>
    <div>
        <span class="font-xs text-muted d-block">Menyalin rincian barang dari dokumen referensi:</span>
        <strong class="text-dark font-monospace">{{ $sourceDoc->document_number }}</strong> <span class="badge bg-info bg-opacity-10 text-info ms-2 font-xs">{{ $sourceDoc->type }}</span>
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

            <!-- Section 1: Informasi Dokumen Baru & Coretax -->
            <div class="mb-4">
                <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                    <span class="badge bg-primary bg-opacity-10 text-primary p-2 rounded-2"><i class="fa-solid fa-file-invoice"></i></span>
                    <h6 class="fw-bold text-dark mb-0">Informasi Dokumen Baru & Pajak (Coretax)</h6>
                </div>

                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-dark small">Jenis Dokumen <span class="text-danger">*</span></label>
                        <select name="type" id="document_type" class="form-select" required>
                            <option value="INVOICE" {{ $sourceDoc->type == 'INVOICE' ? 'selected' : '' }}>Invoice (Faktur Penjualan)</option>
                            <option value="PO" {{ $sourceDoc->type == 'PO' ? 'selected' : '' }}>Purchase Order (PO)</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="customer_select" class="form-label fw-semibold text-dark small">Pilih Customer <span class="text-danger">*</span></label>
                        <select name="customer_id" id="customer_select" class="form-select customer-select smooth-dropdown" data-placeholder="Pilih customer..." required>
                            <option value=""></option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" data-tax-code="{{ $c->default_tax_transaction_code ?? '040' }}" data-tax-id="{{ $c->tax_id_number }}" data-tax-type="{{ $c->tax_id_type ?? 'NPWP' }}" data-email="{{ $c->email }}" {{ $sourceDoc->customer_id == $c->id ? 'selected' : '' }}>
                                    {{ $c->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-dark small">Nomor Dokumen Baru <span class="badge bg-success bg-opacity-10 text-success ms-1 font-xs">Otomatis</span></label>
                        <input type="text" name="document_number" id="document_number" class="form-control fw-bold text-primary font-monospace" value="{{ $sourceDoc->type === 'PO' ? $autoPoNumber : $autoInvoiceNumber }}" data-invoice-number="{{ $autoInvoiceNumber }}" data-po-number="{{ $autoPoNumber }}" required>
                    </div>

                    <div class="col-md-3 invoice-only {{ $sourceDoc->type === 'PO' ? 'd-none' : '' }}">
                        <label class="form-label fw-semibold text-dark small">
                            <i class="fa-solid fa-tag text-primary me-1"></i> Kode Faktur PPN (Coretax)
                        </label>
                        <select name="tax_transaction_code" id="tax_transaction_code" class="form-select fw-semibold" {{ $sourceDoc->type === 'PO' ? 'disabled' : '' }}>
                            <option value="040" {{ ($sourceDoc->tax_transaction_code ?? '040') == '040' ? 'selected' : '' }}>040 — DPP Nilai Lain</option>
                            <option value="020" {{ ($sourceDoc->tax_transaction_code ?? '') == '020' ? 'selected' : '' }}>020 — Pemungut Bendahara (WAPU)</option>
                            <option value="010" {{ ($sourceDoc->tax_transaction_code ?? '') == '010' ? 'selected' : '' }}>010 — Penyerahan Umum (BKP/JKP)</option>
                            <option value="030" {{ ($sourceDoc->tax_transaction_code ?? '') == '030' ? 'selected' : '' }}>030 — Pemungut BUMN</option>
                            <option value="050" {{ ($sourceDoc->tax_transaction_code ?? '') == '050' ? 'selected' : '' }}>050 — Besaran Tertentu</option>
                            <option value="070" {{ ($sourceDoc->tax_transaction_code ?? '') == '070' ? 'selected' : '' }}>070 — PPN Tidak Dipungut</option>
                            <option value="080" {{ ($sourceDoc->tax_transaction_code ?? '') == '080' ? 'selected' : '' }}>080 — PPN Dibebaskan</option>
                            <option value="090" {{ ($sourceDoc->tax_transaction_code ?? '') == '090' ? 'selected' : '' }}>090 — Penyerahan Aktiva (16D)</option>
                        </select>
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
                                <th width="12%" class="py-2.5 item-type-column {{ $sourceDoc->type === 'PO' ? 'd-none' : '' }}">Tipe</th>
                                <th width="9%" class="py-2.5 item-unit-column {{ $sourceDoc->type === 'PO' ? 'd-none' : '' }}">Satuan</th>
                                <th width="14%" class="py-2.5 item-price-column {{ $sourceDoc->type === 'PO' ? 'd-none' : '' }}">Harga Satuan</th>
                                <th width="16%" class="py-2.5 item-subtotal-column {{ $sourceDoc->type === 'PO' ? 'd-none' : '' }}">Subtotal</th>
                                <th width="3%" class="text-center py-2.5"></th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            @foreach($sourceDoc->items as $index => $item)
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
                                <td class="item-type-column {{ $sourceDoc->type === 'PO' ? 'd-none' : '' }}">
                                    <select name="item_type[]" class="form-select form-select-sm item-type-select" {{ $sourceDoc->type === 'PO' ? 'disabled' : 'required' }}>
                                        <option value="" disabled {{ in_array($item->item_type, ['A', 'B'], true) ? '' : 'selected' }}>Pilih tipe Barang/Jasa...</option>
                                        <option value="A" {{ $item->item_type === 'A' ? 'selected' : '' }}>Barang (A)</option>
                                        <option value="B" {{ $item->item_type === 'B' ? 'selected' : '' }}>Jasa (B)</option>
                                    </select>
                                </td>
                                <td class="item-unit-column {{ $sourceDoc->type === 'PO' ? 'd-none' : '' }}">
                                    <input type="text" name="item_unit[]" class="form-control form-control-sm border-0 bg-light" value="{{ $item->unit ?? 'Pcs' }}" {{ $sourceDoc->type === 'PO' ? 'disabled' : 'required' }}>
                                </td>
                                <td class="item-price-column {{ $sourceDoc->type === 'PO' ? 'd-none' : '' }}">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text border-0 bg-light text-muted">{{ $setting->currency_symbol ?? 'Rp' }}</span>
                                        <input type="number" name="item_price[]" class="form-control border-0 bg-light item-price" value="{{ $item->price }}" {{ $sourceDoc->type === 'PO' ? 'disabled' : 'required' }} min="0" step="any">
                                    </div>
                                </td>
                                <td class="item-subtotal-column {{ $sourceDoc->type === 'PO' ? 'd-none' : '' }}">
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

            <div class="card border bg-light mt-3 invoice-only {{ $sourceDoc->type === 'PO' ? 'd-none' : '' }}" id="calculation-preview"
                 data-tax-rate="{{ ($setting->enable_tax ?? true) ? ($setting->default_tax_rate ?? 11) : 0 }}"
                 data-currency="{{ $setting->currency_symbol ?? 'Rp' }}">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                        <h6 class="fw-bold text-dark mb-0">
                            <i class="fa-solid fa-calculator text-primary me-1"></i> Simulasi Perhitungan
                        </h6>
                        <div>
                            <label for="discount" class="form-label fw-bold text-danger small mb-1">
                                <i class="fa-solid fa-tag me-1"></i> Diskon (Nominal)
                            </label>
                            <div class="input-group shadow-sm rounded-3">
                                <span class="input-group-text bg-white border-0 text-muted">{{ $setting->currency_symbol ?? 'Rp' }}</span>
                                <input type="number" name="discount" id="discount" class="form-control border-0 fw-bold text-danger" value="{{ $sourceDoc->discount ?? 0 }}" min="0" step="any" placeholder="0">
                            </div>
                        </div>
                    </div>
                    <small class="text-muted d-block mb-3">Diskon mengurangi DPP sebelum perhitungan PPN.</small>
                    <div class="row g-2 small">
                        <div class="col-7 text-muted">Total DPP sebelum diskon</div>
                        <div class="col-5 text-end fw-semibold" id="preview-gross">Rp 0</div>
                        <div class="col-7 text-muted">Diskon</div>
                        <div class="col-5 text-end fw-semibold text-danger" id="preview-discount">- Rp 0</div>
                        <div class="col-7 text-muted">DPP setelah diskon</div>
                        <div class="col-5 text-end fw-semibold" id="preview-net">Rp 0</div>
                        <div class="col-7 text-muted">PPN ({{ ($setting->enable_tax ?? true) ? ($setting->default_tax_rate ?? 11) : 0 }}%)</div>
                        <div class="col-5 text-end fw-semibold" id="preview-tax">Rp 0</div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center border-top mt-3 pt-3">
                        <span class="fw-bold text-dark">Total (DPP + PPN)</span>
                        <span class="fs-5 fw-bold text-primary" id="preview-total">Rp 0</span>
                    </div>
                    <small class="text-muted mt-2 d-block font-xs">Tarif PPN sistem: <strong>{{ ($setting->enable_tax ?? true) ? ($setting->default_tax_rate ?? 11) . '%' : 'Nonaktif (0%)' }}</strong>.</small>
                </div>
            </div>

            <!-- Section 3: Catatan Kwitansi -->
            <div class="row g-4 mt-2">
                <div class="col-12 invoice-only {{ $sourceDoc->type === 'PO' ? 'd-none' : '' }}">
                    <div class="card border bg-light h-100 p-3">
                        <label class="form-label fw-bold text-dark small mb-2">
                            <i class="fa-solid fa-receipt me-1 text-primary"></i> Keterangan Khusus Kwitansi (Opsional)
                        </label>
                        <textarea name="payment_note" class="form-control border-0 bg-white shadow-sm" rows="3" placeholder="Keterangan khusus kwitansi..." {{ $sourceDoc->type === 'PO' ? 'disabled' : '' }}>{{ $sourceDoc->payment_note }}</textarea>
                    </div>
                </div>
            </div>

            <div class="mt-5 pt-3 border-top d-flex justify-content-end align-items-center gap-2">
                <a href="{{ route($indexRoute) }}" class="btn btn-light border px-4">Batal</a>
                <button type="submit" class="btn btn-primary px-5 shadow-sm fw-bold">
                    <i class="fa-solid fa-floppy-disk me-1.5"></i> Simpan Salinan Dokumen Baru
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

        $('#document_type').on('change', function() {
            let number = $(this).val() === 'PO'
                ? $('#document_number').data('po-number')
                : $('#document_number').data('invoice-number');
            $('#document_number').val(number);
        });

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

        function updateCalculationPreview() {
            let gross = 0;
            $('#item-table tbody tr').each(function() {
                let qty = parseFloat($(this).find('.item-qty').val()) || 0;
                let price = parseFloat($(this).find('.item-price').val()) || 0;
                gross += qty * price;
            });

            let discount = parseFloat($('#discount').val()) || 0;
            let net = gross - discount;
            let taxRate = parseFloat($('#calculation-preview').data('tax-rate')) || 0;
            let tax = net * (taxRate / 100);
            let total = net + tax;
            let currency = $('#calculation-preview').data('currency');
            let formatter = new Intl.NumberFormat('id-ID');
            let formatMoney = amount => `${currency} ${formatter.format(amount)}`;

            $('#preview-gross').text(formatMoney(gross));
            $('#preview-discount').text(`- ${formatMoney(discount)}`);
            $('#preview-net').text(formatMoney(net));
            $('#preview-tax').text(formatMoney(tax));
            $('#preview-total').text(formatMoney(total));
        }

        $('#item-table').on('input', '.item-qty, .item-price', function() {
            let row = $(this).closest('tr');
            calculateRowSubtotal(row);
            updateCalculationPreview();
        });

        $('#discount').on('input', updateCalculationPreview);

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
                    <td class="item-type-column {{ $sourceDoc->type === 'PO' ? 'd-none' : '' }}">
                        <select name="item_type[]" class="form-select form-select-sm item-type-select" {{ $sourceDoc->type === 'PO' ? 'disabled' : 'required' }}>
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
            updateCalculationPreview();
            updateDocumentTypeFields();
        });

        $('#item-table').on('click', '.remove-row', function() {
            if($('#item-table tbody tr').length > 1) {
                $(this).closest('tr').remove();
                updateRowNumbers();
                updateCalculationPreview();
                updateDocumentTypeFields();
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
        updateCalculationPreview();
    });
</script>
@endpush