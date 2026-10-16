<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Faktur Penjualan - {{ $document->document_number }}</title>
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
            line-height: 1.3;
            color: #1e293b;
            background: #fff;
            margin-left: 5%;
            margin-right: 5%;
            margin-top: 2%;
            margin-bottom: 3%; 
        }

        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }

        /* Top Header Container */
        .header-container {
            width: 100%;
            margin-bottom: 10px;
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

        /* Customer Box Layout */
        .customer-card {
            width: 100%;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 6px 10px;
            margin-bottom: 10px;
        }

        .customer-table {
            width: 100%;
            font-size: 8.5pt;
            border-collapse: collapse;
        }

        .customer-table td {
            vertical-align: top;
            padding: 1.5px 3px;
        }

        .cust-label {
            color: #64748b;
            font-weight: 500;
        }

        /* Main Item Table */
        .item-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }

        .item-table th {
            background-color: #1e40af;
            color: #ffffff;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 8.5pt;
            letter-spacing: 0.5px;
            padding: 6px 8px;
            border: 1px solid #1e3a8a;
            text-align: left;
        }

        .item-table td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            font-size: 9pt;
            color: #1e293b;
            vertical-align: top;
        }

        .item-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }

        .footer-layout {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
            page-break-inside: avoid;
        }

        .footer-layout td {
            vertical-align: top;
        }

        .col-signature {
            width: 30%;
            padding-right: 15px;
        }

        .col-notes {
            width: 35%;
            padding-right: 15px;
        }

        .col-summary {
            width: 35%;
        }

        .signature-title {
            font-size: 8.5pt;
            color: #64748b;
            margin-bottom: 6px;
        }

        .stamp-space {
            height: 55px;
        }

        .company-stamp {
            max-width: 95px;
            max-height: 52px;
            object-fit: contain;
        }

        .signer-name {
            font-weight: 700;
            font-size: 9.5pt;
            color: #0f172a;
            border-bottom: 1px dashed #cbd5e1;
            display: inline-block;
            padding-bottom: 2px;
        }

        .signer-title {
            font-size: 8pt;
            color: #64748b;
            margin-top: 2px;
        }

        .notes-card {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 8px;
        }

        .notes-title {
            font-weight: 700;
            font-size: 8.5pt;
            color: #334155;
            margin-bottom: 4px;
        }

        .notes-content {
            font-size: 8pt;
            color: #475569;
            line-height: 1.4;
        }

        /* Summary Table */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            overflow: hidden;
        }

        .summary-table td {
            padding: 3.5px 8px;
            font-size: 8.5pt;
            border-bottom: 1px solid #e2e8f0;
        }

        .summary-table tr:last-child td {
            border-bottom: none;
        }

        .summary-netto {
            background-color: #f1f5f9;
            font-weight: 600;
        }

        .summary-total {
            background-color: #1e40af;
            color: #ffffff !important;
            font-weight: 700;
            font-size: 10pt !important;
        }

        .summary-total td {
            color: #ffffff !important;
            padding: 5px 8px;
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
        
        <!-- Metadata Faktur -->
        <div class="doc-meta">
            <table class="meta-table">
                <tr>
                    <td class="meta-label">No. Faktur</td>
                    <td class="meta-value">: {{ $document->document_number }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Tanggal</td>
                    <td class="meta-value">: {{ date('d M Y', strtotime($document->date)) }}</td>
                </tr>
                @if($setting && $setting->term_of_payment > 0)
                <tr>
                    <td class="meta-label">Jatuh Tempo</td>
                    <td class="meta-value">: {{ date('d M Y', strtotime($document->date . ' + ' . $setting->term_of_payment . ' days')) }}</td>
                </tr>
                @endif
            </table>
        </div>
        
        <!-- Judul Faktur -->
        <div class="doc-title-container">
            <div class="doc-title-badge">
                <span class="title-text">INVOICE</span>
            </div>
        </div>
    </div>

    <!-- Informasi Pelanggan -->
    <div class="customer-card">
        <table class="customer-table">
            <tr>
                <td width="10%" class="cust-label">Pelanggan</td>
                <td width="50%">: <strong style="color: #0f172a;">{{ $document->customer->name }}</strong></td>
                <td width="10%" class="cust-label">Email</td>
                <td width="30%">: {{ $document->customer->email ?? '-' }}</td>
            </tr>
            <tr>
                <td class="cust-label">Alamat</td>
                <td>: {{ $document->customer->address ?? '-' }}</td>
                <td class="cust-label">No. Telepon</td>
                <td>: {{ $document->customer->phone ?? '-' }}</td>
            </tr>
        </table>
    </div>

    <!-- Tabel Rincian Barang -->
    <table class="item-table">
        <thead>
            <tr>
                <th width="4%" class="text-center">NO</th>
                <th width="32%">NAMA BARANG / JASA</th>
                <th width="20%">KETERANGAN</th>
                <th width="8%" class="text-center">QTY</th>
                <th width="8%" class="text-center">SAT</th>
                <th width="14%" class="text-right">HARGA SATUAN</th>
                <th width="14%" class="text-right">BRUTO</th>
            </tr>
        </thead>
        <tbody>
            @foreach($document->items as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $item->description }}</td>
                <td>{!! $item->notes ? nl2br(e($item->notes)) : '-' !!}</td>
                <td class="text-center">{{ $item->qty }}</td>
                <td class="text-center">{{ $item->unit ?? 'Pcs' }}</td>
                <td class="text-right">{{ number_format($item->price, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- KALKULASI AKUNTANSI DINAMIS -->
    @php
        $bruto = $document->items->sum('subtotal');
        $potongan = $document->discount ?? 0;
        $pembulatan = 0; 
        $netto = $bruto - $potongan + $pembulatan;
        $dpp = $netto;
        
        $taxRatePercent = $setting->enable_tax ? ($setting->default_tax_rate ?? 11) : 0;
        $ppn = $dpp * ($taxRatePercent / 100);
        $total_tagihan = $netto + $ppn;
        $currencySymbol = $setting->currency_symbol ?? 'Rp';
    @endphp
    
    <!-- Bagian Bawah -->
    <table class="footer-layout">
        <tr>
            <!-- Kolom Tanda Tangan -->
            <td class="col-signature">
                <div class="signature-title">{{ $setting->signatory_city ?? 'Bandung' }}, {{ date('d M Y', strtotime($document->date)) }}<br>Ditandatangani oleh,</div>
                <div class="stamp-space">
                    @if($setting->stamp_invoice_enabled && $setting->company_stamp && \Illuminate\Support\Facades\Storage::disk('public')->exists($setting->company_stamp))
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->path($setting->company_stamp) }}" class="company-stamp" alt="Stempel perusahaan">
                    @endif
                </div>
                <div class="signer-name">{{ strtoupper($setting->signatory_name ?? 'ADMIN / FINANCE') }}</div>
                <div class="signer-title">{{ $setting->signatory_position ?? 'Direktur Utama' }}</div>
            </td>

            <!-- Kolom Catatan Bank & Terms -->
            <td class="col-notes">
                <div class="notes-card">
                    <div class="notes-title">Petunjuk Pembayaran:</div>
                    <div class="notes-content">
                        {!! nl2br(e($setting->company_bank_account ?? 'Transfer Bank sesuai kesepakatan penagihan.')) !!}
                    </div>
                </div>
                @if(!empty($setting->invoice_footer_notes))
                <div class="notes-card" style="margin-top: 4px;">
                    <div class="notes-title">Syarat & Ketentuan:</div>
                    <div class="notes-content">
                        {!! nl2br(e($setting->invoice_footer_notes)) !!}
                    </div>
                </div>
                @endif
            </td>

            <!-- Kolom Total / Kalkulasi Nominal -->
            <td class="col-summary">
                <table class="summary-table">
                    <tr>
                        <td style="color: #475569;">Jumlah Bruto</td>
                        <td class="text-right">{{ number_format($bruto, 0, ',', '.') }}</td>
                    </tr>
                    @if($potongan > 0)
                    <tr>
                        <td style="color: #475569;">Potongan (Diskon)</td>
                        <td class="text-right">-{{ number_format($potongan, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    <tr class="summary-netto">
                        <td><strong>Jumlah Netto (DPP)</strong></td>
                        <td class="text-right"><strong>{{ number_format($netto, 0, ',', '.') }}</strong></td>
                    </tr>
                    <tr>
                        <td style="color: #475569;">PPN ({{ $taxRatePercent }}%)</td>
                        <td class="text-right">{{ number_format($ppn, 0, ',', '.') }}</td>
                    </tr>
                    <tr class="summary-total">
                        <td>JUMLAH TAGIHAN</td>
                        <td class="text-right">{{ $currencySymbol }} {{ number_format($total_tagihan, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

</body>
</html>