<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserPassport;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * Ijtimoiy-psixologik passportni saqlash va PDF qilib berish. Talaba,
 * xodim hamda ishga qabul qilinmagan nomzod uchun bitta oqim ishlatiladi,
 * faqat hujjatdagi matn va ma'lumot qatorlari role bo'yicha farq qiladi.
 */
class UserPassportService
{
    private const SUPPORTED_ROLES = ['student', 'employee', 'guest'];

    public function __construct(
        private PassportPdfService $passportPdfService,
    ) {}

    /**
     * Nomzodda `character_traits` va `temperament_type` kelmaydi — u yerda
     * passport faqat xulosadan iborat bo'ladi.
     *
     * @param  array{
     *     character_traits?: array<int, string>,
     *     temperament_type?: string,
     *     conclusion: string
     * }  $validated
     */
    public function downloadGeneratedPassport(User $user, array $validated)
    {
        $this->ensureSupportedRole($user);

        $passport = UserPassport::updateOrCreate(
            ['user_id' => $user->id],
            [
                'character_traits' => isset($validated['character_traits'])
                    ? array_map(
                        static fn (string $trait): string => trim($trait),
                        $validated['character_traits']
                    )
                    : null,
                'temperament_type' => isset($validated['temperament_type'])
                    ? trim($validated['temperament_type'])
                    : null,
                'conclusion' => trim($validated['conclusion']),
            ]
        );

        return $this->download($user, $passport);
    }

    public function downloadSavedPassport(User $user)
    {
        $this->ensureSupportedRole($user);

        $user->load('passport');

        abort_if($user->passport === null, Response::HTTP_NOT_FOUND, 'Passport ma\'lumoti topilmadi.');

        return $this->download($user, $user->passport);
    }

    private function download(User $user, UserPassport $passport)
    {
        $pdf = $this->passportPdfService->generatePassportPdf(
            $user,
            [
                'character_traits' => $passport->character_traits ?? [],
                'temperament_type' => $passport->temperament_type,
                'conclusion' => $passport->conclusion,
            ],
            $this->profile($user)
        );

        return $pdf->download(
            'ijtimoiy-psixologik-passport_'.Str::slug($user->name ?: 'foydalanuvchi').'.pdf'
        );
    }

    /**
     * @return array{role_label: string, intro: string, conclusion_title: string, show_photo: bool, show_traits: bool, rows: list<array{label: string, value: string}>}
     */
    private function profile(User $user): array
    {
        return match ($user->role) {
            'employee' => $this->employeeProfile($user),
            'guest' => $this->guestProfile($user),
            default => $this->studentProfile($user),
        };
    }

    /**
     * @return array{role_label: string, intro: string, conclusion_title: string, show_photo: bool, show_traits: bool, rows: list<array{label: string, value: string}>}
     */
    private function studentProfile(User $user): array
    {
        $user->load(['group', 'speciality', 'usersCategory']);

        return [
            'role_label' => 'Talabaning ijtimoiy-psixologik profili',
            'intro' => 'Mazkur passport talabaning umumiy ma’lumotlari hamda psixologik tavsifini '
                .'rasmiy ko‘rinishda aks ettirish uchun tayyorlandi.',
            'conclusion_title' => 'Talaba bo‘yicha xulosa',
            'show_photo' => true,
            'show_traits' => true,
            'rows' => [
                ['label' => 'Login', 'value' => $this->value($user->login)],
                ['label' => 'Telefon', 'value' => $this->value($user->phone)],
                ['label' => 'Guruh', 'value' => $this->value($user->group?->name)],
                ['label' => 'Yo‘nalish', 'value' => $this->value($user->speciality?->name)],
                ['label' => 'Kategoriyalar', 'value' => $this->categories($user)],
            ],
        ];
    }

    /**
     * @return array{role_label: string, intro: string, conclusion_title: string, show_photo: bool, show_traits: bool, rows: list<array{label: string, value: string}>}
     */
    private function employeeProfile(User $user): array
    {
        $user->load(['employee', 'usersCategory']);

        return [
            'role_label' => 'Xodimning ijtimoiy-psixologik profili',
            'intro' => 'Mazkur passport xodimning umumiy ma’lumotlari hamda psixologik tavsifini '
                .'rasmiy ko‘rinishda aks ettirish uchun tayyorlandi.',
            'conclusion_title' => 'Xodim bo‘yicha xulosa',
            'show_photo' => true,
            'show_traits' => true,
            'rows' => [
                ['label' => 'Hodim ID', 'value' => $this->value($user->employee?->employee_id_number)],
                ['label' => 'Telefon', 'value' => $this->value($user->phone)],
                ['label' => 'Bo‘lim', 'value' => $this->value($user->employee?->department_name)],
                ['label' => 'Lavozim turi', 'value' => $this->value($user->employee?->employee_type_name)],
                ['label' => 'Kategoriyalar', 'value' => $this->categories($user)],
            ],
        ];
    }

    /**
     * @return array{role_label: string, intro: string, conclusion_title: string, show_photo: bool, show_traits: bool, rows: list<array{label: string, value: string}>}
     */
    private function guestProfile(User $user): array
    {
        $user->load(['guest', 'usersCategory']);

        return [
            'role_label' => 'Nomzodning ijtimoiy-psixologik profili',
            'intro' => 'Mazkur passport ishga qabul qilinmagan nomzodning umumiy ma’lumotlari hamda '
                .'psixologik tavsifini rasmiy ko‘rinishda aks ettirish uchun tayyorlandi.',
            'conclusion_title' => 'Nomzod bo‘yicha xulosa',
            // Nomzodlarda profil rasmi passportga olinmaydi — har doim person icon.
            'show_photo' => false,
            // Nomzod passportida faqat xulosa bo'ladi: qobiliyatlar ketma-ketligi
            // va temperament tavsifi hujjatga chiqmaydi.
            'show_traits' => false,
            'rows' => [
                ['label' => 'Otasining ismi', 'value' => $this->value($user->guest?->father_name)],
                ['label' => 'Telefon', 'value' => $this->value($user->phone)],
                ['label' => 'Manzil', 'value' => $this->value($user->guest?->address)],
                ['label' => 'Lavozim', 'value' => $this->value($user->guest?->desired_position)],
                ['label' => 'Ariza holati', 'value' => $this->applicationStatus($user)],
                ['label' => 'Kategoriyalar', 'value' => $this->categories($user)],
            ],
        ];
    }

    private function applicationStatus(User $user): string
    {
        return match ($user->guest?->application_status) {
            'accepted' => 'Qabul qilingan',
            'rejected' => 'Rad etilgan',
            'pending' => 'Kutilmoqda',
            default => '-',
        };
    }

    private function categories(User $user): string
    {
        return $user->usersCategory->pluck('name')->filter()->implode(', ') ?: '-';
    }

    private function value(mixed $value): string
    {
        return filled($value) ? (string) $value : '-';
    }

    private function ensureSupportedRole(User $user): void
    {
        abort_unless(
            in_array($user->role, self::SUPPORTED_ROLES, true),
            Response::HTTP_NOT_FOUND,
            'Foydalanuvchi topilmadi.'
        );
    }
}
