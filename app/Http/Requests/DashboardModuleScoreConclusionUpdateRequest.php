<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DashboardModuleScoreConclusionUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role !== 'student';
    }

    public function rules(): array
    {
        return [
            'report_module_id' => ['required', 'integer', 'exists:modules,id'],
            'min_score' => ['required', 'integer', 'min:0'],
            'max_score' => ['required', 'integer', 'min:0'],
            'result_real' => ['required', 'string', 'max:10000'],
            'overwrite_auto_conclusion' => ['required', 'boolean'],
        ];
    }
}
