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
            try {
                $baseUrl = rtrim((string) config('services.hemis.api_base_url'), '/');
                $token = config('services.hemis.token');

                $response = Http::acceptJson()
                    ->when($token, fn ($request) => $request->withToken((string) $token))
                    ->timeout(20)
                    ->get($baseUrl.'/data/employee-list', [
                        'type' => 'all',
                        'page' => 1,
                        'limit' => 1,
                    ])
                    ->throw();

                return (int) $response->json('data.pagination.totalCount', 0);
            } catch (Throwable $e) {
                Log::warning('HEMIS employee population total request failed', [
                    'message' => $e->getMessage(),
                ]);

                return 0;
            }
        });
    }
}
