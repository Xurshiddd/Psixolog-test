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

    /** Qobiliyatlar ketma-ketligi va temperament faqat talaba passportida bo'ladi. */
    private const TRAIT_ROLES = ['student'];

    public function rules(): array
    {
        // Xodim va nomzod passportida faqat xulosa bo'ladi.
        if (! in_array($this->subjectRole(), self::TRAIT_ROLES, true)) {
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
