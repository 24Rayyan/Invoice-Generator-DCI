{{--
    Form dokumen terpadu (Invoice / PO) untuk tiga mode: create, duplicate, edit.
    Variabel dari view pembungkus:
      $mode        'create' | 'duplicate' | 'edit'
      $doc         Document sumber (null saat create)
      $docType     'INVOICE' | 'PO' (tipe awal)
      $indexRoute, $customers, $setting
      $autoInvoiceNumber, $autoPoNumber   (create & duplicate)
      $projects                            (edit)
--}}
@php
    $isEdit = $mode === 'edit';
    $isDuplicate = $mode === 'duplicate';
    $isCreate = $mode === 'create';

    $docType = old('type', $docType);
    $isPo = $docType === 'PO';
    $currency = $setting->currency_symbol ?? 'Rp';
    $taxRate = ($setting->enable_tax ?? true) ? ($setting->default_tax_rate ?? 11) : 0;

    // Baris item: dari input sebelumnya (jika validasi gagal), dari dokumen sumber, atau satu baris kosong.
    $rows = [];
    if (is_array(old('item_desc'))) {
        foreach (old('item_desc') as $i => $desc) {
            $rows[] = [
                'desc' => $desc,
                'notes' => old("item_notes.$i"),
                'qty' => old("item_qty.$i", 1),
                'type' => old("item_type.$i"),
                'unit' => old("item_unit.$i", 'Pcs'),
                'price' => old("item_price.$i"),
            ];
        }
    } elseif ($doc) {
        foreach ($doc->items as $item) {
            $rows[] = [
                'desc' => $item->description,
                'notes' => $item->notes,
                'qty' => $item->qty,
                'type' => $item->item_type,
                'unit' => $item->unit ?? 'Pcs',
                'price' => $doc->price_includes_tax ? round($item->price * (1 + $taxRate / 100), 2) : $item->price,
            ];
        }
    }
    if (empty($rows)) {
        $rows[] = [];
    }

    $taxCodes = [
        '040' => 'DPP Nilai Lain',
        '020' => 'Pemungut Bendahara (WAPU)',
        '010' => 'Penyerahan Umum (BKP/JKP)',
        '030' => 'Pemungut BUMN',
        '050' => 'Besaran Tertentu',
        '070' => 'PPN Tidak Dipungut',
        '080' => 'PPN Dibebaskan',
        '090' => 'Penyerahan Aktiva (16D)',
    ];
    $selectedTaxCode = old('tax_transaction_code', $doc->tax_transaction_code ?? '040');
    $priceMode = old('price_mode', ($doc && ! $doc->price_includes_tax) ? 'exclusive' : 'inclusive');

    $documentNumber = old('document_number', $isEdit
        ? $doc->document_number
        : ($isPo ? ($autoPoNumber ?? '') : ($autoInvoiceNumber ?? '')));

    $listLabel = $isPo ? 'PO' : 'Invoice';
    $formAction = $isEdit ? route('documents.update', $doc->id) : route('documents.store');
    $submitLabel = $isEdit ? 'Perbarui Dokumen' : ($isDuplicate ? 'Simpan Salinan Dokumen Baru' : 'Simpan Dokumen');
@endphp

<!-- Header Page & Breadcrumb -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route($indexRoute) }}" class="text-decoration-none text-muted small">Daftar {{ $listLabel }}</a></li>
                <li class="breadcrumb-item active small fw-semibold" aria-current="page">
                    {{ $isEdit ? 'Edit Dokumen' : ($isDuplicate ? 'Duplikasi Dokumen' : 'Buat Dokumen') }}
                </li>
            </ol>
        </nav>
        @if($isEdit)
            <h1 class="h3 fw-bold text-dark mb-0 tracking-tight">Edit {{ $doc->type }} <span class="text-primary font-monospace">#{{ $doc->document_number }}</span></h1>
        @elseif($isDuplicate)
            <h1 class="h3 fw-bold text-dark mb-0 tracking-tight">Duplikasi Dokumen Transaksi</h1>
        @else
            <h1 class="h3 fw-bold text-dark mb-0 tracking-tight">Buat Dokumen Transaksi Baru</h1>
        @endif
    </div>
    <div>
        <a href="{{ route($indexRoute) }}" class="btn btn-light border btn-sm px-3">
            <i class="fa-solid fa-arrow-left me-1"></i> {{ $isDuplicate ? 'Batal & Kembali' : 'Kembali ke Daftar' }}
        </a>
    </div>
