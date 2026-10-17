<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'project_name' => ['required', 'string', 'max:255'],
            'client_id' => ['nullable', 'integer', 'exists:customers,id'],
            'status' => ['required', 'in:active,on_hold,completed'],
        ];
    }
}
