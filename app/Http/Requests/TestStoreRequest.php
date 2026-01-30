<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TestStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'shuffle' => 'required|boolean',
            'module' => 'required|string|max:255',
            'module_description' => 'nullable|string',
            'questions' => 'required|array|min:1',
            'questions.*.question' => 'required|string',
            'questions.*.type' => 'required|string|in:single,multi,text',
            'questions.*.options' => 'nullable|array',
            'questions.*.options.*.option_text' => 'required|string',
            'questions.*.options.*.option_value' => 'required|numeric',
            'questions.*.image' => 'nullable|image|max:5048',
        ];
    }
}
