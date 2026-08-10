<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserPassportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        // Nomzod uchun passportda faqat xulosa bo'ladi — qobiliyatlar
        // ketma-ketligi va temperament tipi so'ralmaydi.
        if ($this->subjectRole() === 'guest') {
            return [
                'conclusion' => ['required', 'string', 'max:5000'],
            ];
        }

        return [
            'character_traits' => ['required', 'array', 'size:5'],
            'character_traits.*' => ['required', 'string', 'max:255'],
            'temperament_type' => ['required', 'string', 'max:255'],
            'conclusion' => ['required', 'string', 'max:5000'],
        ];
    }

    private function subjectRole(): ?string
    {
        $user = $this->route('user');

        return $user instanceof User ? $user->role : null;
    }
}
