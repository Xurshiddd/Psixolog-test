<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class StudentPopulationStatsService
{
    private const CACHE_KEY = 'dashboard:student-population-stats';

    public function getBakalavrKunduzgiTotal(): int
    {
        $data = $this->getCachedStats();

        $bachelorEducationForms = data_get($data, 'data.education_form.Bakalavr', []);
        $daytimeStudents = collect([
            data_get($bachelorEducationForms, 'Kunduzgi', []),
            data_get($bachelorEducationForms, 'Qo‘shma (kunduzgi)', []),
        ])->flatten(1);

        return $daytimeStudents
            ->filter(fn ($count) => is_numeric($count))
            ->sum();
    }

    /**
     * @return array<string, mixed>
     */
    public function getCachedStats(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->endOfDay(), function (): array {
            try {
                return Http::acceptJson()
                    ->timeout(15)
                    ->get('https://student.ttyesi.uz/rest/v1/public/stat-student')
                    ->throw()
                    ->json() ?? [];
            } catch (Throwable $e) {
                Log::warning('HEMIS student population stats request failed', [
                    'message' => $e->getMessage(),
                ]);

                return [];
            }
        });
    }
}
