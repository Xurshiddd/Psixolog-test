<?php

use App\Services\StudentPopulationStatsService;

it('includes regular and joint daytime bachelor students in the total', function () {
    $service = new class extends StudentPopulationStatsService
    {
        public function getCachedStats(): array
        {
            return [
                'data' => [
                    'education_form' => [
                        'Bakalavr' => [
                            'Kunduzgi' => [
                                'Erkak' => 1541,
                                'Ayol' => 1738,
                            ],
                            'Qo‘shma (kunduzgi)' => [
                                'Erkak' => 24,
                                'Ayol' => 130,
                            ],
                        ],
                    ],
                ],
            ];
        }
    };

    expect($service->getBakalavrKunduzgiTotal())->toBe(3433);
});
