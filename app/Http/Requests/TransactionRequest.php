<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TransactionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->isMethod('POST') && $this->query->has('type')) {
            $this->merge(['type' => $this->query('type')]);
        }
    }

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
                $this->isMethod('POST') || ! $this->user()->hasAccess('invoice_margin') ? 'prohibited' : 'nullable',
                Rule::exists('documents', 'id')->where('type', 'INVOICE'),
            ],
            'project_id'        => ['prohibited'],
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
            if ($this->isMethod('POST')) {
                $categoryType = Category::query()
                    ->whereKey($this->input('category_id'))
                    ->value('type');

                if ($categoryType && $categoryType !== $this->input('type')) {
                    $validator->errors()->add('category_id', 'Kategori akun harus sesuai dengan tipe transaksi yang dipilih.');
                }

                return;
            }

            $categoryType = Category::query()
                ->whereKey($this->input('category_id'))
                ->value('type');
            $type = $this->input('type', $categoryType);

            if ($type !== 'expense') {
                return;
            }

            $invoiceId = $this->input('invoice_id') ?: null;
            $transaction = $this->route('transaction');

            if (! $transaction instanceof Transaction) {
                return;
            }

            $hasChangedInvoice = $invoiceId
                && (int) $invoiceId !== (int) $transaction->invoice_id;
            $categoryType = Category::query()->whereKey($this->input('category_id'))->value('type');
            $hasProjectAllocations = $transaction->projects()->exists();
            $allocatedAmountCents = (int) round(
                (float) $transaction->projects()->sum('project_transaction_allocations.allocated_amount') * 100
            );

            if ($hasProjectAllocations
                && ($categoryType !== 'expense'
                    || (int) round((float) $this->input('amount', $transaction->amount) * 100)
                        < $allocatedAmountCents)) {
                $validator->errors()->add(
                    'amount',
                    'Nominal transaksi tidak boleh lebih kecil dari total alokasi ke proyek.'
                );
            }

            if ($hasProjectAllocations && $invoiceId) {
                $validator->errors()->add('invoice_id', 'Lepas seluruh alokasi proyek sebelum menghubungkan transaksi ke invoice.');
            }

            if ($transaction->invoice_id && $hasChangedInvoice) {
                $validator->errors()->add(
                    'invoice_id',
                    'Lepas hubungan kas saat ini terlebih dahulu sebelum menghubungkannya ke invoice lain.'
                );
            }
        });
    }
}
