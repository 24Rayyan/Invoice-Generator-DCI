@extends('layouts.app')

@section('content')
@php
    $activeTab = $source === 'active';
    $tagClass = function ($action) {
        return match (true) {
            in_array($action, ['CREATE', 'LOGIN'], true) => 'ok',
            in_array($action, ['UPDATE', 'ATTACH', 'EXPORT', 'DOWNLOAD'], true) => 'info',
            in_array($action, ['DELETE', 'LOGIN_FAILED', 'DETACH'], true) => 'bad',
            default => '',
        };
    };
    $hasFilter = request()->filled('search') || request()->filled('action') || request()->filled('module')
        || request()->filled('user_id') || request()->filled('date_from') || request()->filled('date_to');
    $resetParams = $source === 'archive' ? ['source' => 'archive', 'period' => $period] : [];
@endphp

<div class="al-page">

    {{-- Header --}}
    <header class="al-head">
        <div>
            <h1>Log Aktivitas Pengguna</h1>
            <p>Jejak audit tindakan pengguna di sistem: siapa, di modul mana, dan kapan.</p>
        </div>
        <a href="{{ url()->full() }}" class="btn btn-sm btn-outline-secondary">
            <i class="fa-solid fa-rotate-right" aria-hidden="true"></i> Muat ulang
        </a>
    </header>

    {{-- Sumber data: log aktif atau arsip per periode --}}
    <nav class="al-tabs" aria-label="Sumber log">
        <a class="al-tab {{ $activeTab ? 'is-active' : '' }}" href="{{ route('activity_logs.index') }}" @if($activeTab) aria-current="page" @endif>Log aktif</a>
        @foreach($archivePeriods as $archivePeriod)
            @php $isCurrent = $source === 'archive' && $period === $archivePeriod; @endphp
            <a class="al-tab {{ $isCurrent ? 'is-active' : '' }}" href="{{ route('activity_logs.index', ['source' => 'archive', 'period' => $archivePeriod]) }}" @if($isCurrent) aria-current="page" @endif>Arsip {{ $archivePeriod }}</a>
        @endforeach
    </nav>

    {{-- Ringkasan: satu strip, bukan kartu --}}
    <section class="al-stats" aria-label="Ringkasan aktivitas">
        <div class="al-stat">
            <span class="al-stat-label">Hari ini</span>
            <span class="al-stat-value">{{ number_format($stats['today'], 0, ',', '.') }}</span>
        </div>
        <div class="al-stat">
            <span class="al-stat-label">Create</span>
            <span class="al-stat-value">{{ number_format($stats['create'], 0, ',', '.') }}</span>
        </div>
        <div class="al-stat">
            <span class="al-stat-label">Update</span>
            <span class="al-stat-value">{{ number_format($stats['update'], 0, ',', '.') }}</span>
        </div>
    </section>

    {{-- Filter --}}
    <section class="al-filter" aria-label="Filter aktivitas">
        <form method="GET" action="{{ route('activity_logs.index') }}" class="row g-2 align-items-end">
            <input type="hidden" name="source" value="{{ $source }}">
            @if($source === 'archive')
                <div class="col-12 col-md-6 col-xl-2">
                    <label for="archive-period" class="form-label">Periode arsip</label>
                    <select id="archive-period" name="period" class="form-select form-select-sm">
                        @foreach($archivePeriods as $archivePeriod)
                            <option value="{{ $archivePeriod }}" @selected($period === $archivePeriod)>{{ $archivePeriod }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-12 {{ $source === 'archive' ? 'col-xl-4' : 'col-xl-3' }}">
                <label for="activity-search" class="form-label">Pencarian</label>
                <input id="activity-search" type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Cari deskripsi aktivitas">
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <label for="al-action" class="form-label">Aksi</label>
                <select id="al-action" name="action" class="form-select form-select-sm smooth-dropdown" data-placeholder="Semua Aksi">
                    <option value="">Semua Aksi</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>{{ $action }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <label for="al-module" class="form-label">Modul</label>
                <select id="al-module" name="module" class="form-select form-select-sm smooth-dropdown" data-placeholder="Semua Modul">
                    <option value="">Semua Modul</option>
                    @foreach($modules as $module)
                        <option value="{{ $module }}" {{ request('module') === $module ? 'selected' : '' }}>{{ $module }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-4 col-xl-2">
                <label for="al-user" class="form-label">Pengguna</label>
                <select id="al-user" name="user_id" class="form-select form-select-sm smooth-dropdown" data-placeholder="Semua Pengguna">
                    <option value="">Semua Pengguna</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ (string) request('user_id') === (string) $user->id ? 'selected' : '' }}>
                            {{ $user->username ?? $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-xl-2">
                <label for="date_from" class="form-label">Dari tanggal</label>
                <input id="date_from" type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-xl-2">
                <label for="date_to" class="form-label">Sampai tanggal</label>
                <input id="date_to" type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
            </div>
            <div class="col-12 col-xl-auto d-flex gap-2 ms-xl-auto">
                <button type="submit" class="btn btn-sm btn-primary flex-fill">Terapkan filter</button>
                <a href="{{ route('activity_logs.index', $resetParams) }}" class="btn btn-sm btn-outline-secondary">Atur ulang</a>
            </div>
        </form>
    </section>

    {{-- Riwayat --}}
    <section class="al-panel" aria-labelledby="alHistory">
        <div class="al-panel-head">
            <h2 id="alHistory">Riwayat aktivitas</h2>
            <span class="meta">{{ number_format($logs->total(), 0, ',', '.') }} aktivitas{{ $hasFilter ? ' sesuai filter' : '' }} · terbaru di atas · klik baris untuk detail</span>
        </div>

        <div class="table-responsive">
            <table class="al-table">
                <thead>
                    <tr>
                        <th style="width: 150px;">Waktu</th>
                        <th style="width: 190px;">Pengguna</th>
                        <th style="width: 210px;">Modul</th>
                        <th>Aktivitas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        @php $who = $log->user_name ?? $log->user?->username ?? 'Sistem'; @endphp
                        <tr class="al-row" tabindex="0" role="button"
                            aria-label="Lihat detail aktivitas: {{ \Illuminate\Support\Str::limit($log->description, 80) }}"
                            data-id="{{ $log->id }}"
                            data-detail-url="{{ $source === 'archive' ? route('activity_logs.archives.show', [$period, $log->id]) : route('activity_logs.show', $log->id) }}">
                            <td class="al-time">
                                <span class="d-block">{{ optional($log->created_at)->format('d/m/Y') }}</span>
                                <span class="al-time-h">{{ optional($log->created_at)->format('H:i') }}</span>
                            </td>
                            <td>
                                <span class="al-user">
                                    <span class="al-avatar" aria-hidden="true">{{ strtoupper(substr($who, 0, 1)) }}</span>
                                    <span class="al-user-name">{{ $who }}</span>
                                </span>
                            </td>
                            <td class="al-module">{{ $log->module_name }}</td>
                            <td>
                                @if($log->action)
                                    <span class="al-tag {{ $tagClass($log->action) }}">{{ $log->action }}</span>
                                @endif
                                <span class="al-desc">{{ $log->description }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="al-empty">
                                    @if($hasFilter)
                                        <strong>Tidak ada aktivitas yang cocok</strong>
                                        Ubah kata kunci, rentang tanggal, atau filter lain.
                                    @else
                                        <strong>Belum ada aktivitas tercatat</strong>
                                        Tindakan pengguna akan muncul di sini setelah terjadi.
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="al-panel-foot">
            <span>Menampilkan {{ number_format($logs->count(), 0, ',', '.') }} aktivitas di halaman ini dari total {{ number_format($logs->total(), 0, ',', '.') }}</span>
            @if($logs->hasPages())
                <div class="al-pagination">{{ $logs->links('pagination::bootstrap-5') }}</div>
            @endif
        </div>
    </section>

</div>
@endsection

@push('modals')
<div class="modal fade" id="logDetailModal" tabindex="-1" aria-labelledby="logDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="logDetailModalLabel">Detail log aktivitas</h5>
                    <p class="small text-muted mb-0" id="log_detail_meta">-</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <dl class="al-dl">
                    <div><dt>Aksi</dt><dd id="log_detail_action">-</dd></div>
                    <div><dt>Modul</dt><dd id="log_detail_module">-</dd></div>
                    <div><dt>Waktu</dt><dd id="log_detail_time">-</dd></div>
                </dl>
                <div class="mb-3">
                    <div class="al-dl-label">Deskripsi</div>
                    <div class="al-box" id="log_detail_description">-</div>
                </div>
                <div>
                    <div class="al-dl-label">Properti (perubahan data)</div>
                    <pre class="al-pre" id="log_detail_properties" tabindex="0">-</pre>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endpush

@push('styles')
<style>
    /* Token --erp-* dari erp.css. Satu aksen biru; warna status hanya untuk makna aksi. */
    .al-page { color: var(--erp-text); }

    .al-head { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: .9rem 1.5rem; margin-bottom: 1rem; }
    .al-head h1 { font-size: clamp(1.3rem, 1.1rem + .8vw, 1.65rem); font-weight: 700; letter-spacing: -.03em; margin: 0 0 .2rem; }
    .al-head p { margin: 0; font-size: .86rem; color: var(--erp-text-3); max-width: 62ch; }

    /* Tab sumber: garis bawah, sama dengan .nav-tabs global */
    .al-tabs { display: flex; flex-wrap: nowrap; gap: .25rem; margin-bottom: 1rem; border-bottom: 1px solid var(--erp-line); overflow-x: auto; scrollbar-width: thin; }
    .al-tab { flex: 0 0 auto; padding: .6rem .9rem; font-size: .86rem; font-weight: 600; color: var(--erp-text-3); text-decoration: none; border-bottom: 2px solid transparent; margin-bottom: -1px; white-space: nowrap; min-height: 44px; display: inline-flex; align-items: center; transition: color .15s ease, border-color .15s ease; }
    .al-tab:hover { color: var(--erp-ink); }
    .al-tab.is-active { color: var(--erp-primary); border-bottom-color: var(--erp-primary); }

    .al-stats { display: grid; grid-template-columns: repeat(3, 1fr); background: var(--erp-surface); border: 1px solid var(--erp-line); border-radius: var(--erp-radius); margin-bottom: 1rem; overflow: hidden; }
    .al-stat { padding: .85rem 1.15rem; display: flex; flex-direction: column; gap: .25rem; border-left: 1px solid var(--erp-line); min-width: 0; }
    .al-stat:first-child { border-left: 0; }
    .al-stat-label { font-size: .76rem; font-weight: 600; color: var(--erp-text-3); }
    .al-stat-value { font-size: 1.45rem; font-weight: 700; letter-spacing: -.02em; color: var(--erp-ink); font-variant-numeric: tabular-nums; line-height: 1.2; }
    .al-stat:first-child .al-stat-value { color: var(--erp-primary-hover); }

    .al-filter { background: var(--erp-surface); border: 1px solid var(--erp-line); border-radius: var(--erp-radius); padding: .9rem 1rem; margin-bottom: 1rem; }
    .al-filter .form-label { font-size: .74rem; font-weight: 600; color: var(--erp-text-2); margin-bottom: .25rem; }

    .al-panel { background: var(--erp-surface); border: 1px solid var(--erp-line); border-radius: var(--erp-radius); overflow: hidden; }
    .al-panel-head { display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: .25rem 1rem; padding: .85rem 1.1rem; border-bottom: 1px solid var(--erp-line); }
    .al-panel-head h2 { font-size: .95rem; font-weight: 700; margin: 0; }
    .al-panel-head .meta { font-size: .8rem; color: var(--erp-text-3); }
    .al-panel-foot { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .6rem 1rem; padding: .65rem 1.1rem; border-top: 1px solid var(--erp-line); background: var(--erp-surface-2); font-size: .82rem; color: var(--erp-text-3); }
    .al-pagination .pagination { margin: 0; }
    .al-pagination nav > div:first-child { display: none; }

    .al-table { width: 100%; min-width: 720px; border-collapse: collapse; font-size: .85rem; }
    .al-table thead th { background: var(--erp-surface-2); color: var(--erp-text-3); font-size: .72rem; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; padding: .65rem 1rem; text-align: left; white-space: nowrap; border-bottom: 1px solid var(--erp-line-strong); }
    .al-table tbody td { padding: .7rem 1rem; border-bottom: 1px solid var(--erp-line); vertical-align: top; }
    .al-row { cursor: pointer; transition: background-color .12s ease; }
    .al-row:hover { background: #f6f8fc; }
    .al-row:active { background: #eef3ff; }
    .al-row:focus-visible { outline: 2px solid var(--erp-primary); outline-offset: -2px; background: var(--erp-primary-soft); }
    .al-row:last-child td { border-bottom: 0; }

    .al-time { font-variant-numeric: tabular-nums; white-space: nowrap; color: var(--erp-text-2); }
    .al-time-h { color: var(--erp-text-3); font-size: .78rem; }
    .al-user { display: inline-flex; align-items: center; gap: .6rem; max-width: 100%; }
    .al-avatar { width: 26px; height: 26px; border-radius: 50%; background: var(--erp-primary-soft); color: var(--erp-primary-hover); display: grid; place-items: center; font-size: .72rem; font-weight: 700; flex-shrink: 0; }
    .al-user-name { font-weight: 600; color: var(--erp-ink); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .al-module { color: var(--erp-text-2); }
    .al-desc { color: var(--erp-text); overflow-wrap: anywhere; }

    .al-tag { display: inline-block; margin-right: .5rem; padding: .08rem .45rem; border-radius: 5px; font-size: .7rem; font-weight: 700; letter-spacing: .02em; background: #eef1f6; color: var(--erp-text-2); vertical-align: 1px; }
    .al-tag.ok { background: var(--erp-success-soft); color: var(--erp-success); }
    .al-tag.info { background: var(--erp-primary-soft); color: var(--erp-primary-hover); }
    .al-tag.bad { background: var(--erp-danger-soft); color: var(--erp-danger); }

    .al-empty { padding: 2.25rem 1rem; text-align: center; color: var(--erp-text-3); font-size: .86rem; }
    .al-empty strong { display: block; color: var(--erp-ink); font-size: .92rem; margin-bottom: .2rem; }

    .al-dl { display: grid; grid-template-columns: repeat(3, 1fr); gap: .75rem 1rem; margin: 0 0 1rem; }
    .al-dl dt, .al-dl-label { font-size: .74rem; font-weight: 600; color: var(--erp-text-3); margin-bottom: .2rem; }
    .al-dl dd { margin: 0; font-weight: 600; color: var(--erp-ink); font-size: .88rem; overflow-wrap: anywhere; }
    .al-box { padding: .7rem .85rem; background: var(--erp-surface-2); border: 1px solid var(--erp-line); border-radius: var(--erp-radius-sm); font-size: .86rem; overflow-wrap: anywhere; }
    .al-pre { margin: 0; padding: .8rem .9rem; max-height: 320px; overflow: auto; background: var(--erp-surface-2); border: 1px solid var(--erp-line); border-radius: var(--erp-radius-sm); color: var(--erp-ink); font-size: .78rem; line-height: 1.5; white-space: pre-wrap; overflow-wrap: anywhere; }

    @media (max-width: 767.98px) {
        .al-head .btn { width: 100%; }
        .al-panel-head, .al-panel-foot { padding-left: .9rem; padding-right: .9rem; }
        .al-dl { grid-template-columns: 1fr; }
    }
    @media (max-width: 575.98px) {
        .al-stats { grid-template-columns: 1fr; }
        .al-stat { border-left: 0; border-top: 1px solid var(--erp-line); flex-direction: row; align-items: baseline; justify-content: space-between; }
        .al-stat:first-child { border-top: 0; }
    }
</style>
@endpush

@push('scripts')
<script>
    $(document).ready(function () {
        function openDetail(row) {
            const detailUrl = $(row).data('detail-url');

            $.get(detailUrl)
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
        }

        $('.al-row').on('click', function () { openDetail(this); });
        $('.al-row').on('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openDetail(this);
            }
        });
    });
</script>
@endpush
