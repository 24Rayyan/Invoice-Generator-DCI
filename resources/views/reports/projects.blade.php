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

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

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
            <table class="rpt-table m-cards">
                <thead>
                    <tr>
                        <th>Proyek / invoice</th>
                        <th>Pelanggan</th>
                        <th>Status</th>
                        <th class="r">Pendapatan bruto</th>
                        <th class="r">Biaya terhubung</th>
                        <th class="r">Laba bersih</th>
                        <th style="min-width: 210px;">Margin</th>
                        <th>Aksi</th>
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
                                <td class="mc-title mc-o1" data-label="Proyek / invoice">
                                    @if(auth()->user()->hasAccess('projects'))
                                        <a href="{{ route('projects.show', $project) }}" class="text-decoration-none" style="font-weight: 600;">{{ $project->project_name }}</a>
                                    @else
                                        <span style="font-weight: 600;">{{ $project->project_name }}</span>
                                    @endif
                                    <span class="sub">Proyek · {{ $project->invoices_count }} invoice</span>
                                </td>
                                <td class="mc-half mc-o2" data-label="Pelanggan">{{ $project->client?->name ?? '-' }}</td>
                                <td class="mc-half mc-o3" data-label="Status">
                                    <span class="rpt-tag {{ $project->status === 'active' ? 'ok' : ($project->status === 'on_hold' ? 'warn' : '') }}">{{ $projectStatusLabel[$project->status] ?? $project->status }}</span>
                                </td>
                            @else
                                @php($invoice = $row['invoice'])
                                <td class="mc-title mc-o1" data-label="Proyek / invoice">
                                    @if(auth()->user()->hasAccess('invoices'))
                                        <a href="{{ route('documents.show', $invoice) }}" class="text-decoration-none" style="font-weight: 600;">{{ $invoice->document_number }}</a>
                                    @else
                                        <span style="font-weight: 600;">{{ $invoice->document_number }}</span>
                                    @endif
                                    <span class="sub">Invoice belum terhubung ke proyek</span>
                                </td>
                                <td class="mc-half mc-o2" data-label="Pelanggan">{{ $invoice->customer?->name ?? '-' }}</td>
                                <td class="mc-half mc-o3" data-label="Status"><span class="rpt-tag {{ $invoice->status === 'PAID' ? 'ok' : ($invoice->status === 'SENT' ? 'info' : '') }}">{{ $invoice->status }}</span></td>
                            @endif
                            <td class="r mc-half mc-o4" data-label="Pendapatan bruto">{{ number_format($row['revenue'], 0, ',', '.') }}</td>
                            <td class="r mc-half mc-o5" data-label="Biaya terhubung">{{ number_format($row['direct_expense'], 0, ',', '.') }}</td>
                            <td class="r mc-half mc-o6 {{ $row['net_profit'] >= 0 ? '' : 'neg' }}" data-label="Laba bersih" style="font-weight: 700;">{{ number_format($row['net_profit'], 0, ',', '.') }}</td>
                            <td class="mc-foot mc-o7" data-label="Margin">
                                <div class="rpt-meter">
                                    <div class="rpt-diverge" role="img" aria-label="Margin {{ number_format($row['net_margin'], 2, ',', '.') }} persen">
                                        <i class="{{ $marginPositive ? 'pos' : 'neg' }}" style="width: {{ $barWidth }}%;"></i>
                                    </div>
                                    <span class="rpt-meter-val {{ $marginPositive ? '' : 'neg' }}">{{ number_format($row['net_margin'], 2, ',', '.') }}%</span>
                                </div>
                            </td>
                            <td class="mc-foot mc-o8 rpt-project-actions" data-label="Aksi">
                                @if($row['type'] === 'project')
                                    @php($project = $row['project'])
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#reportProjectExpenseModal"
                                        data-project-id="{{ $project->id }}"
                                        data-project-name="{{ $project->project_name }}"
                                        data-submit-url="{{ route('reports.projects.attach-expenses', $project) }}"
                                        aria-label="Alokasikan kas ke proyek {{ $project->project_name }}"
                                        {{ $unallocatedExpenses->isEmpty() ? 'disabled' : '' }}
                                    >
                                        <i class="fa-solid fa-link me-1" aria-hidden="true"></i> Alokasikan Kas
                                    </button>
                                @else
                                    @if(auth()->user()->hasAccess('projects'))
                                        <a
                                            href="{{ route('projects.index', ['invoice_id' => $invoice->id]) }}"
                                            class="btn btn-sm btn-outline-primary"
                                            aria-label="Buat proyek dari invoice {{ $invoice->document_number }}"
                                        >Jadikan Proyek</a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
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

    <div class="modal fade" id="reportProjectExpenseModal" tabindex="-1" aria-labelledby="reportProjectExpenseTitle" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 rounded-4 shadow">
                <form id="reportProjectExpenseForm" method="POST" action="{{ route('reports.projects') }}">
                    @csrf
                    <input type="hidden" name="report_project_id" id="reportProjectExpenseId" value="{{ old('report_project_id') }}">
                    <div class="modal-header">
                        <div>
                            <h2 class="h5 modal-title fw-bold" id="reportProjectExpenseTitle">Alokasikan Kas ke Proyek</h2>
                            <small class="text-muted">Pilih transaksi dan nominal yang akan dihitung sebagai biaya proyek.</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        @if($unallocatedExpenses->isEmpty())
                            <p class="text-center text-muted py-4 mb-0">Tidak ada transaksi kas keluar dengan sisa yang bisa dialokasikan.</p>
                        @else
                            <div class="row g-2 mb-3">
                                <div class="col-12 col-md-6">
                                    <label for="reportExpenseSearch" class="form-label small fw-semibold mb-1">Cari Transaksi</label>
                                    <input type="search" id="reportExpenseSearch" class="form-control form-control-sm" placeholder="Nomor transaksi, keterangan, atau nominal">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label for="reportExpenseDateFrom" class="form-label small fw-semibold mb-1">Tanggal Mulai</label>
                                    <input type="date" id="reportExpenseDateFrom" class="form-control form-control-sm">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label for="reportExpenseDateTo" class="form-label small fw-semibold mb-1">Tanggal Akhir</label>
                                    <input type="date" id="reportExpenseDateTo" class="form-control form-control-sm">
                                </div>
                            </div>
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                <span class="small text-muted"><span id="reportExpenseVisibleCount">{{ $unallocatedExpenses->count() }}</span> transaksi dengan sisa tersedia</span>
                                <button type="button" id="resetReportExpenseFilters" class="btn btn-sm btn-outline-secondary">Atur ulang filter</button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle rpt-allocation-table">
                                    <thead class="bg-light">
                                        <tr>
                                            <th><input class="form-check-input" type="checkbox" id="selectAllReportExpenses" aria-label="Pilih semua transaksi yang tampil"></th>
                                            <th>No. Transaksi</th>
                                            <th>Tanggal</th>
                                            <th>Keterangan</th>
                                            <th class="text-end">Nilai</th>
                                            <th class="text-end">Terpakai</th>
                                            <th class="text-end">Sisa</th>
                                            <th class="text-end">Alokasi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($unallocatedExpenses as $transaction)
                                            <tr
                                                data-report-expense-search="{{ \Illuminate\Support\Str::lower($transaction->transaction_number . ' ' . ($transaction->description ?? '') . ' ' . $transaction->transaction_date?->format('d/m/Y') . ' ' . $transaction->amount . ' ' . number_format($transaction->amount, 0, ',', '.')) }}"
                                                data-report-expense-date="{{ $transaction->transaction_date?->format('Y-m-d') }}"
                                            >
                                                <td>
                                                    <input class="form-check-input report-expense-checkbox" type="checkbox" name="transaction_ids[]" value="{{ $transaction->id }}" aria-label="Pilih {{ $transaction->transaction_number }}" {{ in_array($transaction->id, (array) old('transaction_ids', [])) ? 'checked' : '' }}>
                                                </td>
                                                <td class="text-nowrap">{{ $transaction->transaction_number }}</td>
                                                <td class="text-nowrap">{{ $transaction->transaction_date?->format('d/m/Y') }}</td>
                                                <td>{{ $transaction->description ?? '-' }}</td>
                                                <td class="text-end text-nowrap">{{ $currency }} {{ number_format($transaction->amount, 2, ',', '.') }}</td>
                                                <td class="text-end text-nowrap">{{ $currency }} {{ number_format($transaction->allocated_project_amount ?? 0, 2, ',', '.') }}</td>
                                                <td class="text-end text-nowrap fw-semibold text-success">{{ $currency }} {{ number_format($transaction->remaining_project_amount, 2, ',', '.') }}</td>
                                                <td>
                                                    <input class="form-control form-control-sm text-end report-expense-allocation-input" type="number" name="allocations[{{ $transaction->id }}]" value="{{ old('allocations.'.$transaction->id) }}" min="0.01" max="{{ number_format($transaction->remaining_project_amount, 2, '.', '') }}" step="0.01" placeholder="Nominal" aria-label="Nominal alokasi untuk {{ $transaction->transaction_number }}" {{ in_array($transaction->id, (array) old('transaction_ids', [])) ? 'required' : 'disabled' }}>
                                                </td>
                                            </tr>
                                        @endforeach
                                        <tr id="noReportExpenseMatches" class="d-none"><td colspan="8" class="text-center text-muted py-4">Tidak ada transaksi yang sesuai dengan pencarian atau rentang tanggal.</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Batal</button>
                        <button class="btn btn-primary" type="submit" {{ $unallocatedExpenses->isEmpty() ? 'disabled' : '' }}>Simpan Alokasi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const modal = document.getElementById('reportProjectExpenseModal');
        const form = document.getElementById('reportProjectExpenseForm');
        const title = document.getElementById('reportProjectExpenseTitle');
        const projectId = document.getElementById('reportProjectExpenseId');

        if (!modal || !form) return;

        function setProject(button) {
            form.action = button.dataset.submitUrl;
            title.textContent = `Alokasikan Kas ke ${button.dataset.projectName}`;
            projectId.value = button.dataset.projectId;
        }

        modal.addEventListener('show.bs.modal', (event) => {
            if (event.relatedTarget) setProject(event.relatedTarget);
        });

        const search = document.getElementById('reportExpenseSearch');
        const from = document.getElementById('reportExpenseDateFrom');
        const to = document.getElementById('reportExpenseDateTo');
        const selectAll = document.getElementById('selectAllReportExpenses');
        const visibleCount = document.getElementById('reportExpenseVisibleCount');
        const noMatches = document.getElementById('noReportExpenseMatches');
        const rows = Array.from(document.querySelectorAll('[data-report-expense-search]'));

        function applyFilters() {
            const term = search.value.trim().toLocaleLowerCase();
            let visible = 0;

            rows.forEach((row) => {
                const textMatches = row.dataset.reportExpenseSearch.includes(term);
                const date = row.dataset.reportExpenseDate || '';
                const matches = textMatches && (!from.value || date >= from.value) && (!to.value || date <= to.value);
                row.classList.toggle('d-none', !matches);
                if (!matches) {
                    const checkbox = row.querySelector('.report-expense-checkbox');
                    checkbox.checked = false;
                    checkbox.dispatchEvent(new Event('change'));
                }
                if (matches) visible++;
            });

            visibleCount.textContent = visible;
            noMatches.classList.toggle('d-none', visible !== 0);
            selectAll.checked = false;
        }

        if (search && from && to) {
            [search, from, to].forEach((input) => input.addEventListener('input', applyFilters));
            rows.forEach((row) => {
                const checkbox = row.querySelector('.report-expense-checkbox');
                const allocation = row.querySelector('.report-expense-allocation-input');
                checkbox.addEventListener('change', function () {
                    allocation.disabled = !this.checked;
                    allocation.required = this.checked;
                    if (!this.checked) allocation.value = '';
                });
            });
            selectAll.addEventListener('change', function () {
                rows.filter((row) => !row.classList.contains('d-none')).forEach((row) => {
                    const checkbox = row.querySelector('.report-expense-checkbox');
                    checkbox.checked = this.checked;
                    checkbox.dispatchEvent(new Event('change'));
                });
            });
            document.getElementById('resetReportExpenseFilters').addEventListener('click', () => {
                search.value = '';
                from.value = '';
                to.value = '';
                applyFilters();
            });
        }

        const previousProjectId = @json(old('report_project_id'));
        if (previousProjectId) {
            const previousTrigger = Array.from(document.querySelectorAll('[data-project-id]'))
                .find((button) => button.dataset.projectId === String(previousProjectId));
            if (previousTrigger) {
                setProject(previousTrigger);
                bootstrap.Modal.getOrCreateInstance(modal).show();
            }
        }
    })();
</script>
@endpush
