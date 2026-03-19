<?php

namespace App\Http\Resources\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentModuleSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $existingResult = $this->relationLoaded('usersTestsResults')
            ? $this->usersTestsResults->first()
            : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'shuffle' => (bool) $this->shuffle,
            'is_active' => (bool) $this->is_active,
            'test_count' => $this->whenCounted('tests', $this->tests_count),
            'submitted' => (bool) $existingResult,
            'existing_result' => $existingResult ? [
                'result_fake' => $existingResult->pivot?->result_fake,
                'result_real' => $existingResult->pivot?->result_real,
                'diagnosis' => $existingResult->pivot?->diagnosis,
            ] : null,
        ];
    }
}
