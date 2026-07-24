<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class HemisEmployeeSyncService
{
    /**
     * HEMIS `data/employee-list?type=all` endpointidan barcha hodimlarni
     * sahifama-sahifa yuklab, `users` (role=employee) va `employees`
     * jadvallariga yozadi/yangilaydi.
     *
     * Mavjud hodimning paroli va `login_activated_at` qiymatiga tegilmaydi —
     * faqat HEMIS'dagi ma'lumot (ism, rasm, bo'lim, ...) yangilanadi.
     *
     * @return array{total:int, created:int, updated:int, pages:int}
     */
    public function syncAll(int $perPage = 200, int $maxPages = 500): array
    {
        $baseUrl = rtrim((string) config('services.hemis.api_base_url'), '/');
        $token = (string) config('services.hemis.token');

        if (blank($token)) {
            throw new RuntimeException('HEMIS_TOKEN sozlanmagan. Sinxronlashni amalga oshirib bo\'lmaydi.');
        }

        $endpoint = $baseUrl.'/data/employee-list';

        $created = 0;
        $updated = 0;
        $seen = 0;
        $page = 1;

        // Uzoq davom etishi mumkin — vaqt chegarasini olib tashlaymiz.
        @set_time_limit(0);

        while ($page <= $maxPages) {
            $items = $this->fetchPage($endpoint, $token, $page, $perPage);

            if ($items === []) {
                break;
            }

            foreach ($items as $item) {
                $normalized = $this->normalize($item);

                if ($normalized['employee_id_number'] === '' && blank($normalized['external_id'])) {
                    continue;
                }

                $result = $this->upsert($normalized);
                $result === 'created' ? $created++ : $updated++;
                $seen++;
            }

            // Oxirgi sahifa: to'liq bo'lmasa, tugadi.
            if (count($items) < $perPage) {
                break;
            }

            $page++;
        }

        Log::info('HEMIS xodimlar sinxronlashi yakunlandi', [
            'total' => $seen,
            'created' => $created,
            'updated' => $updated,
            'pages' => $page,
        ]);

        return [
            'total' => $seen,
            'created' => $created,
            'updated' => $updated,
            'pages' => $page,
        ];
    }

    /**
     * Bitta sahifani yuklaydi. Xatolik bo'lsa istisno tashlaydi.
     *
     * @return list<array<string, mixed>>
     */
    private function fetchPage(string $endpoint, string $token, int $page, int $perPage): array
    {
        $query = [
            'type' => 'all',
            'limit' => $perPage,
            'page' => $page,
        ];

        try {
            $response = Http::acceptJson()
                ->withToken($token)
                ->timeout(60)
                ->post($endpoint.'?'.http_build_query($query));
        } catch (Throwable $e) {
            throw new RuntimeException('HEMIS bilan bog\'lanishda xatolik: '.$e->getMessage());
        }

        if (! $response->successful()) {
            $message = $response->json('error') ?? Str::limit($response->body(), 200);

            throw new RuntimeException("HEMIS xodimlar ro'yxatini qaytarmadi (status {$response->status()}): {$message}");
        }

        return array_values($response->json('data.items', []));
    }

    /**
     * Normallashtirilgan hodim ma'lumotini `users` + `employees` ga yozadi.
     *
     * @param  array<string, mixed>  $data
     * @return string 'created' yoki 'updated'
     */
    private function upsert(array $data): string
    {
        return DB::transaction(function () use ($data): string {
            $employee = Employee::query()
                ->when($data['employee_id_number'] !== '', fn ($q) => $q->where('employee_id_number', $data['employee_id_number']))
                ->when($data['employee_id_number'] === '', fn ($q) => $q->where('external_id', $data['external_id']))
                ->first();

            $login = is_numeric($data['employee_id_number']) ? (int) $data['employee_id_number'] : null;

            if ($employee !== null) {
                $user = $employee->user;

                if ($user !== null) {
                    // Faqat HEMIS ma'lumotlarini yangilaymiz; parol/rolga tegmaymiz.
                    $user->fill(array_filter([
                        'name' => $data['name'],
                        'picture' => $data['image'],
                        'birth_date' => $data['birth_date'],
                        'login' => $login,
                    ], fn ($value) => filled($value)));
                    $user->save();
                }

                $employee->update($this->employeeAttributes($data));

                return 'updated';
            }

            // Mavjud user'ni login (employee_id_number) bo'yicha topishga urinamiz.
            $user = $login !== null
                ? User::query()->where('login', $login)->first()
                : null;

            if ($user === null) {
                $email = ($data['employee_id_number'] !== ''
                    ? $data['employee_id_number']
                    : $data['external_id']).'@ttysi.com';

                $user = User::create([
                    'name' => $data['name'] ?: 'Noma\'lum hodim',
                    'email' => $email,
                    'login' => $login,
                    'picture' => $data['image'],
                    'birth_date' => $data['birth_date'],
                    // Parol birinchi kirishda tug'ilgan kun orqali o'rnatiladi.
                    'password' => Hash::make(Str::random(40)),
                    'role' => 'employee',
                ]);
            } elseif ($user->role !== 'employee') {
                $user->update(['role' => 'employee']);
            }

            Employee::create(array_merge(
                ['user_id' => $user->id],
                $this->employeeAttributes($data),
            ));

            return 'created';
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function employeeAttributes(array $data): array
    {
        return [
            'external_id' => $data['external_id'],
            'employee_id_number' => $data['employee_id_number'] ?: null,
            'staff_position' => $data['staff_position'],
            'employee_type_name' => $data['employee_type_name'],
            'department_name' => $data['department_name'],
            'image' => $data['image'],
            'synced_at' => now(),
        ];
    }

    /**
     * employee-list item -> normallashtirilgan massiv.
     *
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function normalize(array $item): array
    {
        $birthTs = data_get($item, 'birth_date');

        return [
            'external_id' => data_get($item, 'id'),
            'employee_id_number' => (string) data_get($item, 'employee_id_number'),
            'name' => data_get($item, 'full_name')
                ?: trim(implode(' ', array_filter([
                    data_get($item, 'second_name'),
                    data_get($item, 'first_name'),
                    data_get($item, 'third_name'),
                ]))),
            // `image` (kichraytirilgan) saqlanadi, `image_full` emas.
            'image' => data_get($item, 'image') ?? data_get($item, 'image_full'),
            'birth_date' => is_numeric($birthTs)
                ? Carbon::createFromTimestamp((int) $birthTs)->toDateString()
                : null,
            'staff_position' => data_get($item, 'staffPosition'),
            'employee_type_name' => data_get($item, 'employeeType.name'),
            'department_name' => data_get($item, 'department.name'),
        ];
    }
}
