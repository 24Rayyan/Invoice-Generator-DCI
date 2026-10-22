<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'type'              => [$this->isMethod('POST') ? 'required' : 'sometimes', 'in:income,expense'],
            'category_id'       => ['required', 'exists:categories,id'],
            'amount'            => ['required', 'numeric', 'min:1'],
            'transaction_date'  => ['required', 'date'],
            'payment_method'    => ['required', 'string'],
            'description'       => ['nullable', 'string'],
            'invoice_id'        => [
                $this->user()->hasAccess('invoice_margin') ? 'nullable' : 'prohibited',
                Rule::exists('documents', 'id')->where('type', 'INVOICE'),
            ],
            'project_id'        => [
                $this->user()->hasAccess('projects') ? 'nullable' : 'prohibited',
                'integer',
                'exists:projects,id',
            ],
            'receipt_file'      => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,webp', 'max:5120'],
            'event_date'        => ['nullable', 'date'],
            'location'          => ['nullable', 'string', 'max:255'],
            'attendee_name'     => ['nullable', 'string', 'max:255'],
            'attendee_company'  => ['nullable', 'string', 'max:255'],
            'attendee_position' => ['nullable', 'string', 'max:255'],
            'purpose'           => ['nullable', 'string'],
        ];
    }
}
