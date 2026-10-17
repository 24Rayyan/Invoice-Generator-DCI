@extends('layouts.app')

@section('content')
<!-- Header Page & Breadcrumb SaaS Style -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none text-muted small">Dashboard</a></li>
                <li class="breadcrumb-item active small fw-semibold" aria-current="page">Daftar Dokumen</li>
            </ol>
        </nav>
        <h3 class="fw-bold text-dark mb-0 tracking-tight">Kelola Dokumen</h3>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('projects.index') }}" class="btn btn-outline-primary px-3 py-2 rounded-3 fw-semibold">
            <i class="fa-solid fa-diagram-project"></i>
            <span>Projects</span>
        </a>
        <a href="{{ route('documents.create') }}" class="btn btn-primary px-3.5 py-2 rounded-3 shadow-sm fw-semibold d-inline-flex align-items-center gap-2">
            <i class="fa-solid fa-plus fs-6"></i>
            <span>Buat Dokumen Baru</span>
        </a>
    </div>
</div>

<!-- Bulk Action Floating Bar -->
<div id="bulkActionBar" class="card border-0 shadow-lg rounded-4 mb-4 bg-dark text-white d-none">
    <div class="card-body p-3 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fw-bold">
                <span id="selectedCount">0</span> Faktur Dipilih
            </div>
            <span class="text-light small d-none d-md-inline">Pilih beberapa invoice untuk diekspor ke Format Resmi Coretax DJP.</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-light rounded-3" id="btnClearSelection">
                Batal Pilih
            </button>
            <button type="button" class="btn btn-sm btn-primary rounded-3 fw-bold px-3 d-inline-flex align-items-center gap-2 shadow-sm" id="btnOpenCoretaxModal">
                <i class="fa-solid fa-file-code"></i>
                <span>Export Template Coretax (XML / Excel)</span>
            </button>
        </div>
    </div>
</div>

