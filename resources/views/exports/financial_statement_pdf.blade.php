<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Keuangan - {{ $reportData['formatted_start_date'] }} s/d {{ $reportData['formatted_end_date'] }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 24px 30px 28px;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
        }
        body {
            font-size: 9px;
            line-height: 1.4;
            color: #1e293b;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 3px solid #17365d;
            padding-bottom: 9px;
            margin-bottom: 15px;
        }
        .company-name {
            font-size: 17px;
            font-weight: bold;
            color: #17365d;
            text-transform: uppercase;
        }
        .company-info {
            font-size: 8.5px;
            color: #586579;
            line-height: 1.5;
        }
        .report-header {
            text-align: center;
            margin-bottom: 16px;
        }
        .report-header h2 {
            margin: 0;
            font-size: 15px;
            font-weight: bold;
            color: #17365d;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .report-header p {
            margin: 4px 0 0;
            font-size: 9px;
            color: #586579;
        }
        .section-title {
            font-size: 10px;
            font-weight: bold;
            color: #17365d;
            margin: 15px 0 7px;
            text-transform: uppercase;
            border-left: 3px solid #2f75b5;
            border-bottom: 1px solid #d8e0ea;
            padding: 0 0 5px 7px;
        }
        .table-data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .table-data thead { display: table-header-group; }
        .table-data tr { page-break-inside: avoid; }
        .table-data th {
            background-color: #eaf0f6;
            border: 1px solid #c4ceda;
            padding: 6px 7px;
            font-size: 8px;
            font-weight: bold;
            color: #334155;
            text-align: left;
            text-transform: uppercase;
        }
        .table-data td {
            border: 1px solid #d8e0e8;
            padding: 5px 7px;
            font-size: 8px;
            vertical-align: top;
        }
        .summary-box {
            width: 100%;
            border-collapse: separate;
            border-spacing: 7px 0;
            margin-bottom: 12px;
        }
        .summary-box td {
            border: 1px solid #d8e0e8;
            border-top: 3px solid #2f75b5;
            padding: 9px 11px;
            width: 25%;
            vertical-align: top;
        }
        .summary-label {
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 0.3px;
        }
        .summary-value {
            font-size: 13px;
            font-weight: bold;
            margin-top: 4px;
            white-space: nowrap;
        }
        .coa-header-row {
            background-color: #edf2f7;
            font-weight: bold;
            color: #17365d;
            page-break-after: avoid;
        }
        .coa-subtotal-row {
            background-color: #f4f7fa;
            font-weight: bold;
        }
        .text-success { color: #166534; }
        .text-danger { color: #991b1b; }
        .text-primary { color: #1e40af; }
        .text-warning { color: #854d0e; }
        .text-muted { color: #64748b; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .text-start { text-align: left; }
        .font-mono { font-family: monospace; }
        .total-row {
            font-weight: bold;
            background-color: #edf2f7;
        }
        .signatures {
            width: 100%;
            margin-top: 20px;
            page-break-inside: avoid;
        }
        .signatures td {
            width: 33.33%;
            text-align: center;
            font-size: 8.5px;
            vertical-align: top;
            padding: 0 12px;
        }
        .amount {
            text-align: right;
            white-space: nowrap;
            font-family: 'DejaVu Sans Mono', monospace;
            font-size: 7.7px !important;
        }
        .reference {
            font-family: 'DejaVu Sans Mono', monospace;
            font-size: 7.2px !important;
            overflow-wrap: anywhere;
        }
        .report-meta {
            font-size: 8px;
            color: #64748b;
            text-align: right;
        }
    </style>
</head>
<body>

    <!-- Header Perusahaan -->
    <table class="header-table">
        <tr>
            <td style="width: 60%; vertical-align: top;">
                <div class="company-name">{{ $setting->company_name ?? 'Nama Perusahaan' }}</div>
                <div class="company-info">
                    {{ $setting->company_address ?? 'Jakarta, Indonesia' }}<br>
                    NPWP 16 Digit: <strong>{{ $setting->company_npwp16 ?? '-' }}</strong> | Telepon: {{ $setting->company_phone ?? '-' }}
                </div>
            </td>
            <td style="width: 40%; text-align: right; vertical-align: top;">
                <div style="font-size: 9px; font-weight: bold; color: #2f75b5; letter-spacing: 0.4px;">LAPORAN KEUANGAN</div>
                <div class="report-meta">
                Dicetak: {{ date('d/m/Y H:i') }}<br>
                Mata uang: {{ $setting->currency_code ?? 'IDR' }} ({{ $setting->currency_symbol ?? 'Rp' }})
                </div>
            </td>
        </tr>
    </table>

    <!-- Judul Laporan -->
    <div class="report-header">
        <h2>Laporan Arus Kas & Rekapitulasi Keuangan</h2>
        <p>Periode <strong>{{ $reportData['formatted_start_date'] }}</strong> s.d. <strong>{{ $reportData['formatted_end_date'] }}</strong>
            &nbsp; | &nbsp; {{ number_format($reportData['total_transactions_count']) }} transaksi tercatat</p>
    </div>

    <!-- 1. Cashflow Summary -->
    <div class="section-title">1. Ikhtisar Arus Kas</div>
    <table class="summary-box">
        <tr>
            <td style="background-color: #f0fdf4;">
                <div class="summary-label">Total Pemasukan Kas</div>
                <div class="summary-value text-success">{{ $setting->currency_symbol ?? 'Rp' }} {{ number_format($reportData['total_income'], 0, ',', '.') }}</div>
                <div style="font-size: 7.5px; color: #166534; margin-top: 2px;">
                    Pelunasan invoice: {{ $setting->currency_symbol ?? 'Rp' }} {{ number_format($reportData['paid_invoices_income'], 0, ',', '.') }}
                </div>
            </td>
            <td style="background-color: #fef2f2;">
                <div class="summary-label">Total Pengeluaran Kas</div>
                <div class="summary-value text-danger">{{ $setting->currency_symbol ?? 'Rp' }} {{ number_format($reportData['total_expense'], 0, ',', '.') }}</div>
                <div style="font-size: 7.5px; color: #991b1b; margin-top: 2px;">
                    Deductible: {{ $setting->currency_symbol ?? 'Rp' }} {{ number_format($reportData['deductible_expense'], 0, ',', '.') }}
                </div>
            </td>
            <td style="background-color: #eff6ff;">
                <div class="summary-label">Surplus / (Defisit) Kas</div>
                <div class="summary-value {{ $reportData['net_profit'] >= 0 ? 'text-primary' : 'text-danger' }}">
                    {{ $setting->currency_symbol ?? 'Rp' }} {{ number_format($reportData['net_profit'], 0, ',', '.') }}
                </div>
                <div style="font-size: 7.5px; color: #1e40af; margin-top: 2px;">
                    Pemasukan dikurangi pengeluaran
                </div>
            </td>
            <td style="background-color: #fefce8;">
                <div class="summary-label">Piutang Belum Terbayar</div>
                <div class="summary-value text-warning">{{ $setting->currency_symbol ?? 'Rp' }} {{ number_format($reportData['invoicing_summary']['unpaid_amount'], 0, ',', '.') }}</div>
                <div style="font-size: 7.5px; color: #854d0e; margin-top: 2px;">
                    {{ $reportData['invoicing_summary']['sent_count'] }} invoice belum lunas
                </div>
            </td>
        </tr>
    </table>

    <!-- Grouping Data Internal Blade untuk CoA -->
    @php
        $groupedLedger = collect($reportData['ledger_entries'])->groupBy(function($item) {
            return '[' . ($item['category_code'] ?? '-') . '] ' . ($item['category_name'] ?? 'Lain-lain');
        });
    @endphp

    <!-- 2. Rekapitulasi per Chart Of Accounts (Group By) -->
    <div class="section-title">2. Ringkasan Mutasi per Akun</div>
    <table class="table-data">
        <thead>
            <tr>
                <th style="width: 46%;">Kode & Nama Akun</th>
                <th style="width: 10%;" class="text-center">Transaksi</th>
                <th style="width: 22%;" class="text-end">Pemasukan ({{ $setting->currency_symbol ?? 'Rp' }})</th>
                <th style="width: 22%;" class="text-end">Pengeluaran ({{ $setting->currency_symbol ?? 'Rp' }})</th>
            </tr>
        </thead>
        <tbody>
            @forelse($groupedLedger as $coaTitle => $entries)
                @php
                    $sumIn = $entries->sum('amount_in');
                    $sumOut = $entries->sum('amount_out');
                @endphp
                <tr>
                    <td class="text-start"><strong>{{ $coaTitle }}</strong></td>
                    <td class="text-center">{{ $entries->count() }}</td>
                    <td class="amount text-success">{{ $sumIn > 0 ? number_format($sumIn, 0, ',', '.') : '-' }}</td>
                    <td class="amount text-danger">{{ $sumOut > 0 ? number_format($sumOut, 0, ',', '.') : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center py-3 text-muted">Tidak ada rekapitulasi akun pada periode ini.</td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td colspan="2" class="text-end">TOTAL KESELURUHAN REKAPITULASI:</td>
                <td class="amount text-success">{{ $setting->currency_symbol ?? 'Rp' }} {{ number_format($reportData['total_income'], 0, ',', '.') }}</td>
                <td class="amount text-danger">{{ $setting->currency_symbol ?? 'Rp' }} {{ number_format($reportData['total_expense'], 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <!-- 3. Rincian Buku Kas & Mutasi Transaksi dikategorikan per Chart of Account -->
    <div class="section-title">3. Rincian Buku Kas</div>
    <table class="table-data">
        <thead>
            <tr>
                <th style="width: 9%;">Tanggal</th>
                <th style="width: 15%;">No. Referensi</th>
                <th style="width: 38%;">Uraian / Relasi</th>
                <th style="width: 11%;">Metode</th>
                <th style="width: 13%;" class="text-end">Masuk ({{ $setting->currency_symbol ?? 'Rp' }})</th>
                <th style="width: 14%;" class="text-end">Keluar ({{ $setting->currency_symbol ?? 'Rp' }})</th>
            </tr>
        </thead>
        <tbody>
            @forelse($groupedLedger as $coaTitle => $entries)
                <!-- Group Header CoA -->
                <tr class="coa-header-row">
                    <td colspan="6" class="text-start" style="padding: 6px;">
                        AKUN: {{ $coaTitle }}
                    </td>
                </tr>

                @foreach($entries as $item)
                <tr>
                    <td class="text-center">{{ $item['formatted_date'] }}</td>
                    <td class="text-center reference">{{ $item['reference_number'] }}</td>
                    <td class="text-start">
                        {{ $item['description'] }}
                        @if($item['party_name'] && $item['party_name'] !== '-')
                            <span style="font-size: 7.5px; color: #64748b; display: block; margin-top: 2px;">Relasi: {{ $item['party_name'] }}</span>
                        @endif
                    </td>
                    <td class="text-center" style="font-size: 7.5px;">{{ $item['payment_method'] }}</td>
                    <td class="amount text-success">
                        {{ $item['amount_in'] > 0 ? number_format($item['amount_in'], 0, ',', '.') : '-' }}
                    </td>
                    <td class="amount text-danger">
                        {{ $item['amount_out'] > 0 ? number_format($item['amount_out'], 0, ',', '.') : '-' }}
                    </td>
                </tr>
                @endforeach

                <!-- Subtotal per CoA -->
                @php
                    $subtotalIn = $entries->sum('amount_in');
                    $subtotalOut = $entries->sum('amount_out');
                @endphp
                <tr class="coa-subtotal-row">
                    <td colspan="4" class="text-end">Subtotal {{ $coaTitle }}:</td>
                    <td class="amount text-success">
                        {{ $subtotalIn > 0 ? ($setting->currency_symbol ?? 'Rp') . ' ' . number_format($subtotalIn, 0, ',', '.') : '-' }}
                    </td>
                    <td class="amount text-danger">
                        {{ $subtotalOut > 0 ? ($setting->currency_symbol ?? 'Rp') . ' ' . number_format($subtotalOut, 0, ',', '.') : '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-3 text-muted">Tidak ada transaksi tercatat pada rentang tanggal ini.</td>
                </tr>
            @endforelse

            <!-- Total Akhir Mutasi -->
            <tr class="total-row" style="border-top: 2px solid #0f172a;">
                <td colspan="4" class="text-end">Total Mutasi Kas:</td>
                <td class="amount text-success">{{ $setting->currency_symbol ?? 'Rp' }} {{ number_format($reportData['total_income'], 0, ',', '.') }}</td>
                <td class="amount text-danger">{{ $setting->currency_symbol ?? 'Rp' }} {{ number_format($reportData['total_expense'], 0, ',', '.') }}</td>
            </tr>
            <tr style="background-color: #0f172a; color: #ffffff; font-weight: bold;">
                <td colspan="4" class="text-end">Surplus / (Defisit) Kas:</td>
                <td colspan="2" class="amount" style="color: #ffffff; font-size: 9px !important;">
                    {{ $setting->currency_symbol ?? 'Rp' }} {{ number_format($reportData['net_profit'], 0, ',', '.') }}
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Blok Tanda Tangan & Otorisasi -->
    <table class="signatures">
        <tr>
            <td>
                Dibuat Oleh,<br>
                <span style="color: #64748b;">Staf Administrasi Keuangan</span>
                <br><br><br><br>
                ( .................................................. )
            </td>
            <td>
                Diperiksa Oleh,<br>
                <span style="color: #64748b;">Finance & Accounting Manager</span>
                <br><br><br><br>
                ( .................................................. )
            </td>
            <td>
                Disetujui Oleh,<br>
                <span style="color: #64748b;">Direktur Utama / Pimpinan</span>
                <br><br><br><br>
                ( <strong>{{ $setting->company_director ?? $setting->company_name ?? 'Direktur' }}</strong> )
            </td>
        </tr>
    </table>

</body>
</html>