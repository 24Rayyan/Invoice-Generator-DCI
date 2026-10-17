@extends('layouts.app')

@push('styles')
<style>
    .cashflow-page {
        --cashflow-ink: #172033;
        --cashflow-muted: #7b8494;
        --cashflow-line: #e9edf3;
        color: var(--cashflow-ink);
    }

    .cashflow-page .cashflow-title {
        font-size: clamp(1.55rem, 2.5vw, 2rem);
        letter-spacing: -0.045em;
    }

    .cashflow-page .cashflow-subtitle,
    .cashflow-page .cashflow-eyebrow,
    .cashflow-page .cashflow-note {
        color: var(--cashflow-muted);
    }

    .cashflow-page .cashflow-kpi {
        min-height: 124px;
        border: 1px solid #eff1f5;
        border-radius: 16px;
        box-shadow: 0 5px 18px rgba(20, 34, 58, 0.035);
    }

    .cashflow-page .cashflow-kpi-value {
        font-size: clamp(1.15rem, 2vw, 1.5rem);
        letter-spacing: -0.04em;
        overflow-wrap: anywhere;
    }

    .cashflow-page .cashflow-kpi-icon {
        color: #788392;
    }

    .cashflow-page .cashflow-kpi-net {
        background: #102446;
        border-color: #102446;
    }

    .cashflow-page .cashflow-kpi-net .cashflow-kpi-label,
    .cashflow-page .cashflow-kpi-net .cashflow-kpi-icon {
        color: #c5d1e2;
    }

    .cashflow-page .cashflow-panel {
        border: 0;
        border-radius: 17px;
        box-shadow: 0 5px 20px rgba(20, 34, 58, 0.04);
    }

    .cashflow-page .cashflow-table {
        min-width: 900px;
    }

    .cashflow-page .cashflow-table thead th {
        background: #f8f9fb;
        color: #747e8d;
        font-size: 0.65rem;
        font-weight: 700;
        letter-spacing: 0.035em;
        padding-top: 0.78rem;
        padding-bottom: 0.78rem;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .cashflow-page .cashflow-table tbody td {
        border-color: var(--cashflow-line);
        font-size: 0.76rem;
        padding-top: 0.8rem;
        padding-bottom: 0.8rem;
        vertical-align: middle;
    }

    .cashflow-page .cashflow-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .cashflow-page .cashflow-transaction-icon {
        width: 25px;
        height: 25px;
        flex: 0 0 auto;
    }

    .cashflow-page .cashflow-page-link {
        min-width: 32px;
    }

    @media (max-width: 575.98px) {
        .cashflow-page .cashflow-kpi {
            min-height: 112px;
        }

        .cashflow-page .cashflow-panel-header {
            padding: 1rem !important;
        }
    }
</style>
@endpush

@section('content')
@php
    $currency = $setting->currency_symbol ?? 'Rp';
    $filters = [
        'period' => request('period', $period),
        'type' => request('type'),
        'category_id' => request('category_id'),
        'payment_method' => request('payment_method'),
        'search' => request('search'),
        'per_page' => $perPage,
    ];
@endphp

<div class="cashflow-page">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="cashflow-title fw-bold mb-1">Manajemen Arus Kas</h1>
            <p class="cashflow-subtitle small mb-0">Kelola pemasukan, pengeluaran, dan pencatatan transaksi kas.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('transactions.create', ['type' => 'expense']) }}" class="btn btn-sm btn-outline-primary bg-white shadow-sm px-3">
                <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Catat Pengeluaran
            </a>
            <a href="{{ route('transactions.create', ['type' => 'income']) }}" class="btn btn-sm btn-primary shadow-sm px-3">
                <i class="fa-solid fa-plus me-1"></i> Catat Pemasukan
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="cashflow-eyebrow small fw-semibold mb-2">Ringkasan Arus Kas</div>
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-4">
            <div class="cashflow-kpi card h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="cashflow-kpi-label small text-muted">Total Pemasukan Kas</span>
                        <i class="cashflow-kpi-icon fa-solid fa-arrow-trend-up"></i>
                    </div>
                    <div class="cashflow-kpi-value fw-bold mb-2">{{ $currency }} {{ number_format($totalIncome, 0, ',', '.') }}</div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge rounded-pill bg-success-subtle text-success">{{ $incomeCount }} transaksi masuk</span>
                        <span class="cashflow-note small">Total akumulasi kas masuk</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-4">
            <div class="cashflow-kpi card h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="cashflow-kpi-label small text-muted">Total Pengeluaran Kas</span>
                        <i class="cashflow-kpi-icon fa-solid fa-arrow-trend-down text-danger"></i>
                    </div>
                    <div class="cashflow-kpi-value fw-bold mb-2">{{ $currency }} {{ number_format($totalExpense, 0, ',', '.') }}</div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge rounded-pill bg-danger-subtle text-danger">{{ $expenseCount }} transaksi keluar</span>
                        <span class="cashflow-note small">Total akumulasi kas keluar</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="cashflow-kpi cashflow-kpi-net card h-100 text-white">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="cashflow-kpi-label small">Arus Kas Bersih</span>
                        <i class="cashflow-kpi-icon fa-solid fa-right-left"></i>
                    </div>
                    <div class="cashflow-kpi-value fw-bold mb-2">{{ $currency }} {{ number_format($netCashflow, 0, ',', '.') }}</div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge rounded-pill bg-primary-subtle text-primary">Net Cashflow</span>
                        <span class="small text-white-50">Kas masuk − kas keluar</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="cashflow-panel card mb-4">
        <div class="card-body p-3 p-lg-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h6 fw-bold mb-0"><i class="fa-solid fa-sliders me-2 text-primary"></i>Filter Transaksi</h2>
                <a href="{{ route('transactions.index') }}" class="small fw-semibold text-decoration-none">Reset filter</a>
            </div>
            <form method="GET" action="{{ route('transactions.index') }}" class="row g-2 align-items-end">
                <div class="col-sm-6 col-xl-3">
                    <label for="transactionPeriod" class="form-label small text-muted mb-1">Periode</label>
                    <select id="transactionPeriod" name="period" class="form-select form-select-sm">
                        <option value="" {{ $period === '' ? 'selected' : '' }}>Semua periode</option>
                        @foreach($availableMonths as $monthOption)
                            <option value="{{ $monthOption->value }}" {{ $period === $monthOption->value ? 'selected' : '' }}>
                                {{ $monthOption->label }}
                            </option>
                        @endforeach
                        @if($period && ! $availableMonths->pluck('value')->contains($period))
                            <option value="{{ $period }}" selected>{{ \Carbon\Carbon::createFromFormat('Y-m', $period)->translatedFormat('F Y') }}</option>
                        @endif
                    </select>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <label for="transactionType" class="form-label small text-muted mb-1">Tipe transaksi</label>
                    <select id="transactionType" name="type" class="form-select form-select-sm">
                        <option value="">Semua Tipe</option>
                        <option value="income" {{ request('type') === 'income' ? 'selected' : '' }}>Pemasukan</option>
                        <option value="expense" {{ request('type') === 'expense' ? 'selected' : '' }}>Pengeluaran</option>
                    </select>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <label for="transactionCategory" class="form-label small text-muted mb-1">Kategori akun (COA)</label>
                    <select id="transactionCategory" name="category_id" class="form-select form-select-sm">
                        <option value="">Semua Kategori</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ (string) request('category_id') === (string) $category->id ? 'selected' : '' }}>
                                [{{ $category->code }}] {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <label for="transactionPaymentMethod" class="form-label small text-muted mb-1">Metode bayar</label>
                    <select id="transactionPaymentMethod" name="payment_method" class="form-select form-select-sm">
                        <option value="">Semua Metode</option>
                        <option value="Bank Transfer" {{ request('payment_method') === 'Bank Transfer' ? 'selected' : '' }}>Bank Transfer</option>
                        <option value="Cash" {{ request('payment_method') === 'Cash' ? 'selected' : '' }}>Cash / Tunai</option>
                        <option value="e-Wallet" {{ request('payment_method') === 'e-Wallet' ? 'selected' : '' }}>e-Wallet / QRIS</option>
                    </select>
                </div>
                <input type="hidden" name="search" value="{{ request('search') }}">
                <input type="hidden" name="per_page" value="{{ $perPage }}">
                <div class="col-12 d-flex justify-content-end pt-1">
                    <button type="submit" class="btn btn-sm btn-primary px-3">
                        <i class="fa-solid fa-filter me-1"></i> Terapkan Filter
                    </button>
                </div>
            </form>
        </div>
    </section>

    <section class="cashflow-panel card">
        <div class="cashflow-panel-header card-header bg-white border-0 px-4 py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2">
                    <h2 class="h6 fw-bold mb-0">Riwayat Transaksi</h2>
                    <span class="badge rounded-pill bg-light text-secondary">{{ number_format($totalTransactions, 0, ',', '.') }} transaksi</span>
                </div>
                <p class="cashflow-note small mb-0 mt-1">Rincian kas masuk, kas keluar, dan klasifikasi akun.</p>
            </div>
            <form method="GET" action="{{ route('transactions.index') }}" class="d-flex gap-2">
                @foreach($filters as $name => $value)
                    @if($name !== 'search' && $value !== null && $value !== '')
                        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                    @endif
                @endforeach
                <div class="input-group input-group-sm" style="max-width: 280px;">
                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="search" name="search" class="form-control border-start-0 bg-light" value="{{ request('search') }}" placeholder="Cari nomor atau keterangan">
                    <button class="btn btn-outline-secondary" type="submit">Cari</button>
                </div>
            </form>
        </div>

        <div class="alert alert-primary border-0 rounded-0 py-2 px-4 mb-0 small">
            <i class="fa-solid fa-circle-info me-1"></i>
            Urutan transaksi dimulai dari tanggal terbaru. Gunakan filter atau pencarian untuk menemukan transaksi tertentu.
        </div>

        <div class="table-responsive">
            <table class="cashflow-table table align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">No. Transaksi / Tanggal</th>
                        <th>Kategori & Klasifikasi Fiskal</th>
                        <th>Keterangan / Relasi</th>
                        <th>Metode</th>
                        <th class="text-end">Jumlah ({{ $currency }})</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $trx)
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center gap-2">
                                <span class="cashflow-transaction-icon rounded-circle {{ $trx->type === 'income' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} d-inline-flex align-items-center justify-content-center">
                                    <i class="fa-solid {{ $trx->type === 'income' ? 'fa-arrow-down' : 'fa-arrow-up' }}" style="font-size: .65rem;"></i>
                                </span>
                                <div>
                                    <div class="font-monospace fw-semibold text-dark">{{ $trx->transaction_number }}</div>
                                    <div class="cashflow-note small">{{ $trx->transaction_date?->format('d/m/Y') ?? 'Tanggal tidak tersedia' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="text-truncate fw-medium" style="max-width: 240px;" title="{{ $trx->category?->name }}">
                                [{{ $trx->category?->code ?? '-' }}] {{ $trx->category?->name ?? 'Tanpa kategori' }}
                            </div>
                            @if($trx->type === 'income')
                                <span class="cashflow-note small">Pemasukan</span>
                            @elseif($trx->category?->is_tax_deductible)
                                <span class="cashflow-note small">Deductible</span>
                            @else
                                <span class="cashflow-note small">Koreksi fiskal</span>
                            @endif
                        </td>
                        <td>
                            <div class="text-truncate" style="max-width: 270px;" title="{{ $trx->description }}">
                                {{ $trx->description ?? '-' }}
                            </div>
                            @if($trx->document)
                                <a href="{{ route('documents.show', $trx->document) }}" class="small text-decoration-none">
                                    <i class="fa-solid fa-link me-1"></i>{{ $trx->document->document_number }}
                                </a>
                            @endif
                            @if($trx->project)
                                <a href="{{ route('projects.show', $trx->project) }}" class="small text-decoration-none d-block">
                                    <i class="fa-solid fa-diagram-project me-1"></i>{{ $trx->project->project_name }}
                                </a>
                            @elseif(!$trx->document && $trx->entertainmentDetail)
                                <div class="cashflow-note small">{{ $trx->entertainmentDetail->attendee_name }} · {{ $trx->entertainmentDetail->attendee_company }}</div>
                            @elseif(!$trx->document && !$trx->project)
                                <div class="cashflow-note small">Tidak terhubung ke invoice</div>
                            @endif
                        </td>
                        <td class="text-nowrap">{{ $trx->payment_method ?: '—' }}</td>
                        <td class="text-end fw-semibold {{ $trx->type === 'income' ? 'text-success' : 'text-danger' }} text-nowrap">
                            {{ $trx->type === 'income' ? '+' : '−' }} {{ $currency }} {{ number_format($trx->amount, 0, ',', '.') }}
                        </td>
                        <td class="text-end pe-4 text-nowrap">
                            @if($trx->receipt_file_path)
                                <a href="{{ route('transactions.receipt', $trx) }}" target="_blank" class="btn btn-sm btn-outline-info" title="Lihat bukti nota">
                                    <i class="fa-solid fa-receipt"></i>
                                </a>
                            @endif
                            @if($trx->type === 'income' && $trx->invoice_id)
                                <span class="badge bg-light text-muted border" title="Transaksi pelunasan otomatis dari invoice">
                                    <i class="fa-solid fa-lock me-1"></i> Auto
                                </span>
                            @else
                                <a href="{{ route('transactions.edit', $trx) }}" class="btn btn-sm btn-outline-primary" title="Edit transaksi">
                                    <i class="fa-solid fa-pencil"></i>
                                </a>
                                <form action="{{ route('transactions.destroy', $trx) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus transaksi ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus transaksi">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-receipt fs-2 mb-2 d-block text-secondary"></i>
                            Tidak ada transaksi kas yang cocok dengan filter yang dipilih.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transactions->total() > 0)
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-center gap-3 px-4 py-3 border-top">
                <form method="GET" action="{{ route('transactions.index') }}" class="d-flex align-items-center gap-2 small">
                    @foreach($filters as $name => $value)
                        @if($name !== 'per_page' && $value !== null && $value !== '')
                            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <label for="transactionsPerPage" class="cashflow-note text-nowrap">Baris per halaman</label>
                    <select id="transactionsPerPage" name="per_page" class="form-select form-select-sm" onchange="this.form.submit()" style="width: 75px;">
                        @foreach([10, 25, 50, 100] as $pageSize)
                            <option value="{{ $pageSize }}" {{ $perPage === $pageSize ? 'selected' : '' }}>{{ $pageSize }}</option>
                        @endforeach
                    </select>
                </form>

                <span class="cashflow-note small text-center">
                    Rentang halaman {{ $transactions->firstItem() }}–{{ $transactions->lastItem() }}
                    dari {{ number_format($transactions->total(), 0, ',', '.') }} transaksi
                </span>

                @if($transactions->lastPage() > 1)
                    <nav aria-label="Navigasi halaman transaksi">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item {{ $transactions->onFirstPage() ? 'disabled' : '' }}">
                                <a class="page-link" href="{{ $transactions->previousPageUrl() ?? '#' }}" aria-label="Sebelumnya">‹ Sebelumnya</a>
                            </li>
                            @for($page = max(1, $transactions->currentPage() - 2); $page <= min($transactions->lastPage(), $transactions->currentPage() + 2); $page++)
                                <li class="page-item {{ $page === $transactions->currentPage() ? 'active' : '' }}">
                                    <a class="page-link cashflow-page-link" href="{{ $transactions->url($page) }}">{{ $page }}</a>
                                </li>
                            @endfor
                            <li class="page-item {{ $transactions->hasMorePages() ? '' : 'disabled' }}">
                                <a class="page-link" href="{{ $transactions->nextPageUrl() ?? '#' }}" aria-label="Berikutnya">Berikutnya ›</a>
                            </li>
                        </ul>
                    </nav>
                @endif
            </div>
        @endif
    </section>
</div>
@endsection