<!-- Main Table Card -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-body p-4">
        
        <!-- TOOLBAR: FILTER BULAN & STATUS -->
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 p-3 bg-light rounded-4 border border-light-subtle">
            <form action="{{ route('documents.index') }}" method="GET" class="d-flex align-items-center gap-2 flex-wrap m-0" id="filterForm">
                
                <!-- HIDDEN INPUT UNTUK MENJAGA STATE DOUBLE FILTER -->
                <input type="hidden" name="period" id="inputPeriod" value="{{ request('period') }}">
                <input type="hidden" name="status" id="inputStatus" value="{{ request('status') }}">

                <!-- Filter Periode Dropdown Custom -->
                <div class="dropdown">
                    <button class="btn btn-white btn-sm border bg-white rounded-3 dropdown-toggle fw-semibold text-dark shadow-sm d-flex align-items-center gap-2 px-3 py-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-regular fa-calendar-days text-primary"></i>
                        <span>
                            @if(request('period'))
                                {{ optional($availableMonths->firstWhere('value', request('period')))->label ?? request('period') }}
                            @else
                                Semua Periode
                            @endif
                        </span>
                    </button>
                    <ul class="dropdown-menu shadow-lg border-0 rounded-3 mt-1 py-2" style="max-height: 280px; overflow-y: auto;">
                        <li>
                            <button class="dropdown-item d-flex align-items-center justify-content-between py-2 px-3 {{ !request('period') ? 'active fw-bold' : '' }}" type="button" onclick="setFilter('period', '')">
                                <span>Semua Periode</span>
                                @if(!request('period')) <i class="fa-solid fa-check small ms-2"></i> @endif
                            </button>
                        </li>
                        <li><hr class="dropdown-divider opacity-50"></li>
                        @foreach($availableMonths as $item)
                            <li>
                                <button class="dropdown-item d-flex align-items-center justify-content-between py-2 px-3 {{ request('period') == $item->value ? 'active fw-bold' : '' }}" type="button" onclick="setFilter('period', '{{ $item->value }}')">
                                    <span>{{ $item->label }}</span>
                                    @if(request('period') == $item->value) <i class="fa-solid fa-check small ms-2"></i> @endif
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <!-- Filter Status Badge Chips -->
                <div class="d-flex align-items-center bg-white p-1 rounded-3 border shadow-sm">
                    <button type="button" onclick="setFilter('status', '')" class="btn btn-sm rounded-2 py-1 px-2.5 border-0 fw-semibold transition-all {{ !request('status') ? 'btn-primary text-white shadow-sm' : 'text-secondary hover-bg-light' }}">
                        Semua
                    </button>
                    <button type="button" onclick="setFilter('status', 'DRAFT')" class="btn btn-sm rounded-2 py-1 px-2.5 border-0 fw-semibold transition-all {{ request('status') == 'DRAFT' ? 'btn-warning text-dark shadow-sm' : 'text-secondary hover-bg-light' }}">
                        DRAFT
                    </button>
                    <button type="button" onclick="setFilter('status', 'SENT')" class="btn btn-sm rounded-2 py-1 px-2.5 border-0 fw-semibold transition-all {{ request('status') == 'SENT' ? 'btn-info text-white shadow-sm' : 'text-secondary hover-bg-light' }}">
                        SENT
                    </button>
                    <button type="button" onclick="setFilter('status', 'PAID')" class="btn btn-sm rounded-2 py-1 px-2.5 border-0 fw-semibold transition-all {{ request('status') == 'PAID' ? 'btn-success text-white shadow-sm' : 'text-secondary hover-bg-light' }}">
                        PAID
                    </button>
                </div>

                <!-- Reset Button -->
                @if(request('period') || request('status'))
                    <a href="{{ route('documents.index') }}" class="btn btn-sm btn-danger-subtle text-danger border border-danger-subtle fw-semibold d-flex align-items-center gap-1.5 ms-md-1 px-2.5 py-1.5 rounded-3" data-bs-toggle="tooltip" title="Bersihkan Filter">
                        <i class="fa-solid fa-xmark"></i>
                        <span class="small">Reset</span>
                    </a>
                @endif

            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 custom-saas-table" id="myTable">
                <thead>
                    <tr>
                        <th width="4%" class="text-center">
                            <input type="checkbox" class="form-check-input" id="selectAllDocs" title="Pilih Semua Faktur">
                        </th>
                        <th width="20%">No. Dokumen</th>
                        <th width="22%">Customer</th>
                        <th width="16%">Total Tagihan</th>
                        <th width="14%">Status</th>
                        <th width="12%" class="text-center">PDF</th>
                        <th width="12%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($documents as $doc)
                    @php
                        $rowGross = $doc->items ? $doc->items->sum('subtotal') : 0;
                        $rowDiscount = $doc->discount ?? 0;
                        $rowDpp = max(0, $rowGross - $rowDiscount);
                        $rowRawTrx = $doc->tax_transaction_code ?? $doc->customer?->default_tax_transaction_code ?? '040';
                        $rowTrxCode = !empty($rowRawTrx) ? substr(preg_replace('/[^0-9]/', '', $rowRawTrx), 0, 2) : '04';
                        $rowOtherTaxBase = ($rowTrxCode === '04') ? round(($rowDpp * 11) / 12, 2) : $rowDpp;
                        $rowTaxRate = ($setting->enable_tax ?? true) ? ($setting->default_tax_rate ?? 11) : 0;
                        $rowPpn = ($rowTrxCode === '04') ? round($rowOtherTaxBase * ($rowTaxRate / 100), 2) : round($rowDpp * ($rowTaxRate / 100), 2);
                    @endphp
                    <tr>
                        <!-- Checkbox Bulk Export (Invoice Only) -->
                        <td class="text-center">
                            @if($doc->type == 'INVOICE')
                                <input type="checkbox" class="form-check-input doc-checkbox" 
                                       value="{{ $doc->id }}" 
                                       data-docnum="{{ $doc->document_number }}"
                                       data-customer="{{ $doc->customer->name ?? '-' }}"
                                       data-subtotal="{{ (float) ($rowTrxCode === '04' ? $rowOtherTaxBase : $rowDpp) }}"
                                       data-dpp="{{ (float) $rowDpp }}"
                                       data-other-tax-base="{{ (float) $rowOtherTaxBase }}"
                                       data-tax="{{ (float) $rowPpn }}"
                                       data-total="{{ (float) ($rowDpp + $rowPpn) }}"
                                       data-has-tax-id="{{ (!empty($doc->customer->tax_id_number) || !empty($doc->customer->npwp)) ? '1' : '0' }}">
                            @else
                                <span class="text-muted small" title="Hanya INVOICE yang dapat diekspor ke Coretax">-</span>
                            @endif
                        </td>

                        <!-- No. Dokumen + Badge Tipe -->
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if($doc->type == 'PO')
                                    <span class="badge-type badge-po">PO</span>
                                @else
                                    <span class="badge-type badge-inv">INV</span>
                                @endif
                                <span class="fw-semibold text-dark font-monospace tracking-wide">{{ $doc->document_number }}</span>
                            </div>
                        </td>

                        <!-- Customer -->
                        <td>
                            <div class="d-flex flex-column">
                                <span class="fw-semibold text-dark">{{ $doc->customer->name ?? 'Tanpa Nama' }}</span>
                                @if(!empty($doc->customer->tax_id_number))
                                    <span class="text-muted font-monospace" style="font-size: 0.72rem;">{{ $doc->customer->tax_id_type ?? 'NPWP16' }}: {{ $doc->customer->tax_id_number }}</span>
                                @endif
                            </div>
                        </td>

                        <!-- Total Tagihan / Nominal -->
                        <td>
                            <span class="fw-bold text-dark">{{ $setting->currency_symbol ?? 'Rp' }} {{ number_format($doc->total_amount, 0, ',', '.') }}</span>
                        </td>
                        
                        <!-- QUICK CHANGE STATUS VIA AJAX -->
                        <td>
                            <select class="form-select status-select fw-semibold" data-id="{{ $doc->id }}">
                                <option value="DRAFT" {{ $doc->status == 'DRAFT' ? 'selected' : '' }}>DRAFT</option>
                                <option value="SENT" {{ $doc->status == 'SENT' ? 'selected' : '' }}>SENT</option>
                                <option value="PAID" {{ $doc->status == 'PAID' ? 'selected' : '' }}>PAID</option>
                            </select>
                        </td>
                        
                        <!-- PDF EXPORT (Modern Button Group) -->
                        <td class="text-center">
                            <div class="action-btn-group justify-content-center">
                                <!-- PDF Utama -->
                                <a href="{{ route('documents.pdf', $doc->id) }}" class="action-icon-btn text-danger" data-bs-toggle="tooltip" data-bs-placement="top" title="Cetak PDF Utama">
                                    <i class="fa-solid fa-file-pdf"></i>
                                </a>

                                @if($doc->type == 'INVOICE')
                                    <!-- Surat Jalan -->
                                    <a href="{{ route('documents.delivery_note', $doc->id) }}" class="action-icon-btn text-success" data-bs-toggle="tooltip" data-bs-placement="top" title="Cetak Surat Jalan">
                                        <i class="fa-solid fa-truck-fast"></i>
                                    </a>

                                    <!-- Kwitansi -->
                                    <a href="{{ route('documents.kwitansi', $doc->id) }}" class="action-icon-btn text-warning" data-bs-toggle="tooltip" data-bs-placement="top" title="Cetak Kwitansi">
                                        <i class="fa-solid fa-receipt"></i>
                                    </a>
                                @endif
                            </div>
                        </td>
                        
                        <!-- ACTIONS (Clean Neutral Design) -->
                        <td class="text-center">
                            <div class="action-btn-group justify-content-center">
                                <a href="{{ route('documents.show', $doc->id) }}" class="action-icon-btn text-primary" data-bs-toggle="tooltip" data-bs-placement="top" title="Detail & Margin Proyek">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <!-- Duplicate -->
                                <a href="{{ route('documents.duplicate', $doc->id) }}" class="action-icon-btn text-secondary" data-bs-toggle="tooltip" data-bs-placement="top" title="Duplikasi">
                                    <i class="fa-solid fa-copy"></i>
                                </a>

                                <!-- Kondisi Edit / Preview Berdasarkan Status -->
                                @if($doc->status == 'PAID')
                                    <!-- Status PAID: Buka Detail Modal (Terkunci) -->
                                <button type="button" class="action-icon-btn text-info btn-show-modal" 
                                            data-docnum="{{ $doc->document_number }}"
                                            data-customer="{{ $doc->customer->name ?? 'Tanpa Nama' }}"
                                            data-taxnum="{{ $doc->customer->tax_id_number ? ($doc->customer->tax_id_type ?? 'NPWP').': '.$doc->customer->tax_id_number : '-' }}"
                                            data-total="{{ $setting->currency_symbol ?? 'Rp' }} {{ number_format($doc->total_amount, 0, ',', '.') }}"
                                            data-items-count="{{ $doc->items ? $doc->items->count() : 0 }} Item"
                                            data-items='@json($doc->items)'
                                            data-pdf="{{ route('documents.pdf', $doc->id) }}"
                                            data-bs-toggle="tooltip" data-bs-placement="top" title="Lihat Detail (Terkunci)">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                @elseif($doc->status == 'SENT')
                                    <!-- Status SENT: Edit dengan Konfirmasi Pop-up -->
                                    <button type="button" class="action-icon-btn text-warning btn-edit-sent" 
                                            data-url="{{ route('documents.edit', $doc->id) }}" 
                                            data-docnum="{{ $doc->document_number }}"
                                            data-bs-toggle="tooltip" data-bs-placement="top" title="Edit (Status SENT)">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                @else
                                    <!-- Status DRAFT: Edit Direct tanpa Warning -->
                                    <a href="{{ route('documents.edit', $doc->id) }}" class="action-icon-btn text-secondary" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit Dokumen">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                @endif
                                
                                <!-- Delete -->
                                <form action="{{ route('documents.destroy', $doc->id) }}" method="POST" class="d-inline form-delete">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="action-icon-btn text-danger btn-delete" data-bs-toggle="tooltip" data-bs-placement="top" title="Hapus">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    {{-- Dikosongkan agar DataTables tidak error. Pesan empty state dihandle oleh opsi language.emptyTable di JS --}}
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Quick Check Coretax Export -->
<div class="modal fade" id="coretaxExportModal" tabindex="-1" aria-labelledby="coretaxExportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow">
            <form action="{{ route('documents.export_coretax') }}" method="POST" id="formExportCoretax">
                @csrf
                <div class="modal-header border-0 pb-0 pt-4 px-4">
                    <div>
                        <div class="badge bg-primary-subtle text-primary fw-bold px-2.5 py-1.5 rounded-pill mb-1 small">
                            <i class="fa-solid fa-code"></i>
                        </div>
                        <h5 class="modal-title fw-bold text-dark" id="coretaxExportModalLabel">Export Faktur Pajak Keluaran (FK)</h5>
                        <p class="text-muted small mb-0">File XML resmi (TaxInvoiceBulk v1.6.1) atau Spreadsheet Excel multi-sheet (NPWP 16 Digit & NITKU 22 Digit).</p>
                    </div>
                    <button type="button" class="btn-close align-self-start" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Quick Stats Cards -->
                    <div class="row g-3 mb-4">
                        <div class="col-sm-4">
                            <div class="p-3 rounded-3 bg-light border text-center">
                                <span class="text-muted small fw-semibold d-block">Jumlah Faktur</span>
                                <span class="fs-4 fw-bold text-dark" id="modalCount">0</span>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="p-3 rounded-3 bg-light border text-center">
                                <span class="text-muted small fw-semibold d-block">Total DPP (Dasar Pengenaan)</span>
                                <span class="fs-5 fw-bold text-primary" id="modalSubtotal">Rp 0</span>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="p-3 rounded-3 bg-light border text-center">
                                <span class="text-muted small fw-semibold d-block">Total PPN (Pajak)</span>
                                <span class="fs-5 fw-bold text-success" id="modalTax">Rp 0</span>
                            </div>
                        </div>
                    </div>

                    <!-- Warning if missing Tax ID -->
                    <div id="modalTaxIdWarning" class="alert alert-warning border-0 rounded-3 d-none mb-4">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-triangle-exclamation text-warning fs-5"></i>
                            <div class="small">
                                <strong>Perhatian:</strong> Ada <span id="missingTaxIdCount" class="fw-bold">0</span> invoice dengan Customer yang belum memiliki Nomor ID Perpajakan (NPWP/NIK). Sistem akan mengisi default '0000000000000000' (0 16-digit).
                            </div>
                        </div>
                    </div>

                    <!-- Options -->
                    <div class="card border rounded-3 p-3 bg-body-tertiary mb-3">
                        <h6 class="fw-bold text-dark mb-2 small text-uppercase tracking-wide">Pengaturan Tanggal Faktur Pajak</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary">Tanggal Faktur Pajak (Opsional)</label>
                                <input type="date" name="tax_invoice_date" class="form-control form-control-sm rounded-3">
                                <span class="text-muted" style="font-size: 0.75rem;">Kosongkan jika ingin menggunakan tanggal asli dari masing-masing Invoice.</span>
                            </div>
                        </div>
                    </div>

                    <!-- Selected IDs Hidden Container -->
                    <div id="modalHiddenIds"></div>
                </div>
                <div class="modal-footer border-0 pt-0 pb-4 px-4 d-flex justify-content-between gap-2 flex-wrap">
                    <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <div class="d-flex align-items-center gap-2">
                        <button type="submit" name="export_type" value="xlsx" class="btn btn-outline-success rounded-3 px-3.5 py-2 fw-semibold d-inline-flex align-items-center gap-2">
                            <i class="fa-solid fa-file-excel fs-6"></i>
                            <span>Excel (.xlsx)</span>
                        </button>
                        <button type="submit" name="export_type" value="xml" class="btn btn-primary rounded-3 px-4 py-2 fw-bold shadow-sm d-inline-flex align-items-center gap-2">
                            <i class="fa-solid fa-file-code fs-6"></i>
                            <span>XML</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detail Document (Read-Only / Terkunci) -->
