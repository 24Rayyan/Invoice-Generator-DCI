@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/reports.css') }}?v={{ @filemtime(public_path('css/reports.css')) }}">
@endpush

@php
    $currency = $setting->currency_symbol ?? 'Rp';
    $netPositive = $totals['net_profit'] >= 0;
    $hasFilter = filled($search) || filled($status) || ($sort && $sort !== 'name_asc');
    $projectStatusLabel = ['active' => 'Aktif', 'on_hold' => 'Ditunda', 'completed' => 'Selesai'];
@endphp

@section('content')
<div class="rpt">
    {{-- Header --}}
    <header class="rpt-head">
        <div>
            <h1>Margin Proyek / Invoice</h1>
            <p class="rpt-desc">Invoice dalam satu proyek digabung per proyek. Invoice yang belum terhubung ke proyek tampil sebagai baris tersendiri.</p>
        </div>
        <div class="rpt-head-actions">
            @if(auth()->user()->hasAccess('projects'))
                <a href="{{ route('projects.index') }}" class="btn btn-sm btn-primary">Kelola proyek</a>
            @endif
        </div>
    </header>

    {{-- Ringkasan profitabilitas: margin gabungan sebagai fokus --}}
    <section class="rpt-summary" aria-label="Ringkasan margin">
        <div class="rpt-fig lead">
            <p class="rpt-fig-label">Margin bersih gabungan</p>
            <div class="rpt-fig-value {{ $totals['net_margin'] >= 0 ? '' : 'neg' }}">{{ number_format($totals['net_margin'], 2, ',', '.') }}%</div>
            <p class="rpt-fig-note">Laba bersih dibagi pendapatan, seluruh baris yang tampil.</p>
        </div>
        <div class="rpt-fig">
            <p class="rpt-fig-label">Total pendapatan</p>
            <div class="rpt-fig-value">{{ $currency }} {{ number_format($totals['revenue'], 0, ',', '.') }}</div>
        </div>
        <div class="rpt-fig">
            <p class="rpt-fig-label">Total biaya langsung</p>
            <div class="rpt-fig-value">{{ $currency }} {{ number_format($totals['direct_expense'], 0, ',', '.') }}</div>
        </div>
        <div class="rpt-fig">
            <p class="rpt-fig-label">Total laba bersih</p>
            <div class="rpt-fig-value {{ $netPositive ? '' : 'neg' }}">{{ $currency }} {{ number_format($totals['net_profit'], 0, ',', '.') }}</div>
        </div>
    </section>

    {{-- Pencarian, status, urutan --}}
    <section class="rpt-filter" aria-label="Filter proyek dan invoice">
        <form method="GET" action="{{ route('reports.projects') }}" class="row g-2 align-items-end">
            <div class="col-12 col-lg-4">
                <label for="projectSearch" class="form-label">Cari proyek / invoice / pelanggan</label>
                <input id="projectSearch" class="form-control form-control-sm" name="search" value="{{ $search }}" placeholder="Proyek, nomor invoice, atau pelanggan">
            </div>
            <div class="col-6 col-lg-2">
                <label for="projectStatus" class="form-label">Status</label>
                <select id="projectStatus" name="status" class="form-select form-select-sm smooth-dropdown" data-placeholder="Semua Status">
                    <option value="">Semua Status</option>
                    <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Project: Aktif</option>
                    <option value="on_hold" {{ $status === 'on_hold' ? 'selected' : '' }}>Project: Ditunda</option>
                    <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Project: Selesai</option>
                    <option value="DRAFT" {{ $status === 'DRAFT' ? 'selected' : '' }}>Invoice: DRAFT</option>
                    <option value="SENT" {{ $status === 'SENT' ? 'selected' : '' }}>Invoice: SENT</option>
                    <option value="PAID" {{ $status === 'PAID' ? 'selected' : '' }}>Invoice: PAID</option>
                </select>
            </div>
            <div class="col-6 col-lg-3">
                <label for="projectSort" class="form-label">Urutkan</label>
                <select id="projectSort" name="sort" class="form-select form-select-sm smooth-dropdown" data-placeholder="Urutkan...">
                    <option value="name_asc" {{ $sort === 'name_asc' ? 'selected' : '' }}>Nama A–Z</option>
                    <option value="name_desc" {{ $sort === 'name_desc' ? 'selected' : '' }}>Nama Z–A</option>
                    <option value="revenue_desc" {{ $sort === 'revenue_desc' ? 'selected' : '' }}>Revenue tertinggi</option>
                    <option value="revenue_asc" {{ $sort === 'revenue_asc' ? 'selected' : '' }}>Revenue terendah</option>
                    <option value="expense_desc" {{ $sort === 'expense_desc' ? 'selected' : '' }}>Expense tertinggi</option>
                    <option value="expense_asc" {{ $sort === 'expense_asc' ? 'selected' : '' }}>Expense terendah</option>
                    <option value="profit_desc" {{ $sort === 'profit_desc' ? 'selected' : '' }}>Profit tertinggi</option>
                    <option value="profit_asc" {{ $sort === 'profit_asc' ? 'selected' : '' }}>Profit terendah</option>
                    <option value="margin_desc" {{ $sort === 'margin_desc' ? 'selected' : '' }}>Margin tertinggi</option>
                    <option value="margin_asc" {{ $sort === 'margin_asc' ? 'selected' : '' }}>Margin terendah</option>
                </select>
            </div>
            <div class="col-12 col-lg-3 d-flex gap-2">
                <button class="btn btn-sm btn-primary flex-fill" type="submit">Terapkan</button>
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('reports.projects') }}">Atur ulang</a>
            </div>
        </form>
    </section>

    {{-- Perbandingan per proyek dan invoice lepas --}}
    <section class="rpt-panel" aria-labelledby="pmTable">
        <div class="rpt-panel-head">
            <h2 id="pmTable">Profitabilitas proyek dan invoice lepas</h2>
            <span class="meta">{{ $rows->count() }} baris{{ $hasFilter ? ' sesuai filter' : '' }} · dalam {{ $currency }}</span>
        </div>
        <div class="table-responsive">
            <table class="rpt-table" style="min-width: 960px;">
                <thead>
                    <tr>
                        <th>Proyek / invoice</th>
                        <th>Pelanggan</th>
                        <th>Status</th>
                        <th class="r">Pendapatan bruto</th>
                        <th class="r">Biaya terhubung</th>
                        <th class="r">Laba bersih</th>
                        <th style="min-width: 210px;">Margin</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php
                            $marginPositive = $row['net_margin'] >= 0;
                            $barWidth = min(abs($row['net_margin']), 100) / 2;
                        @endphp
                        <tr>
                            @if($row['type'] === 'project')
                                @php($project = $row['project'])
                                <td>
                                    <a href="{{ route('projects.show', $project) }}" class="text-decoration-none" style="font-weight: 600;">{{ $project->project_name }}</a>
                                    <span class="sub">Proyek · {{ $project->invoices_count }} invoice</span>
                                </td>
                                <td>{{ $project->client?->name ?? '-' }}</td>
                                <td>
                                    <span class="rpt-tag {{ $project->status === 'active' ? 'ok' : ($project->status === 'on_hold' ? 'warn' : '') }}">{{ $projectStatusLabel[$project->status] ?? $project->status }}</span>
                                </td>
                            @else
                                @php($invoice = $row['invoice'])
                                <td>
                                    @if(auth()->user()->hasAccess('invoices'))
                                        <a href="{{ route('documents.show', $invoice) }}" class="text-decoration-none" style="font-weight: 600;">{{ $invoice->document_number }}</a>
                                    @else
                                        <span style="font-weight: 600;">{{ $invoice->document_number }}</span>
                                    @endif
                                    <span class="sub">Invoice belum terhubung ke proyek</span>
                                </td>
                                <td>{{ $invoice->customer?->name ?? '-' }}</td>
                                <td><span class="rpt-tag {{ $invoice->status === 'PAID' ? 'ok' : ($invoice->status === 'SENT' ? 'info' : '') }}">{{ $invoice->status }}</span></td>
                            @endif
                            <td class="r">{{ number_format($row['revenue'], 0, ',', '.') }}</td>
                            <td class="r">{{ number_format($row['direct_expense'], 0, ',', '.') }}</td>
                            <td class="r {{ $row['net_profit'] >= 0 ? '' : 'neg' }}" style="font-weight: 700;">{{ number_format($row['net_profit'], 0, ',', '.') }}</td>
                            <td>
                                <div class="rpt-meter">
                                    <div class="rpt-diverge" role="img" aria-label="Margin {{ number_format($row['net_margin'], 2, ',', '.') }} persen">
                                        <i class="{{ $marginPositive ? 'pos' : 'neg' }}" style="width: {{ $barWidth }}%;"></i>
                                    </div>
                                    <span class="rpt-meter-val {{ $marginPositive ? '' : 'neg' }}">{{ number_format($row['net_margin'], 2, ',', '.') }}%</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="rpt-empty">
                                    @if($hasFilter)
                                        <strong>Tidak ada proyek atau invoice yang cocok</strong>
                                        Ubah kata kunci atau status, atau atur ulang filter.
                                    @else
                                        <strong>Belum ada proyek atau invoice</strong>
                                        Margin muncul setelah invoice dibuat dan biaya kas dihubungkan.
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($rows->count() > 0)
            <div class="rpt-panel-foot">
                <span>Batang margin: kiri sumbu = negatif, kanan = positif, skala hingga 100%. Angka di sebelahnya selalu nilai penuh.</span>
            </div>
        @endif
    </section>
</div>
@endsection
