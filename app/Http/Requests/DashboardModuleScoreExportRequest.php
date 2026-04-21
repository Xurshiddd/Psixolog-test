<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DashboardModuleScoreExportRequest extends FormRequest
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
        ];
    }
}
