<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DashboardModuleScoreExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Aniq ruxsat ro'yxati: `!== 'student'` shakli xodim va ishga qabul
        // qilinmagan nomzodni ham o'tkazib yuborardi.
        return auth()->check() && in_array(auth()->user()->role, ['admin', 'psiholog'], true);
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
