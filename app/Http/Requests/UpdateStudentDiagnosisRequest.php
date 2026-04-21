<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentDiagnosisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && in_array(auth()->user()->role, ['admin', 'psiholog'], true);
    }

    public function rules(): array
    {
        return [
            'diagnosis' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
