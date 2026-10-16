@extends('layouts.app')

@section('content')
<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h3 class="fw-bold text-dark mb-1">
            <i class="fa-solid fa-scale-balanced text-primary me-2"></i> Laporan Laba Rugi Komersial & Rekonsiliasi Fiskal
        </h3>
        <p class="text-muted mb-0 font-sm">
            Format resmi penyajian laporan keuangan sesuai pos pembukuan dan Lampiran SPT Tahunan PPh Badan / OP.
        </p>
    </div>
    <div class="col-md-6 text-md-end mt-3 mt-md-0">
        <div class="btn-group shadow-sm">
            <a href="{{ route('reports.profit_loss.pdf', ['year' => $year, 'month' => $month]) }}" class="btn btn-danger" target="_blank">
                <i class="fa-solid fa-file-pdf me-1.5"></i> Cetak PDF SPT
            </a>
            <a href="{{ route('reports.profit_loss.excel', ['year' => $year, 'month' => $month]) }}" class="btn btn-success">
                <i class="fa-solid fa-file-excel me-1.5"></i> Export Excel
            </a>
        </div>
    </div>
</div>

<!-- Filter Periode Laporan -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('reports.profit_loss') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label font-xs fw-bold text-muted text-uppercase mb-1">Tahun Pajak</label>
                <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach($availableYears as $yr)
                        <option value="{{ $yr }}" {{ $year == $yr ? 'selected' : '' }}>Tahun {{ $yr }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label font-xs fw-bold text-muted text-uppercase mb-1">Bulan Buku</label>
                <select name="month" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="" {{ empty($month) ? 'selected' : '' }}>Sepanjang Tahun (Tahunan)</option>
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                        </option>
                    @endfor
                </select>
            </div>
            <div class="col-md-6 text-end">
                <span class="badge bg-light text-dark border p-2 font-xs">
                    <i class="fa-solid fa-building me-1 text-primary"></i> {{ $setting->company_name ?? 'PT Dwitama Cipta Internusa' }} | NPWP 16 Digit: {{ $setting->company_npwp16 ?? '-' }}
                </span>
            </div>
        </form>
    </div>
</div>

<!-- Executive Metric Cards (5 Pilar SPT) -->
<div class="row g-3 mb-4">
    <!-- 1. Total Pendapatan Bruto -->
    <div class="col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm p-3 h-100 bg-white border-start border-4 border-success">
            <span class="text-muted fw-semibold font-xs text-uppercase">1. Pendapatan Bruto</span>
            <h5 class="fw-bold text-success mb-0 mt-1">Rp {{ number_format($reportData['total_income'], 0, ',', '.') }}</h5>
            <small class="text-muted font-xs">Omset Penjualan</small>
        </div>
    </div>
    <!-- 2. Beban Fiskal (Deductible) -->
    <div class="col-md-4 col-xl-3">
        <div class="card border-0 shadow-sm p-3 h-100 bg-white border-start border-4 border-primary">
            <span class="text-muted fw-semibold font-xs text-uppercase">2. Beban Usaha Fiskal</span>
            <h5 class="fw-bold text-primary mb-0 mt-1">Rp {{ number_format($reportData['total_deductible_expense'], 0, ',', '.') }}</h5>
            <small class="text-muted font-xs">Deductible (Diakui Pajak)</small>
        </div>
    </div>
    <!-- 3. Koreksi Fiskal Positif (Non-Deductible) -->
    <div class="col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm p-3 h-100 bg-white border-start border-4 border-warning">
            <span class="text-muted fw-semibold font-xs text-uppercase">3. Koreksi Positif</span>
            <h5 class="fw-bold text-warning mb-0 mt-1">Rp {{ number_format($reportData['fiscal_correction_positive'], 0, ',', '.') }}</h5>
            <small class="text-muted font-xs">Non-Deductible SPT</small>
        </div>
    </div>
    <!-- 4. Laba Bersih Komersial -->
    <div class="col-md-6 col-xl-2">
        <div class="card border-0 shadow-sm p-3 h-100 bg-white border-start border-4 border-info">
            <span class="text-muted fw-semibold font-xs text-uppercase">4. Laba Komersial</span>
            <h5 class="fw-bold {{ $reportData['commercial_net_profit'] >= 0 ? 'text-info' : 'text-danger' }} mb-0 mt-1">
                Rp {{ number_format($reportData['commercial_net_profit'], 0, ',', '.') }}
            </h5>
            <small class="text-muted font-xs">Pendapatan - Total Biaya</small>
        </div>
    </div>
    <!-- 5. Penghasilan Neto Fiskal (Laba Kena Pajak) -->
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm p-3 h-100 bg-dark text-white border-start border-4 border-light">
            <span class="text-white-50 fw-semibold font-xs text-uppercase">5. Laba Neto Fiskal (SPT)</span>
            <h5 class="fw-bold text-white mb-0 mt-1">Rp {{ number_format($reportData['fiscal_net_profit'], 0, ',', '.') }}</h5>
            <small class="text-white-50 font-xs">Dasar Penghitungan PPh Terutang</small>
        </div>
    </div>
</div>

<!-- Formal Profit & Loss Statement -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 px-4 border-bottom">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold text-dark mb-0">
                    <i class="fa-solid fa-list-check me-2 text-primary"></i> Tabel Rekonsiliasi Laba Rugi Komersial & Fiskal
                </h5>
                <small class="text-muted">Periode: {{ $reportData['month_name'] }} {{ $reportData['year'] }}</small>
            </div>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1.5 font-xs">
                Standar Akuntansi Keuangan & Perpajakan DJP
            </span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead class="bg-light text-center">
                    <tr>
                        <th class="text-start ps-4" style="width: 45%;">Uraian Pos Laporan Keuangan</th>
                        <th style="width: 18%;">Pembukuan Komersial (Rp)</th>
                        <th style="width: 18%;">Koreksi Fiskal Positif (Rp)</th>
                        <th style="width: 19%;">Laporan Fiskal / SPT (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- 1. PENDAPATAN -->
                    <tr class="table-light fw-bold">
                        <td colspan="4" class="ps-4 text-dark text-uppercase font-xs">
                            <i class="fa-solid fa-circle-arrow-down text-success me-1"></i> I. PENDAPATAN / PEREDARAN USAHA
                        </td>
                    </tr>
                    @foreach($reportData['income_categories'] as $inc)
                    <tr>
                        <td class="ps-5">
                            <span class="font-mono font-xs text-muted me-2">[{{ $inc['category_code'] }}]</span>
                            {{ $inc['category_name'] }}
                        </td>
                        <td class="text-end fw-semibold">{{ number_format($inc['total_amount'], 0, ',', '.') }}</td>
                        <td class="text-end text-muted">-</td>
                        <td class="text-end fw-semibold text-success">{{ number_format($inc['total_amount'], 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    <tr class="fw-bold bg-success bg-opacity-10">
                        <td class="ps-4 text-success">TOTAL PENDAPATAN BRUTO (A)</td>
                        <td class="text-end text-success">Rp {{ number_format($reportData['total_income'], 0, ',', '.') }}</td>
                        <td class="text-end text-muted">-</td>
                        <td class="text-end text-success">Rp {{ number_format($reportData['total_income'], 0, ',', '.') }}</td>
                    </tr>

                    <!-- 2. BEBAN OPERASIONAL -->
                    <tr class="table-light fw-bold">
                        <td colspan="4" class="ps-4 text-dark text-uppercase font-xs">
                            <i class="fa-solid fa-circle-arrow-up text-danger me-1"></i> II. BEBAN OPERASIONAL & BIAYA USAHA
                        </td>
                    </tr>
                    @foreach($reportData['expense_categories'] as $exp)
                    <tr>
                        <td class="ps-5">
                            <span class="font-mono font-xs text-muted me-2">[{{ $exp['category_code'] }}]</span>
                            {{ $exp['category_name'] }}
                            @if(!$exp['is_tax_deductible'])
                                <span class="badge bg-warning-subtle text-dark border border-warning-subtle font-xs ms-1">Non-Deductible</span>
                            @else
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-xs ms-1">Deductible</span>
                            @endif
                        </td>
                        <td class="text-end">{{ number_format($exp['total_amount'], 0, ',', '.') }}</td>
                        <td class="text-end text-warning fw-semibold">
                            {{ !$exp['is_tax_deductible'] ? number_format($exp['total_amount'], 0, ',', '.') : '-' }}
                        </td>
                        <td class="text-end fw-semibold {{ $exp['is_tax_deductible'] ? 'text-primary' : 'text-muted' }}">
                            {{ $exp['is_tax_deductible'] ? number_format($exp['total_amount'], 0, ',', '.') : '0' }}
                        </td>
                    </tr>
                    @endforeach
                    <tr class="fw-bold bg-danger bg-opacity-10">
                        <td class="ps-4 text-danger">TOTAL BIAYA / BEBAN USAHA (B)</td>
                        <td class="text-end text-danger">Rp {{ number_format($reportData['total_expense'], 0, ',', '.') }}</td>
                        <td class="text-end text-warning">Rp {{ number_format($reportData['total_non_deductible_expense'], 0, ',', '.') }}</td>
                        <td class="text-end text-primary">Rp {{ number_format($reportData['total_deductible_expense'], 0, ',', '.') }}</td>
                    </tr>

                    <!-- 3. HASIL AKHIR / LABA BERSIH -->
                    <tr class="table-dark text-white fw-bold">
                        <td class="ps-4 py-3">
                            <i class="fa-solid fa-trophy text-warning me-2"></i> PENGHASILAN NETO / LABA BERSIH (A - B)
                        </td>
                        <td class="text-end py-3 text-info fs-6">
                            Rp {{ number_format($reportData['commercial_net_profit'], 0, ',', '.') }}
                        </td>
                        <td class="text-end py-3 text-warning fs-6">
                            + Rp {{ number_format($reportData['fiscal_correction_positive'], 0, ',', '.') }}
                        </td>
                        <td class="text-end py-3 text-white fs-6">
                            Rp {{ number_format($reportData['fiscal_net_profit'], 0, ',', '.') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 12-Month Matrix Breakdown (Jika Melihat Tahunan) -->
@if(empty($month))
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 px-4 border-bottom">
        <h6 class="fw-bold text-dark mb-0">
            <i class="fa-solid fa-chart-column me-2 text-primary"></i> Ringkasan Matriks Bulanan Tahun {{ $year }}
        </h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 font-sm">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Bulan</th>
                        <th class="text-end">Pendapatan</th>
                        <th class="text-end">Beban Fiskal</th>
                        <th class="text-end">Koreksi Positif</th>
                        <th class="text-end">Laba Komersial</th>
                        <th class="text-end pe-4">Laba Fiskal (SPT)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reportData['monthly_trend'] as $mTrend)
                    <tr>
                        <td class="ps-4 fw-semibold text-dark">{{ $mTrend['month_name'] }}</td>
                        <td class="text-end text-success">{{ number_format($mTrend['income'], 0, ',', '.') }}</td>
                        <td class="text-end text-primary">{{ number_format($mTrend['deductible_expense'], 0, ',', '.') }}</td>
                        <td class="text-end text-warning">{{ number_format($mTrend['non_deductible_expense'], 0, ',', '.') }}</td>
                        <td class="text-end fw-semibold {{ $mTrend['commercial_profit'] >= 0 ? 'text-dark' : 'text-danger' }}">
                            {{ number_format($mTrend['commercial_profit'], 0, ',', '.') }}
                        </td>
                        <td class="text-end pe-4 fw-bold {{ $mTrend['fiscal_profit'] >= 0 ? 'text-dark' : 'text-danger' }}">
                            {{ number_format($mTrend['fiscal_profit'], 0, ',', '.') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection
