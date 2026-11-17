{{--
    Satu baris item dokumen. Dipakai server-side (baris awal) dan sebagai <template> untuk tombol "Tambah Item".
    Variabel: $row (array: desc, notes, qty, type, unit, price, subtotal), $isPo (bool), $currency (string)
--}}
@php
    $row = $row ?? [];
    // Baris baru -> default Barang (A). Dokumen lama tanpa tipe -> paksa pengguna memilih.
    $type = array_key_exists('type', $row) ? $row['type'] : 'A';
    $blankType = ! in_array($type, ['A', 'B'], true);
@endphp
<tr>
    <td class="text-center row-number fw-semibold text-muted small mc-num mc-o0">{{ $index ?? 1 }}</td>
    <td class="mc-o1 item-desc-cell" data-label="{{ $isPo ? 'Nama Barang' : 'Nama Barang / Jasa' }}">
        <input type="text" name="item_desc[]" class="form-control form-control-sm border-0 bg-light" value="{{ $row['desc'] ?? '' }}" placeholder="Nama barang / jasa..." required>
    </td>
    <td class="mc-o2 item-spec-cell" data-label="{{ $isPo ? 'Spesifikasi' : 'Keterangan' }}">
        <input type="text" name="item_notes[]" class="form-control form-control-sm border-0 bg-light item-specification-input" value="{{ $row['notes'] ?? '' }}" placeholder="Catatan opsional...">
    </td>
    <td class="mc-half mc-o3 item-qty-cell" data-label="{{ $isPo ? 'Kuantitas' : 'QTY' }}">
        <input type="number" name="item_qty[]" class="form-control form-control-sm border-0 bg-light item-qty" value="{{ $row['qty'] ?? 1 }}" min="0" step="any" required>
    </td>
    <td class="item-type-column mc-half mc-o5 {{ $isPo ? 'd-none' : '' }}" data-label="Tipe">
        <select name="item_type[]" class="form-select form-select-sm item-type-select" {{ $isPo ? 'disabled' : 'required' }}>
            @if($blankType)
                <option value="" disabled selected>Pilih tipe Barang/Jasa...</option>
            @endif
            <option value="A" {{ $type === 'A' ? 'selected' : '' }}>Barang (A)</option>
            <option value="B" {{ $type === 'B' ? 'selected' : '' }}>Jasa (B)</option>
        </select>
    </td>
    <td class="item-unit-column mc-half mc-o4 {{ $isPo ? 'd-none' : '' }}" data-label="Satuan">
        <input type="text" name="item_unit[]" class="form-control form-control-sm border-0 bg-light" value="{{ $row['unit'] ?? 'Pcs' }}" placeholder="Pcs/Unit" {{ $isPo ? 'disabled' : 'required' }}>
    </td>
    <td class="item-price-column mc-half mc-o6 {{ $isPo ? 'd-none' : '' }}" data-label="Harga Satuan">
        <div class="input-group input-group-sm">
            <span class="input-group-text border-0 bg-light text-muted">{{ $currency }}</span>
            <input type="number" name="item_price[]" class="form-control border-0 bg-light item-price" value="{{ $row['price'] ?? '' }}" min="0" step="any" placeholder="0" {{ $isPo ? 'disabled' : 'required' }}>
        </div>
    </td>
    <td class="item-subtotal-column mc-half mc-o7 {{ $isPo ? 'd-none' : '' }}" data-label="Subtotal">
        <div class="input-group input-group-sm">
            <span class="input-group-text border-0 bg-light text-muted">{{ $currency }}</span>
            <input type="text" class="form-control border-0 bg-light fw-bold item-subtotal" readonly tabindex="-1" value="0">
        </div>
    </td>
    <td class="text-center mc-remove">
        <button type="button" class="btn btn-sm btn-link text-danger p-1 remove-row" title="Hapus Baris" aria-label="Hapus baris item"><i class="fa-solid fa-xmark fs-6"></i></button>
    </td>
</tr>