<div class="modal fade" id="detailDocumentModal" tabindex="-1" aria-labelledby="detailDocumentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-bottom pb-3 pt-4 px-4">
                <div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold px-2.5 py-1 rounded-pill mb-1 small" id="detailDocStatus">PAID</span>
                    <h5 class="modal-title fw-bold text-dark" id="detailDocNumber">INV/2026/001</h5>
                    <p class="text-muted small mb-0">Detail ringkasan dokumen yang sudah terkunci (PAID).</p>
                </div>
                <button type="button" class="btn-close align-self-start" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Info Customer & Total -->
                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <div class="p-3 rounded-3 bg-light border">
                            <span class="text-muted small d-block fw-semibold text-uppercase tracking-wide mb-1">Customer</span>
                            <span class="fw-bold text-dark fs-6 d-block" id="detailCustomerName">-</span>
                            <span class="text-muted font-monospace small" id="detailCustomerTax">-</span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 rounded-3 bg-light border">
                            <span class="text-muted small d-block fw-semibold text-uppercase tracking-wide mb-1">Total Tagihan</span>
                            <span class="fw-bold text-primary fs-5 d-block" id="detailTotalAmount">Rp 0</span>
                            <span class="text-muted small" id="detailItemCount">0 Items</span>
                        </div>
                    </div>
                </div>

                <!-- Tabel List Items Invoice -->
                <div class="mb-4">
                    <h6 class="fw-bold text-dark small text-uppercase tracking-wide mb-2">Rincian Barang / Jasa</h6>
                    <div class="table-responsive border rounded-3 overflow-hidden">
                        <table class="table table-sm table-striped align-middle mb-0" id="detailItemsTable">
                            <thead class="bg-light">
                                <tr class="text-secondary small fw-bold">
                                    <th class="ps-3 py-2">Nama Barang / Jasa</th>
                                    <th class="text-center py-2">Jumlah</th>
                                    <th class="text-center py-2">Satuan</th>
                                    <th class="text-end py-2">Harga</th>
                                    <th class="text-end pe-3 py-2">Total Harga</th>
                                </tr>
                            </thead>
                            <tbody id="detailItemsList">
                                <!-- Render via JS -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="alert alert-info border-0 rounded-3 d-flex align-items-center gap-3 mb-0">
                    <i class="fa-solid fa-lock fs-4 text-info"></i>
                    <div class="small">
                        <strong>Dokumen Terkunci:</strong> Dokumen ini telah berstatus <strong>PAID</strong> sehingga tidak dapat diubah lagi demi menjaga integritas data keuangan & perpajakan.
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 pb-4 px-4 d-flex justify-content-between">
                <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">Tutup</button>
                <a href="#" id="btnDetailPdf" target="_blank" class="btn btn-danger rounded-3 px-3.5 py-2 fw-semibold d-inline-flex align-items-center gap-2">
                    <i class="fa-solid fa-file-pdf"></i>
                    <span>Cetak / Open PDF</span>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<style>
    /* Typography & Layout Rules */
    .tracking-tight { letter-spacing: -0.025em; }
    .tracking-wide { letter-spacing: 0.03em; }

    .custom-saas-table {
        border-collapse: separate;
        border-spacing: 0;
    }
    .custom-saas-table thead th {
        background-color: #f8fafc;
        color: #64748b;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 1rem 0.75rem;
        border-bottom: 1px solid #e2e8f0;
    }
    .custom-saas-table tbody td {
        padding: 0.875rem 0.75rem;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.875rem;
    }

    /* Type Badges */
    .badge-type {
        font-size: 0.7rem;
        font-weight: 700;
        padding: 0.25rem 0.5rem;
        border-radius: 6px;
        letter-spacing: 0.05em;
    }
    .badge-po {
        background-color: #e0f2fe;
        color: #0369a1;
    }
    .badge-inv {
        background-color: #dcfce7;
        color: #15803d;
    }

    /* Action Buttons UI Optimization */
    .action-btn-group {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background-color: #f8fafc;
        padding: 3px 6px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
    }
    .action-icon-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border-radius: 6px;
        border: none;
        background: transparent;
        font-size: 0.875rem;
        transition: all 0.15s ease-in-out;
        text-decoration: none;
    }
    .action-icon-btn:hover {
        background-color: #ffffff;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        transform: translateY(-1px);
    }

    /* Status Select Pill Styles */
    select.status-select {
        font-size: 0.75rem !important;
        font-weight: 700 !important;
        padding: 0.3rem 1.75rem 0.3rem 0.75rem !important;
        border-radius: 20px !important;
        box-shadow: none !important;
        cursor: pointer;
        width: 125px;
        transition: all 0.2s;
    }
    select.status-select.status-draft {
        background-color: #f1f5f9 !important;
        color: #475569 !important;
        border: 1px solid #cbd5e1 !important;
    }
    select.status-select.status-sent {
        background-color: #eff6ff !important;
        color: #1d4ed8 !important;
        border: 1px solid #bfdbfe !important;
    }
    select.status-select.status-paid {
        background-color: #f0fdf4 !important;
        color: #15803d !important;
        border: 1px solid #bbf7d0 !important;
    }

    /* DataTables Control UI Overhaul */
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter {
        margin-bottom: 1.25rem;
        font-size: 0.85rem;
        color: #64748b;
        font-weight: 500;
    }
    .dataTables_wrapper .dataTables_length select {
        border: 1px solid #cbd5e1 !important;
        border-radius: 8px !important;
        padding: 0.35rem 1.8rem 0.35rem 0.75rem !important;
        font-size: 0.85rem !important;
        color: #334155 !important;
        outline: none;
        margin: 0 6px;
    }
    .dataTables_wrapper .dataTables_filter input {
        border: 1px solid #cbd5e1 !important;
        border-radius: 8px !important;
        padding: 0.35rem 0.75rem !important;
        font-size: 0.85rem !important;
        outline: none;
        margin-left: 8px;
    }
    .dataTables_wrapper .dataTables_filter input:focus,
    .dataTables_wrapper .dataTables_length select:focus {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
    }
