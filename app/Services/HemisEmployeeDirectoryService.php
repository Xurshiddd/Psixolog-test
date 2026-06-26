<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class HemisEmployeeDirectoryService
{
    /**
     * HEMIS `data/employee-list` endpointidan `employee_id_number` bo'yicha
     * bitta hodimni qidiradi va normallashtirilgan massiv qaytaradi.
     * Topilmasa yoki xatolik bo'lsa null.
     *
     * @return array<string, mixed>|null
     */
    public function findByHemisId(string $hemisId): ?array
    {
        $hemisId = trim($hemisId);

        if ($hemisId === '') {
            return null;
        }

        $item = $this->fetchItem($hemisId);

        return $item ? $this->normalize($item) : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchItem(string $hemisId): ?array
    {
        $baseUrl = rtrim((string) config('services.hemis.api_base_url'), '/');
        $token = (string) config('services.hemis.token');

        if (blank($token)) {
            Log::warning('HEMIS employee qidiruv: HEMIS_TOKEN sozlanmagan.');

            return null;
        }

        $endpoint = $baseUrl.'/data/employee-list';
        $query = [
            'type' => 'all',
            'search' => $hemisId,
            'limit' => 50,
            'page' => 1,
        ];

        try {
            $response = Http::acceptJson()
                ->withToken($token)
                ->timeout(20)
                ->post($endpoint.'?'.http_build_query($query));

            if (! $response->successful()) {
                Log::warning('HEMIS employee qidiruv muvaffaqiyatsiz', [
                    'status' => $response->status(),
                    'body' => $response->json('error') ?? \Illuminate\Support\Str::limit($response->body(), 200),
                ]);

                return null;
            }

            $items = $response->json('data.items', []);

            foreach ($items as $item) {
                if ((string) data_get($item, 'employee_id_number') === $hemisId) {
                    return $item;
                }
            }

            // Aniq mos kelmasa, lekin bitta natija bo'lsa — o'shani olamiz.
            return count($items) === 1 ? $items[0] : null;
        } catch (Throwable $e) {
            Log::warning('HEMIS employee qidiruv xatosi', ['message' => $e->getMessage()]);

            return null;
        }
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
                ?? trim(implode(' ', array_filter([
                    data_get($item, 'second_name'),
                    data_get($item, 'first_name'),
                    data_get($item, 'third_name'),
                ]))),
            'first_name' => data_get($item, 'first_name'),
            'second_name' => data_get($item, 'second_name'),
            'third_name' => data_get($item, 'third_name'),
            'image' => data_get($item, 'image_full') ?? data_get($item, 'image'),
            'birth_date' => is_numeric($birthTs)
                ? Carbon::createFromTimestamp((int) $birthTs)->toDateString()
                : null,
            'gender' => data_get($item, 'gender.name'),
            'specialty' => data_get($item, 'specialty'),
            'department_name' => data_get($item, 'department.name'),
            'staff_position' => data_get($item, 'staffPosition'),
            'staff_position_name' => data_get($item, 'staffPosition.name'),
            'employee_type_name' => data_get($item, 'employeeType.name'),
            'employee_status_name' => data_get($item, 'employeeStatus.name'),
        ];
    }
}
