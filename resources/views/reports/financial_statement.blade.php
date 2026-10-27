@extends('layouts.app')

@push('styles')
<style>
    .professional-report {
        --report-ink: #172033;
        --report-muted: #7b8494;
        --report-line: #e9edf3;
        color: var(--report-ink);
    }

    .professional-report .report-title {
        font-size: clamp(1.55rem, 2.5vw, 2rem);
        letter-spacing: -0.045em;
    }

    .professional-report .report-subtitle,
    .professional-report .report-eyebrow,
    .professional-report .report-note {
        color: var(--report-muted);
    }

    .professional-report .report-eyebrow {
        font-size: 0.73rem;
    }

    .professional-report .report-panel {
        border: 0;
        border-radius: 17px;
        box-shadow: 0 5px 20px rgba(20, 34, 58, 0.04);
    }

    .professional-report .report-panel-header {
        border-bottom: 1px solid var(--report-line) !important;
    }

    .professional-report .report-period {
        background: #eff6ff;
        border-radius: 12px;
    }

    .professional-report .report-kpi {
        min-height: 136px;
        border: 1px solid #eff1f5 !important;
        border-radius: 16px !important;
        box-shadow: 0 5px 18px rgba(20, 34, 58, 0.035) !important;
    }

    .professional-report .report-kpi-value {
        font-size: clamp(1.15rem, 1.8vw, 1.45rem);
        letter-spacing: -0.04em;
        overflow-wrap: anywhere;
    }

    .professional-report .report-table {
        min-width: 760px;
    }

    .professional-report .report-table thead th {
        background: #f8f9fb;
        color: #747e8d;
        font-size: 0.65rem;
        font-weight: 700;
        letter-spacing: 0.035em;
        padding-top: 0.8rem;
        padding-bottom: 0.8rem;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .professional-report .report-table tbody td,
    .professional-report .report-table tfoot td {
        border-color: var(--report-line);
        font-size: 0.76rem;
        padding-top: 0.8rem;
        padding-bottom: 0.8rem;
        vertical-align: middle;
    }

    .professional-report .report-table tbody tr:last-child td {
        border-bottom: 0;
    }

    @media (max-width: 575.98px) {
        .professional-report .report-panel-header {
            padding: 1rem !important;
        }

        .professional-report .report-period {
            align-items: flex-start !important;
        }
    }
</style>
@endpush

@section('content')
@php
    $currency = $setting->currency_symbol ?? 'Rp';
    $exportFilters = [
        'start_date' => $startDate,
        'end_date' => $endDate,
        'category_id' => $categoryId,
        'type' => $type,
    ];
    $netProfitColor = $reportData['net_profit'] >= 0 ? 'primary' : 'danger';
@endphp

<div class="professional-report">
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="report-title fw-bold mb-1">Laporan Keuangan Profesional</h1>
        <p class="report-subtitle small mb-0">Pantau arus kas, performa penagihan, dan klasifikasi fiskal dalam satu laporan.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('reports.financial_statement.pdf', $exportFilters) }}" class="btn btn-sm btn-outline-danger bg-white shadow-sm px-3" target="_blank" rel="noopener">
            <i class="fa-solid fa-file-pdf me-1"></i> Download PDF
        </a>
        <a href="{{ route('reports.financial_statement.excel', $exportFilters) }}" class="btn btn-sm btn-primary shadow-sm px-3">
            <i class="fa-solid fa-file-excel me-1"></i> Export Excel
        </a>
    </div>
</div>

