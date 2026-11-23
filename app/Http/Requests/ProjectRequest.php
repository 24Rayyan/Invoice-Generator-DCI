<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $rules = [
            'project_name' => ['required', 'string', 'max:255'],
            'client_id' => ['nullable', 'integer', 'exists:customers,id'],
            'status' => ['required', 'in:active,on_hold,completed'],
        ];

        if ($this->isMethod('post')) {
            $rules['invoice_ids'] = ['sometimes', 'array'];
            $rules['invoice_ids.*'] = [
                'integer',
                'distinct',
                Rule::exists('documents', 'id')
                    ->where('type', 'INVOICE')
                    ->whereNull('project_id'),
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'invoice_ids.*.exists' => 'Invoice yang dipilih sudah terhubung ke proyek lain atau tidak tersedia.',
        ];
    }
}
