<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Surat Jalan - {{ $document->delivery_note_number ?: $document->document_number }}</title>
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
            margin-bottom: 10px;
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

        /* Signature Layout */
        .signature-layout {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            page-break-inside: avoid;
        }

        .signature-layout td {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            padding: 0 10px;
        }

        .sig-title {
            font-size: 8.5pt;
            color: #475569;
            margin-bottom: 6px;
        }

        .stamp-space {
            height: 45px;
        }

        .company-stamp {
            max-width: 95px;
            max-height: 42px;
            object-fit: contain;
        }

        .sig-name {
            font-weight: 700;
            font-size: 9pt;
            color: #0f172a;
        }

        .sig-caption {
            font-size: 8pt;
            color: #64748b;
            margin-top: 2px;
        }

        .notes-card {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 4px;
            padding: 6px 10px;
            margin-top: 8px;
            font-size: 8pt;
            color: #475569;
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
                <div class="company-name">{{ strtoupper($setting->company_name ?? 'NAMA PERUSAHAAN') }}</div>
            @endif
            
            <div class="company-details">
                {{ $setting->company_address ?? 'Alamat Perusahaan Belum Diset' }}<br>
                <span>Email: {{ $setting->company_email ?? '-' }}</span> | <span>Telp: {{ $setting->company_phone ?? '-' }}</span>
                @if(!empty($setting->company_npwp16))
                    <br><span>NPWP 16 Digit: {{ $setting->company_npwp16 }}</span>
                @endif
            </div>
        </div>
        
        <!-- Metadata Surat Jalan -->
        <div class="doc-meta">
            <table class="meta-table">
                <tr>
                    <td class="meta-label">No. Dokumen</td>
                    <td class="meta-value">: {{ $document->delivery_note_number ?: $document->document_number }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Tgl. Kirim</td>
                    <td class="meta-value">: {{ date('d M Y', strtotime($document->date)) }}</td>
                </tr>
            </table>
        </div>
        
        <!-- Judul Dokumen -->
        <div class="doc-title-container">
            <div class="doc-title-badge">
                <span class="title-text">SURAT JALAN</span>
            </div>
        </div>
    </div>

    <!-- Informasi Tujuan Pengiriman (Customer) -->
    <div class="customer-card">
        <table class="customer-table">
            <tr>
                <td width="12%" class="cust-label">Penerima / Tujuan</td>
                <td width="48%">: <strong style="color: #0f172a;">{{ $document->customer->name }}</strong></td>
                <td width="10%" class="cust-label">Email</td>
                <td width="30%">: {{ $document->customer->email ?? '-' }}</td>
            </tr>
            <tr>
                <td class="cust-label">Alamat Kirim</td>
                <td>: {{ $document->customer->address ?? '-' }}</td>
                <td class="cust-label">No. Telepon</td>
                <td>: {{ $document->customer->phone ?? '-' }}</td>
            </tr>
        </table>
    </div>

    <!-- Tabel Rincian Barang (TANPA HARGA & NOMINAL) -->
    <table class="item-table">
        <thead>
            <tr>
                <th width="5%" class="text-center">NO</th>
                <th width="45%">NAMA BARANG / JASA</th>
                <th width="32%">KETERANGAN</th>
                <th width="9%" class="text-center">QTY</th>
                <th width="9%" class="text-center">SAT</th>
            </tr>
        </thead>
        <tbody>
            @foreach($document->items as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $item->description }}</td>
                <td>{{ $item->notes ?? '-' }}</td>
                <td class="text-center"><strong>{{ $item->qty }}</strong></td>
                <td class="text-center">{{ $item->unit ?? 'Pcs' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if(!empty($setting->delivery_note_footer_notes))
    <div class="notes-card">
        <strong>Catatan Pengiriman:</strong> {{ $setting->delivery_note_footer_notes }}
    </div>
    @endif

    <!-- Tanda Tangan 3 Kolom Sejajar Presisi -->
    <table class="signature-layout">
        <tr>
            <!-- Kolom 1: Penerima -->
            <td>
                <div class="sig-title">Penerima (Customer),</div>
                <div class="stamp-space"></div>
                <br>
                <div class="sig-name">( .................................... )</div>
                <div class="sig-caption">Tanda Tangan & Cap</div>
            </td>

            <!-- Kolom 2: Pengirim / Kurir -->
            <td>
                <div class="sig-title">Pengirim / Ekspedisi,</div>
                <div class="stamp-space"></div>
                <br>
                <div class="sig-name">( .................................... )</div>
                <div class="sig-caption">Nama Terang</div>
            </td>

            <!-- Kolom 3: Admin / Logistik -->
            <td>
                <div class="sig-title">{{ $setting->signatory_city ?? $setting->company_city ?? 'Kota' }}, {{ date('d M Y', strtotime($document->date)) }}<br>Hormat Kami,</div>
                <div class="stamp-space">
                    @if($setting->stamp_delivery_note_enabled && $setting->company_stamp && \Illuminate\Support\Facades\Storage::disk('public')->exists($setting->company_stamp))
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->path($setting->company_stamp) }}" class="company-stamp" alt="Stempel perusahaan">
                    @endif
                </div>
                <br>
                <div class="sig-name">{{ strtoupper($setting->signatory_name ?? 'ADMIN / LOGISTIK') }}</div>
                <div class="sig-caption">{{ $setting->company_name ?? 'Nama Perusahaan' }}</div>
            </td>
        </tr>
    </table>

</body>
</html>