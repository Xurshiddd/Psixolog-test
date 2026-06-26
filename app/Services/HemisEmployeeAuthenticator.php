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
        $externalId = data_get($userData, 'id') ?? data_get($userData, 'employee_id_number');
        $employeeIdNumber = data_get($userData, 'employee_id_number') ?? data_get($userData, 'login');

        if (blank($externalId) && blank($employeeIdNumber)) {
            throw new RuntimeException('HEMIS hodim ma\'lumotlari topilmadi.');
        }

        return DB::transaction(function () use ($userData, $externalId, $employeeIdNumber): User {
            $login = is_numeric($employeeIdNumber) ? (int) $employeeIdNumber : null;

            $name = data_get($userData, 'full_name')
                ?? data_get($userData, 'name')
                ?? data_get($userData, 'short_name');

            $email = filled(data_get($userData, 'email'))
                ? data_get($userData, 'email')
                : (($login ?? $externalId).'@ttysi.com');

            $image = data_get($userData, 'image') ?? data_get($userData, 'picture');

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'login' => $login,
                    'phone' => data_get($userData, 'phone'),
                    'picture' => $image,
                    'birth_date' => data_get($userData, 'birth_date'),
                    'password' => Hash::make((string) data_get($userData, 'passport_number', Str::random(16))),
                    'role' => 'employee',
                ]
            );

            Employee::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'external_id' => $externalId,
                    'employee_id_number' => $employeeIdNumber,
                    'staff_position' => data_get($userData, 'staffPosition') ?? data_get($userData, 'data.staffPosition'),
                    'employee_type_name' => data_get($userData, 'employeeType.name') ?? data_get($userData, 'data.employeeType.name'),
                    'department_name' => data_get($userData, 'department.name')
                        ?? data_get($userData, 'staffPosition.0.department.name'),
                    'image' => $image,
                    'synced_at' => now(),
                ]
            );

            return $user;
        });
    }
}
