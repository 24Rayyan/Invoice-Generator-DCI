@extends('layouts.app')

@push('styles')
<style>
    .finance-dashboard {
        --dashboard-ink: #172033;
        --dashboard-muted: #7b8494;
        --dashboard-line: #e9edf3;
        --dashboard-blue: #0878f9;
        color: var(--dashboard-ink);
    }

    .finance-dashboard .dashboard-heading {
        font-size: clamp(1.55rem, 2.5vw, 2rem);
        letter-spacing: -0.045em;
    }

    .finance-dashboard .dashboard-subtitle,
    .finance-dashboard .dashboard-eyebrow,
    .finance-dashboard .dashboard-footnote {
        color: var(--dashboard-muted);
    }

    .finance-dashboard .dashboard-eyebrow {
        font-size: 0.73rem;
    }

    .finance-dashboard .dashboard-kpi {
        min-height: 124px;
        border: 1px solid #eff1f5;
        border-radius: 16px;
        box-shadow: 0 5px 18px rgba(20, 34, 58, 0.035);
    }

    .finance-dashboard .dashboard-kpi-value {
        font-size: clamp(1.2rem, 2vw, 1.55rem);
        letter-spacing: -0.045em;
        overflow-wrap: anywhere;
    }

    .finance-dashboard .dashboard-kpi-icon {
        color: #737e8e;
    }

    .finance-dashboard .dashboard-kpi-profit {
        background: #102446;
        border-color: #102446;
    }

    .finance-dashboard .dashboard-kpi-profit .dashboard-kpi-label,
    .finance-dashboard .dashboard-kpi-profit .dashboard-kpi-icon {
        color: #c5d1e2;
    }

    .finance-dashboard .dashboard-section {
        border: 0;
        border-radius: 17px;
        box-shadow: 0 5px 20px rgba(20, 34, 58, 0.04);
    }

    .finance-dashboard .dashboard-section-header {
        border-bottom: 1px solid var(--dashboard-line);
    }

    .finance-dashboard .dashboard-table {
        min-width: 690px;
    }

    .finance-dashboard .dashboard-table thead th {
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

    .finance-dashboard .dashboard-table tbody td {
        border-color: var(--dashboard-line);
        font-size: 0.76rem;
        padding-top: 0.85rem;
        padding-bottom: 0.85rem;
    }

    .finance-dashboard .dashboard-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .finance-dashboard .dashboard-row-icon {
        width: 27px;
        height: 27px;
        background: #f2f4f7;
        color: #788392;
        flex: 0 0 auto;
    }

    .finance-dashboard .dashboard-expense-icon {
        width: 24px;
        height: 24px;
        background: #fff0f0;
        color: #e65757;
        flex: 0 0 auto;
    }

    .finance-dashboard .dashboard-status {
        font-size: 0.62rem;
        font-weight: 700;
    }

    @media (max-width: 575.98px) {
        .finance-dashboard .dashboard-kpi {
            min-height: 112px;
        }

        .finance-dashboard .dashboard-section-header {
            padding: 1rem !important;
        }
    }
</style>
@endpush

@section('content')
@php
    $currency = $setting->currency_symbol ?? 'Rp';
    $statusClasses = [
        'PAID' => 'bg-success-subtle text-success',
        'SENT' => 'bg-primary-subtle text-primary',
        'DRAFT' => 'bg-warning-subtle text-dark',
    ];
@endphp

<div class="finance-dashboard">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="dashboard-heading fw-bold mb-1">Dashboard Keuangan</h1>
            <p class="dashboard-subtitle small mb-0">Ringkasan bisnis, tagihan, dan transaksi kas dalam satu tempat.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('dashboard') }}" class="btn btn-sm btn-white border shadow-sm px-3">
                <i class="fa-solid fa-rotate me-1 text-primary"></i> Muat ulang
            </a>
            <a href="{{ route('transactions.index') }}" class="btn btn-sm btn-primary shadow-sm px-3">
                <i class="fa-solid fa-book-open me-1"></i> Buka Buku Kas
            </a>
        </div>
    </div>

    <div class="dashboard-eyebrow fw-semibold mb-2">Executive Summary</div>
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="dashboard-kpi card h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="dashboard-kpi-label small text-muted">Total Omset</span>
                        <i class="dashboard-kpi-icon fa-regular fa-file-lines"></i>
                    </div>
                    <div class="dashboard-kpi-value fw-bold mb-2">{{ $currency }} {{ number_format($totalOmset, 0, ',', '.') }}</div>
                    <span class="badge rounded-pill bg-success-subtle text-success dashboard-status">
                        <i class="fa-solid fa-circle-check me-1"></i> Lunas
                    </span>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="dashboard-kpi card h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="dashboard-kpi-label small text-muted">Total Piutang</span>
                        <i class="dashboard-kpi-icon fa-solid fa-clock-rotate-left"></i>
                    </div>
                    <div class="dashboard-kpi-value fw-bold mb-2">{{ $currency }} {{ number_format($totalPiutang, 0, ',', '.') }}</div>
                    <span class="badge rounded-pill bg-warning-subtle text-dark dashboard-status">
                        <i class="fa-solid fa-clock me-1"></i> Belum Lunas
                    </span>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="dashboard-kpi card h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="dashboard-kpi-label small text-muted">Pengeluaran Kas</span>
                        <i class="dashboard-kpi-icon fa-solid fa-arrow-up-right-from-square"></i>
                    </div>
                    <div class="dashboard-kpi-value fw-bold mb-2">{{ $currency }} {{ number_format($totalExpense, 0, ',', '.') }}</div>
                    <span class="badge rounded-pill bg-danger-subtle text-danger dashboard-status">
                        <i class="fa-solid fa-arrow-down me-1"></i> Expense
                    </span>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="dashboard-kpi dashboard-kpi-profit card h-100 text-white">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="dashboard-kpi-label small">Laba Bersih</span>
                        <i class="dashboard-kpi-icon fa-solid fa-scale-balanced"></i>
                    </div>
                    <div class="dashboard-kpi-value fw-bold mb-2">{{ $currency }} {{ number_format($netProfit, 0, ',', '.') }}</div>
                    <span class="badge rounded-pill bg-primary-subtle text-primary dashboard-status">
                        <i class="fa-solid fa-chart-line me-1"></i> Net Profit
                    </span>
                </div>
            </div>
        </div>
    </div>

    <section class="dashboard-section card mb-4">
        <div class="dashboard-section-header card-header bg-white border-0 px-4 py-3 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h6 fw-bold mb-1">Dokumen Invoice & PO Terbaru</h2>
                <p class="dashboard-footnote small mb-0">Pantau tagihan dan status pembayaran customer.</p>
            </div>
            <a href="{{ route('documents.index') }}" class="link-primary text-decoration-none small fw-semibold">
                Lihat Semua <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="table-responsive">
            <table class="dashboard-table table align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">No. Dokumen</th>
                        <th>Customer</th>
                        <th class="text-end">Tagihan</th>
                        <th class="text-center pe-4">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentDocuments as $doc)
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center gap-2">
                                <span class="dashboard-row-icon rounded d-inline-flex align-items-center justify-content-center">
                                    <i class="fa-regular fa-file-lines"></i>
                                </span>
                                <span class="font-monospace text-dark">{{ $doc->document_number }}</span>
                            </div>
                        </td>
                        <td>{{ $doc->customer?->name ?? '-' }}</td>
                        <td class="text-end fw-semibold">{{ $currency }} {{ number_format($doc->total_amount, 0, ',', '.') }}</td>
                        <td class="text-center pe-4">
                            <span class="dashboard-status badge rounded-pill {{ $statusClasses[$doc->status] ?? 'bg-secondary-subtle text-secondary' }}">
                                <i class="fa-solid {{ $doc->status === 'PAID' ? 'fa-check' : ($doc->status === 'SENT' ? 'fa-paper-plane' : 'fa-file-lines') }} me-1"></i>
                                {{ $doc->status }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">Belum ada invoice untuk ditampilkan.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex flex-column flex-sm-row justify-content-between gap-2 px-4 py-3 border-top">
            <span class="dashboard-footnote small">Menampilkan {{ $recentDocuments->count() }} invoice terbaru</span>
            <span class="dashboard-footnote small">PAID · Lunas &nbsp;&nbsp; SENT · Terkirim</span>
        </div>
    </section>

    <section class="dashboard-section card">
        <div class="dashboard-section-header card-header bg-white border-0 px-4 py-3 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h6 fw-bold mb-1">Transaksi Kas & Jurnal Terbaru</h2>
                <p class="dashboard-footnote small mb-0">Rincian pengeluaran dan klasifikasi fiskal.</p>
            </div>
            <a href="{{ route('transactions.index') }}" class="link-primary text-decoration-none small fw-semibold">
                Buku Kas <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="table-responsive">
            <table class="dashboard-table table align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">No. Transaksi</th>
                        <th>Kategori / Akun</th>
                        <th class="text-end">Nominal</th>
                        <th class="text-center pe-4">Fiskal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentTransactions as $trx)
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center gap-2">
                                <span class="dashboard-expense-icon rounded-circle d-inline-flex align-items-center justify-content-center">
                                    <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: .62rem;"></i>
                                </span>
                                <span class="font-monospace text-dark">{{ $trx->transaction_number }}</span>
                            </div>
                        </td>
                        <td>
                            <div class="text-truncate" style="max-width: 360px;" title="{{ $trx->category?->name }}">
                                [{{ $trx->category?->code ?? '-' }}] {{ $trx->category?->name ?? 'Tanpa kategori' }}
                            </div>
                        </td>
                        <td class="text-end fw-semibold text-danger">
                            - {{ $currency }} {{ number_format($trx->amount, 0, ',', '.') }}
                        </td>
                        <td class="text-center pe-4">
                            @if($trx->category?->is_tax_deductible)
                                <span class="dashboard-status badge rounded-pill bg-light text-secondary">Deductible</span>
                            @else
                                <span class="dashboard-status badge rounded-pill bg-light text-secondary">Koreksi</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">Belum ada pengeluaran kas tercatat.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex flex-column flex-sm-row justify-content-between gap-2 px-4 py-3 border-top">
            <span class="dashboard-footnote small">Menampilkan {{ $recentTransactions->count() }} transaksi terbaru</span>
            <span class="dashboard-footnote small"><i class="fa-regular fa-circle-question me-1"></i> Nominal negatif menunjukkan kas keluar.</span>
        </div>
    </section>
</div>
@endsection
