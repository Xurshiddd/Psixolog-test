<?php

namespace App\Http\Resources\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class StudentTestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question' => $this->question,
            'type' => $this->type,
            'image_url' => $this->image
                ? url(Storage::disk('public')->url($this->image))
                : null,
            'options' => StudentTestOptionResource::collection($this->whenLoaded('options')),
        ];
    }
}
