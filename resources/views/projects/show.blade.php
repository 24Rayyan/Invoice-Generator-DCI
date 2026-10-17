@extends('layouts.app')

@php($currency = $setting->currency_symbol ?? 'Rp')

@section('content')
@push('styles')
<style>
    .project-attach-modal-content > form {
        display: flex;
        flex: 1 1 auto;
        flex-direction: column;
        min-height: 0;
        overflow: hidden;
    }

    .project-attach-modal-content > form > .modal-body {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
    }
</style>
@endpush
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('projects.index') }}" class="text-decoration-none text-muted">Projects</a></li>
                <li class="breadcrumb-item active">{{ $project->project_name }}</li>
            </ol>
        </nav>
        <h3 class="fw-bold text-dark mb-1">{{ $project->project_name }}</h3>
        <p class="text-muted mb-0">{{ $project->client?->name ?? 'Client belum ditentukan' }} · Status: {{ str_replace('_', ' ', ucfirst($project->status)) }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="#project-settings" class="btn btn-outline-primary"><i class="fa-solid fa-pen-to-square me-1"></i>Edit Project</a>
        <a href="{{ route('reports.projects') }}" class="btn btn-outline-primary"><i class="fa-solid fa-chart-pie me-1"></i>Laporan Margin</a>
        <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Daftar Project</a>
    </div>
</div>

@if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
@if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3"><div class="card border-0 shadow-sm h-100 border-start border-4 border-success"><div class="card-body"><span class="small text-muted fw-bold text-uppercase">Total Revenue</span><h4 class="fw-bold text-success mt-2 mb-0">{{ $currency }} {{ number_format($revenue, 0, ',', '.') }}</h4><small class="text-muted">Total semua invoice project</small></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card border-0 shadow-sm h-100 border-start border-4 border-danger"><div class="card-body"><span class="small text-muted fw-bold text-uppercase">Total Direct Expense</span><h4 class="fw-bold text-danger mt-2 mb-0">{{ $currency }} {{ number_format($directExpense, 0, ',', '.') }}</h4><small class="text-muted">Kas keluar yang terhubung ke project</small></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card border-0 shadow-sm h-100 border-start border-4 border-info"><div class="card-body"><span class="small text-muted fw-bold text-uppercase">Net Profit</span><h4 class="fw-bold {{ $netProfit >= 0 ? 'text-info' : 'text-danger' }} mt-2 mb-0">{{ $currency }} {{ number_format($netProfit, 0, ',', '.') }}</h4><small class="text-muted">Revenue dikurangi direct expense</small></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card border-0 shadow-sm bg-dark text-white h-100"><div class="card-body"><span class="small text-white-50 fw-bold text-uppercase">Net Margin</span><h4 class="fw-bold mt-2 mb-0">{{ number_format($netMargin, 2, ',', '.') }}%</h4><small class="text-white-50">{{ $revenue > 0 ? 'Net profit / revenue' : 'Revenue 0 — margin ditampilkan 0%' }}</small></div></div></div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
        <div><h6 class="fw-bold mb-1"><i class="fa-solid fa-file-invoice me-2 text-primary"></i>Invoice dalam Project</h6><small class="text-muted">Pendapatan dari semua invoice yang dikelompokkan di project ini.</small></div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#attachProjectInvoices"><i class="fa-solid fa-link me-1"></i>Hubungkan Invoice</button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light"><tr><th class="ps-4">No. Invoice</th><th>Customer</th><th>Tanggal</th><th>Status</th><th class="text-end">Nilai Invoice</th><th class="text-end pe-4">Aksi</th></tr></thead>
            <tbody>
                @forelse($project->invoices as $invoice)
                    <tr>
                        <td class="ps-4"><a href="{{ route('documents.show', $invoice) }}" class="fw-bold text-decoration-none">{{ $invoice->document_number }}</a></td>
                        <td>{{ $invoice->customer?->name ?? '—' }}</td>
                        <td>{{ $invoice->date?->format('d/m/Y') }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $invoice->status }}</span></td>
                        <td class="text-end fw-semibold">{{ $currency }} {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                        <td class="text-end pe-4">
                            <form method="POST" action="{{ route('projects.detach-invoice', [$project, $invoice]) }}" onsubmit="return confirm('Lepas invoice ini dari project? Invoice tidak dihapus.')">
                                @csrf
                                <button class="btn btn-sm btn-outline-warning" type="submit"><i class="fa-solid fa-unlink me-1"></i>Detach</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada invoice pada project ini. Hubungkan invoice yang tersedia atau pilih project dari form invoice.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="attachProjectInvoices" tabindex="-1" aria-labelledby="attachProjectInvoicesLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content project-attach-modal-content border-0 rounded-4 shadow">
            <form class="project-attach-modal-form" method="POST" action="{{ route('projects.attach-invoices', $project) }}">
                @csrf
                <div class="modal-header">
                    <div><h5 class="modal-title fw-bold" id="attachProjectInvoicesLabel">Hubungkan Invoice ke Project</h5><small class="text-muted">Hanya menampilkan invoice yang belum terikat ke project mana pun.</small></div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    @if($unallocatedInvoices->isEmpty())
                        <p class="text-center text-muted py-4 mb-0">Tidak ada invoice yang belum terhubung ke project.</p>
                    @else
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label for="projectInvoiceSearch" class="form-label small fw-semibold mb-1">Cari Invoice / Customer</label>
                                <input type="search" id="projectInvoiceSearch" class="form-control form-control-sm" placeholder="Nomor invoice atau nama customer">
                            </div>
                            <div class="col-md-3">
                                <label for="projectInvoiceDateFrom" class="form-label small fw-semibold mb-1">Tanggal Mulai</label>
                                <input type="date" id="projectInvoiceDateFrom" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3">
                                <label for="projectInvoiceDateTo" class="form-label small fw-semibold mb-1">Tanggal Akhir</label>
                                <input type="date" id="projectInvoiceDateTo" class="form-control form-control-sm">
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small text-muted"><span id="projectInvoiceVisibleCount">{{ $unallocatedInvoices->count() }}</span> invoice tersedia</span>
                            <button type="button" id="resetProjectInvoiceFilters" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-rotate-left me-1"></i>Reset filter</button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead class="bg-light"><tr><th><input class="form-check-input" type="checkbox" id="selectAllProjectInvoices" aria-label="Pilih invoice yang tampil"></th><th>No. Invoice</th><th>Customer</th><th>Tanggal</th><th>Status</th><th class="text-end">Nilai</th></tr></thead>
                                <tbody>
                                    @foreach($unallocatedInvoices as $invoice)
                                        <tr data-project-invoice-search="{{ \Illuminate\Support\Str::lower($invoice->document_number . ' ' . ($invoice->customer?->name ?? '') . ' ' . $invoice->status . ' ' . $invoice->total_amount . ' ' . number_format($invoice->total_amount, 0, ',', '.')) }}" data-project-invoice-date="{{ $invoice->date?->format('Y-m-d') }}">
                                            <td><input class="form-check-input project-invoice-checkbox" type="checkbox" name="invoice_ids[]" value="{{ $invoice->id }}"></td>
                                            <td class="fw-semibold">{{ $invoice->document_number }}</td>
                                            <td>{{ $invoice->customer?->name ?? '—' }}</td>
                                            <td>{{ $invoice->date?->format('d/m/Y') }}</td>
                                            <td><span class="badge bg-light text-dark border">{{ $invoice->status }}</span></td>
                                            <td class="text-end">{{ $currency }} {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                    <tr id="noProjectInvoiceMatches" class="d-none"><td colspan="6" class="text-center text-muted py-4">Tidak ada invoice yang sesuai dengan pencarian atau rentang tanggal.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
                <div class="modal-footer"><button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" type="submit" {{ $unallocatedInvoices->isEmpty() ? 'disabled' : '' }}>Hubungkan yang Dipilih</button></div>
            </form>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-5">
    <div class="card-header bg-white py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
        <div><h6 class="fw-bold mb-1"><i class="fa-solid fa-money-bill-transfer me-2 text-danger"></i>Kas Keluar Project</h6><small class="text-muted">Biaya operasional dengan project_id kosong tidak dihitung.</small></div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#attachProjectExpenses"><i class="fa-solid fa-link me-1"></i>Hubungkan Kas</button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light"><tr><th class="ps-4">No. Transaksi</th><th>Tanggal</th><th>Kategori</th><th>Keterangan</th><th class="text-end">Jumlah</th><th class="text-end pe-4">Aksi</th></tr></thead>
            <tbody>
                @forelse($project->directExpenses as $transaction)
                    <tr>
                        <td class="ps-4 fw-semibold">{{ $transaction->transaction_number }}</td>
                        <td>{{ $transaction->transaction_date?->format('d/m/Y') }}</td>
                        <td>{{ $transaction->category?->name ?? '—' }}</td>
                        <td>{{ $transaction->description ?? '—' }}</td>
                        <td class="text-end text-danger fw-semibold">{{ $currency }} {{ number_format($transaction->amount, 0, ',', '.') }}</td>
                        <td class="text-end pe-4">
                            <form method="POST" action="{{ route('projects.detach-expense', [$project, $transaction]) }}" onsubmit="return confirm('Lepas transaksi kas ini dari project?')">
                                @csrf
                                <button class="btn btn-sm btn-outline-warning" type="submit"><i class="fa-solid fa-unlink me-1"></i>Detach</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada kas keluar yang terhubung ke project ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="attachProjectExpenses" tabindex="-1" aria-labelledby="attachProjectExpensesLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content project-attach-modal-content border-0 rounded-4 shadow">
            <form class="project-attach-modal-form" method="POST" action="{{ route('projects.attach-expenses', $project) }}">
                @csrf
                <div class="modal-header"><div><h5 class="modal-title fw-bold" id="attachProjectExpensesLabel">Hubungkan Kas ke Project</h5><small class="text-muted">Hanya kas keluar dengan project_id kosong.</small></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    @if($unallocatedExpenses->isEmpty())
                        <p class="text-center text-muted py-4 mb-0">Tidak ada transaksi kas keluar yang belum terhubung ke project.</p>
                    @else
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label for="projectExpenseSearch" class="form-label small fw-semibold mb-1">Cari Transaksi</label>
                                <input type="search" id="projectExpenseSearch" class="form-control form-control-sm" placeholder="Nomor transaksi, keterangan, atau nominal">
                            </div>
                            <div class="col-md-3">
                                <label for="projectExpenseDateFrom" class="form-label small fw-semibold mb-1">Tanggal Mulai</label>
                                <input type="date" id="projectExpenseDateFrom" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3">
                                <label for="projectExpenseDateTo" class="form-label small fw-semibold mb-1">Tanggal Akhir</label>
                                <input type="date" id="projectExpenseDateTo" class="form-control form-control-sm">
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small text-muted"><span id="projectExpenseVisibleCount">{{ $unallocatedExpenses->count() }}</span> transaksi tersedia</span>
                            <button type="button" id="resetProjectExpenseFilters" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-rotate-left me-1"></i>Reset filter</button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead class="bg-light"><tr><th><input class="form-check-input" type="checkbox" id="selectAllProjectExpenses" aria-label="Pilih transaksi yang tampil"></th><th>No. Transaksi</th><th>Tanggal</th><th>Keterangan</th><th class="text-end">Jumlah</th></tr></thead>
                                <tbody>
                                    @foreach($unallocatedExpenses as $transaction)
                                        <tr data-project-expense-search="{{ \Illuminate\Support\Str::lower($transaction->transaction_number . ' ' . ($transaction->description ?? '') . ' ' . $transaction->transaction_date?->format('d/m/Y') . ' ' . $transaction->amount . ' ' . number_format($transaction->amount, 0, ',', '.') . ' ' . $currency) }}" data-project-expense-date="{{ $transaction->transaction_date?->format('Y-m-d') }}">
                                            <td><input class="form-check-input project-expense-checkbox" type="checkbox" name="transaction_ids[]" value="{{ $transaction->id }}"></td>
                                            <td>{{ $transaction->transaction_number }}</td>
                                            <td>{{ $transaction->transaction_date?->format('d/m/Y') }}</td>
                                            <td>{{ \Illuminate\Support\Str::limit($transaction->description, 50) }}</td>
                                            <td class="text-end">{{ $currency }} {{ number_format($transaction->amount, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                    <tr id="noProjectExpenseMatches" class="d-none"><td colspan="5" class="text-center text-muted py-4">Tidak ada transaksi yang sesuai dengan pencarian atau rentang tanggal.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
                <div class="modal-footer"><button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" type="submit" {{ $unallocatedExpenses->isEmpty() ? 'disabled' : '' }}>Hubungkan yang Dipilih</button></div>
            </form>
        </div>
    </div>
</div>

<form id="project-settings" method="POST" action="{{ route('projects.update', $project) }}" class="card border-0 shadow-sm mb-5">
    @csrf
    @method('PUT')
    <div class="card-header bg-white py-3"><h6 class="fw-bold mb-0">Pengaturan Project</h6></div>
    <div class="card-body row g-3 align-items-end">
        <div class="col-md-5"><label class="form-label fw-semibold" for="edit_project_name">Nama Project</label><input id="edit_project_name" class="form-control" name="project_name" value="{{ $project->project_name }}" required></div>
        <div class="col-md-4"><label class="form-label fw-semibold" for="edit_client_id">Client</label><select id="edit_client_id" class="form-select" name="client_id"><option value="">-- Tanpa client --</option>@foreach($customers as $customer)<option value="{{ $customer->id }}" {{ $project->client_id == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label fw-semibold" for="edit_status">Status</label><select id="edit_status" class="form-select" name="status"><option value="active" {{ $project->status === 'active' ? 'selected' : '' }}>Aktif</option><option value="on_hold" {{ $project->status === 'on_hold' ? 'selected' : '' }}>Ditunda</option><option value="completed" {{ $project->status === 'completed' ? 'selected' : '' }}>Selesai</option></select></div>
        <div class="col-md-1 d-grid"><button class="btn btn-outline-primary" type="submit" aria-label="Simpan project"><i class="fa-solid fa-floppy-disk"></i></button></div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    function setupProjectModalFilters(config) {
        const search = document.getElementById(config.search);
        const from = document.getElementById(config.from);
        const to = document.getElementById(config.to);
        const selectAll = document.getElementById(config.selectAll);
        const reset = document.getElementById(config.reset);
        const visibleCount = document.getElementById(config.visibleCount);
        const noMatches = document.getElementById(config.noMatches);
        const rows = Array.from(document.querySelectorAll(config.rows));

        if (!search || !from || !to) return;

        function applyFilters() {
            const term = search.value.trim().toLocaleLowerCase();
            const startDate = from.value;
            const endDate = to.value;
            let visible = 0;

            rows.forEach((row) => {
                const matchesTerm = (row.getAttribute(config.searchAttribute) || '').includes(term);
                const date = row.getAttribute(config.dateAttribute) || '';
                const matchesStart = !startDate || date >= startDate;
                const matchesEnd = !endDate || date <= endDate;
                const matches = matchesTerm && matchesStart && matchesEnd;
                row.classList.toggle('d-none', !matches);
                if (!matches) row.querySelector('input[type="checkbox"]').checked = false;
                if (matches) visible++;
            });

            if (visibleCount) visibleCount.textContent = visible;
            if (noMatches) noMatches.classList.toggle('d-none', visible !== 0);
            if (selectAll) selectAll.checked = false;
        }

        [search, from, to].forEach((input) => input.addEventListener('input', applyFilters));
        if (selectAll) {
            selectAll.addEventListener('change', function () {
                rows.filter((row) => !row.classList.contains('d-none')).forEach((row) => {
                    row.querySelector('input[type="checkbox"]').checked = this.checked;
                });
            });
        }
        if (reset) {
            reset.addEventListener('click', function () {
                search.value = '';
                from.value = '';
                to.value = '';
                applyFilters();
            });
        }
    }

    setupProjectModalFilters({
        search: 'projectExpenseSearch',
        from: 'projectExpenseDateFrom',
        to: 'projectExpenseDateTo',
        selectAll: 'selectAllProjectExpenses',
        reset: 'resetProjectExpenseFilters',
        visibleCount: 'projectExpenseVisibleCount',
        noMatches: 'noProjectExpenseMatches',
        rows: '[data-project-expense-search]',
        searchAttribute: 'data-project-expense-search',
        dateAttribute: 'data-project-expense-date'
    });
    setupProjectModalFilters({
        search: 'projectInvoiceSearch',
        from: 'projectInvoiceDateFrom',
        to: 'projectInvoiceDateTo',
        selectAll: 'selectAllProjectInvoices',
        reset: 'resetProjectInvoiceFilters',
        visibleCount: 'projectInvoiceVisibleCount',
        noMatches: 'noProjectInvoiceMatches',
        rows: '[data-project-invoice-search]',
        searchAttribute: 'data-project-invoice-search',
        dateAttribute: 'data-project-invoice-date'
    });
</script>
@endpush
