<table>
    <thead>
        <tr>
            <th>Baris</th>
            <th>Barang/Jasa</th>
            <th>Kode Barang Jasa</th>
            <th>Nama Barang/Jasa</th>
            <th>Nama Satuan Ukur</th>
            <th>Harga Satuan</th>
            <th>Jumlah Barang Jasa</th>
            <th>Total Diskon</th>
            <th>DPP</th>
            <th>DPP Nilai Lain</th>
            <th>Tarif PPN</th>
            <th>PPN</th>
            <th>Tarif PPnBM</th>
            <th>PPnBM</th>
        </tr>
    </thead>
    <tbody>
        @foreach($documents as $docIndex => $doc)
            @php
                $taxRatePercent = ($setting->enable_tax ?? true) ? ($setting->default_tax_rate ?? 12) : 0;
                
                $customerFpCode = $doc->customer?->default_tax_transaction_code ?? null;
                $rawTrxCode = (!empty($customerFpCode)) ? $customerFpCode : ($doc->tax_transaction_code ?? '04');
                
                $trxCode = substr(preg_replace('/[^0-9]/', '', $rawTrxCode), 0, 2);
                if (empty($trxCode)) $trxCode = '04';

                $rowNumber = $docIndex + 1;
            @endphp
            
            @foreach($doc->items as $item)
                @php
                    $itemDpp = (float) $item->subtotal;
                    
                    $opt = \App\Services\CoretaxXmlService::determineItemOpt($item);
                    $unitCode = \App\Services\CoretaxXmlService::getUnitCode($opt);
                    $itemCode = $item->coretax_code ?? '000000';
                    
                    $typeCategory = \App\Services\CoretaxXmlService::determineItemOpt($item);
                    
                    $isTrx04 = ($trxCode === '04');
                    
                    if ($isTrx04) {
                        $dppNilaiLain = round(($itemDpp * 11) / 12, 2);
                        $displayTaxRate = $taxRatePercent > 0 ? 12 : 0;
                        $ppnAmount = round($dppNilaiLain * ($displayTaxRate / 100), 2);
                    } else {
                        $dppNilaiLain = 0;
                        $ppnAmount = round($itemDpp * ($taxRatePercent / 100), 2);
                        $displayTaxRate = $taxRatePercent;
                    }
                @endphp
                <tr>
                    {{-- 1. Baris --}}
                    <td>{{ (int)$rowNumber }}</td>
                    
                    {{-- 2. Barang/Jasa --}}
                    <td>{{ $typeCategory }}</td>
                    
                    {{-- 3. Kode Barang Jasa --}}
                    <td style='mso-number-format:"\@";'>{{ $itemCode }}</td>
                    
                    {{-- 4. Nama Barang/Jasa --}}
                    <td>{{ $item->description }}</td>
                    
                    {{-- 5. Nama Satuan Ukur --}}
                    <td style='mso-number-format:"\@";'>{{ $unitCode }}</td>
                    
                    {{-- 6. Harga Satuan (RAW FLOAT) --}}
                    <td>{{ (float)$item->price }}</td>
                    
                    {{-- 7. Jumlah Barang Jasa --}}
                    <td>{{ (int)$item->qty }}</td>
                    
                    {{-- 8. Total Diskon --}}
                    <td>0</td>
                    
                    {{-- 9. DPP (RAW FLOAT) --}}
                    <td>{{ (float)$itemDpp }}</td>
                    
                    {{-- 10. DPP NILAI LAIN (RAW FLOAT) --}}
                    <td>{{ (float)$dppNilaiLain }}</td>
                    
                    {{-- 11. Tarif PPN --}}
                    <td>{{ (int)$displayTaxRate }}</td>
                    
                    {{-- 12. PPN (RAW FLOAT) --}}
                    <td>{{ (float)$ppnAmount }}</td>
                    
                    {{-- 13. Tarif PPnBM --}}
                    <td>0</td>
                    
                    {{-- 14. PPnBM --}}
                    <td>0</td>
                </tr>
            @endforeach
        @endforeach
        <tr>
            <td>END</td>
        </tr>
    </tbody>
</table>