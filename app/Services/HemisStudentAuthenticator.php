<?php

namespace App\Services;

use App\Models\Faculity;
use App\Models\Group;
use App\Models\Speciality;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class HemisStudentAuthenticator
{
    public function syncFromResourceOwner(array $userData): User
    {
        $this->ensureStudentCanAccess($userData);

        return DB::transaction(function () use ($userData): User {
            $groupData = data_get($userData, 'groups.0');

            if (! is_array($groupData)) {
                throw new RuntimeException('Talabaning guruhi topilmadi.');
            }

            $faculity = Faculity::firstOrCreate(
                ['code' => data_get($userData, 'data.faculty.code')],
                ['name' => data_get($userData, 'data.faculty.name')]
            );

            $group = Group::firstOrCreate(
                ['code' => data_get($groupData, 'id')],
                [
                    'name' => data_get($groupData, 'name'),
                    'education_lang' => data_get($groupData, 'education_lang.name'),
                    'education_form' => data_get($groupData, 'education_form.name'),
                    'education_type' => data_get($groupData, 'education_type.name'),
                ]
            );

            $speciality = Speciality::firstOrCreate(
                ['code' => (int) data_get($userData, 'data.specialty.code')],
                ['name' => data_get($userData, 'data.specialty.name')]
            );

            $login = (int) data_get($userData, 'login');
            $email = filled(data_get($userData, 'email'))
                ? data_get($userData, 'email')
                : "{$login}@ttysi.com";

            return User::updateOrCreate(
                ['login' => $login],
                [
                    'name' => data_get($userData, 'name'),
                    'email' => $email,
                    'phone' => data_get($userData, 'phone'),
                    'picture' => data_get($userData, 'picture'),
                    'birth_date' => data_get($userData, 'birth_date'),
                    'group_id' => $group->id,
                    'password' => Hash::make((string) data_get($userData, 'passport_number', Str::random(16))),
                    'level' => data_get($userData, 'data.level.name'),
                    'speciality_id' => $speciality->id,
                    'faculity_id' => $faculity->id,
                    'education_type_code' => data_get($userData, 'data.educationType.code'),
                    'education_type_name' => data_get($userData, 'data.educationType.name'),
                    'education_form_code' => data_get($userData, 'data.educationForm.code'),
                    'education_form_name' => data_get($userData, 'data.educationForm.name'),
                    'role' => 'student',
                ]
            );
        });
    }

    public function syncFromAccountMe(array $accountData, string|int $login, ?string $password = null): User
    {
        return $this->syncFromResourceOwner($this->normalizeAccountMe($accountData, $login, $password));
    }

    private function normalizeAccountMe(array $accountData, string|int $login, ?string $password): array
    {
        $group = data_get($accountData, 'group');

        return [
            'login' => $login ?: data_get($accountData, 'student_id_number'),
            'name' => data_get($accountData, 'full_name') ?: data_get($accountData, 'short_name'),
            'email' => data_get($accountData, 'email'),
            'phone' => data_get($accountData, 'phone'),
            'picture' => data_get($accountData, 'image'),
            'birth_date' => data_get($accountData, 'birth_date'),
            'passport_number' => $password ?: data_get($accountData, 'passport_pin'),
            'groups' => [
                [
                    'id' => $this->objectValue($group, ['id', 'code']),
                    'name' => $this->objectValue($group, ['name']),
                    'education_lang' => data_get($accountData, 'educationLang'),
                    'education_form' => data_get($accountData, 'educationForm'),
                    'education_type' => data_get($accountData, 'educationType'),
                ],
            ],
            'data' => [
                'faculty' => data_get($accountData, 'faculty'),
                'specialty' => data_get($accountData, 'specialty'),
                'level' => data_get($accountData, 'level'),
                'educationType' => data_get($accountData, 'educationType'),
                'educationForm' => data_get($accountData, 'educationForm'),
                'studentStatus' => data_get($accountData, 'studentStatus'),
            ],
        ];
    }

    private function objectValue(mixed $value, array $keys): mixed
    {
        foreach ($keys as $key) {
            $nested = data_get($value, $key);

            if (filled($nested)) {
                return $nested;
            }
        }

        return is_string($value) && filled($value) ? $value : null;
    }

    protected function ensureStudentCanAccess(array $userData): void
    {
        $studentStatus = str_replace(["'", '`', 'ʻ'], '‘', (string) data_get($userData, 'data.studentStatus.name'));

        if ($studentStatus !== "O‘qimoqda") {
            throw new RuntimeException('Siz hozirda Institutda o‘qimayotganingiz uchun kira olmaysiz');
        }

        if (
            (string) data_get($userData, 'data.educationType.code') !== '11' ||
            ! in_array((string) data_get($userData, 'data.educationForm.code'), ['11', '20'], true)
        ) {
            Log::error('Bakalavr kunduzgi talabalar uchun platformaga kirish urinishlari', ['userData' => $userData]);
            throw new RuntimeException('Bu platforma hozirda Bakalavr kunduzgi talabalar uchun');
        }
    }
}
