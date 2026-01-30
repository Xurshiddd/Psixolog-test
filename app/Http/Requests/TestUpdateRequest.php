<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TestUpdateRequest extends FormRequest
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
            'id' => 'required|exists:modules,id',
            'shuffle' => 'required|boolean',
            'module' => 'required|string|max:255',
            'module_description' => 'nullable|string',
            'questions' => 'required|array|min:1',
            'questions.*.id' => 'nullable|exists:tests,id',
            'questions.*.question' => 'required|string',
            'questions.*.question_image' => 'nullable|string|image|max:5048',
            'questions.*.type' => 'required|string|in:single,multi,text',
            'questions.*.options' => 'nullable|array',
            'questions.*.options.*.id' => 'nullable|exists:options,id',
            'questions.*.options.*.option_text' => 'required|string',
            'questions.*.options.*.option_value' => 'required|numeric',
        ];
    }
}
