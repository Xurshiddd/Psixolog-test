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
        $token = config('services.hemis.token');

        if (blank($token)) {
            Log::warning('HEMIS employee population total: HEMIS_TOKEN sozlanmagan.');

            return 0;
        }

        try {
            // HEMIS bu endpointni POST orqali qabul qiladi (Postman'da
            // ham POST ishlaydi). `type` query parametr sifatida yuboriladi.
            $response = Http::acceptJson()
                ->withToken((string) $token)
                ->timeout(20)
                ->post($baseUrl.'/data/employee-list?type=all&page=1');

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
