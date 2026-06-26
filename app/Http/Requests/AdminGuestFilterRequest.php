<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminGuestFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'desired_position' => ['nullable', 'string', 'max:255'],
            'application_status' => ['nullable', 'string', 'max:255'],
            'test_status' => ['nullable', Rule::in(['submitted', 'not_submitted'])],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
