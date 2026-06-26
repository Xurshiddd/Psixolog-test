<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class HemisEmployeeAuthenticator
{
    /**
     * HEMIS hodim OAuth resource owner ma'lumotidan foydalanuvchini
     * yaratadi/yangilaydi. Asosiy ma'lumot `users`, hodimga xosi `employees`.
     *
     * @param  array<string, mixed>  $userData
     */
    public function syncFromResourceOwner(array $userData): User
    {
        $type = data_get($userData, 'type');
        if (filled($type) && (string) $type !== 'employee') {
            throw new RuntimeException('Bu HEMIS hisobi hodimga tegishli emas.');
        }

        $externalId = data_get($userData, 'id') ?? data_get($userData, 'employee_id');
        $employeeIdNumber = data_get($userData, 'employee_id_number');

        if (blank($externalId) && blank($employeeIdNumber)) {
            throw new RuntimeException('HEMIS hodim ma\'lumotlari topilmadi.');
        }

        return DB::transaction(function () use ($userData, $externalId, $employeeIdNumber): User {
            $login = is_numeric($employeeIdNumber)
                ? (int) $employeeIdNumber
                : (is_numeric($externalId) ? (int) $externalId : null);

            // Ism: SURNAME FIRSTNAME PATRONYMIC, bo'lmasa `name` yoki `login`.
            $name = trim(implode(' ', array_filter([
                data_get($userData, 'surname'),
                data_get($userData, 'firstname'),
                data_get($userData, 'patronymic'),
            ]))) ?: (data_get($userData, 'name') ?? data_get($userData, 'login'));

            $email = filled(data_get($userData, 'email'))
                ? data_get($userData, 'email')
                : (($login ?? $externalId).'@ttysi.com');

            $image = data_get($userData, 'picture_full') ?? data_get($userData, 'picture');

            // Asosiy ish joyi (departments massivining birinchi elementi).
            $department = data_get($userData, 'departments.0');

            $password = data_get($userData, 'passport_number')
                ?? data_get($userData, 'passport_pin')
                ?? Str::random(16);

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'login' => $login,
                    'phone' => data_get($userData, 'phone'),
                    'picture' => $image,
                    'birth_date' => data_get($userData, 'birth_date'),
                    'password' => Hash::make((string) $password),
                    'role' => 'employee',
                ]
            );

            Employee::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'external_id' => $externalId,
                    'employee_id_number' => $employeeIdNumber,
                    'staff_position' => data_get($department, 'staffPosition'),
                    'employee_type_name' => data_get($department, 'employeeType.name'),
                    'department_name' => data_get($department, 'department.name'),
                    'image' => $image,
                    'synced_at' => now(),
                ]
            );

            return $user;
        });
    }
}
