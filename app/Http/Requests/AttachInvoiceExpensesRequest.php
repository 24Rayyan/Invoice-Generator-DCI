<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttachInvoiceExpensesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'transaction_ids'   => ['required', 'array', 'min:1'],
            'transaction_ids.*' => ['integer', 'distinct', 'exists:transactions,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'transaction_ids.required' => 'Pilih minimal satu transaksi kas yang akan dihubungkan.',
            'transaction_ids.min'      => 'Pilih minimal satu transaksi kas yang akan dihubungkan.',
        ];
    }
};
