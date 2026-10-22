<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $categoryType = Category::query()
                ->whereKey($this->input('category_id'))
                ->value('type');
            $type = $this->input('type', $categoryType);

            if ($type !== 'expense') {
                return;
            }

            $invoiceId = $this->input('invoice_id') ?: null;
            $projectId = $this->input('project_id') ?: null;

            if ($invoiceId && $projectId) {
                $message = 'Kas keluar hanya dapat dihubungkan ke satu invoice atau project.';
                $validator->errors()->add('invoice_id', $message);
                $validator->errors()->add('project_id', $message);

                return;
            }

            $transaction = $this->route('transaction');

            if (! $transaction instanceof Transaction) {
                return;
            }

            $hasChangedInvoice = $invoiceId
                && (int) $invoiceId !== (int) $transaction->invoice_id;
            $hasChangedProject = $projectId
                && (int) $projectId !== (int) $transaction->project_id;

            if (($transaction->invoice_id || $transaction->project_id)
                && ($hasChangedInvoice || $hasChangedProject)) {
                $validator->errors()->add(
                    'invoice_id',
                    'Lepas hubungan kas saat ini terlebih dahulu sebelum menghubungkannya ke invoice atau project lain.'
                );
            }
        });
    }
}
