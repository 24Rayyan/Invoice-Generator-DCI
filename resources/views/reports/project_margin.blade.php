@extends('layouts.app')

@php
    $currency = $setting->currency_symbol ?? 'Rp';
@endphp

@section('content')
<div class="row mb-4 align-items-center">
    <div class="col-md-7">
        <h3 class="fw-bold text-dark mb-1">
            <i class="fa-solid fa-chart-pie text-success me-2"></i> Laporan Margin Proyek / Invoice
        </h3>
        <p class="text-muted mb-0 font-sm">
            Bandingkan pendapatan invoice dengan kas keluar yang terikat, lalu lihat laba bersih dan persentase margin per proyek.
        </p>
    </div>
    <div class="col-md-5 text-md-end">
        <a href="{{ route('reports.projects') }}" class="btn btn-outline-primary btn-sm me-1">
            <i class="fa-solid fa-diagram-project me-1"></i> Project Grouping
        </a>
        <a href="{{ route('projects.index') }}" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-folder-plus me-1"></i> Kelola Project
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm border-start border-4 border-success h-100">
            <div class="card-body p-3">
                <span class="text-muted fw-semibold font-xs text-uppercase">Total Revenue</span>
                <h5 class="fw-bold text-success mb-0 mt-1">{{ $currency }} {{ number_format($totals['revenue'], 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm border-start border-4 border-danger h-100">
            <div class="card-body p-3">
                <span class="text-muted fw-semibold font-xs text-uppercase">Total Direct Expense</span>
                <h5 class="fw-bold text-danger mb-0 mt-1">{{ $currency }} {{ number_format($totals['direct_expense'], 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm border-start border-4 border-info h-100">
            <div class="card-body p-3">
                <span class="text-muted fw-semibold font-xs text-uppercase">Total Net Profit</span>
                <h5 class="fw-bold {{ $totals['net_profit'] >= 0 ? 'text-info' : 'text-danger' }} mb-0 mt-1">{{ $currency }} {{ number_format($totals['net_profit'], 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-dark text-white h-100">
            <div class="card-body p-3">
                <span class="text-white-50 fw-semibold font-xs text-uppercase">Blended Net Margin</span>
                <h5 class="fw-bold text-white mb-0 mt-1">{{ number_format($totals['net_margin'], 2, ',', '.') }}%</h5>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('reports.project_margin') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label font-xs fw-bold text-muted text-uppercase mb-1">Cari Invoice / Customer</label>
                <input type="text" name="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="Nomor invoice atau nama customer">
            </div>
            <div class="col-md-3">
                <label class="form-label font-xs fw-bold text-muted text-uppercase mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <option value="DRAFT" {{ $status === 'DRAFT' ? 'selected' : '' }}>DRAFT</option>
                    <option value="SENT" {{ $status === 'SENT' ? 'selected' : '' }}>SENT</option>
                    <option value="PAID" {{ $status === 'PAID' ? 'selected' : '' }}>PAID</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label font-xs fw-bold text-muted text-uppercase mb-1">Urutkan</label>
                <select name="sort" class="form-select form-select-sm">
                    <option value="date_desc" {{ $sort === 'date_desc' ? 'selected' : '' }}>Tanggal terbaru</option>
                    <option value="date_asc" {{ $sort === 'date_asc' ? 'selected' : '' }}>Tanggal terlama</option>
                    <option value="margin_desc" {{ $sort === 'margin_desc' ? 'selected' : '' }}>Margin tertinggi</option>
                    <option value="margin_asc" {{ $sort === 'margin_asc' ? 'selected' : '' }}>Margin terendah</option>
                    <option value="profit_desc" {{ $sort === 'profit_desc' ? 'selected' : '' }}>Laba tertinggi</option>
                    <option value="profit_asc" {{ $sort === 'profit_asc' ? 'selected' : '' }}>Laba terendah</option>
                    <option value="revenue_desc" {{ $sort === 'revenue_desc' ? 'selected' : '' }}>Revenue tertinggi</option>
                    <option value="revenue_asc" {{ $sort === 'revenue_asc' ? 'selected' : '' }}>Revenue terendah</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary flex-fill">Filter</button>
                <a href="{{ route('reports.project_margin') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">No. Invoice</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th class="text-end">Gross Revenue</th>
                        <th class="text-end">Attached Expenses</th>
                        <th class="text-end">Net Profit</th>
                        <th class="text-end pe-4">Margin %</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                    @php $invoice = $row['invoice']; @endphp
                    <tr>
                        <td class="ps-4">
                            <a href="{{ route('documents.show', $invoice) }}" class="fw-bold text-decoration-none">
                                {{ $invoice->document_number }}
                            </a>
                            <div class="text-muted font-xs">{{ $invoice->date?->format('d/m/Y') }}</div>
                        </td>
                        <td>{{ $invoice->customer->name ?? '-' }}</td>
                        <td>
                            <span class="badge bg-{{ $invoice->status === 'PAID' ? 'success' : ($invoice->status === 'SENT' ? 'info' : 'warning') }}-subtle text-{{ $invoice->status === 'PAID' ? 'success' : ($invoice->status === 'SENT' ? 'info' : 'dark') }} border">
                                {{ $invoice->status }}
                            </span>
                        </td>
                        <td class="text-end">{{ $currency }} {{ number_format($row['revenue'], 0, ',', '.') }}</td>
                        <td class="text-end text-danger">{{ $currency }} {{ number_format($row['direct_expense'], 0, ',', '.') }}</td>
                        <td class="text-end fw-bold {{ $row['net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $currency }} {{ number_format($row['net_profit'], 0, ',', '.') }}
                        </td>
                        <td class="text-end pe-4 fw-bold">
                            {{ number_format($row['net_margin'], 2, ',', '.') }}%
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">Belum ada invoice untuk ditampilkan.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