<!-- Filter Bar Komprehensif -->
<section class="report-panel card mb-4">
    <div class="card-body p-3 p-lg-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h6 fw-bold mb-0"><i class="fa-solid fa-sliders me-2 text-primary"></i>Filter Laporan</h2>
            <a href="{{ route('reports.financial_statement') }}" class="small fw-semibold text-decoration-none">Reset filter</a>
        </div>
        <form method="GET" action="{{ route('reports.financial_statement') }}" id="filterForm" class="row g-2 align-items-end">
            <div class="col-sm-6 col-xl-3">
                <label for="start_date" class="form-label small text-muted mb-1">Tanggal mulai</label>
                <input type="date" name="start_date" id="start_date" class="form-control form-control-sm" value="{{ $startDate }}" required>
            </div>
            <div class="col-sm-6 col-xl-3">
                <label for="end_date" class="form-label small text-muted mb-1">Tanggal selesai</label>
                <input type="date" name="end_date" id="end_date" class="form-control form-control-sm" value="{{ $endDate }}" required>
            </div>
            <div class="col-sm-6 col-xl-3">
                <label for="reportCategory" class="form-label small text-muted mb-1">Kategori akun (COA)</label>
                <select id="reportCategory" name="category_id" class="form-select form-select-sm smooth-dropdown" data-placeholder="Semua Kategori">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>
                            [{{ $cat->code }}] {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-xl-2">
                <label for="reportType" class="form-label small text-muted mb-1">Tipe transaksi</label>
                <select id="reportType" name="type" class="form-select form-select-sm smooth-dropdown" data-placeholder="Semua (Income & Expense)">
                    <option value="">Semua (Income & Expense)</option>
                    <option value="income" {{ $type == 'income' ? 'selected' : '' }}>Pemasukan (Income)</option>
                    <option value="expense" {{ $type == 'expense' ? 'selected' : '' }}>Pengeluaran (Expense)</option>
                </select>
            </div>
            <div class="col-sm-6 col-xl-1">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="fa-solid fa-filter me-1"></i> Terapkan
                </button>
            </div>

            <!-- Quick Presets -->
            <div class="col-12 mt-3 pt-3 border-top d-flex gap-2 align-items-center flex-wrap">
                <span class="small text-muted fw-semibold me-1">Pilihan cepat</span>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="setPreset('this_month')">Bulan Ini</button>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="setPreset('last_month')">Bulan Lalu</button>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="setPreset('this_year')">Tahun Berjalan</button>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="setPreset('last_30_days')">30 Hari Terakhir</button>
            </div>
        </form>
    </div>
</div>

<!-- Header Info Periode -->
<div class="report-period d-flex flex-column flex-md-row justify-content-between gap-2 py-3 px-3 px-lg-4 mb-4">
    <div class="small text-primary-emphasis">
        <i class="fa-solid fa-calendar-days me-2"></i>
        Periode laporan: <strong>{{ $reportData['formatted_start_date'] }}</strong> s/d <strong>{{ $reportData['formatted_end_date'] }}</strong>
    </div>
    <span class="small text-muted fw-semibold">
        {{ $setting->company_name ?? 'Nama Perusahaan' }}
    </span>
</div>

<!-- 1. Executive Summary Cards -->
<!-- Executive Financial Summary Cards Container -->
<section class="report-panel report-summary card mb-4">
    <!-- Header Banner dengan Gradient Premium -->
    <div class="report-panel-header card-header bg-white border-0 p-3 p-lg-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
            <h2 class="h6 fw-bold mb-1">Ringkasan Keuangan</h2>
            <p class="report-note small mb-0">Ikhtisar kas, laba bersih, dan piutang untuk periode laporan.</p>
            </div>
        </div>
    </div>

    <!-- Body / Content KPI Cards -->
    <div class="card-body p-3 p-lg-4">
        <div class="row g-3">
            
            <!-- 1. Total Pemasukan Kas -->
            <div class="col-sm-6 col-xl-3">
                <div class="report-kpi card h-100 position-relative overflow-hidden">
                    <div class="position-absolute top-0 start-0 bottom-0 bg-success" style="width: 4px;"></div>
                    <div class="card-body p-3 ps-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <span class="text-uppercase font-xs fw-bold text-muted tracking-wider d-block">Total Pemasukan</span>
                                <span class="badge bg-success-subtle text-success border border-success border-opacity-25 rounded-pill font-xs px-2 py-0.5 mt-1">
                                    <i class="fa-solid fa-circle-check me-1"></i>Kas Masuk
                                </span>
                            </div>
                            <div class="bg-success bg-opacity-10 text-success rounded-3 p-2.5 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                                <i class="fa-solid fa-wallet fs-5"></i>
                            </div>
                        </div>
                        <h3 class="report-kpi-value fw-bold text-dark mb-1">
                            {{ $setting->currency_symbol ?? 'Rp' }} {{ number_format($reportData['total_income'], 0, ',', '.') }}
                        </h3>
                        <p class="text-muted font-xs mb-0">
                            Invoice: {{ $setting->currency_symbol ?? 'Rp' }} {{ number_format($reportData['paid_invoices_income'], 0, ',', '.') }} | Lainnya: {{ $setting->currency_symbol ?? 'Rp' }} {{ number_format($reportData['other_income'], 0, ',', '.') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- 2. Total Pengeluaran Kas -->
            <div class="col-sm-6 col-xl-3">
                <div class="report-kpi card h-100 position-relative overflow-hidden">
                    <div class="position-absolute top-0 start-0 bottom-0 bg-danger" style="width: 4px;"></div>
                    <div class="card-body p-3 ps-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <span class="text-uppercase font-xs fw-bold text-muted tracking-wider d-block">Total Pengeluaran</span>
                                <span class="badge bg-danger-subtle text-danger border border-danger border-opacity-25 rounded-pill font-xs px-2 py-0.5 mt-1">
                                    <i class="fa-solid fa-arrow-down-long me-1"></i>Beban Ops
                                </span>
                            </div>
                            <div class="bg-danger bg-opacity-10 text-danger rounded-3 p-2.5 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                                <i class="fa-solid fa-money-bill-transfer fs-5"></i>
                            </div>
                        </div>
                        <h3 class="report-kpi-value fw-bold text-dark mb-1">
                            {{ $setting->currency_symbol ?? 'Rp' }} {{ number_format($reportData['total_expense'], 0, ',', '.') }}
                        </h3>
                        <p class="text-muted font-xs mb-0">
                            Fiskal: {{ $setting->currency_symbol ?? 'Rp' }} {{ number_format($reportData['deductible_expense'], 0, ',', '.') }} | Non: {{ $setting->currency_symbol ?? 'Rp' }} {{ number_format($reportData['non_deductible_expense'], 0, ',', '.') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- 3. Laba / Rugi Bersih -->
            <div class="col-sm-6 col-xl-3">
                <div class="report-kpi card h-100 position-relative overflow-hidden">
                    <div class="position-absolute top-0 start-0 bottom-0 bg-{{ $netProfitColor }}" style="width: 4px;"></div>
                    <div class="card-body p-3 ps-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <span class="text-uppercase font-xs fw-bold text-muted tracking-wider d-block">Laba / Rugi Bersih</span>
                                <span class="badge bg-{{ $netProfitColor }}-subtle text-{{ $netProfitColor }} border border-{{ $netProfitColor }} border-opacity-25 rounded-pill font-xs px-2 py-0.5 mt-1">
                                    <i class="fa-solid {{ $reportData['net_profit'] >= 0 ? 'fa-chart-line' : 'fa-chart-line-down' }} me-1"></i>{{ $reportData['net_profit'] >= 0 ? 'Net Profit' : 'Net Loss' }}
                                </span>
                            </div>
                            <div class="bg-{{ $netProfitColor }} bg-opacity-10 text-{{ $netProfitColor }} rounded-3 p-2.5 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                                <i class="fa-solid fa-scale-balanced fs-5"></i>
                            </div>
                        </div>
                        <h3 class="report-kpi-value fw-bold text-dark mb-1">
                            {{ $setting->currency_symbol ?? 'Rp' }} {{ number_format($reportData['net_profit'], 0, ',', '.') }}
                        </h3>
                        <p class="report-kpi-note text-muted font-xs mb-0">Pemasukan Bersih - Pengeluaran</p>
                    </div>
                </div>
            </div>

            <!-- 4. Piutang Belum Terbayar -->
            <div class="col-sm-6 col-xl-3">
                <div class="report-kpi card h-100 position-relative overflow-hidden">
                    <div class="position-absolute top-0 start-0 bottom-0 bg-warning" style="width: 4px;"></div>
                    <div class="card-body p-3 ps-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <span class="text-uppercase font-xs fw-bold text-muted tracking-wider d-block">Piutang Aktif</span>
                                <span class="badge bg-warning-subtle text-warning border border-warning border-opacity-25 rounded-pill font-xs px-2 py-0.5 mt-1">
                                    <i class="fa-solid fa-clock me-1"></i>Pending
                                </span>
                            </div>
                            <div class="bg-warning bg-opacity-10 text-warning rounded-3 p-2.5 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                                <i class="fa-solid fa-clock-rotate-left fs-5"></i>
                            </div>
                        </div>
                        <h3 class="report-kpi-value fw-bold text-dark mb-1">
                            {{ $setting->currency_symbol ?? 'Rp' }} {{ number_format($reportData['invoicing_summary']['unpaid_amount'], 0, ',', '.') }}
                        </h3>
                        <p class="text-muted font-xs mb-0">{{ $reportData['invoicing_summary']['sent_count'] }} Invoice Sent pending</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- 2. Rekap Performa Invoicing Periode Ini -->
<section class="report-panel card mb-4">
    <div class="report-panel-header card-header bg-white py-3 px-4">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-dark mb-0">
                <i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i> Rekap Penagihan & Performa Invoice Periode Ini
            </h6>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-xs">
                Collection Rate: {{ $reportData['invoicing_summary']['collection_rate'] }}%
            </span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="report-table table align-middle mb-0 font-sm">
                <thead class="bg-light text-center">
                    <tr>
                        <th class="text-start ps-4">Status Tagihan</th>
                        <th style="width: 15%;">Jumlah Dokumen</th>
                        <th style="width: 25%;">Total Nominal Tagihan</th>
                        <th style="width: 30%;">Persentase Kolektibilitas</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="ps-4">
                            <span class="badge bg-success-subtle text-success border border-success-subtle me-1.5">PAID</span>
                            Invoice Lunas (Terbayar)
                        </td>
                        <td class="text-center fw-bold">{{ $reportData['invoicing_summary']['paid_count'] }}</td>
                        <td class="text-end fw-bold text-success">Rp {{ number_format($reportData['invoicing_summary']['paid_amount'], 0, ',', '.') }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height: 6px;">
                                    <div class="progress-bar bg-success" style="width: {{ $reportData['invoicing_summary']['collection_rate'] }}%;"></div>
                                </div>
                                <span class="font-xs fw-bold">{{ $reportData['invoicing_summary']['collection_rate'] }}%</span>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="ps-4">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1.5">SENT</span>
                            Invoice Terkirim (Piutang Menunggu Bayar)
                        </td>
                        <td class="text-center fw-bold">{{ $reportData['invoicing_summary']['sent_count'] }}</td>
                        <td class="text-end fw-bold text-primary">Rp {{ number_format($reportData['invoicing_summary']['sent_amount'], 0, ',', '.') }}</td>
                        <td>
                            <span class="text-muted font-xs">Piutang Lancar</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="ps-4">
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle me-1.5">DRAFT</span>
                            Invoice Konsep (Draft)
                        </td>
                        <td class="text-center fw-bold">{{ $reportData['invoicing_summary']['draft_count'] }}</td>
                        <td class="text-end text-muted">Rp {{ number_format($reportData['invoicing_summary']['draft_amount'], 0, ',', '.') }}</td>
                        <td><span class="text-muted font-xs">Belum Diterbitkan</span></td>
                    </tr>
                    <tr class="fw-bold bg-light">
                        <td class="ps-4 text-uppercase">Total Penerbitan Invoice</td>
                        <td class="text-center">{{ $reportData['invoicing_summary']['total_count'] }}</td>
                        <td class="text-end text-dark">Rp {{ number_format($reportData['invoicing_summary']['total_amount'], 0, ',', '.') }}</td>
                        <td>100%</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- 3. Rincian Arus Transaksi (Transaction Ledger) -->
<section class="report-panel card mb-4">
    <div class="report-panel-header card-header bg-white py-3 px-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h6 class="fw-bold text-dark mb-0">
                    <i class="fa-solid fa-list-check text-primary me-2"></i> Rincian Buku Kas Kronologis (Transaction Ledger)
                </h6>
                <small class="text-muted">{{ count($reportData['ledger_entries']) }} entri transaksi tercatat</small>
            </div>
            <span class="badge bg-dark text-white px-3 py-1 font-xs font-mono">
                Saldo Akhir: Rp {{ number_format($reportData['net_profit'], 0, ',', '.') }}
            </span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="ledgerTable" class="report-table table table-hover align-middle mb-0 font-sm">
                <thead class="bg-light text-uppercase font-xs">
                    <tr>
                        <th class="ps-4" style="width: 100px;">Tanggal</th>
                        <th style="width: 140px;">No. Referensi</th>
                        <th style="width: 180px;">Kategori / Akun</th>
                        <th>Keterangan / Relasi</th>
                        <th style="width: 110px;">Metode</th>
                        <th class="text-end text-success" style="width: 130px;">Masuk (Rp)</th>
                        <th class="text-end text-danger" style="width: 130px;">Keluar (Rp)</th>
                        <th class="text-end pe-4" style="width: 140px;">Saldo (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportData['ledger_entries'] as $item)
                    <tr data-ledger-row>
                        <td class="ps-4 font-mono font-xs">{{ $item['formatted_date'] }}</td>
                        <td>
                            <span class="font-mono text-dark fw-bold" style="font-size: 0.775rem;">{{ $item['reference_number'] }}</span>
                        </td>
                        <td>
                            <span class="font-mono font-xs text-muted">[{{ $item['category_code'] }}]</span>
                            <span class="fw-semibold text-dark">{{ $item['category_name'] }}</span>
                        </td>
                        <td>
                            <div class="text-truncate" style="max-width: 250px;" title="{{ $item['description'] }}">
                                {{ $item['description'] }}
                            </div>
                            @if($item['party_name'] && $item['party_name'] !== '-')
                                <small class="text-muted font-xs d-block">
                                    <i class="fa-solid fa-user me-1"></i> {{ $item['party_name'] }}
                                </small>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border font-xs">{{ $item['payment_method'] }}</span>
                        </td>
                        <td class="text-end fw-bold text-success">
                            {{ $item['amount_in'] > 0 ? number_format($item['amount_in'], 0, ',', '.') : '-' }}
                        </td>
                        <td class="text-end fw-bold text-danger">
                            {{ $item['amount_out'] > 0 ? number_format($item['amount_out'], 0, ',', '.') : '-' }}
                        </td>
                        <td class="text-end pe-4 fw-bold {{ $item['running_balance'] >= 0 ? 'text-dark' : 'text-danger' }}">
                            {{ number_format($item['running_balance'], 0, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-receipt fs-2 mb-2 d-block text-secondary"></i>
                            Tidak ada transaksi yang cocok dengan filter tanggal/kategori ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if(count($reportData['ledger_entries']) > 0)
                <tfoot class="bg-light fw-bold font-sm">
                    <tr>
                        <td colspan="5" class="text-end pe-3 text-uppercase">Total Mutasi Arus Kas:</td>
                        <td class="text-end text-success">Rp {{ number_format($reportData['total_income'], 0, ',', '.') }}</td>
                        <td class="text-end text-danger">Rp {{ number_format($reportData['total_expense'], 0, ',', '.') }}</td>
                        <td class="text-end pe-4 text-primary">Rp {{ number_format($reportData['net_profit'], 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        @if(count($reportData['ledger_entries']) > 0)
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-center gap-3 px-4 py-3 border-top">
                <div class="d-flex align-items-center gap-2 small">
                    <label for="ledgerPerPage" class="text-muted text-nowrap">Baris per halaman</label>
                    <select id="ledgerPerPage" class="form-select form-select-sm smooth-dropdown pagination-dropdown" data-placeholder="Baris">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <span id="ledgerPageSummary" class="text-muted small text-center" aria-live="polite"></span>
                <nav aria-label="Halaman rincian buku kas">
                    <ul id="ledgerPagination" class="pagination pagination-sm mb-0"></ul>
                </nav>
            </div>
        @endif
    </div>
</section>

<!-- 4. Breakdown Kategori Perkiraan -->
<section class="report-panel card mb-4">
    <div class="report-panel-header card-header bg-white py-3 px-4">
        <h6 class="fw-bold text-dark mb-0">
            <i class="fa-solid fa-tags text-primary me-2"></i> Rekapitulasi per Akun Kategori (Chart of Accounts)
        </h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="report-table table table-hover align-middle mb-0 font-sm">
                <thead class="bg-light font-xs text-uppercase">
                    <tr>
                        <th class="ps-4" style="width: 120px;">Kode</th>
                        <th>Nama Akun</th>
                        <th style="width: 140px;">Tipe</th>
                        <th style="width: 220px;">Status Pajak Fiskal</th>
                        <th class="text-center" style="width: 100px;">Jumlah Trx</th>
                        <th class="text-end pe-4" style="width: 180px;">Total Nominal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reportData['category_breakdown'] as $catItem)
                    <tr>
                        <td class="ps-4 font-mono font-xs fw-bold text-dark">{{ $catItem['code'] }}</td>
                        <td class="fw-bold text-dark">{{ $catItem['name'] }}</td>
                        <td>
                            @if($catItem['type'] == 'income')
                                <span class="badge bg-success-subtle text-success font-xs">Income</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger font-xs">Expense</span>
                            @endif
                        </td>
                        <td>
                            @if($catItem['type'] == 'income')
                                <span class="text-muted font-xs">Penghasilan Bruto</span>
                            @elseif($catItem['is_tax_deductible'])
                                <span class="badge bg-primary-subtle text-primary font-xs">Deductible (Biaya Fiskal)</span>
                            @else
                                <span class="badge bg-warning-subtle text-dark font-xs">Koreksi Positif (Non-Deductible)</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $catItem['count'] }}</td>
                        <td class="text-end pe-4 fw-bold {{ $catItem['type'] == 'income' ? 'text-success' : 'text-danger' }}">
                            Rp {{ number_format($catItem['total_amount'], 0, ',', '.') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
</div>
@endsection

@push('scripts')
<script>
    function initializeLedgerPagination() {
        var ledgerTable = document.getElementById('ledgerTable');
        var pageSizeSelect = document.getElementById('ledgerPerPage');
        var pagination = document.getElementById('ledgerPagination');
        var pageSummary = document.getElementById('ledgerPageSummary');

        if (!ledgerTable || !pageSizeSelect || !pagination || !pageSummary) {
            return;
        }

        var rows = Array.from(ledgerTable.querySelectorAll('[data-ledger-row]'));
        var currentPage = 1;

        function renderPagination() {
            var pageSize = Number(pageSizeSelect.value);
            var pageCount = Math.ceil(rows.length / pageSize);
            var startIndex = (currentPage - 1) * pageSize;
            var endIndex = Math.min(startIndex + pageSize, rows.length);

            rows.forEach(function (row, index) {
                row.classList.toggle('d-none', index < startIndex || index >= endIndex);
            });

            pageSummary.textContent = 'Rentang baris ' + (startIndex + 1) + '–' + endIndex + ' dari ' + rows.length + ' transaksi';
            pagination.replaceChildren();

            function addPageButton(label, page, disabled, active, ariaLabel) {
                var item = document.createElement('li');
                item.className = 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '');

                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'page-link';
                button.textContent = label;
                if (ariaLabel) {
                    button.setAttribute('aria-label', ariaLabel);
                }
                button.disabled = disabled || active;
                button.addEventListener('click', function () {
                    currentPage = page;
                    renderPagination();
                });

                item.appendChild(button);
                pagination.appendChild(item);
            }

            addPageButton('‹ Sebelumnya', currentPage - 1, currentPage === 1, false, 'Halaman sebelumnya');

            var firstPage = Math.max(1, currentPage - 2);
            var lastPage = Math.min(pageCount, currentPage + 2);
            for (var page = firstPage; page <= lastPage; page++) {
                addPageButton(String(page), page, false, page === currentPage, 'Halaman ' + page);
            }

            addPageButton('Berikutnya ›', currentPage + 1, currentPage === pageCount, false, 'Halaman berikutnya');
        }

        pageSizeSelect.addEventListener('change', function () {
            currentPage = 1;
            renderPagination();
        });

        renderPagination();
    }

    function setPreset(preset) {
        var startInput = document.getElementById('start_date');
        var endInput = document.getElementById('end_date');
        var now = new Date();

        if (preset === 'this_month') {
            var firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
            var lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
            startInput.value = formatDate(firstDay);
            endInput.value = formatDate(lastDay);
        } else if (preset === 'last_month') {
            var firstDay = new Date(now.getFullYear(), now.getMonth() - 1, 1);
            var lastDay = new Date(now.getFullYear(), now.getMonth(), 0);
            startInput.value = formatDate(firstDay);
            endInput.value = formatDate(lastDay);
        } else if (preset === 'this_year') {
            var firstDay = new Date(now.getFullYear(), 0, 1);
            var lastDay = new Date(now.getFullYear(), 11, 31);
            startInput.value = formatDate(firstDay);
            endInput.value = formatDate(lastDay);
        } else if (preset === 'last_30_days') {
            var pastDate = new Date();
            pastDate.setDate(now.getDate() - 30);
            startInput.value = formatDate(pastDate);
            endInput.value = formatDate(now);
        }

        document.getElementById('filterForm').submit();
    }

    function formatDate(date) {
        var d = new Date(date),
            month = '' + (d.getMonth() + 1),
            day = '' + d.getDate(),
            year = d.getFullYear();

        if (month.length < 2) month = '0' + month;
        if (day.length < 2) day = '0' + day;

        return [year, month, day].join('-');
    }

    document.addEventListener('DOMContentLoaded', initializeLedgerPagination);
</script>
@endpush
