<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AttachProjectExpensesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'transaction_ids' => ['required', 'array', 'min:1'],
            'transaction_ids.*' => ['integer', 'distinct', 'exists:transactions,id'],
            'allocations' => ['sometimes', 'array'],
            'allocations.*' => ['nullable', 'numeric', 'decimal:0,2', 'min:0.01'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $selected = $this->input('transaction_ids', []);
            $allocations = $this->input('allocations', []);
            $selectedIds = is_array($selected) ? array_map('strval', $selected) : [];
            $allocationIds = is_array($allocations) ? array_map('strval', array_keys($allocations)) : [];

            if (array_diff($allocationIds, $selectedIds)) {
                $validator->errors()->add('allocations', 'Nominal alokasi hanya boleh diisi untuk transaksi yang dipilih.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'transaction_ids.required' => 'Pilih minimal satu transaksi kas yang akan dihubungkan.',
            'transaction_ids.min' => 'Pilih minimal satu transaksi kas yang akan dihubungkan.',
            'allocations.*.numeric' => 'Nominal alokasi harus berupa angka.',
            'allocations.*.decimal' => 'Nominal alokasi maksimal memiliki dua angka desimal.',
            'allocations.*.min' => 'Nominal alokasi harus lebih besar dari nol.',
        ];
    }
}
