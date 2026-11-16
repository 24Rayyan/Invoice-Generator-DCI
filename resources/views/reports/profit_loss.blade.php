@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/reports.css') }}?v={{ @filemtime(public_path('css/reports.css')) }}">
@endpush

@section('content')
@php
    $currency = $setting->currency_symbol ?? 'Rp';
    $commercialPositive = $reportData['commercial_net_profit'] >= 0;
    $fiscalPositive = $reportData['fiscal_net_profit'] >= 0;
@endphp

<div class="rpt">
    {{-- Header --}}
    <header class="rpt-head">
        <div>
            <h1>Laba Rugi Komersial &amp; Fiskal</h1>
            <p class="rpt-desc">Pembukuan komersial, koreksi fiskal positif, dan penghasilan neto fiskal sebagai dasar Lampiran SPT Tahunan PPh Badan / OP.</p>
        </div>
        <div class="rpt-head-actions">
            <a data-download href="{{ route('reports.profit_loss.pdf', ['year' => $year, 'month' => $month]) }}" class="btn btn-sm btn-outline-secondary" target="_blank">
                <i class="fa-solid fa-file-pdf" aria-hidden="true"></i> Cetak PDF SPT
            </a>
            <a data-download href="{{ route('reports.profit_loss.excel', ['year' => $year, 'month' => $month]) }}" class="btn btn-sm btn-primary">
                <i class="fa-solid fa-file-excel" aria-hidden="true"></i> Ekspor Excel
            </a>
        </div>
    </header>

    {{-- Pemilihan periode: tahun pajak dan bulan buku --}}
    <section class="rpt-filter" aria-label="Periode laporan">
        <form method="GET" action="{{ route('reports.profit_loss') }}" class="row g-2 align-items-end">
            <div class="col-6 col-md-3 col-xl-2">
                <label for="plYear" class="form-label">Tahun pajak</label>
                <select id="plYear" name="year" class="form-select form-select-sm smooth-dropdown" data-placeholder="Pilih tahun..." onchange="this.form.submit()">
                    @foreach($availableYears as $yr)
                        <option value="{{ $yr }}" {{ $year == $yr ? 'selected' : '' }}>Tahun {{ $yr }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-4 col-xl-3">
                <label for="plMonth" class="form-label">Bulan buku</label>
                <select id="plMonth" name="month" class="form-select form-select-sm smooth-dropdown" data-placeholder="Pilih bulan..." onchange="this.form.submit()">
                    <option value="" {{ empty($month) ? 'selected' : '' }}>Sepanjang tahun (tahunan)</option>
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                        </option>
                    @endfor
                </select>
            </div>
        </form>
    </section>

    <div class="rpt-context">
        <span>Periode: <strong>{{ $reportData['month_name'] }} {{ $reportData['year'] }}</strong></span>
        <span class="rpt-context-end">
            {{ $setting->company_name ?? 'Nama Perusahaan' }} · NPWP 16 digit {{ $setting->company_npwp16 ?? '-' }}
        </span>
    </div>

    {{-- Ringkasan: penghasilan neto fiskal sebagai fokus (dasar PPh terutang) --}}
    <section class="rpt-summary cols-5" aria-label="Ringkasan laba rugi">
        <div class="rpt-fig lead">
            <p class="rpt-fig-label">Laba neto fiskal (SPT)</p>
            <div class="rpt-fig-value {{ $fiscalPositive ? '' : 'neg' }}">{{ $currency }} {{ number_format($reportData['fiscal_net_profit'], 0, ',', '.') }}</div>
            <p class="rpt-fig-note">Dasar penghitungan PPh terutang.</p>
        </div>
        <div class="rpt-fig">
            <p class="rpt-fig-label">Pendapatan bruto</p>
            <div class="rpt-fig-value">{{ $currency }} {{ number_format($reportData['total_income'], 0, ',', '.') }}</div>
            <p class="rpt-fig-note">Omzet penjualan.</p>
        </div>
        <div class="rpt-fig">
            <p class="rpt-fig-label">Beban usaha fiskal</p>
            <div class="rpt-fig-value">{{ $currency }} {{ number_format($reportData['total_deductible_expense'], 0, ',', '.') }}</div>
            <p class="rpt-fig-note">Deductible, diakui pajak.</p>
        </div>
        <div class="rpt-fig">
            <p class="rpt-fig-label">Koreksi fiskal positif</p>
            <div class="rpt-fig-value">{{ $currency }} {{ number_format($reportData['fiscal_correction_positive'], 0, ',', '.') }}</div>
            <p class="rpt-fig-note">Non-deductible.</p>
        </div>
        <div class="rpt-fig">
            <p class="rpt-fig-label">Laba komersial</p>
            <div class="rpt-fig-value {{ $commercialPositive ? '' : 'neg' }}">{{ $currency }} {{ number_format($reportData['commercial_net_profit'], 0, ',', '.') }}</div>
            <p class="rpt-fig-note">Pendapatan dikurangi total biaya.</p>
        </div>
    </section>

    {{-- Laporan rekonsiliasi: komersial, koreksi, fiskal berdampingan --}}
    <section class="rpt-panel" aria-labelledby="plStatement">
        <div class="rpt-panel-head">
            <h2 id="plStatement">Rekonsiliasi laba rugi komersial dan fiskal</h2>
            <span class="meta">{{ $reportData['month_name'] }} {{ $reportData['year'] }} · dalam {{ $currency }}</span>
        </div>
        <div class="table-responsive">
            <table class="rpt-table" style="min-width: 720px;">
                <thead>
                    <tr>
                        <th>Uraian pos laporan keuangan</th>
                        <th class="r" style="width: 19%;">Komersial</th>
                        <th class="r" style="width: 19%;">Koreksi fiskal positif</th>
                        <th class="r col-fiscal" style="width: 19%;">Fiskal (SPT)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="group"><td colspan="4">I. Pendapatan / peredaran usaha</td></tr>
                    @forelse($reportData['income_categories'] as $inc)
                    <tr>
                        <td class="lv1"><span class="mono dim">{{ $inc['category_code'] }}</span> {{ $inc['category_name'] }}</td>
                        <td class="r">{{ number_format($inc['total_amount'], 0, ',', '.') }}</td>
                        <td class="r dim">-</td>
                        <td class="r col-fiscal">{{ number_format($inc['total_amount'], 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="lv1 dim">Belum ada pendapatan tercatat pada periode ini.</td></tr>
                    @endforelse
                    <tr class="subtotal">
                        <td>Total pendapatan bruto (A)</td>
                        <td class="r">{{ $currency }} {{ number_format($reportData['total_income'], 0, ',', '.') }}</td>
                        <td class="r dim">-</td>
                        <td class="r col-fiscal">{{ $currency }} {{ number_format($reportData['total_income'], 0, ',', '.') }}</td>
                    </tr>

                    <tr class="group"><td colspan="4">II. Beban operasional dan biaya usaha</td></tr>
                    @forelse($reportData['expense_categories'] as $exp)
                    <tr>
                        <td class="lv1">
                            <span class="mono dim">{{ $exp['category_code'] }}</span> {{ $exp['category_name'] }}
                            @if(!$exp['is_tax_deductible'])
                                <span class="rpt-tag warn ms-1">Non-deductible</span>
                            @else
                                <span class="rpt-tag info ms-1">Deductible</span>
                            @endif
                        </td>
                        <td class="r">{{ number_format($exp['total_amount'], 0, ',', '.') }}</td>
                        <td class="r {{ !$exp['is_tax_deductible'] ? '' : 'dim' }}">
                            {{ !$exp['is_tax_deductible'] ? number_format($exp['total_amount'], 0, ',', '.') : '-' }}
                        </td>
                        <td class="r col-fiscal {{ $exp['is_tax_deductible'] ? '' : 'dim' }}">
                            {{ $exp['is_tax_deductible'] ? number_format($exp['total_amount'], 0, ',', '.') : '0' }}
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="lv1 dim">Belum ada beban tercatat pada periode ini.</td></tr>
                    @endforelse
                    <tr class="subtotal">
                        <td>Total biaya / beban usaha (B)</td>
                        <td class="r">{{ $currency }} {{ number_format($reportData['total_expense'], 0, ',', '.') }}</td>
                        <td class="r">{{ $currency }} {{ number_format($reportData['total_non_deductible_expense'], 0, ',', '.') }}</td>
                        <td class="r col-fiscal">{{ $currency }} {{ number_format($reportData['total_deductible_expense'], 0, ',', '.') }}</td>
                    </tr>

                    <tr class="total">
                        <td>Penghasilan neto / laba bersih (A - B)</td>
                        <td class="r {{ $commercialPositive ? '' : 'neg' }}">{{ $currency }} {{ number_format($reportData['commercial_net_profit'], 0, ',', '.') }}</td>
                        <td class="r">+ {{ $currency }} {{ number_format($reportData['fiscal_correction_positive'], 0, ',', '.') }}</td>
                        <td class="r col-fiscal {{ $fiscalPositive ? '' : 'neg' }}">{{ $currency }} {{ number_format($reportData['fiscal_net_profit'], 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    {{-- Matriks bulanan, hanya pada tampilan tahunan --}}
    @if(empty($month))
    <section class="rpt-panel" aria-labelledby="plMonthly">
        <div class="rpt-panel-head">
            <h2 id="plMonthly">Matriks bulanan tahun {{ $year }}</h2>
            <span class="meta">dalam {{ $currency }}</span>
        </div>
        <div class="table-responsive">
            <table class="rpt-table" style="min-width: 720px;">
                <thead>
                    <tr>
                        <th>Bulan</th>
                        <th class="r">Pendapatan</th>
                        <th class="r">Beban fiskal</th>
                        <th class="r">Koreksi positif</th>
                        <th class="r">Laba komersial</th>
                        <th class="r col-fiscal">Laba fiskal (SPT)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportData['monthly_trend'] as $mTrend)
                    <tr>
                        <td style="font-weight: 600;">{{ $mTrend['month_name'] }}</td>
                        <td class="r">{{ number_format($mTrend['income'], 0, ',', '.') }}</td>
                        <td class="r">{{ number_format($mTrend['deductible_expense'], 0, ',', '.') }}</td>
                        <td class="r">{{ number_format($mTrend['non_deductible_expense'], 0, ',', '.') }}</td>
                        <td class="r {{ $mTrend['commercial_profit'] >= 0 ? '' : 'neg' }}" style="font-weight: 600;">{{ number_format($mTrend['commercial_profit'], 0, ',', '.') }}</td>
                        <td class="r col-fiscal {{ $mTrend['fiscal_profit'] >= 0 ? '' : 'neg' }}" style="font-weight: 700;">{{ number_format($mTrend['fiscal_profit'], 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6"><div class="rpt-empty"><strong>Belum ada data bulanan</strong>Tidak ada transaksi pada tahun {{ $year }}.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    @endif
</div>
@endsection
