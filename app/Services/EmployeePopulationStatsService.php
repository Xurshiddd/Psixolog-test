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
                // Token'ni ham Bearer header, ham `access-token` query (Yii2)
                // sifatida yuboramiz — qaysi auth ishlasa, o'sha qabul qilinadi.
                $response = Http::acceptJson()
                    ->withToken((string) $token)
                    ->timeout(20)
                    ->get($baseUrl.'/data/employee-list', [
                        'type' => 'all',
                        'page' => 1,
                        'access-token' => $token,
                    ]);

                if ($response->failed()) {
                    Log::warning('HEMIS employee population total request failed', [
                        'url' => $baseUrl.'/data/employee-list',
                        'status' => $response->status(),
                        'body' => $response->json('error') ?? \Illuminate\Support\Str::limit($response->body(), 200),
                        'token_preview' => $this->maskToken((string) $token),
                        'token_length' => strlen((string) $token),
                    ]);

                    return 0;
                }

                return (int) ($response->json('data.pagination.totalCount')
                    ?? count($response->json('data.items', [])));
            } catch (Throwable $e) {
                Log::warning('HEMIS employee population total request error', [
                    'url' => $baseUrl.'/data/employee-list',
                    'message' => $e->getMessage(),
                ]);

                return 0;
            }
        });
    }

    private function maskToken(string $token): string
    {
        $length = strlen($token);

        if ($length <= 10) {
            return str_repeat('*', $length);
        }

        return substr($token, 0, 6).'...'.substr($token, -4);
    }
}