</style>

<script>
    function setFilter(type, value) {
        if (type === 'period') {
            document.getElementById('inputPeriod').value = value;
        } else if (type === 'status') {
            document.getElementById('inputStatus').value = value;
        }
        document.getElementById('filterForm').submit();
    }

    function updateSelectColor(elem) {
        let val = $(elem).val();
        $(elem).removeClass('status-draft status-sent status-paid');
        if (val === 'DRAFT') {
            $(elem).addClass('status-draft');
        } else if (val === 'SENT') {
            $(elem).addClass('status-sent');
        } else if (val === 'PAID') {
            $(elem).addClass('status-paid');
        }
    }

    function initTooltips() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    $(document).ready(function() {
        // ==========================================
        // POP UP ERROR JIKA DATA FILTER KOSONG
        // ==========================================
        @if($documents->isEmpty() && (request('period') || request('status')))
            Swal.fire({
                icon: 'info',
                title: 'Data Tidak Ditemukan',
                text: 'Tidak ada dokumen yang sesuai dengan filter Periode/Status yang Anda pilih.',
                confirmButtonText: 'OK',
                confirmButtonColor: '#0d6efd',
                customClass: {
                    popup: 'rounded-4 shadow-lg border-0'
                }
            });
        @endif

        // Init Bootstrap Tooltips & Select Colors pada load awal
        initTooltips();
        $('.status-select').each(function() {
            updateSelectColor(this);
        });

        // Initialize DataTables
        const dataTable = $('#myTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json',
                emptyTable: "Tidak ada dokumen yang ditemukan untuk periode/status ini."
            },
            columnDefs: [
                { orderable: false, targets: [0, 4, 5, 6] }
            ]
        });

        // Re-initialize Tooltips & Select Colors ketika DataTables me-render ulang halaman
        dataTable.on('draw.dt', function () {
            initTooltips();
            $('.status-select').each(function() {
                updateSelectColor(this);
            });
        });

        function formatRupiah(number) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(number);
        }

        function updateBulkBar() {
            let checkedBoxes = dataTable.$('.doc-checkbox:checked');
            let count = checkedBoxes.length;

            if (count > 0) {
                $('#selectedCount').text(count);
                $('#bulkActionBar').removeClass('d-none');
            } else {
                $('#bulkActionBar').addClass('d-none');
                $('#selectAllDocs').prop('checked', false);
            }
        }

        // Select All handler
        $('#selectAllDocs').on('change', function() {
            let isChecked = $(this).is(':checked');
            dataTable.$('.doc-checkbox').prop('checked', isChecked);
            updateBulkBar();
        });

        // Individual checkbox handler (Event Delegation)
        $('#myTable tbody').on('change', '.doc-checkbox', function() {
            let totalCheckboxes = dataTable.$('.doc-checkbox').length;
            let checkedCount = dataTable.$('.doc-checkbox:checked').length;
            
            $('#selectAllDocs').prop('checked', totalCheckboxes > 0 && totalCheckboxes === checkedCount);
            updateBulkBar();
        });

        // Clear selection handler
        $('#btnClearSelection').on('click', function() {
            dataTable.$('.doc-checkbox').prop('checked', false);
            $('#selectAllDocs').prop('checked', false);
            updateBulkBar();
        });

        // Open Coretax Modal & calculate summary
        $('#btnOpenCoretaxModal').on('click', function() {
            let checkedBoxes = dataTable.$('.doc-checkbox:checked');
            let count = checkedBoxes.length;

            if (count === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Pilih Dokumen',
                    text: 'Pilih minimal satu faktur / invoice untuk diekspor ke Coretax!'
                });
                return;
            }

            let totalDpp = 0;
            let totalTax = 0;
            let missingTaxId = 0;
            let hiddenInputsHtml = '';

            checkedBoxes.each(function() {
                let id = $(this).val();
                let dpp = parseFloat($(this).data('dpp')) || 0; 
                let tax = parseFloat($(this).data('tax')) || 0;
                let hasTaxId = $(this).data('has-tax-id') == '1';

                totalDpp += dpp;
                totalTax += tax;
                if (!hasTaxId) {
                    missingTaxId++;
                }

                hiddenInputsHtml += `<input type="hidden" name="document_ids[]" value="${id}">`;
            });

            $('#modalCount').text(count);
            $('#modalSubtotal').text(formatRupiah(totalDpp));
            $('#modalTax').text(formatRupiah(totalTax));
            $('#modalHiddenIds').html(hiddenInputsHtml);

            if (missingTaxId > 0) {
                $('#missingTaxIdCount').text(missingTaxId);
                $('#modalTaxIdWarning').removeClass('d-none');
            } else {
                $('#modalTaxIdWarning').addClass('d-none');
            }

            let modalEl = document.getElementById('coretaxExportModal');
            let modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.show();
        });

        // Quick status update AJAX (Event Delegation)
        $('#myTable tbody').on('change', '.status-select', function() {
            let docId = $(this).data('id');
            let newStatus = $(this).val();
            let selectElem = this;

            updateSelectColor(selectElem);

            $.ajax({
                url: `/documents/${docId}/update-status`,
                type: 'PATCH',
                data: {
                    _token: '{{ csrf_token() }}',
                    status: newStatus
                },
                success: function(response) {
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2500,
                        timerProgressBar: true
                    });
                    
                    Toast.fire({
                        icon: 'success',
                        title: response.message
                    });
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Update',
                        text: 'Terjadi kesalahan saat memperbarui status dokumen!'
                    });
                }
            });
        });

        // Event Delegation untuk Tombol Hapus
        $(document).on('click', '.btn-delete', function(e) {
            e.preventDefault();
            let form = $(this).closest('form');
            
            Swal.fire({
                title: 'Apakah Anda Yakin?',
                text: "Dokumen yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });

        // Event Delegation untuk Tombol Open Modal Detail (Status PAID)
        $(document).on('click', '.btn-show-modal', function() {
            let docNum = $(this).data('docnum');
            let customer = $(this).data('customer');
            let taxNum = $(this).data('taxnum');
            let total = $(this).data('total');
            let itemsCount = $(this).data('items-count');
            let itemsData = $(this).data('items');
            let pdfUrl = $(this).data('pdf');

            $('#detailDocNumber').text(docNum);
            $('#detailCustomerName').text(customer);
            $('#detailCustomerTax').text(taxNum);
            $('#detailTotalAmount').text(total);
            $('#detailItemCount').text(itemsCount);
            $('#btnDetailPdf').attr('href', pdfUrl);

            // Render List Items sesuai kolom DocumentItem
            let itemsHtml = '';
            if (itemsData && itemsData.length > 0) {
                itemsData.forEach(function(item) {
                    let description = item.description || '-';
                    let qty = item.qty || 0;
                    let unit = item.unit || 'Pcs';
                    let price = parseFloat(item.price || 0);
                    let subtotal = parseFloat(item.subtotal || (qty * price));

                    itemsHtml += `
                        <tr>
                            <td class="ps-3 py-2 fw-semibold text-dark">${description}</td>
                            <td class="text-center py-2">${qty}</td>
                            <td class="text-center py-2"><span class="badge bg-light text-secondary border">${unit}</span></td>
                            <td class="text-end py-2">Rp ${new Intl.NumberFormat('id-ID').format(price)}</td>
                            <td class="text-end pe-3 py-2 fw-bold text-dark">Rp ${new Intl.NumberFormat('id-ID').format(subtotal)}</td>
                        </tr>
                    `;
                });
            } else {
                itemsHtml = `
                    <tr>
                        <td colspan="5" class="text-center py-3 text-muted small">Tidak ada item dalam dokumen ini.</td>
                    </tr>
                `;
            }

            $('#detailItemsList').html(itemsHtml);

            let modalEl = document.getElementById('detailDocumentModal');
            let modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.show();
        });
    });

    // Event Handler untuk Tombol Edit Status SENT dengan SweetAlert2
    $(document).on('click', '.btn-edit-sent', function(e) {
        e.preventDefault();
        let editUrl = $(this).data('url');
        let docNum = $(this).data('docnum');

        Swal.fire({
            title: 'Edit Dokumen yang Sudah Terkirim?',
            html: `Dokumen <strong>${docNum}</strong> memiliki status <span class="badge bg-info text-white">SENT</span>. Perubahan data mungkin mempengaruhi informasi yang sudah diterima customer.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<span class="text-dark fw-bold">Ya, Lanjutkan Edit</span>',
            cancelButtonText: 'Batal',
            customClass: {
                popup: 'rounded-4 shadow-lg border-0'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = editUrl;
            }
        });
    });
</script>
@endpush