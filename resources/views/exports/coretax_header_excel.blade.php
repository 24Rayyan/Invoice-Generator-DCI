<table>
    <!-- Row 1: Header NPWP Penjual -->
    <thead>
        <tr style="background-color: #ffffff !important;">
            <td style="background-color: #ffffff !important; color: #000000 !important; font-weight: bold; text-align: left; border: none;">NPWP Penjual</td>
            <td></td>
            <td style="background-color: #ffffff !important; color: #000000 !important; font-weight: bold; text-align: left; border: none; mso-number-format:'\@';">
                {{ str_pad(preg_replace('/[^0-9]/', '', $setting->company_npwp16 ?? '0123456789012345'), 16, '0', STR_PAD_LEFT) }}
            </td>
            <td colspan="15" style="background-color: #ffffff !important; border: none;"></td>
        </tr>
        <!-- Row 2: Baris Kosong Separator -->
        <tr style="background-color: #ffffff !important;">
            <td colspan="18" style="background-color: #ffffff !important; border: none;"></td>
        </tr>
        <!-- Row 3: Header Kolom Utama -->
        <tr>
            <th>Baris</th>
            <th>Tanggal Faktur</th>
            <th>Jenis Faktur</th>
            <th>Kode Transaksi</th>
            <th>Keterangan Tambahan</th>
            <th>Dokumen Pendukung</th>
            <th>Period Dok Pendukung</th>
            <th>Referensi</th>
            <th>Cap Fasilitas</th>
            <th>ID TKU Penjual</th>
            <th>NPWP/NIK Pembeli</th>
            <th>Jenis ID Pembeli</th>
            <th>Negara Pembeli</th>
            <th>Nomor Dokumen Pembeli</th>
            <th>Nama Pembeli</th>
            <th>Alamat Pembeli</th>
            <th>Email Pembeli</th>
            <th>ID TKU Pembeli</th>
        </tr>
    </thead>
    <tbody>
        @php $barisIndex = 1; @endphp
        @foreach($documents as $doc)
            @php
                // Kode Transaksi
                $customerFpCode = $doc->customer?->default_tax_transaction_code ?? null;
                $rawTrxCode = (!empty($customerFpCode)) ? $customerFpCode : ($doc->tax_transaction_code ?? '04');
                $trxCode = substr(preg_replace('/[^0-9]/', '', $rawTrxCode), 0, 2);
                if (empty($trxCode)) $trxCode = '04';

                // Jenis ID Pembeli & Handling Paspor/NIK
                $customerTaxType = strtoupper($doc->customer?->tax_id_type ?? '');
                
                if ($customerTaxType === 'PASPOR' || $customerTaxType === 'PASSPORT') {
                    $idType = 'PASSPORT';
                    $buyerNpwp = '0000000000000000';
                    $passportNo = $doc->customer?->passport_number ?? '-';
                } else {
                    $idType = 'TIN';
                    $rawNpwp = preg_replace('/[^0-9]/', '', $doc->customer?->tax_id_number ?? '0');
                    $buyerNpwp = str_pad($rawNpwp, 16, '0', STR_PAD_LEFT);
                    $passportNo = '-';
                }

                // Tanggal Faktur
                $rawTaxDate = !empty($customTaxDate) 
                    ? $customTaxDate 
                    : (!empty($doc->tax_invoice_date) ? $doc->tax_invoice_date : $doc->date);
                $taxDate = date('d/m/Y', strtotime($rawTaxDate));

                // Clean & Pad Identitas Penjual & Pembeli (NITKU / TKU 22 digit)
                $sellerTkuRaw = preg_replace('/[^0-9]/', '', $setting->company_nitku ?? '1000000009806122000000');
                $sellerTku = str_pad($sellerTkuRaw, 22, '0', STR_PAD_LEFT);

                $buyerTkuRaw = preg_replace('/[^0-9]/', '', $doc->customer?->nitku ?? ($buyerNpwp . '000000'));
                $buyerTku = str_pad($buyerTkuRaw, 22, '0', STR_PAD_LEFT);
            @endphp
            <tr>
                <!-- Baris -->
                <td>{{ $barisIndex++ }}</td>
                
                <!-- Tanggal Faktur -->
                <td>{{ $taxDate }}</td>
                
                <!-- Jenis Faktur -->
                <td>Normal</td>
                
                <!-- Kode Transaksi -->
                <td style="mso-number-format:'\@';">{{ $trxCode }}</td>
                
                <!-- Keterangan Tambahan -->
                <td>{{ $doc->additional_info ?? '' }}</td>
                
                <!-- Dokumen Pendukung -->
                <td>{{ $doc->supporting_doc ?? '' }}</td>
                
                <!-- Period Dok Pendukung -->
                <td>{{ $doc->supporting_doc_period ?? '' }}</td>
                
                <!-- Referensi -->
                <td style="mso-number-format:'\@';">{{ $doc->document_number }}</td>
                
                <!-- Cap Fasilitas -->
                <td>{{ $doc->facility_stamp ?? '' }}</td>
                
                <!-- ID TKU Penjual (22 digit) -->
                <td style="mso-number-format:'\@';">{{ $sellerTku }}</td>
                
                <!-- NPWP/NIK Pembeli (16 digit) -->
                <td style="mso-number-format:'\@';">{{ $buyerNpwp }}</td>
                
                <!-- Jenis ID Pembeli -->
                <td>{{ $idType }}</td>
                
                <!-- Negara Pembeli -->
                <td>{{ $doc->customer?->country_code ?? 'IDN' }}</td>
                
                <!-- Nomor Dokumen Pembeli -->
                <td style="mso-number-format:'\@';">{{ $passportNo }}</td>
                
                <!-- Nama Pembeli -->
                <td>{{ $doc->customer?->name }}</td>
                
                <!-- Alamat Pembeli -->
                <td>{{ $doc->customer?->address ?? '-' }}</td>
                
                <!-- Email Pembeli -->
                <td>{{ $doc->customer?->email ?? '' }}</td>
                
                <!-- ID TKU Pembeli (22 digit) -->
                <td style="mso-number-format:'\@';">{{ $buyerTku }}</td>
            </tr>
        @endforeach
        <tr>
            <td>END</td>
        </tr>
    </tbody>
</table>