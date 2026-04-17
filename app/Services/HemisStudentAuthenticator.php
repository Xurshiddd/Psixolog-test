<?php

namespace App\Services;

use App\Models\Faculity;
use App\Models\Group;
use App\Models\Speciality;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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

    protected function ensureStudentCanAccess(array $userData): void
    {
        if (data_get($userData, 'data.studentStatus.name') !== "O‘qimoqda") {
            throw new RuntimeException('Siz hozirda Institutda o‘qimayotganingiz uchun kira olmaysiz');
        }

        if (
            data_get($userData, 'data.educationType.code') !== '11'
            || data_get($userData, 'data.educationForm.code') !== '11'
            || data_get($userData, 'data.educationForm.code') !== '20'
        ) {
            throw new RuntimeException('Bu platforma hozirda Bakalavr kunduzgi talabalar uchun');
        }
    }
}
