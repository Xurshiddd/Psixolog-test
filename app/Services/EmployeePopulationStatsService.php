<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class EmployeePopulationStatsService
{
    private const CACHE_KEY = 'dashboard:employee-population-total';

    /**
     * HEMIS `data/employee-list?type=all` endpointidan jami hodimlar sonini
     * (pagination.totalCount) oladi. Kunlik keshlanadi.
     */
    public function getTotalEmployees(): int
    {
        return Cache::remember(self::CACHE_KEY, now()->endOfDay(), function (): int {
            $baseUrl = rtrim((string) config('services.hemis.api_base_url'), '/');
            $token = config('services.hemis.token');

            if (blank($token)) {
                Log::warning('HEMIS employee population total: HEMIS_TOKEN sozlanmagan.');

                return 0;
            }

            try {
                $response = Http::acceptJson()
                    ->withToken((string) $token)
                    ->timeout(20)
                    ->get($baseUrl.'/data/employee-list', [
                        'type' => 'all',
                        'page' => 1,
                    ])
                    ->throw();

                return (int) ($response->json('data.pagination.totalCount')
                    ?? count($response->json('data.items', [])));
            } catch (Throwable $e) {
                Log::warning('HEMIS employee population total request failed', [
                    'url' => $baseUrl.'/data/employee-list',
                    'status' => method_exists($e, 'response') ? optional($e->response)->status() : null,
                    'message' => $e->getMessage(),
                ]);

                return 0;
            }
        });
    }
}
