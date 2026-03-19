<?php

namespace App\Http\Resources\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'login' => $this->login,
            'email' => $this->email,
            'phone' => $this->phone,
            'picture' => $this->picture,
            'birth_date' => $this->birth_date,
            'level' => $this->level,
            'role' => $this->role,
            'education_type_code' => $this->education_type_code,
            'education_type_name' => $this->education_type_name,
            'education_form_code' => $this->education_form_code,
            'education_form_name' => $this->education_form_name,
            'group' => $this->group ? [
                'id' => $this->group->id,
                'name' => $this->group->name,
                'code' => $this->group->code,
            ] : null,
            'speciality' => $this->speciality ? [
                'id' => $this->speciality->id,
                'name' => $this->speciality->name,
                'code' => $this->speciality->code,
            ] : null,
            'faculity' => $this->faculity ? [
                'id' => $this->faculity->id,
                'name' => $this->faculity->name,
                'code' => $this->faculity->code,
            ] : null,
            'categories' => $this->whenLoaded('usersCategory', fn () => $this->usersCategory
                ->map(fn ($category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                ])->values()),
        ];
    }
}
