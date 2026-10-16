<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Kwitansi - {{ $document->receipt_number ?: $document->document_number }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 8mm 10mm;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Helvetica Neue', 'Helvetica', 'Arial', sans-serif;
            font-size: 9.5pt;
            line-height: 1.35;
            color: #1e293b;
            background: #fff;
            margin-left: 5%;
            margin-right: 5%;
            margin-top: 2%; 
        }

        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }

        /* Top Header Container */
        .header-container {
            width: 100%;
            margin-bottom: 12px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 8px;
        }

        .company-info {
            width: 50%;
            float: left;
        }

        .company-logo {
            max-height: 38px;
            max-width: 180px;
            object-fit: contain;
            margin-bottom: 4px;
        }

        .company-name {
            font-size: 12pt;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .company-details {
            font-size: 8pt;
            color: #475569;
            line-height: 1.3;
        }

        .doc-meta {
            width: 25%;
            float: left;
            padding-left: 10px;
        }

        .meta-table {
            width: 100%;
            font-size: 8.5pt;
            border-collapse: collapse;
        }

        .meta-table td {
            padding: 1.5px 0;
            vertical-align: top;
        }

        .meta-label {
            color: #64748b;
            width: 45%;
        }

        .meta-value {
            font-weight: 600;
            color: #0f172a;
        }

        .doc-title-container {
            width: 25%;
            float: right;
            text-align: right;
        }

        .doc-title-badge {
            padding: 4px 10px;
            display: inline-block;
        }

        .title-text {
            font-size: 20pt;
            font-weight: 900;
            letter-spacing: 0.8px;
            color: #0f172a;
        }

        /* Card Container Kwitansi */
        .kwitansi-card {
            width: 100%;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 14px 18px;
            margin-bottom: 12px;
        }

        .kwitansi-table {
            width: 100%;
            border-collapse: collapse;
        }

        .kwitansi-table td {
            padding: 5px 4px;
            vertical-align: top;
            font-size: 9.5pt;
        }

        .label-col {
            width: 22%;
            font-weight: 600;
            color: #475569;
        }

        .separator-col {
            width: 2%;
            font-weight: 600;
            color: #475569;
        }

        .content-col {
            width: 76%;
            color: #0f172a;
        }

        /* Kotak Terbilang */
        .terbilang-box {
            background-color: #ffffff;
            border: 1px dashed #94a3b8;
            border-radius: 4px;
            padding: 5px 10px;
            font-weight: 600;
            font-style: italic;
            color: #1e40af;
            display: inline-block;
            width: 98%;
        }

        .amount-text {
            font-size: 14pt;
            font-weight: 800;
            color: #1e40af;
            letter-spacing: 0.5px;
        }

        /* Footer Layout / Signature */
        .footer-layout {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            page-break-inside: avoid;
        }

        .footer-layout td {
            vertical-align: top;
        }

        .col-empty {
            width: 60%;
        }

        .col-signature {
            width: 40%;
            text-align: center;
        }

        .sig-title {
            font-size: 8.5pt;
            color: #475569;
            margin-bottom: 4px;
        }

        .stamp-space {
            height: 40px;
        }

        .company-stamp {
            max-width: 95px;
            max-height: 38px;
            object-fit: contain;
        }

        .sig-name {
            font-weight: 700;
            font-size: 9.5pt;
            color: #0f172a;
            border-bottom: 1px solid #94a3b8;
            display: inline-block;
            padding: 0 10px 2px 10px;
        }

        .sig-caption {
            font-size: 8pt;
            color: #64748b;
            margin-top: 3px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>

    <!-- Header / Kop Surat Dinamis -->
    <div class="header-container clearfix">
        <div class="company-info">
            @if($setting && $setting->company_logo && file_exists(public_path('storage/' . $setting->company_logo)))
                <img src="{{ public_path('storage/' . $setting->company_logo) }}" class="company-logo">
            @else
                <div class="company-name">{{ strtoupper($setting->company_name ?? 'PT DWITAMA CIPTA INTERNUSA') }}</div>
            @endif
            
            <div class="company-details">
                {{ $setting->company_address ?? 'Alamat Perusahaan Belum Diset' }}<br>
                <span>Email: {{ $setting->company_email ?? '-' }}</span> | <span>Telp: {{ $setting->company_phone ?? '-' }}</span>
                @if(!empty($setting->company_npwp16))
                    <br><span>NPWP 16 Digit: {{ $setting->company_npwp16 }}</span>
                @endif
            </div>
        </div>
        
        <!-- Metadata Kwitansi -->
        <div class="doc-meta">
            <table class="meta-table">
                <tr>
                    <td class="meta-label">No. Dokumen</td>
                    <td class="meta-value">: {{ $document->receipt_number ?: $document->document_number }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Tanggal</td>
                    <td class="meta-value">: {{ date('d M Y', strtotime($document->date)) }}</td>
                </tr>
            </table>
        </div>
        
        <!-- Judul Dokumen -->
        <div class="doc-title-container">
            <div class="doc-title-badge">
                <span class="title-text">KWITANSI</span>
            </div>
        </div>
    </div>

    <!-- KALKULASI NOMINAL KWITANSI -->
    @php
        $bruto = $document->items->sum('subtotal');
        $potongan = $document->discount ?? 0;
        $netto = $bruto - $potongan;
        $taxRatePercent = $setting->enable_tax ? ($setting->default_tax_rate ?? 11) : 0;
        $ppn = $netto * ($taxRatePercent / 100);
        $total_tagihan = $netto + $ppn;
        $currencySymbol = $setting->currency_symbol ?? 'Rp';

        if (!function_exists('terbilang')) {
            function terbilang($angka) {
                $angka = abs($angka);
                $baca = array("", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas");
                $terbilang = "";
                
                if ($angka < 12) {
                    $terbilang = " " . $baca[(int)$angka];
                } else if ($angka < 20) {
                    $terbilang = terbilang($angka - 10) . " Belas";
                } else if ($angka < 100) {
                    $terbilang = terbilang($angka / 10) . " Puluh" . terbilang($angka % 10);
                } else if ($angka < 200) {
                    $terbilang = " Seratus" . terbilang($angka - 100);
                } else if ($angka < 1000) {
                    $terbilang = terbilang($angka / 100) . " Ratus" . terbilang($angka % 100);
                } else if ($angka < 2000) {
                    $terbilang = " Seribu" . terbilang($angka - 1000);
                } else if ($angka < 1000000) {
                    $terbilang = terbilang($angka / 1000) . " Ribu" . terbilang($angka % 1000);
                } else if ($angka < 1000000000) {
                    $terbilang = terbilang($angka / 1000000) . " Juta" . terbilang($angka % 1000000);
                } else if ($angka < 1000000000000) {
                    $terbilang = terbilang($angka / 1000000000) . " Milyar" . terbilang(fmod($angka, 1000000000));
                }
                return $terbilang;
            }
        }

        $string_terbilang = trim(terbilang($total_tagihan)) . " Rupiah";
    @endphp

    <!-- Rincian Kwitansi -->
    <div class="kwitansi-card">
        <table class="kwitansi-table">
            <tr>
                <td class="label-col">Telah Diterima Dari</td>
                <td class="separator-col">:</td>
                <td class="content-col">
                    <strong style="font-size: 10.5pt; color: #0f172a;">{{ $document->customer->name }}</strong>
                </td>
            </tr>
            <tr>
                <td class="label-col">Uang Sejumlah</td>
                <td class="separator-col">:</td>
                <td class="content-col">
                    <div class="terbilang-box">
                        # {{ $string_terbilang }} #
                    </div>
                </td>
            </tr>
            <tr>
                <td class="label-col">Untuk Pembayaran</td>
                <td class="separator-col">:</td>
                <td class="content-col">
                    @if(!empty($document->payment_note))
                        {{ $document->payment_note }}
                    @elseif(!empty($setting->receipt_footer_notes))
                        {{ $setting->receipt_footer_notes }}
                    @else
                        Pembayaran Dokumen Invoice No. <strong>{{ $document->document_number }}</strong>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="label-col" style="padding-top: 12px; vertical-align: middle;">Nominal / Jumlah</td>
                <td class="separator-col" style="padding-top: 12px; vertical-align: middle;">:</td>
                <td class="content-col" style="padding-top: 8px;">
                    <div class="amount-text">
                        {{ $currencySymbol }} {{ number_format($total_tagihan, 0, ',', '.') }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Bagian Bawah: Tanda Tangan -->
    <table class="footer-layout">
        <tr>
            <td class="col-empty"></td>
            <td class="col-signature">
                <div class="sig-title">{{ $setting->signatory_city ?? $setting->company_city ?? 'Bandung' }}, {{ date('d M Y', strtotime($document->date)) }}<br>Hormat Kami,</div>
                
                <div class="stamp-space">
                    @if($setting->stamp_receipt_enabled && $setting->company_stamp && \Illuminate\Support\Facades\Storage::disk('public')->exists($setting->company_stamp))
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->path($setting->company_stamp) }}" class="company-stamp" alt="Stempel perusahaan">
                    @endif
                </div>
                <br><br>
                <div class="sig-name">{{ $setting->signatory_name ?? 'Fauzan Septiana' }}</div>
                <div class="sig-caption">{{ $setting->company_name ?? 'PT. DWITAMA CIPTA INTERNUSA' }}</div>
            </td>
        </tr>
    </table>

</body>
</html>