<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncStudentCategoriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'category_ids' => ['array'],
            'category_ids.*' => ['exists:categories,id'],
        ];
    }
}
