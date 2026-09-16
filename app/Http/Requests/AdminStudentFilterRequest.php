<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminStudentFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'faculity_id' => ['nullable', 'integer', 'exists:faculities,id'],
            'speciality_id' => ['nullable', 'integer', 'exists:specialities,id'],
            'group_id' => ['nullable', 'integer', 'exists:groups,id'],
            'level' => ['nullable', 'string', 'max:255'],
            'test_status' => ['nullable', Rule::in(['submitted', 'not_submitted'])],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'passport_status' => ['nullable', Rule::in(['exists', 'not_exists'])],
            'tested_from' => ['nullable', 'date_format:Y-m-d'],
            'tested_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:tested_from'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
