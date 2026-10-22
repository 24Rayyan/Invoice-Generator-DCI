@extends('layouts.app')

@php($currency = $setting->currency_symbol ?? 'Rp')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold text-dark mb-1"><i class="fa-solid fa-chart-pie text-success me-2"></i>Project Margin Overview</h3>
        <p class="text-muted mb-0">Invoice dalam project digabungkan per project. Invoice yang belum terhubung ditampilkan sebagai baris tersendiri.</p>
    </div>
    @if(auth()->user()->hasAccess('projects'))
        <a href="{{ route('projects.index') }}" class="btn btn-outline-primary"><i class="fa-solid fa-diagram-project me-1"></i>Kelola Project</a>
    @endif
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card border-0 shadow-sm border-start border-4 border-success h-100"><div class="card-body"><span class="text-muted fw-bold small text-uppercase">Total Revenue</span><h5 class="fw-bold text-success mt-2 mb-0">{{ $currency }} {{ number_format($totals['revenue'], 0, ',', '.') }}</h5></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm border-start border-4 border-danger h-100"><div class="card-body"><span class="text-muted fw-bold small text-uppercase">Total Direct Expense</span><h5 class="fw-bold text-danger mt-2 mb-0">{{ $currency }} {{ number_format($totals['direct_expense'], 0, ',', '.') }}</h5></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm border-start border-4 border-info h-100"><div class="card-body"><span class="text-muted fw-bold small text-uppercase">Total Net Profit</span><h5 class="fw-bold {{ $totals['net_profit'] >= 0 ? 'text-info' : 'text-danger' }} mt-2 mb-0">{{ $currency }} {{ number_format($totals['net_profit'], 0, ',', '.') }}</h5></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm bg-dark text-white h-100"><div class="card-body"><span class="text-white-50 fw-bold small text-uppercase">Blended Net Margin</span><h5 class="fw-bold mt-2 mb-0">{{ number_format($totals['net_margin'], 2, ',', '.') }}%</h5></div></div></div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('reports.projects') }}" class="row g-2 align-items-end">
            <div class="col-md-4"><label class="form-label small fw-bold text-muted" for="projectSearch">Cari Project / Invoice / Client</label><input id="projectSearch" class="form-control form-control-sm" name="search" value="{{ $search }}" placeholder="Project, nomor invoice, atau client"></div>
            <div class="col-md-3"><label class="form-label small fw-bold text-muted" for="projectStatus">Status Project / Invoice</label><select id="projectStatus" name="status" class="form-select form-select-sm"><option value="">Semua Status</option><option value="active" {{ $status === 'active' ? 'selected' : '' }}>Project: Aktif</option><option value="on_hold" {{ $status === 'on_hold' ? 'selected' : '' }}>Project: Ditunda</option><option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Project: Selesai</option><option value="DRAFT" {{ $status === 'DRAFT' ? 'selected' : '' }}>Invoice: DRAFT</option><option value="SENT" {{ $status === 'SENT' ? 'selected' : '' }}>Invoice: SENT</option><option value="PAID" {{ $status === 'PAID' ? 'selected' : '' }}>Invoice: PAID</option></select></div>
            <div class="col-md-3"><label class="form-label small fw-bold text-muted" for="projectSort">Urutkan</label><select id="projectSort" name="sort" class="form-select form-select-sm">
                <option value="name_asc" {{ $sort === 'name_asc' ? 'selected' : '' }}>Nama A–Z</option><option value="name_desc" {{ $sort === 'name_desc' ? 'selected' : '' }}>Nama Z–A</option>
                <option value="revenue_desc" {{ $sort === 'revenue_desc' ? 'selected' : '' }}>Revenue tertinggi</option><option value="revenue_asc" {{ $sort === 'revenue_asc' ? 'selected' : '' }}>Revenue terendah</option>
                <option value="expense_desc" {{ $sort === 'expense_desc' ? 'selected' : '' }}>Expense tertinggi</option><option value="expense_asc" {{ $sort === 'expense_asc' ? 'selected' : '' }}>Expense terendah</option>
                <option value="profit_desc" {{ $sort === 'profit_desc' ? 'selected' : '' }}>Profit tertinggi</option><option value="profit_asc" {{ $sort === 'profit_asc' ? 'selected' : '' }}>Profit terendah</option>
                <option value="margin_desc" {{ $sort === 'margin_desc' ? 'selected' : '' }}>Margin tertinggi</option><option value="margin_asc" {{ $sort === 'margin_asc' ? 'selected' : '' }}>Margin terendah</option>
            </select></div>
            <div class="col-md-2 d-flex gap-2"><button class="btn btn-sm btn-primary flex-fill" type="submit">Filter</button><a class="btn btn-sm btn-outline-secondary" href="{{ route('reports.projects') }}">Reset</a></div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center"><h6 class="fw-bold mb-0">Profitabilitas Project & Invoice Lepas</h6><span class="badge bg-light text-dark border">{{ $rows->count() }} baris</span></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light"><tr><th class="ps-4">Project / Invoice</th><th>Client</th><th>Status</th><th class="text-end">Gross Revenue</th><th class="text-end">Attached Expenses</th><th class="text-end">Net Profit</th><th class="text-end pe-4">Margin %</th></tr></thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        @if($row['type'] === 'project')
                            @php($project = $row['project'])
                            <td class="ps-4"><a href="{{ route('projects.show', $project) }}" class="fw-bold text-decoration-none">{{ $project->project_name }}</a><small class="d-block text-muted">Project · {{ $project->invoices_count }} invoice</small></td>
                            <td>{{ $project->client?->name ?? '—' }}</td>
                            <td><span class="badge bg-{{ $project->status === 'active' ? 'success' : ($project->status === 'completed' ? 'secondary' : 'warning') }}-subtle text-dark border">{{ ['active' => 'Aktif', 'on_hold' => 'Ditunda', 'completed' => 'Selesai'][$project->status] ?? $project->status }}</span></td>
                        @else
                            @php($invoice = $row['invoice'])
                            <td class="ps-4">
                                @if(auth()->user()->hasAccess('invoices'))
                                    <a href="{{ route('documents.show', $invoice) }}" class="fw-bold text-decoration-none">{{ $invoice->document_number }}</a>
                                @else
                                    <span class="fw-bold">{{ $invoice->document_number }}</span>
                                @endif
                                <small class="d-block text-muted">Invoice belum terhubung ke project</small>
                            </td>
                            <td>{{ $invoice->customer?->name ?? '—' }}</td>
                            <td><span class="badge bg-light text-dark border">{{ $invoice->status }}</span></td>
                        @endif
                        <td class="text-end">{{ $currency }} {{ number_format($row['revenue'], 0, ',', '.') }}</td>
                        <td class="text-end text-danger">{{ $currency }} {{ number_format($row['direct_expense'], 0, ',', '.') }}</td>
                        <td class="text-end fw-bold {{ $row['net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">{{ $currency }} {{ number_format($row['net_profit'], 0, ',', '.') }}</td>
                        <td class="text-end pe-4 fw-bold">{{ number_format($row['net_margin'], 2, ',', '.') }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-5">Belum ada project atau invoice yang sesuai filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
