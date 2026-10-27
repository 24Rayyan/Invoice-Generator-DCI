<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Purchase Order - {{ $document->document_number }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #1e293b; line-height: 1.35; }
        .header { text-align: center; margin-bottom: 25px; border-bottom: 2px solid #0f172a; padding-bottom: 12px; }
        .logo { max-height: 45px; max-width: 180px; object-fit: contain; margin-bottom: 8px; }
        .company-title { font-size: 13pt; font-weight: 800; color: #0f172a; }
        .company-info { font-size: 8.5pt; color: #475569; }
        
        .info-table { width: 100%; margin-bottom: 15px; font-size: 9pt; }
        .info-table td { padding: 3px 4px; vertical-align: top; }
        
        .item-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 9pt; }
        .item-table th, .item-table td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
        .item-table th { background-color: #1e40af; color: #ffffff; text-align: center; text-transform: uppercase; font-size: 8pt; }
        
        .signature-area { width: 100%; margin-top: 30px; }
        .signature-box { text-align: right; float: right; width: 40%; }
        .signer-name { font-weight: 700; border-bottom: 1px solid #94a3b8; display: inline-block; padding: 0 10px 2px 10px; }
    </style>
</head>
<body>
    <div class="header">
        @if($setting && $setting->company_logo && file_exists(public_path('storage/' . $setting->company_logo)))
            <img src="{{ public_path('storage/' . $setting->company_logo) }}" class="logo"><br>
        @else
            <div class="company-title">{{ strtoupper($setting->company_name ?? 'NAMA PERUSAHAAN') }}</div>
        @endif
        
        <div class="company-info">{{ $setting->company_address ?? 'Alamat Belum Diset' }}</div>
        <div class="company-info">Email: {{ $setting->company_email ?? '-' }} | Telp: {{ $setting->company_phone ?? '-' }}</div>
        
        <h3 style="margin-top: 10px; text-decoration: underline; letter-spacing: 0.5px;">PURCHASE ORDER</h3>
    </div>

    <table class="info-table">
        <tr>
            <td width="15%"><strong>Kepada</strong></td>
            <td width="35%">: {{ $document->customer->name }}</td>
            <td width="15%"><strong>No. PO</strong></td>
            <td width="35%">: {{ $document->document_number }}</td>
        </tr>
        <tr>
            <td><strong>Alamat</strong></td>
            <td>: {{ $document->customer->address ?? '-' }}</td>
            <td><strong>Tanggal</strong></td>
            <td>: {{ date('d M Y', strtotime($document->date)) }}</td>
        </tr>
    </table>

    <table class="item-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="40%">Nama Barang</th>
                <th width="40%">Spesifikasi</th>
                <th width="15%">Kuantitas</th>
            </tr>
        </thead>
        <tbody>
            @foreach($document->items as $index => $item)
            <tr>
                <td style="text-align: center;">{{ $index + 1 }}</td>
                <td>{{ $item->description }}</td>
                <td>{{ $item->notes ?? '-' }}</td>
                <td style="text-align: center;">{{ $item->qty }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="signature-area">
        <div class="signature-box">
            <div style="font-size: 8.5pt; color: #64748b;">{{ $setting->signatory_city ?? $setting->company_city ?? 'Kota' }}, {{ date('d M Y', strtotime($document->date)) }}<br>Disetujui Oleh,</div>
            <br><br><br><br>
            <div class="signer-name">{{ $setting->signatory_name ?? 'Nama Pejabat' }}</div>
            <div style="font-size: 8pt; color: #64748b; margin-top: 2px;">{{ $setting->signatory_position ?? 'Jabatan' }}</div>
        </div>
    </div>
</body>
</html>