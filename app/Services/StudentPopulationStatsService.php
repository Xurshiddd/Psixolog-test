<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class StudentPopulationStatsService
{
    private const CACHE_KEY = 'dashboard:student-population-stats';

    public function getBakalavrKunduzgiTotal(): int
    {
        $data = $this->getCachedStats();

        $daytimeStudents = data_get($data, 'data.education_form.Bakalavr.Kunduzgi', []);

        return collect($daytimeStudents)
            ->filter(fn ($count) => is_numeric($count))
            ->sum();
    }

    /**
     * @return array<string, mixed>
     */
    public function getCachedStats(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->endOfDay(), function (): array {
            return Http::acceptJson()
                ->timeout(15)
                ->get('https://student.ttyesi.uz/rest/v1/public/stat-student')
                ->throw()
                ->json() ?? [];
        });
    }
}
