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
     * (pagination.totalCount) oladi. Faqat musbat natija kun oxirigacha
     * keshlanadi — 0 (xato) keshlanmaydi, keyingi so'rovda qayta urinadi.
     */
    public function getTotalEmployees(): int
    {
        $cached = Cache::get(self::CACHE_KEY);

        if (is_int($cached) && $cached > 0) {
            return $cached;
        }

        $total = $this->fetchTotal();

        if ($total > 0) {
            Cache::put(self::CACHE_KEY, $total, now()->endOfDay());
        }

        return $total;
    }

    private function fetchTotal(): int
    {
        $baseUrl = rtrim((string) config('services.hemis.api_base_url'), '/');
        $token = (string) config('services.hemis.token');

        if (blank($token)) {
            Log::warning('HEMIS employee population total: HEMIS_TOKEN sozlanmagan.');

            return 0;
        }

        $endpoint = $baseUrl.'/data/employee-list';
        $query = ['type' => 'all', 'page' => 1];

        // HEMIS qaysi auth usulini kutishini bilmaganimiz uchun bir nechtasini
        // ketma-ket sinaymiz. Qaysi biri ishlasa, log'da ko'rinadi.
        $strategies = [
            'bearer' => fn () => Http::acceptJson()->withToken($token)->timeout(20)
                ->post($endpoint.'?'.http_build_query($query)),
            'raw_header' => fn () => Http::acceptJson()->withHeaders(['Authorization' => $token])->timeout(20)
                ->post($endpoint.'?'.http_build_query($query)),
            'access_token_query' => fn () => Http::acceptJson()->timeout(20)
                ->post($endpoint.'?'.http_build_query($query + ['access-token' => $token])),
        ];

        $lastStatus = null;
        $lastBody = null;

        foreach ($strategies as $name => $strategy) {
            try {
                $response = $strategy();

                if ($response->successful()) {
                    $total = (int) ($response->json('data.pagination.totalCount')
                        ?? count($response->json('data.items', [])));

                    if ($total > 0) {
                        Log::info("HEMIS employee total OK via '{$name}': {$total}");

                        return $total;
                    }
                }

                $lastStatus = $response->status();
                $lastBody = $response->json('error') ?? \Illuminate\Support\Str::limit($response->body(), 200);
            } catch (Throwable $e) {
                $lastBody = $e->getMessage();
            }
        }

        Log::warning('HEMIS employee population total request failed (all strategies)', [
            'url' => $endpoint,
            'status' => $lastStatus,
            'body' => $lastBody,
            'token_preview' => $this->maskToken($token),
            'token_length' => strlen($token),
        ]);

        return 0;
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
