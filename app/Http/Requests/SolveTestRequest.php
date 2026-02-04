<?php

namespace App\Http\Requests;
use App\Models\Module;
use Illuminate\Foundation\Http\FormRequest;

class SolveTestRequest extends FormRequest
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
        $test_count = Module::find($this->module_id)->tests->count();
        return [
            'module_id' => 'required|exists:modules,id',
            'answers' => 'required|array|min:'.$test_count,
            'answers.*' => 'required',
        ];
    }
}
