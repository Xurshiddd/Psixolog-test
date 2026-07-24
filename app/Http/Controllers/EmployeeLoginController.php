<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use DateTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class EmployeeLoginController extends Controller
{
    /**
     * F.I.SH bo'yicha hodimlarni qidiradi. HEMIS orqali kira olmaydigan,
     * lekin sinxronlangan hodimlar shu ro'yxatdan o'zini tanlab kiradi.
     */
    public function search(Request $request): JsonResponse
    {
        $data = $request->validate([
            'query' => ['required', 'string', 'min:3', 'max:100'],
        ]);

        $term = trim($data['query']);

        $employees = User::query()
            ->where('role', 'employee')
            // Katta-kichik harfga sezgir emas (Postgres'da LIKE sezgir).
            ->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($term).'%'])
            ->with('employee:id,user_id,department_name,staff_position,login_activated_at')
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'picture']);

        return response()->json([
            'employees' => $employees->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'picture' => $user->picture,
                'department' => $user->employee?->department_name,
                'position' => data_get($user->employee?->staff_position, 'name'),
                // Birinchi kirishmi? (parol hali tug'ilgan kun orqali o'rnatilmagan)
                'needs_activation' => $user->employee?->login_activated_at === null,
            ])->all(),
        ]);
    }

    /**
     * Hodimni platformaga kiritadi.
     *
     * Birinchi kirish: `birth_date` (kun/oy/yil) HEMIS'dagi tug'ilgan kun bilan
     * tasdiqlanadi, `password` esa yangi parol sifatida saqlanadi.
     * Keyingi kirishlar: `password` — o'sha yangi parol.
     */
    public function authenticate(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'password' => ['required', 'string', 'min:4', 'max:100'],
            'birth_date' => ['nullable', 'string', 'max:20'],
        ]);

        $user = User::query()->with('employee')->findOrFail($data['user_id']);

        if ($user->role !== 'employee') {
            throw ValidationException::withMessages([
                'password' => 'Bu foydalanuvchi hodim sifatida kira olmaydi.',
            ]);
        }

        $activated = $user->employee?->login_activated_at !== null;

        if ($activated) {
            $this->loginWithPassword($user, $data['password']);
        } else {
            $this->activateAndLogin($user, (string) ($data['birth_date'] ?? ''), $data['password']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    private function loginWithPassword(User $user, string $password): void
    {
        if (! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Parol noto\'g\'ri.',
            ]);
        }

        Auth::login($user);
    }

    /**
     * Birinchi kirish: tug'ilgan kunni tasdiqlaydi, yangi parolni saqlaydi va
     * kirishni faollashtiradi.
     */
    private function activateAndLogin(User $user, string $birthDateInput, string $newPassword): void
    {
        $stored = $this->canonicalizeStored($user->birth_date);

        if ($stored === null) {
            throw ValidationException::withMessages([
                'birth_date' => 'Tug\'ilgan kun ma\'lumoti topilmadi. Administratorga murojaat qiling.',
            ]);
        }

        $entered = $this->canonicalizeDate($birthDateInput);

        if ($entered === null) {
            throw ValidationException::withMessages([
                'birth_date' => 'Sanani kun/oy/yil ko\'rinishida kiriting (masalan: 20/05/1990).',
            ]);
        }

        if ($stored !== $entered) {
            throw ValidationException::withMessages([
                'birth_date' => 'Tug\'ilgan kun mos kelmadi.',
            ]);
        }

        // Foydalanuvchi tanlagan yangi parolni saqlaymiz va kirishni faollashtiramiz.
        $user->forceFill(['password' => Hash::make($newPassword)])->save();

        Employee::updateOrCreate(
            ['user_id' => $user->id],
            ['login_activated_at' => now()],
        );

        Auth::login($user);
    }

    /**
     * Foydalanuvchi kiritgan sanani (kun/oy/yil va boshqa ko'rinishlar) Y-m-d
     * ga keltiradi. Noto'g'ri sana bo'lsa null qaytaradi.
     */
    private function canonicalizeDate(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        foreach (['d/m/Y', 'd.m.Y', 'd-m-Y', 'Y-m-d', 'Y/m/d'] as $format) {
            $dt = DateTime::createFromFormat('!'.$format, $value);

            if ($dt === false) {
                continue;
            }

            $errors = DateTime::getLastErrors();

            if ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0)) {
                return $dt->format('Y-m-d');
            }
        }

        return null;
    }

    /**
     * Bazadagi tug'ilgan kunni Y-m-d ga keltiradi (Y-m-d yoki unix timestamp).
     */
    private function canonicalizeStored(mixed $birthDate): ?string
    {
        if (blank($birthDate)) {
            return null;
        }

        if (is_numeric($birthDate)) {
            return Carbon::createFromTimestamp((int) $birthDate)->toDateString();
        }

        return $this->canonicalizeDate((string) $birthDate);
    }
}
