<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Laba Rugi Fiskal</title>
</head>
<body>
    <table border="1">
        <tr>
            <th colspan="4" style="font-size: 14pt; font-weight: bold; text-align: center; background-color: #0f172a; color: #ffffff;">
                {{ strtoupper($setting->company_name ?? 'PT DWITAMA CIPTA INTERNUSA') }}
            </th>
        </tr>
        <tr>
            <th colspan="4" style="font-size: 11pt; text-align: center;">
                LAPORAN LABA RUGI KOMERSIAL & REKONSILIASI FISKAL SPT TAHUNAN
            </th>
        </tr>
        <tr>
            <th colspan="4" style="font-size: 9pt; text-align: center;">
                Periode: {{ $reportData['month_name'] }} {{ $reportData['year'] }} | NPWP 16 Digit: {{ $setting->company_npwp16 ?? '-' }}
            </th>
        </tr>
        <tr>
            <td colspan="4"></td>
        </tr>
        <tr style="background-color: #cbd5e1; font-weight: bold;">
            <th style="width: 400px; text-align: left;">POS LAPORAN KEUANGAN</th>
            <th style="width: 180px; text-align: right;">KOMERSIAL (RP)</th>
            <th style="width: 180px; text-align: right;">KOREKSI FISKAL POSITIF (RP)</th>
            <th style="width: 180px; text-align: right;">FISKAL / SPT (RP)</th>
        </tr>

        <!-- PENDAPATAN -->
        <tr style="background-color: #f1f5f9; font-weight: bold;">
            <td colspan="4">I. PENDAPATAN / PEREDARAN USAHA (OMSET)</td>
        </tr>
        @foreach($reportData['income_categories'] as $inc)
        <tr>
            <td style="padding-left: 20px;">[{{ $inc['category_code'] }}] {{ $inc['category_name'] }}</td>
            <td style="text-align: right;">{{ $inc['total_amount'] }}</td>
            <td style="text-align: right;">0</td>
            <td style="text-align: right;">{{ $inc['total_amount'] }}</td>
        </tr>
        @endforeach
        <tr style="background-color: #dcfce7; font-weight: bold;">
            <td>TOTAL PENDAPATAN BRUTO (A)</td>
            <td style="text-align: right;">{{ $reportData['total_income'] }}</td>
            <td style="text-align: right;">0</td>
            <td style="text-align: right;">{{ $reportData['total_income'] }}</td>
        </tr>

        <!-- BEBAN USAHA -->
        <tr style="background-color: #f1f5f9; font-weight: bold;">
            <td colspan="4">II. BEBAN OPERASIONAL & BIAYA USAHA</td>
        </tr>
        @foreach($reportData['expense_categories'] as $exp)
        <tr>
            <td style="padding-left: 20px;">
                [{{ $exp['category_code'] }}] {{ $exp['category_name'] }}
                {{ !$exp['is_tax_deductible'] ? '(Non-Deductible)' : '' }}
            </td>
            <td style="text-align: right;">{{ $exp['total_amount'] }}</td>
            <td style="text-align: right;">{{ !$exp['is_tax_deductible'] ? $exp['total_amount'] : 0 }}</td>
            <td style="text-align: right;">{{ $exp['is_tax_deductible'] ? $exp['total_amount'] : 0 }}</td>
        </tr>
        @endforeach
        <tr style="background-color: #fee2e2; font-weight: bold;">
            <td>TOTAL BIAYA / BEBAN USAHA (B)</td>
            <td style="text-align: right;">{{ $reportData['total_expense'] }}</td>
            <td style="text-align: right;">{{ $reportData['total_non_deductible_expense'] }}</td>
            <td style="text-align: right;">{{ $reportData['total_deductible_expense'] }}</td>
        </tr>

        <!-- HASIL AKHIR -->
        <tr style="background-color: #0f172a; color: #ffffff; font-weight: bold; font-size: 11pt;">
            <td>PENGHASILAN NETO / LABA BERSIH (A - B)</td>
            <td style="text-align: right; color: #67e8f9;">{{ $reportData['commercial_net_profit'] }}</td>
            <td style="text-align: right; color: #fde047;">{{ $reportData['fiscal_correction_positive'] }}</td>
            <td style="text-align: right; color: #ffffff;">{{ $reportData['fiscal_net_profit'] }}</td>
        </tr>
    </table>
</body>
</html>
