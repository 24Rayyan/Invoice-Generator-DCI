@extends('layouts.app')

@php
    $currency = $setting->currency_symbol ?? 'Rp';
@endphp

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none text-muted small">Ringkasan</a></li>
                <li class="breadcrumb-item"><a href="{{ route($indexRoute) }}" class="text-decoration-none text-muted small">{{ $document->type === 'PO' ? 'PO' : 'Invoice' }}</a></li>
                <li class="breadcrumb-item active small fw-semibold" aria-current="page">{{ $document->document_number }}</li>
            </ol>
        </nav>
        <h1 class="h3 fw-bold text-dark mb-1">{{ $document->document_number }}</h1>
        <p class="text-muted mb-0 font-sm">
            {{ $document->customer->name ?? 'Tanpa Pelanggan' }}
            @if($document->project && auth()->user()->hasAccess('projects'))
                · <a href="{{ route('projects.show', $document->project) }}" class="text-decoration-none">{{ $document->project->project_name }}</a>
            @endif
            · {{ $document->date?->format('d/m/Y') }}
            · <span class="badge bg-{{ $document->status === 'PAID' ? 'success' : ($document->status === 'SENT' ? 'info' : 'warning') }}-subtle text-{{ $document->status === 'PAID' ? 'success' : ($document->status === 'SENT' ? 'info' : 'dark') }} border">{{ $document->status }}</span>
            · {{ $document->type }}
        </p>
        @if($document->sourcePurchaseOrder)
            <p class="small mb-0 mt-1">PO sumber:
                <a href="{{ route('documents.show', $document->sourcePurchaseOrder) }}" class="text-decoration-none">{{ $document->sourcePurchaseOrder->document_number }}</a>
            </p>
        @endif
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a data-download href="{{ route('documents.pdf', $document->id) }}" class="btn btn-outline-danger">
            <i class="fa-solid fa-file-pdf me-1"></i> PDF
        </a>
        @if($document->status !== 'PAID')
            <a href="{{ route('documents.edit', $document->id) }}" class="btn btn-outline-secondary">
                <i class="fa-solid fa-pen-to-square me-1"></i> Edit
            </a>
        @endif
        <a href="{{ route($indexRoute) }}" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3" role="alert">
    <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3" role="alert">
    <i class="fa-solid fa-triangle-exclamation me-2"></i> {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3" role="alert">
    <ul class="mb-0 ps-3">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
</div>
@endif

@if($document->type === 'INVOICE' && $canViewInvoiceMargin)
<section class="financial-summary-strip mb-4" aria-label="{{ $document->project ? 'Ringkasan keuangan proyek' : 'Ringkasan margin invoice' }}">
    <div class="financial-summary-grid">
        <div class="financial-summary-item financial-summary-item--lead">
            <div class="financial-summary-label">Laba Bersih</div>
            <div class="financial-summary-value {{ $margin['net_profit'] >= 0 ? 'financial-summary-value--positive' : 'financial-summary-value--negative' }}">{{ $currency }} {{ number_format($margin['net_profit'], 0, ',', '.') }}</div>
            <small class="financial-summary-note">{{ $document->project ? 'Pendapatan proyek dikurangi biaya proyek.' : 'Pendapatan dikurangi biaya langsung.' }}</small>
        </div>
        <div class="financial-summary-item">
            <div class="financial-summary-label">Total Pendapatan</div>
            <div class="financial-summary-value financial-summary-value--positive">{{ $currency }} {{ number_format($margin['revenue'], 0, ',', '.') }}</div>
            <small class="financial-summary-note">{{ $document->project ? 'Total seluruh invoice dalam proyek.' : 'Nilai invoice, termasuk pajak jika aktif.' }}</small>
        </div>
        <div class="financial-summary-item">
            <div class="financial-summary-label">Total Biaya Langsung</div>
            <div class="financial-summary-value financial-summary-value--negative">{{ $currency }} {{ number_format($margin['direct_expense'], 0, ',', '.') }}</div>
            <small class="financial-summary-note">{{ $document->project ? 'Total alokasi kas dan pengeluaran invoice proyek.' : 'Kas keluar yang terhubung ke invoice ini.' }}</small>
        </div>
        <div class="financial-summary-item">
            <div class="financial-summary-label">Margin Bersih</div>
            <div class="financial-summary-value">{{ number_format($margin['net_margin'], 2, ',', '.') }}%</div>
            <small class="financial-summary-note">
                @if($margin['revenue'] <= 0)
                    Pendapatan 0, margin ditampilkan 0%.
                @else
                    Laba bersih dibagi pendapatan.
                @endif
            </small>
        </div>
    </div>
</section>
@endif

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white border-0 py-3 px-4">
        <h2 class="h6 fw-bold mb-0"><i class="fa-solid fa-list me-2 text-primary"></i> Item Dokumen</h2>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0 m-cards">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">{{ $document->type === 'PO' ? 'Nama Barang' : 'Deskripsi' }}</th>
                        @if($document->type === 'PO')
                            <th>Spesifikasi</th>
                            <th>Kuantitas</th>
                        @else
                            <th>Kuantitas</th>
                            <th>Satuan</th>
                            <th class="text-end">Harga</th>
                            <th class="text-end pe-4">Subtotal</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($document->items as $item)
                    <tr>
                        @if($document->type === 'PO')
                            <td class="ps-4 fw-semibold mc-title mc-o1" data-label="Nama Barang">{{ $item->description }}</td>
                            <td class="mc-o3" data-label="Spesifikasi">{{ $item->notes ?: '-' }}</td>
                            <td class="mc-half mc-o2" data-label="Kuantitas">{{ $item->qty }}</td>
                        @else
                            <td class="ps-4 mc-title mc-o1" data-label="Deskripsi">
                                <div class="fw-semibold">{{ $item->description }}</div>
                                @if($item->notes)
                                    <small class="text-muted">{{ $item->notes }}</small>
                                @endif
                            </td>
                            <td class="mc-half mc-o2" data-label="Kuantitas">{{ $item->qty }}</td>
                            <td class="mc-half mc-o3" data-label="Satuan">{{ $item->unit }}</td>
                            <td class="text-end mc-half mc-o4" data-label="Harga">{{ $currency }} {{ number_format($item->price, 0, ',', '.') }}</td>
                            <td class="text-end pe-4 fw-bold mc-half mc-o5" data-label="Subtotal">{{ $currency }} {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        @endif
                    </tr>
                    @empty
                    <tr class="mc-empty">
                        <td colspan="{{ $document->type === 'PO' ? 3 : 5 }}" class="text-center text-muted py-4">Belum ada item.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($document->type === 'INVOICE' && $canViewInvoiceMargin)
<div class="card border-0 shadow-sm rounded-4 mb-5">
    <div class="card-header bg-white border-0 py-3 px-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
        <div>
            <h2 class="h6 fw-bold mb-0"><i class="fa-solid fa-link me-2 text-danger"></i> Biaya Langsung Terhubung</h2>
            <small class="text-muted">{{ $document->project ? 'Daftar ini memuat kas yang tertaut langsung ke invoice; ringkasan di atas juga mencakup alokasi kas proyek.' : 'Biaya operasional umum yang tidak terhubung ke Invoice tidak masuk perhitungan margin ini.' }}</small>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#attachKasModal">
            <i class="fa-solid fa-link me-1"></i> Hubungkan Kas
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0 m-cards">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">No. Transaksi</th>
                        <th>Tanggal</th>
                        <th>Kategori</th>
                        <th>Keterangan</th>
                        <th class="text-end">Jumlah</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($document->directExpenses as $trx)
                    <tr>
                        <td class="ps-4 font-mono fw-semibold mc-title mc-o1" data-label="No. Transaksi">{{ $trx->transaction_number }}</td>
                        <td class="mc-half mc-o3" data-label="Tanggal">{{ $trx->transaction_date?->format('d/m/Y') }}</td>
                        <td class="mc-half mc-o4" data-label="Kategori">
                            <span class="fw-semibold font-sm">[{{ $trx->category?->code }}] {{ $trx->category?->name }}</span>
                        </td>
                        <td class="font-sm mc-o5" data-label="Keterangan">{{ $trx->description ?? '-' }}</td>
                        <td class="text-end fw-bold text-danger mc-amount mc-o2" data-label="Jumlah">- {{ $currency }} {{ number_format($trx->amount, 0, ',', '.') }}</td>
                        <td class="text-end pe-4 mc-foot pm-action-cell mc-o6" data-label="Aksi">
                            <form action="{{ route('documents.detach-transaction', [$document, $trx]) }}" method="POST" class="d-inline" data-confirm="Transaksi tidak dihapus, hanya dilepas dari invoice ini." data-confirm-impact="Transaksi menjadi biaya operasional umum dan tidak lagi dihitung dalam margin invoice ini." data-confirm-title="Lepas Transaksi dari Invoice?" data-confirm-subject="{{ $document->document_number }}" data-confirm-button="Ya, Lepas" data-confirm-variant="warning">
                                @csrf
                                <button type="submit" class="btn btn-sm pm-detach-btn">
                                    <i class="fa-solid fa-unlink"></i><span>Lepas</span>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr class="mc-empty">
                        <td colspan="6" class="text-center text-muted py-5">
                            Belum ada kas keluar yang dihubungkan ke invoice ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="attachKasModal" tabindex="-1" aria-labelledby="attachKasModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4 shadow">
            <form action="{{ route('documents.attach-transactions', $document) }}" method="POST" class="d-flex flex-column flex-grow-1" style="min-height: 0;">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-bold" id="attachKasModalLabel">Hubungkan Kas ke Invoice</h5>
                        <p class="text-muted font-sm mb-0">Hanya menampilkan kas keluar yang belum terhubung ke invoice maupun project.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body overflow-y-auto" style="min-height: 0;">
                    @if($unallocatedExpenses->isEmpty())
                        <div class="text-center text-muted py-4">
                            Tidak ada kas keluar yang belum terhubung ke invoice maupun project.
                        </div>
                    @else
                        <div class="row g-2 mb-3">
                            <div class="col-md-5">
                                <label for="unallocatedSearch" class="form-label small fw-semibold mb-1">Cari Transaksi</label>
                                <input type="search" id="unallocatedSearch" class="form-control form-control-sm" placeholder="Nomor transaksi, keterangan, atau nominal">
                            </div>
                            <div class="col-md-3">
                                <label for="unallocatedDateFrom" class="form-label small fw-semibold mb-1">Tanggal Mulai</label>
                                <input type="date" id="unallocatedDateFrom" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3">
                                <label for="unallocatedDateTo" class="form-label small fw-semibold mb-1">Tanggal Akhir</label>
                                <input type="date" id="unallocatedDateTo" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="button" id="resetUnallocatedFilters" class="btn btn-sm btn-outline-secondary w-100" title="Hapus filter">
                                    <i class="fa-solid fa-rotate-left"></i>
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead class="bg-light">
                                    <tr>
                                        <th width="40">
                                            <input type="checkbox" class="form-check-input" id="selectAllUnallocated">
                                        </th>
                                        <th>No. Transaksi</th>
                                        <th>Tanggal</th>
                                        <th>Keterangan</th>
                                        <th class="text-end">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($unallocatedExpenses as $trx)
                                    <tr
                                        data-transaction-search="{{ \Illuminate\Support\Str::lower($trx->transaction_number . ' ' . ($trx->description ?? '') . ' ' . $trx->transaction_date?->format('d/m/Y') . ' ' . $trx->amount . ' ' . number_format($trx->amount, 0, ',', '.') . ' ' . $currency) }}"
                                        data-transaction-date="{{ $trx->transaction_date?->format('Y-m-d') }}"
                                    >
                                        <td>
                                            <input type="checkbox" class="form-check-input unallocated-check" name="transaction_ids[]" value="{{ $trx->id }}">
                                        </td>
                                        <td class="font-mono font-sm">{{ $trx->transaction_number }}</td>
                                        <td>{{ $trx->transaction_date?->format('d/m/Y') }}</td>
                                        <td class="font-sm">{{ \Illuminate\Support\Str::limit($trx->description, 40) }}</td>
                                        <td class="text-end fw-semibold">{{ $currency }} {{ number_format($trx->amount, 0, ',', '.') }}</td>
                                    </tr>
                                    @endforeach
                                    <tr id="noUnallocatedMatches" class="d-none">
                                        <td colspan="5" class="text-center text-muted py-4">Tidak ada transaksi yang sesuai dengan pencarian atau rentang tanggal.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" {{ $unallocatedExpenses->isEmpty() ? 'disabled' : '' }}>
                        <i class="fa-solid fa-link me-1"></i> Hubungkan yang Dipilih
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        function filterUnallocatedTransactions() {
            const search = $('#unallocatedSearch').val().trim().toLocaleLowerCase();
            const dateFrom = $('#unallocatedDateFrom').val();
            const dateTo = $('#unallocatedDateTo').val();
            let visibleCount = 0;

            $('#attachKasModal tbody tr[data-transaction-date]').each(function () {
                const row = $(this);
                const rowDate = row.data('transaction-date');
                const matchesSearch = !search || String(row.data('transaction-search')).toLocaleLowerCase().includes(search);
                const matchesDateFrom = !dateFrom || rowDate >= dateFrom;
                const matchesDateTo = !dateTo || rowDate <= dateTo;
                const isVisible = matchesSearch && matchesDateFrom && matchesDateTo;

                row.toggleClass('d-none', !isVisible);
                visibleCount += isVisible ? 1 : 0;
            });

            $('#noUnallocatedMatches').toggleClass('d-none', visibleCount > 0);

            const visibleCheckboxes = $('#attachKasModal tbody tr[data-transaction-date]:not(.d-none) .unallocated-check');
            const selectedCount = visibleCheckboxes.filter(':checked').length;
            $('#selectAllUnallocated')
                .prop('checked', visibleCheckboxes.length > 0 && selectedCount === visibleCheckboxes.length)
                .prop('indeterminate', selectedCount > 0 && selectedCount < visibleCheckboxes.length);
        }

        $('#unallocatedSearch, #unallocatedDateFrom, #unallocatedDateTo').on('input change', filterUnallocatedTransactions);
        $('#resetUnallocatedFilters').on('click', function () {
            $('#unallocatedSearch, #unallocatedDateFrom, #unallocatedDateTo').val('');
            filterUnallocatedTransactions();
        });

        $('#selectAllUnallocated').on('change', function () {
            $('#attachKasModal tbody tr[data-transaction-date]:not(.d-none) .unallocated-check').prop('checked', this.checked);
            filterUnallocatedTransactions();
        });

        $('#attachKasModal').on('change', '.unallocated-check', filterUnallocatedTransactions);
    });
</script>
@endpush
