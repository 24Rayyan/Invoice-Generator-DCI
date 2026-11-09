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
    <td class="text-center row-number fw-semibold text-muted small">{{ $index ?? 1 }}</td>
    <td>
        <input type="text" name="item_desc[]" class="form-control form-control-sm border-0 bg-light" value="{{ $row['desc'] ?? '' }}" placeholder="Nama barang / jasa..." required>
    </td>
    <td>
        <input type="text" name="item_notes[]" class="form-control form-control-sm border-0 bg-light item-specification-input" value="{{ $row['notes'] ?? '' }}" placeholder="Catatan opsional...">
    </td>
    <td>
        <input type="number" name="item_qty[]" class="form-control form-control-sm border-0 bg-light item-qty" value="{{ $row['qty'] ?? 1 }}" min="0" step="any" required>
    </td>
    <td class="item-type-column {{ $isPo ? 'd-none' : '' }}">
        <select name="item_type[]" class="form-select form-select-sm item-type-select" {{ $isPo ? 'disabled' : 'required' }}>
            @if($blankType)
                <option value="" disabled selected>Pilih tipe Barang/Jasa...</option>
            @endif
            <option value="A" {{ $type === 'A' ? 'selected' : '' }}>Barang (A)</option>
            <option value="B" {{ $type === 'B' ? 'selected' : '' }}>Jasa (B)</option>
        </select>
    </td>
    <td class="item-unit-column {{ $isPo ? 'd-none' : '' }}">
        <input type="text" name="item_unit[]" class="form-control form-control-sm border-0 bg-light" value="{{ $row['unit'] ?? 'Pcs' }}" placeholder="Pcs/Unit" {{ $isPo ? 'disabled' : 'required' }}>
    </td>
    <td class="item-price-column {{ $isPo ? 'd-none' : '' }}">
        <div class="input-group input-group-sm">
            <span class="input-group-text border-0 bg-light text-muted">{{ $currency }}</span>
            <input type="number" name="item_price[]" class="form-control border-0 bg-light item-price" value="{{ $row['price'] ?? '' }}" min="0" step="any" placeholder="0" {{ $isPo ? 'disabled' : 'required' }}>
        </div>
    </td>
    <td class="item-subtotal-column {{ $isPo ? 'd-none' : '' }}">
        <div class="input-group input-group-sm">
            <span class="input-group-text border-0 bg-light text-muted">{{ $currency }}</span>
            <input type="text" class="form-control border-0 bg-light fw-bold item-subtotal" readonly tabindex="-1" value="0">
        </div>
    </td>
    <td class="text-center">
        <button type="button" class="btn btn-sm btn-link text-danger p-1 remove-row" title="Hapus Baris" aria-label="Hapus baris item"><i class="fa-solid fa-xmark fs-6"></i></button>
    </td>
</tr>
