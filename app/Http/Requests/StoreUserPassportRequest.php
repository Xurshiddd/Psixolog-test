<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserPassportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'character_traits' => ['required', 'array', 'size:5'],
            'character_traits.*' => ['required', 'string', 'max:255'],
            'temperament_type' => ['required', 'string', 'max:255'],
            'conclusion' => ['required', 'string', 'max:5000'],
        ];
    }
}
