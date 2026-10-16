<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Laba Rugi Fiskal - {{ $reportData['month_name'] }} {{ $reportData['year'] }}</title>
    <style>
        @page {
            margin: 25px 30px;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
        }
        body {
            font-size: 11px;
            line-height: 1.4;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }
        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
        }
        .company-info {
            font-size: 9px;
            color: #475569;
        }
        .report-title {
            text-align: center;
            margin-bottom: 15px;
        }
        .report-title h2 {
            margin: 0;
            font-size: 14px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
        }
        .report-title p {
            margin: 2px 0 0 0;
            font-size: 10px;
            color: #64748b;
        }
        .content-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .content-table th {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            font-size: 9.5px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .content-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 8px;
            font-size: 10px;
        }
        .section-header {
            background-color: #f8fafc;
            font-weight: bold;
            color: #0f172a;
        }
        .total-row {
            font-weight: bold;
            background-color: #f1f5f9;
        }
        .grand-total-row {
            font-weight: bold;
            background-color: #0f172a;
            color: #ffffff;
            font-size: 11px;
        }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .text-start { text-align: left; }
        .ps-4 { padding-left: 20px !important; }
        .signatures {
            width: 100%;
            margin-top: 30px;
        }
        .signatures td {
            width: 50%;
            text-align: center;
            font-size: 10px;
        }
    </style>
</head>
<body>

    <!-- Header Perusahaan -->
    <table class="header-table">
        <tr>
            <td style="width: 60%; vertical-align: top;">
                <div class="company-name">{{ $setting->company_name ?? 'PT DWITAMA CIPTA INTERNUSA' }}</div>
                <div class="company-info">
                    {{ $setting->company_address ?? 'Jakarta, Indonesia' }}<br>
                    NPWP 16 Digit: <strong>{{ $setting->company_npwp16 ?? '-' }}</strong> | Telepon: {{ $setting->company_phone ?? '-' }}
                </div>
            </td>
            <td style="width: 40%; text-align: right; vertical-align: top;">
                <div style="font-size: 12px; font-weight: bold; color: #1e40af;">LAMPIRAN LAPORAN KEUANGAN</div>
                <div style="font-size: 9px; color: #64748b;">
                    Formulir SPT Tahunan PPh Badan / OP<br>
                    Tanggal Cetak: {{ date('d/m/Y H:i') }}
                </div>
            </td>
        </tr>
    </table>

    <!-- Judul Laporan -->
    <div class="report-title">
        <h2>LAPORAN LABA RUGI KOMERSIAL & REKONSILIASI FISKAL</h2>
        <p>Periode: <strong>{{ $reportData['month_name'] }} {{ $reportData['year'] }}</strong> (Dalam Rupiah)</p>
    </div>

    <!-- Tabel Rekonsiliasi Fiskal -->
    <table class="content-table">
        <thead>
            <tr>
                <th style="width: 46%;" class="text-start">Pos Laporan Keuangan</th>
                <th style="width: 18%;" class="text-end">Komersial (Rp)</th>
                <th style="width: 18%;" class="text-end">Koreksi Fiskal Positif (Rp)</th>
                <th style="width: 18%;" class="text-end">Fiskal / SPT (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <!-- PENDAPATAN -->
            <tr class="section-header">
                <td colspan="4">I. PENDAPATAN / PEREDARAN USAHA (OMSET)</td>
            </tr>
            @foreach($reportData['income_categories'] as $inc)
            <tr>
                <td class="ps-4">[{{ $inc['category_code'] }}] {{ $inc['category_name'] }}</td>
                <td class="text-end">{{ number_format($inc['total_amount'], 0, ',', '.') }}</td>
                <td class="text-end">-</td>
                <td class="text-end">{{ number_format($inc['total_amount'], 0, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td>JUMLAH PENDAPATAN BRUTO (A)</td>
                <td class="text-end">{{ number_format($reportData['total_income'], 0, ',', '.') }}</td>
                <td class="text-end">-</td>
                <td class="text-end">{{ number_format($reportData['total_income'], 0, ',', '.') }}</td>
            </tr>

            <!-- BEBAN USAHA -->
            <tr class="section-header">
                <td colspan="4">II. BEBAN OPERASIONAL & BIAYA USAHA</td>
            </tr>
            @foreach($reportData['expense_categories'] as $exp)
            <tr>
                <td class="ps-4">
                    [{{ $exp['category_code'] }}] {{ $exp['category_name'] }}
                    @if(!$exp['is_tax_deductible'])
                        <em>(Non-Deductible)</em>
                    @endif
                </td>
                <td class="text-end">{{ number_format($exp['total_amount'], 0, ',', '.') }}</td>
                <td class="text-end">
                    {{ !$exp['is_tax_deductible'] ? number_format($exp['total_amount'], 0, ',', '.') : '-' }}
                </td>
                <td class="text-end">
                    {{ $exp['is_tax_deductible'] ? number_format($exp['total_amount'], 0, ',', '.') : '0' }}
                </td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td>JUMLAH BEBAN USAHA (B)</td>
                <td class="text-end">{{ number_format($reportData['total_expense'], 0, ',', '.') }}</td>
                <td class="text-end">{{ number_format($reportData['total_non_deductible_expense'], 0, ',', '.') }}</td>
                <td class="text-end">{{ number_format($reportData['total_deductible_expense'], 0, ',', '.') }}</td>
            </tr>

            <!-- LABA BERSIH -->
            <tr class="grand-total-row">
                <td>PENGHASILAN NETO / LABA BERSIH (A - B)</td>
                <td class="text-end">{{ number_format($reportData['commercial_net_profit'], 0, ',', '.') }}</td>
                <td class="text-end">+ {{ number_format($reportData['fiscal_correction_positive'], 0, ',', '.') }}</td>
                <td class="text-end">{{ number_format($reportData['fiscal_net_profit'], 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Tanda Tangan -->
    <table class="signatures">
        <tr>
            <td>
                Dibuat Oleh,<br>
                <strong>Bagian Keuangan & Pajak</strong>
                <br><br><br><br>
                ( .................................................. )
            </td>
            <td>
                Menyetujui,<br>
                <strong>Direktur Utama / Pimpinan</strong>
                <br><br><br><br>
                ( <strong>{{ $setting->company_director ?? $setting->company_name ?? 'Direktur' }}</strong> )
            </td>
        </tr>
    </table>

</body>
</html>