</div>

@if($isDuplicate)
    <!-- Banner Referensi Salinan -->
    <div class="alert border-0 bg-info bg-opacity-10 text-info rounded-3 mb-4 p-3 d-flex align-items-center shadow-sm">
        <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 36px; height: 36px;">
            <i class="fa-solid fa-copy fs-6"></i>
        </div>
        <div>
            <span class="font-xs text-muted d-block">Menyalin rincian barang dari dokumen referensi:</span>
            <strong class="text-dark font-monospace">{{ $doc->document_number }}</strong> <span class="badge bg-info bg-opacity-10 text-info ms-2 font-xs">{{ $doc->type }}</span>
        </div>
    </div>
@endif

<form action="{{ $formAction }}" method="POST" id="document-form">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4 p-lg-5">

            <!-- Validation Errors -->
            @if ($errors->any())
                <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger rounded-3 mb-4 p-3">
                    <h2 class="h6 fw-bold mb-1"><i class="fa-solid fa-triangle-exclamation me-1"></i> Periksa kembali input formulir:</h2>
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
                    <span class="badge bg-primary bg-opacity-10 text-primary p-2 rounded-2"><i class="fa-solid {{ $isEdit ? 'fa-file-pen' : 'fa-file-invoice' }}"></i></span>
                    <h2 class="h6 fw-bold text-dark mb-0">Informasi Transaksi & Pajak (Coretax)</h2>
                </div>

                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="document_type" class="form-label fw-semibold text-dark small">Jenis Dokumen @if(! $isCreate)<span class="text-danger">*</span>@endif</label>
                        @if($isCreate)
                            {{-- Jenis dokumen ditentukan dari menu asal; tampil sebagai teks tetap, bukan dropdown mati. --}}
                            <input type="text" class="form-control bg-light" value="{{ $isPo ? 'PO' : 'Invoice' }}" readonly tabindex="-1" aria-label="Jenis dokumen">
                            <input type="hidden" name="type" id="document_type" value="{{ $docType }}">
                        @else
                            <select name="type" id="document_type" class="form-select" required>
                                <option value="INVOICE" {{ $docType === 'INVOICE' ? 'selected' : '' }}>Invoice</option>
                                <option value="PO" {{ $docType === 'PO' ? 'selected' : '' }}>PO</option>
                            </select>
                        @endif
                    </div>

                    @if($isEdit)
                        <div class="col-md-3">
                            <label for="document_status" class="form-label fw-semibold text-dark small">Status Dokumen <span class="text-danger">*</span></label>
                            <select name="status" id="document_status" class="form-select fw-semibold" required>
                                <option value="DRAFT" {{ old('status', $doc->status) == 'DRAFT' ? 'selected' : '' }}>DRAFT</option>
                                <option value="SENT" {{ old('status', $doc->status) == 'SENT' ? 'selected' : '' }}>SENT</option>
                                <option value="PAID" {{ old('status', $doc->status) == 'PAID' ? 'selected' : '' }}>PAID</option>
                            </select>
                        </div>
                    @endif

                    <div class="col-md-3">
                        <label for="customer_select" class="form-label fw-semibold text-dark small">Pilih Pelanggan <span class="text-danger">*</span></label>
                        <select name="customer_id" id="customer_select" class="form-select customer-select smooth-dropdown" data-placeholder="Pilih pelanggan..." required>
                            <option value="">-- Pilih Pelanggan --</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" data-tax-code="{{ $c->default_tax_transaction_code ?? '040' }}" data-tax-id="{{ $c->tax_id_number }}" data-tax-type="{{ $c->tax_id_type ?? 'NPWP' }}" data-email="{{ $c->email }}" {{ old('customer_id', $doc->customer_id ?? null) == $c->id ? 'selected' : '' }}>
                                    {{ $c->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="document_number" class="form-label fw-semibold text-dark small">
                            Nomor Dokumen
                            @if($isEdit)
                                <span class="badge bg-secondary bg-opacity-10 text-secondary ms-1 font-xs"><i class="fa-solid fa-lock me-1"></i>Terkunci</span>
                            @else
                                <span class="badge bg-success bg-opacity-10 text-success ms-1 font-xs">Otomatis</span>
                            @endif
                        </label>
                        <input type="text" name="document_number" id="document_number"
                               class="form-control fw-bold text-primary font-monospace bg-light"
                               value="{{ $documentNumber }}" readonly
                               @unless($isEdit) data-invoice-number="{{ $autoInvoiceNumber }}" data-po-number="{{ $autoPoNumber }}" @endunless
                               required>
                        <small class="text-muted font-xs">Nomor dibuat sistem agar urut dan tidak ganda.</small>
                    </div>

                    <div class="col-12 po-only {{ $isPo ? '' : 'd-none' }}">
                        <label for="document_description" class="form-label fw-semibold text-dark small">Keterangan (Opsional)</label>
                        <textarea name="description" id="document_description" class="form-control" rows="2" maxlength="2000" placeholder="Tambahkan keterangan untuk PO..." {{ $isPo ? '' : 'disabled' }}>{{ old('description', $doc->description ?? '') }}</textarea>
                    </div>

                    @if($isEdit && auth()->user()->hasAccess('projects'))
                        <div class="col-md-6 invoice-only {{ $isPo ? 'd-none' : '' }}">
                            <label for="project_id" class="form-label fw-semibold text-dark small">Proyek (Opsional)</label>
                            <select name="project_id" id="project_id" class="form-select smooth-dropdown" {{ $isPo ? 'disabled' : '' }}>
                                <option value="">-- Tidak terkait project --</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}" {{ old('project_id', $doc->project_id) == $project->id ? 'selected' : '' }}>
                                        {{ $project->project_name }}{{ $project->client?->name ? ' | ' . $project->client->name : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="{{ $isEdit ? 'col-md-4' : 'col-md-3' }} invoice-only {{ $isPo ? 'd-none' : '' }}">
                        <label for="tax_transaction_code" class="form-label fw-semibold text-dark small">
                            <i class="fa-solid fa-tag text-primary me-1"></i> Kode Faktur PPN (Coretax)
                        </label>
                        <select name="tax_transaction_code" id="tax_transaction_code" class="form-select fw-semibold" {{ $isPo ? 'disabled' : '' }}>
                            @foreach($taxCodes as $code => $label)
                                <option value="{{ $code }}" {{ (string) $selectedTaxCode === (string) $code ? 'selected' : '' }}>{{ $code }}: {{ $label }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted font-xs">Otomatis mengikuti pelanggan (dapat diubah).</small>
                    </div>

                    @if($isEdit)
                        <div class="col-md-4 invoice-only {{ $isPo ? 'd-none' : '' }}">
                            <label for="tax_invoice_date" class="form-label fw-semibold text-dark small">Tanggal Faktur Pajak Coretax</label>
                            <input type="date" name="tax_invoice_date" id="tax_invoice_date" class="form-control" value="{{ old('tax_invoice_date', $doc->tax_invoice_date ?? $doc->date) }}" {{ $isPo ? 'disabled' : '' }}>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Section 2: Input Barang (Item) -->
            <div class="mb-4 mt-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-info bg-opacity-10 text-info p-2 rounded-2"><i class="fa-solid fa-list-check"></i></span>
                        <h2 class="h6 fw-bold text-dark mb-0">Rincian Barang & Jasa</h2>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm fw-bold" id="add-row">
                        <i class="fa-solid fa-plus me-1"></i> Tambah Item
                    </button>
                </div>

                <div class="table-responsive document-items-table-container rounded-3 border">
                    <table class="table table-hover align-middle mb-0" id="item-table">
                        <thead class="table-dark">
                            <tr class="small text-uppercase font-xs">
                                <th width="4%" class="text-center py-2.5">#</th>
                                <th width="20%" class="py-2.5 item-description-heading">{{ $isPo ? 'Nama Barang' : 'Nama Barang / Jasa' }}</th>
                                <th width="16%" class="py-2.5 item-specification-heading">{{ $isPo ? 'Spesifikasi' : 'Keterangan' }}</th>
                                <th width="7%" class="py-2.5 item-quantity-heading">{{ $isPo ? 'Kuantitas' : 'QTY' }}</th>
                                <th width="12%" class="py-2.5 item-type-column {{ $isPo ? 'd-none' : '' }}">Tipe</th>
                                <th width="9%" class="py-2.5 item-unit-column {{ $isPo ? 'd-none' : '' }}">Satuan</th>
                                <th width="14%" class="py-2.5 item-price-column item-price-heading {{ $isPo ? 'd-none' : '' }}">Harga Satuan</th>
                                <th width="16%" class="py-2.5 item-subtotal-column item-subtotal-heading {{ $isPo ? 'd-none' : '' }}">Subtotal</th>
                                <th width="3%" class="text-center py-2.5"><span class="visually-hidden">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            @foreach($rows as $i => $row)
                                @include('documents._form_row', ['row' => $row, 'index' => $i + 1, 'isPo' => $isPo, 'currency' => $currency])
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <small class="text-muted d-block mt-2 font-xs"><i class="fa-regular fa-keyboard me-1"></i>Tips: tekan <kbd>Tab</kbd> untuk pindah kolom, dan <kbd>Enter</kbd> di kolom harga untuk menambah baris baru.</small>
            </div>

            {{-- Templat baris baru: memakai markup yang sama dengan baris server-side --}}
            <template id="item-row-template">
                @include('documents._form_row', ['row' => [], 'index' => 1, 'isPo' => false, 'currency' => $currency])
            </template>

            <div class="card border bg-light mt-3 invoice-only {{ $isPo ? 'd-none' : '' }}" id="calculation-preview"
                 data-tax-rate="{{ $taxRate }}"
                 data-currency="{{ $currency }}">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-3">
                        <h2 class="h6 fw-bold text-dark mb-0">
                            <i class="fa-solid fa-calculator text-primary me-1"></i> Simulasi Perhitungan
                        </h6>
                        <div>
                            <label class="form-label fw-bold text-dark small mb-1">Mode Harga</label>
                            <div class="btn-group d-flex" role="group" aria-label="Pilih mode harga">
                                <input type="radio" class="btn-check" name="price_mode" id="price_mode_exclusive" value="exclusive" {{ $priceMode === 'exclusive' ? 'checked' : '' }}>
                                <label class="btn btn-outline-primary btn-sm" for="price_mode_exclusive">DPP / Belum Termasuk PPN</label>
                                <input type="radio" class="btn-check" name="price_mode" id="price_mode_inclusive" value="inclusive" {{ $priceMode === 'inclusive' ? 'checked' : '' }}>
                                <label class="btn btn-outline-primary btn-sm" for="price_mode_inclusive">Harga Termasuk PPN</label>
                            </div>
                        </div>
                        <div>
                            <label for="rounding_discount" class="form-label fw-bold text-warning-emphasis small mb-1"><i class="fa-solid fa-scissors me-1"></i> Diskon Pembulatan</label>
                            <div class="input-group shadow-sm rounded-3">
                                <span class="input-group-text bg-white border-0 text-muted">{{ $currency }}</span>
                                <input type="number" name="rounding_discount" id="rounding_discount" class="form-control border-0 fw-bold text-warning-emphasis" value="{{ old('rounding_discount', $doc->rounding_discount ?? 0) }}" min="0" step="any" placeholder="0">
                            </div>
                        </div>
                    </div>
                    <small class="text-muted d-block mb-3">Diskon pembulatan mengurangi total setelah PPN.</small>
                    <div class="row g-2 small">
                        <div class="col-7 text-muted" id="preview-input-label">Total harga input</div>
                        <div class="col-5 text-end fw-semibold" id="preview-input-total">{{ $currency }} 0</div>
                        <div class="col-7 text-muted">Total DPP</div>
                        <div class="col-5 text-end fw-semibold" id="preview-gross">{{ $currency }} 0</div>
                        <div class="col-7 text-muted">DPP</div>
                        <div class="col-5 text-end fw-semibold" id="preview-net">{{ $currency }} 0</div>
                        <div class="col-7 text-muted">PPN ({{ $taxRate }}%)</div>
                        <div class="col-5 text-end fw-semibold" id="preview-tax">{{ $currency }} 0</div>
                        <div class="col-7 text-muted">Diskon Pembulatan</div>
                        <div class="col-5 text-end fw-semibold text-warning-emphasis" id="preview-rounding-discount">- {{ $currency }} 0</div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center border-top mt-3 pt-3">
                        <span class="fw-bold text-dark">Total (DPP + PPN)</span>
                        <span class="fs-5 fw-bold text-primary" id="preview-total">{{ $currency }} 0</span>
                    </div>
                </div>
            </div>

            <!-- Section 3: Catatan Kwitansi -->
            <div class="row g-4 mt-2">
                <div class="col-12 invoice-only {{ $isPo ? 'd-none' : '' }}">
                    <div class="card border bg-light h-100 p-3">
                        <label for="payment_note" class="form-label fw-bold text-dark small mb-2">
                            <i class="fa-solid fa-receipt me-1 text-primary"></i> Keterangan Khusus Kwitansi (Opsional)
                        </label>
                        <textarea name="payment_note" id="payment_note" class="form-control border-0 bg-white shadow-sm" rows="3" placeholder="{{ $setting->receipt_footer_notes ?? 'Contoh: Pembayaran Pelunasan Tagihan Dokumen Invoice' }}" {{ $isPo ? 'disabled' : '' }}>{{ old('payment_note', $doc->payment_note ?? '') }}</textarea>
                        <small class="text-muted mt-2 d-block font-xs">*Kosongkan jika ingin menggunakan format default otomatis.</small>
                    </div>
                </div>
            </div>

            <div class="mt-5 pt-3 border-top d-flex justify-content-end align-items-center gap-2">
                <a href="{{ route($indexRoute) }}" class="btn btn-light border px-4" id="cancel-form">Batal</a>
                <button type="submit" class="btn btn-primary px-5 shadow-sm fw-bold">
                    <i class="fa-solid fa-floppy-disk me-1.5"></i> {{ $submitLabel }}
                </button>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
    $(document).ready(function() {
        const $form = $('#document-form');
        const numberField = $('#document_number');
        const hasAutoNumber = numberField.data('invoice-number') !== undefined;

        // ---- Perubahan belum disimpan -------------------------------------------------
        let dirty = false;
        let submitted = false;
        $form.on('input change', 'input, select, textarea', function() { dirty = true; });
        $form.on('submit', function() { submitted = true; });
        window.addEventListener('beforeunload', function(event) {
            if (dirty && !submitted) {
                event.preventDefault();
                event.returnValue = '';
            }
        });

        // ---- Kode pajak mengikuti customer -------------------------------------------
        $('#customer_select').on('change', function() {
            let taxCode = $(this).find('option:selected').data('tax-code');
            let taxSelect = $('#tax_transaction_code')[0];
            if (taxCode && taxSelect) {
                if (taxSelect.tomselect) {
                    taxSelect.tomselect.setValue(String(taxCode));
                } else {
                    taxSelect.value = String(taxCode);
                }
            }
        });

        // ---- Nomor otomatis mengikuti jenis dokumen (hanya create/duplikasi) ---------
        if (hasAutoNumber) {
            $('#document_type').on('change', function() {
                numberField.val($(this).val() === 'PO'
                    ? numberField.data('po-number')
                    : numberField.data('invoice-number'));
            });
        }

        function updateRowNumbers() {
            $('#item-table tbody tr').each(function(index) {
                $(this).find('.row-number').text(index + 1);
            });
        }

        function roundMoney(amount) {
            return Math.round((amount + Number.EPSILON) * 100) / 100;
        }

        function calculateRowSubtotal(row) {
            let qty = parseFloat(row.find('.item-qty').val()) || 0;
            let price = parseFloat(row.find('.item-price').val()) || 0;
            row.find('.item-subtotal').val(new Intl.NumberFormat('id-ID').format(qty * price));
        }

        function updateCalculationPreview() {
            let enteredTotal = 0;
            let gross = 0;
            let taxRate = (parseFloat($('#calculation-preview').data('tax-rate')) || 0) / 100;
            let isInclusive = $('input[name="price_mode"]:checked').val() === 'inclusive';
            $('#item-table tbody tr').each(function() {
                let qty = parseFloat($(this).find('.item-qty').val()) || 0;
                let price = parseFloat($(this).find('.item-price').val()) || 0;
                enteredTotal += qty * price;
                let dppUnitPrice = isInclusive && taxRate > 0 ? roundMoney(price / (1 + taxRate)) : price;
                gross += roundMoney(qty * dppUnitPrice);
            });

            let net = roundMoney(gross);
            let tax = roundMoney(net * taxRate);
            let totalBeforeRounding = roundMoney(net + tax);
            let roundingDiscount = parseFloat($('#rounding_discount').val()) || 0;
            let total = roundMoney(totalBeforeRounding - roundingDiscount);
            let currency = $('#calculation-preview').data('currency');
            let formatter = new Intl.NumberFormat('id-ID');
            let formatMoney = amount => `${currency} ${formatter.format(amount)}`;

            $('#preview-input-label').text(isInclusive ? 'Total harga input (termasuk PPN)' : 'Total harga input (DPP)');
            $('#preview-input-total').text(formatMoney(enteredTotal));
            $('#preview-gross').text(formatMoney(gross));
            $('#preview-net').text(formatMoney(net));
            $('#preview-tax').text(formatMoney(tax));
            $('#preview-rounding-discount').text(`- ${formatMoney(roundingDiscount)}`);
            $('#preview-total').text(formatMoney(total));
            $('#rounding_discount').attr('max', Math.max(0, totalBeforeRounding));
            $('.item-price-heading').text(isInclusive ? 'Harga Satuan (PPN)' : 'Harga Satuan (DPP)');
            $('.item-subtotal-heading').text(isInclusive ? 'Subtotal (PPN)' : 'Subtotal (DPP)');
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
            $('.po-only').toggleClass('d-none', !isPurchaseOrder);
            $('.po-only input, .po-only select, .po-only textarea').prop('disabled', !isPurchaseOrder);
        }

        $('#document_type').on('change', updateDocumentTypeFields);

        $('#item-table').on('input', '.item-qty, .item-price', function() {
            calculateRowSubtotal($(this).closest('tr'));
            updateCalculationPreview();
        });

        $('#rounding_discount').on('input', updateCalculationPreview);
        $('input[name="price_mode"]').on('change', updateCalculationPreview);

        function addRow(focus) {
            $('#item-table tbody').append($('#item-row-template').html());
            updateRowNumbers();
            updateDocumentTypeFields();
            updateCalculationPreview();
            if (focus) {
                $('#item-table tbody tr:last input[name="item_desc[]"]').trigger('focus');
            }
        }

        $('#add-row').on('click', function() { addRow(true); });

        // Enter di kolom harga (kolom qty untuk PO) = lanjut ke baris berikutnya / tambah baris, bukan submit form
        $('#item-table').on('keydown', '.item-price, .item-qty', function(event) {
            if (event.key !== 'Enter') return;
            let isPurchaseOrder = $('#document_type').val() === 'PO';
            if ($(this).hasClass('item-qty') !== isPurchaseOrder) return;
            event.preventDefault();
            let row = $(this).closest('tr');
            if (row.is(':last-child')) {
                addRow(true);
            } else {
                row.next().find('input[name="item_desc[]"]').trigger('focus');
            }
        });

        $('#item-table').on('click', '.remove-row', function() {
            if ($('#item-table tbody tr').length > 1) {
                $(this).closest('tr').remove();
                updateRowNumbers();
                updateCalculationPreview();
                dirty = true;
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Perhatian',
                    text: 'Minimal harus ada 1 baris item transaksi!',
                    confirmButtonColor: '#1e40af'
                });
            }
        });

        // Batal: minta konfirmasi bila ada perubahan belum tersimpan
        $('#cancel-form').on('click', function(event) {
            if (!dirty) return;
            event.preventDefault();
            let href = this.href;
            ErpUI.confirm({
                title: 'Buang Perubahan?',
                text: 'Perubahan yang belum disimpan akan hilang.',
                confirmText: 'Ya, Buang',
                variant: 'danger'
            }).then(function(result) {
                if (result.isConfirmed) {
                    submitted = true;
                    window.location.href = href;
                }
            });
        });

        updateDocumentTypeFields();
        $('#item-table tbody tr').each(function() { calculateRowSubtotal($(this)); });
        updateCalculationPreview();
    });
</script>
@endpush
