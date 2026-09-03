<?php

namespace App\Http\Requests;

use App\Application\Dashboard\Data\ReportAudience;
use App\Support\RiskFlag;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class DashboardModuleScoreConclusionUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role !== 'student';
    }

    public function rules(): array
    {
        $rules = [
            'report_module_id' => ['required', 'integer', 'exists:modules,id'],
            'min_score' => ['required', 'integer', 'min:0'],
            'max_score' => ['required', 'integer', 'min:0'],
            'conclusions' => ['required', 'array'],
            'flags' => ['nullable', 'array'],
            'overwrite_auto_conclusion' => ['required', 'boolean'],
        ];

        foreach (ReportAudience::roles() as $role) {
            $rules["conclusions.{$role}"] = ['nullable', 'string', 'max:10000'];
            $rules["flags.{$role}"] = ['nullable', Rule::in(RiskFlag::values())];
        }

        return $rules;
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->filledConclusions() === [] && $this->selectedFlags() === []) {
                    $validator->errors()->add(
                        'conclusions',
                        'Kamida bitta toifa uchun avtomatik xulosa yozing yoki bayroq tanlang.'
                    );
                }
            },
        ];
    }

    /**
     * Faqat to'ldirilgan toifalar: ['student' => '...', 'employee' => '...'].
     *
     * @return array<string, string>
     */
    public function filledConclusions(): array
    {
        $conclusions = [];

        foreach (ReportAudience::roles() as $role) {
            $conclusion = trim((string) $this->input("conclusions.{$role}", ''));

            if ($conclusion !== '') {
                $conclusions[$role] = $conclusion;
            }
        }

        return $conclusions;
    }

    /**
     * Faqat tanlangan bayroqlar: ['student' => 'red', ...].
     *
     * @return array<string, string>
     */
    public function selectedFlags(): array
    {
        $flags = [];

        foreach (ReportAudience::roles() as $role) {
            $flag = $this->input("flags.{$role}");

            if (RiskFlag::isValid($flag)) {
                $flags[$role] = $flag;
            }
        }

        return $flags;
    }
}
