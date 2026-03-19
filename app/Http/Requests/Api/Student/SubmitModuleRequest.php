<?php

namespace App\Http\Requests\Api\Student;

use App\Models\Module;
use Illuminate\Foundation\Http\FormRequest;

class SubmitModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $module = $this->route('module');
        $testCount = $module instanceof Module ? $module->tests()->count() : 0;

        return [
            'answers' => ['required', 'array', 'size:'.$testCount],
            'answers.*' => ['required'],
        ];
    }
}
