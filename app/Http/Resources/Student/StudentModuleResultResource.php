<?php

namespace App\Http\Resources\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentModuleResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'module_id' => $this->id,
            'module_name' => $this->name,
            'test_count' => $this->whenCounted('tests', $this->tests_count),
            'result_fake' => $this->pivot?->result_fake,
            'result_real' => $this->pivot?->result_real,
            'diagnosis' => $this->pivot?->diagnosis,
            'submitted_at' => $this->pivot?->created_at,
        ];
    }
}
