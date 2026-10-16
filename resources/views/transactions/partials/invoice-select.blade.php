<div class="col-md-12">
    <label class="form-label fw-bold text-dark font-sm">Relasikan ke Invoice</label>
    <select name="invoice_id" id="invoiceSelect" class="form-select select2-invoice">
        <option value="">-- Umum / Operasional (tidak terikat invoice) --</option>
        @foreach($invoices as $invoice)
            <option value="{{ $invoice->id }}" {{ (string) old('invoice_id', $selectedInvoiceId ?? '') === (string) $invoice->id ? 'selected' : '' }}>
                {{ $invoice->document_number }}
                — {{ $invoice->customer->name ?? 'Tanpa Customer' }}
                ({{ $invoice->status }})
                · Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}
            </option>
        @endforeach
    </select>
    <small class="text-muted font-xs d-block mt-1">
        Opsional. Jika dipilih, kas ini dihitung sebagai biaya/pendapatan langsung proyek invoice tersebut. Kosongkan untuk biaya operasional umum.
    </small>
</div>
