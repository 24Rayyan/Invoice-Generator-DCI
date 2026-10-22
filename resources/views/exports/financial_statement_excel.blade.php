<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Keuangan Profesional</title>
</head>
<body>
    <table border="1">
        <!-- HEADER PERUSAHAAN -->
        <tr>
            <th colspan="8" style="font-size: 14pt; font-weight: bold; text-align: center; background-color: #0f172a; color: #ffffff;">
                {{ strtoupper($setting->company_name ?? 'NAMA PERUSAHAAN') }}
            </th>
        </tr>
        <tr>
            <th colspan="8" style="font-size: 11pt; text-align: center;">
                LAPORAN ARUS KAS &amp; REKAPITULASI KEUANGAN PERUSAHAAN
            </th>
        </tr>
        <tr>
            <th colspan="8" style="font-size: 9pt; text-align: center;">
                Periode: {{ $reportData['formatted_start_date'] }} s/d {{ $reportData['formatted_end_date'] }} | NPWP 16 Digit: {{ $setting->company_npwp16 ?? '-' }}
            </th>
        </tr>
        <tr>
            <td colspan="8"></td>
        </tr>

        <!-- 1. EXECUTIVE SUMMARY -->
        <tr style="background-color: #cbd5e1; font-weight: bold;">
            <th colspan="8">1. RINGKASAN EKSEKUTIF (EXECUTIVE SUMMARY)</th>
        </tr>
        <tr style="background-color: #f8fafc; font-weight: bold;">
            <td colspan="3">METRIK KEUANGAN</td>
            <td colspan="5" style="text-align: right;">NILAI (RP)</td>
        </tr>
        <tr>
            <td colspan="3">Total Pemasukan Kas (Total Income)</td>
            <td colspan="5" style="text-align: right; font-weight: bold; color: #166534;">{{ $reportData['total_income'] }}</td>
        </tr>
        <tr>
            <td colspan="3"> - Dari Pelunasan Tagihan Invoice</td>
            <td colspan="5" style="text-align: right;">{{ $reportData['paid_invoices_income'] }}</td>
        </tr>
        <tr>
            <td colspan="3"> - Dari Pendapatan Lainnya</td>
            <td colspan="5" style="text-align: right;">{{ $reportData['other_income'] }}</td>
        </tr>
        <tr>
            <td colspan="3">Total Pengeluaran Kas (Total Expenses)</td>
            <td colspan="5" style="text-align: right; font-weight: bold; color: #991b1b;">{{ $reportData['total_expense'] }}</td>
        </tr>
        <tr>
            <td colspan="3"> - Beban Usaha Fiskal (Deductible)</td>
            <td colspan="5" style="text-align: right;">{{ $reportData['deductible_expense'] }}</td>
        </tr>
        <tr>
            <td colspan="3"> - Koreksi Fiskal Positif (Non-Deductible)</td>
            <td colspan="5" style="text-align: right;">{{ $reportData['non_deductible_expense'] }}</td>
        </tr>
        <tr style="background-color: #0f172a; color: #ffffff; font-weight: bold;">
            <td colspan="3">SURPLUS / DEFISIT BERSIH (NET PROFIT / CASHFLOW)</td>
            <td colspan="5" style="text-align: right; color: #ffffff; font-size: 11pt;">{{ $reportData['net_profit'] }}</td>
        </tr>
        <tr>
            <td colspan="3">Total Piutang Berjalan Belum Terbayar (Sent Invoices)</td>
            <td colspan="5" style="text-align: right; font-weight: bold; color: #854d0e;">{{ $reportData['invoicing_summary']['unpaid_amount'] }}</td>
        </tr>
        <tr>
            <td colspan="8"></td>
        </tr>

        <!-- 2. REKAP INVOICING -->
        <tr style="background-color: #cbd5e1; font-weight: bold;">
            <th colspan="8">2. REKAPITULASI PENAGIHAN INVOICE</th>
        </tr>
        <tr style="background-color: #f8fafc; font-weight: bold;">
            <th colspan="2">STATUS INVOICE</th>
            <th colspan="2" style="text-align: center;">JUMLAH DOKUMEN</th>
            <th colspan="2" style="text-align: right;">TOTAL NILAI TAGIHAN (RP)</th>
            <th colspan="2" style="text-align: center;">KOLEKTIBILITAS (%)</th>
        </tr>
        <tr>
            <td colspan="2">PAID (Invoice Lunas)</td>
            <td colspan="2" style="text-align: center;">{{ $reportData['invoicing_summary']['paid_count'] }}</td>
            <td colspan="2" style="text-align: right;">{{ $reportData['invoicing_summary']['paid_amount'] }}</td>
            <td colspan="2" style="text-align: center;">{{ $reportData['invoicing_summary']['collection_rate'] }}%</td>
        </tr>
        <tr>
            <td colspan="2">SENT (Piutang Menunggu Bayar)</td>
            <td colspan="2" style="text-align: center;">{{ $reportData['invoicing_summary']['sent_count'] }}</td>
            <td colspan="2" style="text-align: right;">{{ $reportData['invoicing_summary']['sent_amount'] }}</td>
            <td colspan="2" style="text-align: center;">-</td>
        </tr>
        <tr>
            <td colspan="2">DRAFT (Konsep Belum Terbit)</td>
            <td colspan="2" style="text-align: center;">{{ $reportData['invoicing_summary']['draft_count'] }}</td>
            <td colspan="2" style="text-align: right;">{{ $reportData['invoicing_summary']['draft_amount'] }}</td>
            <td colspan="2" style="text-align: center;">-</td>
        </tr>
        <tr style="background-color: #f1f5f9; font-weight: bold;">
            <td colspan="2">TOTAL INVOICE TERBIT</td>
            <td colspan="2" style="text-align: center;">{{ $reportData['invoicing_summary']['total_count'] }}</td>
            <td colspan="2" style="text-align: right;">{{ $reportData['invoicing_summary']['total_amount'] }}</td>
            <td colspan="2" style="text-align: center;">100%</td>
        </tr>
        <tr>
            <td colspan="8"></td>
        </tr>

        <!-- 3. TRANSACTION LEDGER -->
        <tr style="background-color: #cbd5e1; font-weight: bold;">
            <th colspan="8">3. BUKU KAS &amp; MUTASI TRANSAKSI KRONOLOGIS (TRANSACTION LEDGER)</th>
        </tr>
        <tr style="background-color: #f8fafc; font-weight: bold; text-align: center;">
            <th style="width: 100px;">TANGGAL</th>
            <th style="width: 140px;">NO. REFERENSI</th>
            <th style="width: 180px;">KATEGORI / AKUN</th>
            <th style="width: 280px;">DESKRIPSI / RELASI</th>
            <th style="width: 120px;">METODE BAYAR</th>
            <th style="width: 140px; text-align: right;">MASUK (RP)</th>
            <th style="width: 140px; text-align: right;">KELUAR (RP)</th>
            <th style="width: 150px; text-align: right;">SALDO BERJALAN (RP)</th>
        </tr>
        @forelse($reportData['ledger_entries'] as $trx)
        <tr>
            <td style="text-align: center;">{{ $trx['formatted_date'] }}</td>
            <td style="text-align: center;">{{ $trx['reference_number'] }}</td>
            <td>[{{ $trx['category_code'] }}] {{ $trx['category_name'] }}</td>
            <td>{{ $trx['description'] }} {{ $trx['party_name'] && $trx['party_name'] !== '-' ? '(' . $trx['party_name'] . ')' : '' }}</td>
            <td style="text-align: center;">{{ $trx['payment_method'] }}</td>
            <td style="text-align: right;">{{ $trx['amount_in'] }}</td>
            <td style="text-align: right;">{{ $trx['amount_out'] }}</td>
            <td style="text-align: right;">{{ $trx['running_balance'] }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="8" style="text-align: center;">Tidak ada data transaksi.</td>
        </tr>
        @endforelse
        <tr style="background-color: #f1f5f9; font-weight: bold;">
            <td colspan="5" style="text-align: right;">TOTAL MUTASI ARUS KAS:</td>
            <td style="text-align: right;">{{ $reportData['total_income'] }}</td>
            <td style="text-align: right;">{{ $reportData['total_expense'] }}</td>
            <td style="text-align: right;">{{ $reportData['net_profit'] }}</td>
        </tr>
    </table>
</body>
</html>
