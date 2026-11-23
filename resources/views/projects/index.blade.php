@extends('layouts.app')

@push('styles')
<style>
    .project-row-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: .5rem;
        width: max-content;
        margin-left: auto;
    }

    .project-row-actions form {
        display: flex;
        margin: 0;
        flex: 0 0 auto;
    }

    .project-row-actions > a {
        flex: 0 0 auto;
    }

    .project-row-actions .btn {
        white-space: nowrap;
        min-height: 34px;
    }

    .project-invoice-picker {
        max-height: 15rem;
        overflow-y: auto;
    }

    .project-invoice-picker .form-check {
        display: flex;
        align-items: flex-start;
        gap: .5rem;
        min-height: 44px;
        padding: .5rem 0;
    }

    .project-invoice-picker .form-check-input {
        flex: 0 0 auto;
        margin: .25rem 0 0;
    }

    .project-invoice-picker .form-check-label {
        flex: 1 1 auto;
        cursor: pointer;
    }

    @media (max-width: 767.98px) {
        .project-row-actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            width: 100%;
        }

        .project-row-actions > a,
        .project-row-actions form {
            min-width: 0;
        }

        .project-row-actions form.d-inline {
            width: auto !important;
        }

        .project-row-actions .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 44px;
        }
    }
</style>
@endpush

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1"><i class="fa-solid fa-diagram-project text-primary me-2"></i>Manajemen Proyek</h1>
        <p class="text-muted mb-0">Kelompokkan beberapa Invoice dan biaya kas untuk memantau profitabilitas setiap proyek.</p>
    </div>
    @if(auth()->user()->hasAccess('project_margin'))
        <a href="{{ route('reports.projects') }}" class="btn btn-outline-primary">
            <i class="fa-solid fa-chart-pie me-1"></i> Laporan Margin Proyek
        </a>
    @endif
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h2 class="h6 fw-bold mb-0">Buat Proyek Baru</h2>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('projects.store') }}" class="row g-3">
            @csrf
            <div class="col-md-5">
                <label for="project_name" class="form-label fw-semibold">Nama Proyek</label>
                <input id="project_name" type="text" name="project_name" class="form-control" value="{{ old('project_name') }}" required maxlength="255" placeholder="Contoh: Implementasi ERP Tahap 1">
            </div>
            <div class="col-md-4">
                <label for="client_id" class="form-label fw-semibold">Pelanggan (Opsional)</label>
                <select id="client_id" name="client_id" class="form-select">
                    <option value="">-- Pilih Pelanggan --</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" {{ old('client_id', $selectedClientId) == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label fw-semibold">Status</label>
                <select id="status" name="status" class="form-select">
                    <option value="active" selected>Aktif</option>
                    <option value="on_hold">Ditunda</option>
                    <option value="completed">Selesai</option>
                </select>
            </div>

            <fieldset class="col-12">
                <legend class="form-label fw-semibold">Invoice yang langsung dihubungkan (Opsional)</legend>
                @if($unallocatedInvoices->isEmpty())
                    <p class="form-text mb-0">Tidak ada invoice yang belum terhubung ke proyek.</p>
                @else
                    <div class="project-invoice-picker border rounded p-3">
                        @foreach($unallocatedInvoices as $invoice)
                            <div class="form-check">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="invoice_ids[]"
                                    id="project_invoice_{{ $invoice->id }}"
                                    value="{{ $invoice->id }}"
                                    {{ in_array($invoice->id, (array) old('invoice_ids', $selectedInvoiceIds)) ? 'checked' : '' }}
                                >
                                <label class="form-check-label" for="project_invoice_{{ $invoice->id }}">
                                    <span class="fw-semibold">{{ $invoice->document_number }}</span>
                                    <span class="text-muted">· {{ $invoice->customer?->name ?? '-' }}</span>
                                    <small class="d-block text-muted">
                                        Nilai {{ $currency }} {{ number_format($invoice->total_amount, 0, ',', '.') }}
                                        · Kas tertaut {{ $currency }} {{ number_format($invoice->direct_expenses_sum_amount ?? 0, 2, ',', '.') }}
                                    </small>
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <div class="form-text" id="projectInvoiceHelp">Kas yang sudah tertaut ke invoice pilihan otomatis dihitung sebagai biaya proyek. Tidak perlu menghubungkan transaksi itu lagi.</div>
                @endif
            </fieldset>

            <div class="col-md-2 ms-md-auto d-grid">
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-plus me-1" aria-hidden="true"></i>Buat proyek</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h2 class="h6 fw-bold mb-0">Daftar Proyek</h2>
        <span class="badge bg-light text-dark border">{{ $projects->total() }} proyek</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 m-cards">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4">Proyek</th>
                    <th>Pelanggan</th>
                    <th>Status</th>
                    <th class="text-end">Nominal Keuntungan</th>
                    <th class="text-end">Pendapatan</th>
                    <th class="text-end">Biaya Kas</th>
                    <th class="text-end pe-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($projects as $project)
                    <tr>
                        <td class="ps-4 mc-title mc-o1" data-label="Proyek">
                            <a class="fw-bold text-decoration-none" href="{{ route('projects.show', $project) }}">{{ $project->project_name }}</a>
                        </td>
                        <td class="mc-o2" data-label="Pelanggan">{{ $project->client?->name ?? '-' }}</td>
                        <td class="mc-half mc-o3" data-label="Status">
                            @php($statusLabel = ['active' => 'Aktif', 'on_hold' => 'Ditunda', 'completed' => 'Selesai'][$project->status] ?? $project->status)
                            <span class="badge bg-{{ $project->status === 'active' ? 'success' : ($project->status === 'completed' ? 'secondary' : 'warning') }}-subtle text-dark border">{{ $statusLabel }}</span>
                        </td>
                        <td class="text-end fw-semibold mc-half mc-o4 {{ $project->net_profit >= 0 ? 'text-success' : 'text-danger' }}" data-label="Nominal Keuntungan">{{ number_format($project->net_profit, 0, ',', '.') }}</td>
                        <td class="text-end text-success fw-semibold mc-half mc-o5" data-label="Pendapatan">{{ number_format($project->invoices_sum_total_amount ?? 0, 0, ',', '.') }}</td>
                        <td class="text-end text-danger fw-semibold mc-half mc-o6" data-label="Biaya Kas">{{ number_format(($project->direct_expenses_sum_amount ?? 0) + ($project->invoice_expenses_sum_amount ?? 0), 0, ',', '.') }}</td>
                        <td class="text-end pe-4 mc-foot mc-o7" data-label="Aksi">
                            <div class="project-row-actions">
                                <a href="{{ route('projects.show', $project) }}#project-settings" class="btn btn-sm btn-outline-primary btn-with-label" title="Edit proyek {{ $project->project_name }}">
                                    <i class="fa-solid fa-pen-to-square me-1" aria-hidden="true"></i> Edit
                                </a>
                                <form method="POST" action="{{ route('projects.destroy', $project) }}" class="d-inline" data-confirm="Proyek akan dihapus." data-confirm-impact="Invoice dan transaksi terkait tetap tersimpan, tetapi relasi proyeknya dilepas." data-confirm-title="Hapus Proyek?" data-confirm-subject="{{ $project->project_name }}" data-confirm-button="Ya, Hapus">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger btn-with-label" title="Hapus proyek {{ $project->project_name }}">
                                        <i class="fa-solid fa-trash-can me-1" aria-hidden="true"></i> Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr class="mc-empty"><td colspan="7" class="text-center text-muted py-5">Belum ada proyek. Buat proyek pertama untuk mulai mengelompokkan Invoice dan kas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($projects->hasPages())
        <div class="card-footer bg-white">{{ $projects->links() }}</div>
    @endif
</div>
@endsection
