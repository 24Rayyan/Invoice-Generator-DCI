@extends('layouts.app')

@section('content')
@php
    $moduleIcons = [
        'Autentikasi'          => 'fa-key',
        'User Management'      => 'fa-user-gear',
        'Invoicing & PO'       => 'fa-file-invoice-dollar',
        'Buku Kas & Transaksi' => 'fa-money-bill-transfer',
        'Pengaturan Sistem'    => 'fa-sliders',
    ];
@endphp

<div class="al-page">

    <!-- Header -->
    <div class="al-head">
        <div>
            <h1 class="al-title">Log Aktivitas Pengguna</h1>
            <p class="al-subtitle">Pantau aktivitas yang dilakukan pengguna di sistem.</p>
        </div>
        <a href="{{ url()->full() }}" class="al-reload">
            <i class="fa-solid fa-rotate-right"></i> Muat ulang
        </a>
    </div>

    <!-- Statistik -->
    <div class="al-stats">
        <div class="al-stat">
            <div class="al-stat-top">
                <span class="al-stat-label">Hari Ini</span>
                <span class="al-stat-icon is-white"><i class="fa-regular fa-clock"></i></span>
            </div>
            <div class="al-stat-value">{{ number_format($stats['today'], 0, ',', '.') }}</div>
        </div>
        <div class="al-stat">
            <div class="al-stat-top">
                <span class="al-stat-label">Create</span>
                <span class="al-stat-icon"><i class="fa-solid fa-plus"></i></span>
            </div>
            <div class="al-stat-value">{{ number_format($stats['create'], 0, ',', '.') }}</div>
        </div>
        <div class="al-stat">
            <div class="al-stat-top">
                <span class="al-stat-label">Update</span>
                <span class="al-stat-icon"><i class="fa-solid fa-pen-to-square"></i></span>
            </div>
            <div class="al-stat-value">{{ number_format($stats['update'], 0, ',', '.') }}</div>
        </div>
    </div>

    <!-- Filter -->
    <div class="al-card al-filter">
        <div class="al-filter-head">
            <h2 class="al-card-title">Filter aktivitas</h2>
            <a href="{{ route('activity_logs.index') }}" class="al-reset">Atur ulang</a>
        </div>

        <form method="GET" action="{{ route('activity_logs.index') }}" class="al-filter-grid">
            <div class="al-field al-field-search">
                <label>Pencarian</label>
                <input type="text" name="search" value="{{ request('search') }}" class="al-input" placeholder=" Cari aktivitas...">
            </div>
            <div class="al-field">
                <label>Aksi</label>
                <select name="action" class="al-select">
                    <option value="">Semua Aksi</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>{{ $action }}</option>
                    @endforeach
                </select>
            </div>
            <div class="al-field">
                <label>Modul</label>
                <select name="module" class="al-select">
                    <option value="">Semua Modul</option>
                    @foreach($modules as $module)
                        <option value="{{ $module }}" {{ request('module') === $module ? 'selected' : '' }}>{{ $module }}</option>
                    @endforeach
                </select>
            </div>
            <div class="al-field">
                <label>Pengguna</label>
                <select name="user_id" class="al-select">
                    <option value="">Semua Pengguna</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ (string) request('user_id') === (string) $user->id ? 'selected' : '' }}>
                            {{ $user->username ?? $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="al-field">
                <label>Dari</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="al-input al-date">
            </div>
            <div class="al-field al-field-submit">
                <button type="submit" class="al-btn">
                    <i class="fa-solid fa-filter"></i> Terapkan
                </button>
            </div>
        </form>
    </div>

    <!-- Riwayat -->
    <div class="al-card al-table-card">
        <div class="al-table-head">
            <div class="al-table-title">
                <h2>Riwayat aktivitas</h2>
                <span class="al-pill">{{ number_format($logs->total(), 0, ',', '.') }} aktivitas</span>
            </div>
            <span class="al-hint">
                <i class="fa-solid fa-arrow-down-wide-short"></i> Log lama akan ditampilkan otomatis.
            </span>
        </div>

        <div class="al-table-scroll">
            <table class="al-table">
                <thead>
                    <tr>
                        <th>Pengguna</th>
                        <th>Modul</th>
                        <th>Deskripsi</th>
                        <th class="al-th-right">Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr class="al-row" data-id="{{ $log->id }}" title="Klik untuk melihat detail">
                            <td>
                                <div class="al-user">
                                    <span class="al-avatar">{{ strtoupper(substr($log->user_name ?? $log->user?->username ?? 'S', 0, 1)) }}</span>
                                    <span class="al-user-name">{{ $log->user_name ?? $log->user?->username ?? 'Sistem' }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="al-module">
                                    <i class="fa-solid {{ $moduleIcons[$log->module_name] ?? 'fa-cube' }}"></i>
                                    <span>{{ $log->module_name }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="al-desc">{{ $log->description }}</span>
                            </td>
                            <td class="al-time">
                                {{ optional($log->created_at)->format('d/m/Y') }}
                                <span class="al-time-sep">•</span>
                                {{ optional($log->created_at)->format('H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="al-empty">
                                    <i class="fa-solid fa-clipboard-list"></i>
                                    <span>Belum ada aktivitas yang tercatat.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="al-table-foot">
            Menampilkan {{ number_format($logs->count(), 0, ',', '.') }} aktivitas
        </div>

        @if($logs->hasPages())
            <div class="al-pagination">
                {{ $logs->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>

    <!-- Modal Detail Log -->
    <div class="modal fade" id="logDetailModal" tabindex="-1" aria-labelledby="logDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 rounded-4 shadow-lg">
                <div class="modal-header border-bottom p-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-primary bg-opacity-10 d-flex align-items-center justify-content-center text-primary" style="width: 38px; height: 38px;">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </div>
                        <div>
                            <h5 class="modal-title h6 fw-bold text-dark m-0" id="logDetailModalLabel">Detail Log Aktivitas</h5>
                            <p class="small text-muted mb-0" id="log_detail_meta">-</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-uppercase fw-bold text-secondary small mb-1" style="font-size: 0.68rem;">Deskripsi</label>
                        <div class="p-3 bg-light rounded-3 small" id="log_detail_description">-</div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label text-uppercase fw-bold text-secondary small mb-1" style="font-size: 0.68rem;">Aksi</label>
                            <div class="small fw-bold" id="log_detail_action">-</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-uppercase fw-bold text-secondary small mb-1" style="font-size: 0.68rem;">Modul</label>
                            <div class="small fw-bold" id="log_detail_module">-</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-uppercase fw-bold text-secondary small mb-1" style="font-size: 0.68rem;">Waktu</label>
                            <div class="small fw-bold" id="log_detail_time">-</div>
                        </div>
                    </div>

                    <div>
                        <label class="form-label text-uppercase fw-bold text-secondary small mb-1" style="font-size: 0.68rem;">Properties (Perubahan Data)</label>
                        <pre class="bg-dark text-success rounded-3 p-3 mb-0" style="font-size: 0.75rem; max-height: 320px; overflow: auto;" id="log_detail_properties">-</pre>
                    </div>
                </div>
                <div class="modal-footer border-top p-3">
                    <button type="button" class="btn btn-light rounded-pill px-4 text-sm font-medium" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('styles')
<style>
    .al-page {
        --al-border: #e5e7eb;
        --al-muted: #64748b;
        --al-dark: #0f172a;
        --al-blue: #2563eb;
        --al-blue-soft: #eff6ff;
    }

    .al-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        margin-bottom: 22px;
    }

    .al-title {
        font-size: 1.45rem;
        font-weight: 800;
        color: var(--al-dark);
        margin: 0;
        letter-spacing: -0.4px;
    }

    .al-subtitle {
        color: var(--al-muted);
        font-size: 0.85rem;
        margin: 4px 0 0;
    }

    .al-reload {
        color: var(--al-blue);
        font-weight: 600;
        font-size: 0.82rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        white-space: nowrap;
        padding-top: 4px;
    }

    .al-reload:hover { text-decoration: underline; }

    .al-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 20px;
    }

    .al-stat {
        background: #0D1C42;
        border: 1px solid var(--al-border);
        border-radius: 14px;
        padding: 16px 18px;
    }

    .al-stat-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }

    .al-stat-label {
        color: #ffffff;
        font-size: 0.82rem;
        font-weight: 600;
    }

    .al-stat-icon {
        width: 32px;
        height: 32px;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.82rem;
        background: #f1f5f9;
        color: #94a3b8;
    }

    .al-stat-icon.is-blue {
        background: var(--al-blue-soft);
        color: var(--al-blue);
    }

    .al-stat-value {
        font-size: 1.9rem;
        font-weight: 800;
        color: #ffffff;
        line-height: 1.1;
        margin-top: 10px;
    }

    .al-stat-value.is-blue { color: var(--al-blue); }

    .al-card {
        background: #fff;
        border: 1px solid var(--al-border);
        border-radius: 14px;
    }

    .al-filter {
        padding: 18px;
        margin-bottom: 20px;
    }

    .al-filter-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }

    .al-card-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--al-dark);
        margin: 0;
    }

    .al-reset {
        color: var(--al-blue);
        font-size: 0.78rem;
        font-weight: 600;
        text-decoration: none;
    }

    .al-reset:hover { text-decoration: underline; }

    .al-filter-grid {
        display: grid;
        grid-template-columns: 1.6fr 1fr 1fr 1fr 1fr auto;
        gap: 14px;
        align-items: end;
    }

    .al-field label {
        display: block;
        font-size: 0.72rem;
        font-weight: 600;
        color: var(--al-muted);
        margin-bottom: 6px;
    }

    .al-input,
    .al-select {
        width: 100%;
        border: 1px solid var(--al-border);
        border-radius: 9px;
        padding: 9px 12px;
        font-size: 0.82rem;
        color: var(--al-dark);
        background-color: #fff;
        outline: none;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .al-input::placeholder { color: #94a3b8; }

    .al-input:focus,
    .al-select:focus {
        border-color: var(--al-blue);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    .al-btn {
        border: 0;
        background: var(--al-blue);
        color: #fff;
        border-radius: 9px;
        padding: 9px 20px;
        font-size: 0.82rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
        transition: background 0.15s ease;
    }

    .al-btn:hover { background: #1d4ed8; }

    .al-table-card { overflow: hidden; }

    .al-table-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 16px 18px;
        border-bottom: 1px solid var(--al-border);
        flex-wrap: wrap;
    }

    .al-table-title {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .al-table-title h2 {
        font-size: 0.95rem;
        font-weight: 700;
        margin: 0;
        color: var(--al-dark);
    }

    .al-pill {
        background: #f1f5f9;
        color: #475569;
        font-size: 0.72rem;
        font-weight: 600;
        padding: 3px 10px;
        border-radius: 999px;
    }

    .al-hint {
        color: var(--al-muted);
        font-size: 0.75rem;
        display: inline-flex;
        align-items: center;
        gap: 7px;
    }

    .al-table-scroll { overflow-x: auto; }

    .al-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 640px;
    }

    .al-table thead th {
        background: #f8fafc;
        color: var(--al-muted);
        font-size: 0.72rem;
        font-weight: 600;
        padding: 12px 18px;
        text-align: left;
        border-bottom: 1px solid var(--al-border);
        white-space: nowrap;
    }

    .al-table thead th.al-th-right { text-align: right; }

    .al-table tbody td {
        padding: 15px 18px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
        font-size: 0.84rem;
        color: var(--al-dark);
    }

    .al-row { cursor: pointer; transition: background 0.15s ease; }
    .al-row:hover { background: #f8fafc; }
    .al-row:last-child td { border-bottom: 0; }

    .al-user {
        display: flex;
        align-items: center;
        gap: 11px;
    }

    .al-avatar {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: #f1f5f9;
        color: #475569;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        flex-shrink: 0;
    }

    .al-user-name {
        font-weight: 500;
        color: var(--al-dark);
    }

    .al-module {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #334155;
        font-weight: 500;
    }

    .al-module i {
        color: #94a3b8;
        width: 16px;
        text-align: center;
    }

    .al-desc { color: #334155; }

    .al-time {
        text-align: right;
        color: var(--al-muted);
        font-size: 0.78rem;
        white-space: nowrap;
    }

    .al-time-sep { color: #cbd5e1; margin: 0 2px; }

    .al-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        padding: 48px 0;
        color: var(--al-muted);
    }

    .al-empty i { font-size: 1.6rem; color: #cbd5e1; }

    .al-table-foot {
        padding: 14px 18px;
        color: var(--al-muted);
        font-size: 0.76rem;
        border-top: 1px solid #f1f5f9;
    }

    .al-pagination { padding: 12px 18px; border-top: 1px solid var(--al-border); }
    .al-pagination .pagination { margin: 0; }

    @media (max-width: 991.98px) {
        .al-filter-grid { grid-template-columns: 1fr 1fr; }
        .al-field-search { grid-column: 1 / -1; }
        .al-field-submit { grid-column: 1 / -1; }
        .al-field-submit .al-btn { width: 100%; justify-content: center; }
    }

    @media (max-width: 575.98px) {
        .al-stats { grid-template-columns: 1fr; }
        .al-filter-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

@push('scripts')
<script>
    const activityLogBaseUrl = "{{ url('activity-logs') }}";

    $(document).ready(function () {
        $('.al-row').on('click', function () {
            const id = $(this).data('id');

            $.get(activityLogBaseUrl + '/' + id)
                .done(function (data) {
                    $('#log_detail_meta').text('Log #' + data.id);
                    $('#log_detail_description').text(data.description ?? '-');
                    $('#log_detail_action').text(data.action ?? '-');
                    $('#log_detail_module').text(data.module_name ?? '-');
                    $('#log_detail_time').text(data.created_at ?? '-');
                    $('#log_detail_properties').text(data.properties ? JSON.stringify(data.properties, null, 2) : 'Tidak ada data perubahan.');

                    const modalEl = document.getElementById('logDetailModal');
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                })
                .fail(function () {
                    Swal.fire('Gagal', 'Tidak dapat memuat detail log aktivitas.', 'error');
                });
        });
    });
</script>
@endpush
