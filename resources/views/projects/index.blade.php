@extends('layouts.app')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold text-dark mb-1"><i class="fa-solid fa-diagram-project text-primary me-2"></i>Manajemen Project</h3>
        <p class="text-muted mb-0">Kelompokkan beberapa invoice dan biaya kas untuk memantau profitabilitas setiap project.</p>
    </div>
    <a href="{{ route('reports.projects') }}" class="btn btn-outline-primary">
        <i class="fa-solid fa-chart-pie me-1"></i> Laporan Margin Project
    </a>
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
        <h6 class="fw-bold mb-0">Buat Project Baru</h6>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('projects.store') }}" class="row g-3 align-items-end">
            @csrf
            <div class="col-md-5">
                <label for="project_name" class="form-label fw-semibold">Nama Project</label>
                <input id="project_name" type="text" name="project_name" class="form-control" value="{{ old('project_name') }}" required maxlength="255" placeholder="Contoh: Implementasi ERP Tahap 1">
            </div>
            <div class="col-md-4">
                <label for="client_id" class="form-label fw-semibold">Client (Opsional)</label>
                <select id="client_id" name="client_id" class="form-select">
                    <option value="">-- Pilih Client --</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" {{ old('client_id') == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label fw-semibold">Status</label>
                <select id="status" name="status" class="form-select">
                    <option value="active" selected>Aktif</option>
                    <option value="on_hold">Ditunda</option>
                    <option value="completed">Selesai</option>
                </select>
            </div>
            <div class="col-md-1 d-grid">
                <button class="btn btn-primary" type="submit" aria-label="Buat project"><i class="fa-solid fa-plus"></i></button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h6 class="fw-bold mb-0">Daftar Project</h6>
        <span class="badge bg-light text-dark border">{{ $projects->total() }} project</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4">Project</th>
                    <th>Client</th>
                    <th>Status</th>
                    <th class="text-end">Invoice</th>
                    <th class="text-end">Pendapatan</th>
                    <th class="text-end">Biaya Kas</th>
                    <th class="text-end pe-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($projects as $project)
                    <tr>
                        <td class="ps-4">
                            <a class="fw-bold text-decoration-none" href="{{ route('projects.show', $project) }}">{{ $project->project_name }}</a>
                        </td>
                        <td>{{ $project->client?->name ?? '—' }}</td>
                        <td>
                            @php($statusLabel = ['active' => 'Aktif', 'on_hold' => 'Ditunda', 'completed' => 'Selesai'][$project->status] ?? $project->status)
                            <span class="badge bg-{{ $project->status === 'active' ? 'success' : ($project->status === 'completed' ? 'secondary' : 'warning') }}-subtle text-dark border">{{ $statusLabel }}</span>
                        </td>
                        <td class="text-end">{{ $project->invoices_count }}</td>
                        <td class="text-end text-success fw-semibold">{{ number_format($project->invoices_sum_total_amount ?? 0, 0, ',', '.') }}</td>
                        <td class="text-end text-danger fw-semibold">{{ number_format($project->direct_expenses_sum_amount ?? 0, 0, ',', '.') }}</td>
                        <td class="text-end pe-4 text-nowrap">
                            <a href="{{ route('projects.show', $project) }}#project-settings" class="btn btn-sm btn-outline-primary" title="Edit project {{ $project->project_name }}">
                                <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                            </a>
                            <form method="POST" action="{{ route('projects.destroy', $project) }}" class="d-inline" onsubmit="return confirm('Hapus project ini? Invoice dan transaksi terkait tetap tersimpan, tetapi relasi project-nya akan dilepas.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus project {{ $project->project_name }}">
                                    <i class="fa-solid fa-trash-can me-1"></i> Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-5">Belum ada project. Buat project pertama untuk mulai mengelompokkan invoice dan kas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($projects->hasPages())
        <div class="card-footer bg-white">{{ $projects->links() }}</div>
    @endif
</div>
@endsection
