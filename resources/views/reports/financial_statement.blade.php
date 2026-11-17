@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/reports.css') }}?v={{ @filemtime(public_path('css/reports.css')) }}">
@endpush

@section('content')
@php
    $currency = $setting->currency_symbol ?? 'Rp';
    $exportFilters = [
        'start_date' => $startDate,
        'end_date' => $endDate,
        'category_id' => $categoryId,
        'type' => $type,
    ];
    $inv = $reportData['invoicing_summary'];
    $ledgerEntries = collect($reportData['ledger_entries'])->reverse()->values();
    $ledgerCount = $ledgerEntries->count();
    $netPositive = $reportData['net_profit'] >= 0;
@endphp

<div class="rpt">
    {{-- Header: judul, fungsi laporan, aksi unduh --}}
    <header class="rpt-head">
        <div>
            <h1>Laporan Keuangan</h1>
            <p class="rpt-desc">Arus kas, penagihan invoice, dan klasifikasi fiskal per akun untuk periode yang dipilih.</p>
        </div>
        <div class="rpt-head-actions">
            <a data-download href="{{ route('reports.financial_statement.pdf', $exportFilters) }}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">
                <i class="fa-solid fa-file-pdf" aria-hidden="true"></i> Unduh PDF
            </a>
            <a data-download href="{{ route('reports.financial_statement.excel', $exportFilters) }}" class="btn btn-sm btn-primary">
                <i class="fa-solid fa-file-excel" aria-hidden="true"></i> Ekspor Excel
            </a>
        </div>
    </header>

    {{-- Filter periode: tanggal di depan, filter sekunder sesudahnya, pilihan cepat di bawah --}}
    <section class="rpt-filter" aria-label="Filter laporan">
        <form method="GET" action="{{ route('reports.financial_statement') }}" id="filterForm" class="row g-2 align-items-end">
            <div class="col-6 col-lg-2">
                <label for="start_date" class="form-label">Tanggal mulai</label>
                <input type="date" name="start_date" id="start_date" class="form-control form-control-sm" value="{{ $startDate }}" required>
            </div>
            <div class="col-6 col-lg-2">
                <label for="end_date" class="form-label">Tanggal selesai</label>
                <input type="date" name="end_date" id="end_date" class="form-control form-control-sm" value="{{ $endDate }}" required>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <label for="reportCategory" class="form-label">Kategori akun (COA)</label>
                <select id="reportCategory" name="category_id" class="form-select form-select-sm smooth-dropdown" data-placeholder="Semua Kategori">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>
                            [{{ $cat->code }}] {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <label for="reportType" class="form-label">Tipe transaksi</label>
                <select id="reportType" name="type" class="form-select form-select-sm smooth-dropdown" data-placeholder="Semua (Income & Expense)">
                    <option value="">Semua (Income & Expense)</option>
                    <option value="income" {{ $type == 'income' ? 'selected' : '' }}>Pemasukan (Income)</option>
                    <option value="expense" {{ $type == 'expense' ? 'selected' : '' }}>Pengeluaran (Expense)</option>
                </select>
            </div>
            <div class="col-12 col-lg-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">Terapkan filter</button>
            </div>

            <div class="col-12">
                <div class="rpt-filter-foot">
                    <span class="lbl">Pilihan cepat</span>
                    <button type="button" class="rpt-chip" onclick="setPreset('this_month')">Bulan ini</button>
                    <button type="button" class="rpt-chip" onclick="setPreset('last_month')">Bulan lalu</button>
                    <button type="button" class="rpt-chip" onclick="setPreset('this_year')">Tahun berjalan</button>
                    <button type="button" class="rpt-chip" onclick="setPreset('last_30_days')">30 hari terakhir</button>
                    <a href="{{ route('reports.financial_statement') }}" class="rpt-reset">Atur ulang filter</a>
                </div>
            </div>
        </form>
    </section>

    {{-- Periode aktif --}}
    <div class="rpt-context">
        <span>Periode: <strong>{{ $reportData['formatted_start_date'] }}</strong> s/d <strong>{{ $reportData['formatted_end_date'] }}</strong></span>
        <span class="rpt-context-end">{{ $setting->company_name ?? 'Nama Perusahaan' }}</span>
    </div>

    {{-- Ringkasan: laba bersih sebagai fokus, tiga angka pendukung. Satuan di dalam nilai. --}}
    <section class="rpt-summary" aria-label="Ringkasan keuangan">
        <div class="rpt-fig lead">
            <p class="rpt-fig-label">{{ $netPositive ? 'Laba bersih' : 'Rugi bersih' }}</p>
            <div class="rpt-fig-value {{ $netPositive ? '' : 'neg' }}">{{ $currency }} {{ number_format($reportData['net_profit'], 0, ',', '.') }}</div>
            <p class="rpt-fig-note">Pemasukan dikurangi pengeluaran.</p>
        </div>
        <div class="rpt-fig">
            <p class="rpt-fig-label">Total pemasukan</p>
            <div class="rpt-fig-value">{{ $currency }} {{ number_format($reportData['total_income'], 0, ',', '.') }}</div>
            <p class="rpt-fig-note">
                Invoice {{ $currency }} {{ number_format($reportData['paid_invoices_income'], 0, ',', '.') }}<br>
                Lainnya {{ $currency }} {{ number_format($reportData['other_income'], 0, ',', '.') }}
            </p>
        </div>
        <div class="rpt-fig">
            <p class="rpt-fig-label">Total pengeluaran</p>
            <div class="rpt-fig-value">{{ $currency }} {{ number_format($reportData['total_expense'], 0, ',', '.') }}</div>
            <p class="rpt-fig-note">
                Deductible {{ $currency }} {{ number_format($reportData['deductible_expense'], 0, ',', '.') }}<br>
                Non-deductible {{ $currency }} {{ number_format($reportData['non_deductible_expense'], 0, ',', '.') }}
            </p>
        </div>
        <div class="rpt-fig">
            <p class="rpt-fig-label">Piutang aktif</p>
            <div class="rpt-fig-value">{{ $currency }} {{ number_format($inv['unpaid_amount'], 0, ',', '.') }}</div>
            <p class="rpt-fig-note">{{ $inv['sent_count'] }} invoice terkirim, belum dibayar.</p>
        </div>
    </section>

    {{-- Penagihan invoice --}}
    <section class="rpt-panel" aria-labelledby="rptInvoicing">
        <div class="rpt-panel-head">
            <h2 id="rptInvoicing">Penagihan invoice</h2>
            <span class="meta">Tingkat koleksi {{ $inv['collection_rate'] }}%</span>
        </div>
        <div class="table-responsive">
            <table class="rpt-table" style="min-width: 640px;">
                <thead>
                    <tr>
                        <th>Status tagihan</th>
                        <th class="c">Dokumen</th>
                        <th class="r">Nominal tagihan</th>
                        <th style="width: 28%;">Kolektibilitas</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><span class="rpt-tag ok">PAID</span> Invoice lunas</td>
                        <td class="c num">{{ $inv['paid_count'] }}</td>
                        <td class="r pos">{{ $currency }} {{ number_format($inv['paid_amount'], 0, ',', '.') }}</td>
                        <td>
                            <div class="rpt-meter">
                                <div class="rpt-meter-track"><div class="rpt-meter-fill pos" style="width: {{ min(100, max(0, $inv['collection_rate'])) }}%;"></div></div>
                                <span class="rpt-meter-val">{{ $inv['collection_rate'] }}%</span>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td><span class="rpt-tag info">SENT</span> Invoice terkirim, menunggu bayar</td>
                        <td class="c num">{{ $inv['sent_count'] }}</td>
                        <td class="r">{{ $currency }} {{ number_format($inv['sent_amount'], 0, ',', '.') }}</td>
                        <td class="dim">Piutang lancar</td>
                    </tr>
                    <tr>
                        <td><span class="rpt-tag">DRAFT</span> Invoice konsep</td>
                        <td class="c num">{{ $inv['draft_count'] }}</td>
                        <td class="r dim">{{ $currency }} {{ number_format($inv['draft_amount'], 0, ',', '.') }}</td>
                        <td class="dim">Belum diterbitkan</td>
                    </tr>
                    <tr class="total">
                        <td>Total penerbitan invoice</td>
                        <td class="c num">{{ $inv['total_count'] }}</td>
                        <td class="r">{{ $currency }} {{ number_format($inv['total_amount'], 0, ',', '.') }}</td>
                        <td>100%</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    {{-- Buku kas kronologis --}}
    <section class="rpt-panel" aria-labelledby="rptLedger">
        <div class="rpt-panel-head">
            <h2 id="rptLedger">Buku kas kronologis</h2>
            <span class="meta">{{ $ledgerCount }} transaksi{{ $ledgerCount > 0 ? ' · saldo akhir ' . $currency . ' ' . number_format($reportData['net_profit'], 0, ',', '.') : '' }}</span>
        </div>
        <div class="table-responsive">
            <table id="ledgerTable" class="rpt-table" style="min-width: 900px;">
                <thead>
                    <tr>
                        <th id="ledgerDateHeader" style="width: 104px;" @if($ledgerCount > 0) aria-sort="descending" @endif>
                            @if($ledgerCount > 0)
                                <button type="button" id="ledgerDateSort" class="rpt-ledger-sort" aria-label="Urutkan tanggal, terlama ke terbaru">
                                    <span>Tanggal</span>
                                    <i class="fa-solid fa-arrow-down" aria-hidden="true"></i>
                                </button>
                            @else
                                Tanggal
                            @endif
                        </th>
                        <th style="width: 150px;">No. referensi</th>
                        <th style="width: 190px;">Kategori / akun</th>
                        <th>Keterangan</th>
                        <th style="width: 110px;">Metode</th>
                        <th class="r" style="width: 125px;">Masuk ({{ $currency }})</th>
                        <th class="r" style="width: 125px;">Keluar ({{ $currency }})</th>
                        <th class="r" style="width: 135px;">Saldo ({{ $currency }})</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ledgerEntries as $item)
                    <tr data-ledger-row data-ledger-date="{{ $item['date']->format('Y-m-d') }}" data-ledger-id="{{ $item['id'] }}">
                        <td class="nw num">{{ $item['formatted_date'] }}</td>
                        <td class="mono nw" style="font-weight: 600;">{{ $item['reference_number'] }}</td>
                        <td>
                            <span class="mono dim">{{ $item['category_code'] }}</span>
                            <span style="font-weight: 600;">{{ $item['category_name'] }}</span>
                        </td>
                        <td>
                            <div class="text-truncate" style="max-width: 280px;" title="{{ $item['description'] }}">{{ $item['description'] }}</div>
                            @if($item['party_name'] && $item['party_name'] !== '-')
                                <span class="sub">{{ $item['party_name'] }}</span>
                            @endif
                        </td>
                        <td class="dim">{{ $item['payment_method'] }}</td>
                        <td class="r {{ $item['amount_in'] > 0 ? 'pos' : 'dim' }}">{{ $item['amount_in'] > 0 ? number_format($item['amount_in'], 0, ',', '.') : '-' }}</td>
                        <td class="r {{ $item['amount_out'] > 0 ? 'neg' : 'dim' }}">{{ $item['amount_out'] > 0 ? number_format($item['amount_out'], 0, ',', '.') : '-' }}</td>
                        <td class="r {{ $item['running_balance'] >= 0 ? '' : 'neg' }}" style="font-weight: 600;">{{ number_format($item['running_balance'], 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8">
                            <div class="rpt-empty">
                                <strong>Tidak ada transaksi pada periode ini</strong>
                                Ubah rentang tanggal, kategori, atau tipe transaksi, lalu terapkan filter lagi.
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($ledgerCount > 0)
                <tfoot>
                    <tr class="total">
                        <td colspan="5">Total mutasi arus kas</td>
                        <td class="r">{{ $currency }} {{ number_format($reportData['total_income'], 0, ',', '.') }}</td>
                        <td class="r">{{ $currency }} {{ number_format($reportData['total_expense'], 0, ',', '.') }}</td>
                        <td class="r {{ $netPositive ? '' : 'neg' }}">{{ $currency }} {{ number_format($reportData['net_profit'], 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        @if($ledgerCount > 0)
            <div class="rpt-panel-foot">
                <div class="rpt-pager-size">
                    <label for="ledgerPerPage">Baris per halaman</label>
                    <select id="ledgerPerPage" class="form-select form-select-sm smooth-dropdown pagination-dropdown" data-placeholder="Baris">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <span id="ledgerPageSummary" aria-live="polite"></span>
                <nav aria-label="Halaman rincian buku kas">
                    <ul id="ledgerPagination" class="pagination pagination-sm mb-0"></ul>
                </nav>
            </div>
        @endif
    </section>

    {{-- Rekapitulasi per akun --}}
    <section class="rpt-panel" aria-labelledby="rptCoa">
        <div class="rpt-panel-head">
            <h2 id="rptCoa">Rekapitulasi per akun (COA)</h2>
            <span class="meta">{{ count($reportData['category_breakdown']) }} akun</span>
        </div>
        <div class="table-responsive">
            <table class="rpt-table" style="min-width: 760px;">
                <thead>
                    <tr>
                        <th style="width: 110px;">Kode</th>
                        <th>Nama akun</th>
                        <th style="width: 100px;">Tipe</th>
                        <th style="width: 230px;">Status pajak fiskal</th>
                        <th class="c" style="width: 90px;">Transaksi</th>
                        <th class="r" style="width: 170px;">Total nominal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportData['category_breakdown'] as $catItem)
                    <tr>
                        <td class="mono" style="font-weight: 600;">{{ $catItem['code'] }}</td>
                        <td style="font-weight: 600;">{{ $catItem['name'] }}</td>
                        <td>
                            @if($catItem['type'] == 'income')
                                <span class="rpt-tag ok">Income</span>
                            @else
                                <span class="rpt-tag bad">Expense</span>
                            @endif
                        </td>
                        <td>
                            @if($catItem['type'] == 'income')
                                <span class="dim">Penghasilan bruto</span>
                            @elseif($catItem['is_tax_deductible'])
                                <span class="rpt-tag info">Deductible (biaya fiskal)</span>
                            @else
                                <span class="rpt-tag warn">Koreksi positif (non-deductible)</span>
                            @endif
                        </td>
                        <td class="c num">{{ $catItem['count'] }}</td>
                        <td class="r {{ $catItem['type'] == 'income' ? 'pos' : 'neg' }}" style="font-weight: 600;">{{ $currency }} {{ number_format($catItem['total_amount'], 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6"><div class="rpt-empty"><strong>Belum ada akun bertransaksi</strong>Tidak ada data akun untuk filter ini.</div></td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
    function initializeLedgerPagination() {
        var ledgerTable = document.getElementById('ledgerTable');
        var pageSizeSelect = document.getElementById('ledgerPerPage');
        var pagination = document.getElementById('ledgerPagination');
        var pageSummary = document.getElementById('ledgerPageSummary');
        var dateHeader = document.getElementById('ledgerDateHeader');
        var dateSortButton = document.getElementById('ledgerDateSort');

        if (!ledgerTable || !pageSizeSelect || !pagination || !pageSummary) {
            return;
        }

        var rows = Array.from(ledgerTable.querySelectorAll('[data-ledger-row]'));
        var currentPage = 1;
        var dateSortDirection = 'desc';

        function renderPagination() {
            var pageSize = Number(pageSizeSelect.value);
            var pageCount = Math.ceil(rows.length / pageSize);
            var startIndex = (currentPage - 1) * pageSize;
            var endIndex = Math.min(startIndex + pageSize, rows.length);

            rows.forEach(function (row, index) {
                row.classList.toggle('d-none', index < startIndex || index >= endIndex);
            });

            pageSummary.textContent = 'Rentang baris ' + (startIndex + 1) + '–' + endIndex + ' dari ' + rows.length + ' transaksi';
            pagination.replaceChildren();

            function addPageButton(label, page, disabled, active, ariaLabel) {
                var item = document.createElement('li');
                item.className = 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '');

                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'page-link';
                button.textContent = label;
                if (ariaLabel) {
                    button.setAttribute('aria-label', ariaLabel);
                }
                button.disabled = disabled || active;
                button.addEventListener('click', function () {
                    currentPage = page;
                    renderPagination();
                });

                item.appendChild(button);
                pagination.appendChild(item);
            }

            addPageButton('‹ Sebelumnya', currentPage - 1, currentPage === 1, false, 'Halaman sebelumnya');

            var firstPage = Math.max(1, currentPage - 2);
            var lastPage = Math.min(pageCount, currentPage + 2);
            for (var page = firstPage; page <= lastPage; page++) {
                addPageButton(String(page), page, false, page === currentPage, 'Halaman ' + page);
            }

            addPageButton('Berikutnya ›', currentPage + 1, currentPage === pageCount, false, 'Halaman berikutnya');
        }

        pageSizeSelect.addEventListener('change', function () {
            currentPage = 1;
            renderPagination();
        });

        dateSortButton.addEventListener('click', function () {
            dateSortDirection = dateSortDirection === 'desc' ? 'asc' : 'desc';
            rows.sort(function (left, right) {
                var comparison = left.dataset.ledgerDate.localeCompare(right.dataset.ledgerDate);
                if (comparison === 0) {
                    comparison = Number(left.dataset.ledgerId) - Number(right.dataset.ledgerId);
                }

                return dateSortDirection === 'desc' ? -comparison : comparison;
            });

            rows.forEach(function (row) {
                ledgerTable.tBodies[0].appendChild(row);
            });

            dateHeader.setAttribute('aria-sort', dateSortDirection === 'desc' ? 'descending' : 'ascending');
            dateSortButton.setAttribute(
                'aria-label',
                dateSortDirection === 'desc' ? 'Urutkan tanggal, terlama ke terbaru' : 'Urutkan tanggal, terbaru ke terlama'
            );
            dateSortButton.querySelector('i').className = 'fa-solid ' + (dateSortDirection === 'desc' ? 'fa-arrow-down' : 'fa-arrow-up');
            currentPage = 1;
            renderPagination();
        });

        renderPagination();
    }

    function setPreset(preset) {
        var startInput = document.getElementById('start_date');
        var endInput = document.getElementById('end_date');
        var now = new Date();

        if (preset === 'this_month') {
            var firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
            var lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
            startInput.value = formatDate(firstDay);
            endInput.value = formatDate(lastDay);
        } else if (preset === 'last_month') {
            var firstDay = new Date(now.getFullYear(), now.getMonth() - 1, 1);
            var lastDay = new Date(now.getFullYear(), now.getMonth(), 0);
            startInput.value = formatDate(firstDay);
            endInput.value = formatDate(lastDay);
        } else if (preset === 'this_year') {
            var firstDay = new Date(now.getFullYear(), 0, 1);
            var lastDay = new Date(now.getFullYear(), 11, 31);
            startInput.value = formatDate(firstDay);
            endInput.value = formatDate(lastDay);
        } else if (preset === 'last_30_days') {
            var pastDate = new Date();
            pastDate.setDate(now.getDate() - 30);
            startInput.value = formatDate(pastDate);
            endInput.value = formatDate(now);
        }

        document.getElementById('filterForm').submit();
    }

    function formatDate(date) {
        var d = new Date(date),
            month = '' + (d.getMonth() + 1),
            day = '' + d.getDate(),
            year = d.getFullYear();

        if (month.length < 2) month = '0' + month;
        if (day.length < 2) day = '0' + day;

        return [year, month, day].join('-');
    }

    document.addEventListener('DOMContentLoaded', initializeLedgerPagination);
</script>
@endpush
