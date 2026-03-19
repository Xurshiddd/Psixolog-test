<?php

namespace App\Http\Resources\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentModuleDetailResource extends JsonResource
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
            'has_submitted' => (bool) $existingResult,
            'existing_result' => $existingResult ? new StudentModuleResultResource($existingResult) : null,
            'tests' => StudentTestResource::collection($this->whenLoaded('tests')),
        ];
    }
}
