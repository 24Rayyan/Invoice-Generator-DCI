<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttachProjectInvoicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'invoice_ids' => ['required', 'array', 'min:1'],
            'invoice_ids.*' => ['integer', 'distinct', 'exists:documents,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'invoice_ids.required' => 'Pilih minimal satu invoice yang akan dihubungkan.',
            'invoice_ids.min' => 'Pilih minimal satu invoice yang akan dihubungkan.',
        ];
    }
}
