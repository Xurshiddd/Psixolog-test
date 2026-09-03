<?php

namespace App\Support;

/**
 * Talaba xavf darajasi bayrog'i. Dashboarddagi ball oralig'i hisobotida
 * avtomatik xulosa bilan birga biriktiriladi va passportda rangli doiracha
 * ko'rinishida chiqadi.
 */
final class RiskFlag
{
    public const RED = 'red';

    public const YELLOW = 'yellow';

    public const GREEN = 'green';

    /**
     * Og'irlik tartibi: eng xavflisi birinchi. Foydalanuvchining umumiy
     * bayrog'i uning natijalari orasidagi eng og'ir bayroq bo'ladi.
     *
     * @var array<string, string>
     */
    private const LABELS = [
        self::RED => 'Qizil bayroq',
        self::YELLOW => 'Sariq bayroq',
        self::GREEN => 'Yashil bayroq',
    ];

    /**
     * Tailwind sinflari — frontendda bir joydan boshqariladi.
     *
     * @var array<string, string>
     */
    private const COLORS = [
        self::RED => '#ef4444',
        self::YELLOW => '#f59e0b',
        self::GREEN => '#22c55e',
    ];

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_keys(self::LABELS);
    }

    public static function label(?string $flag): string
    {
        return self::LABELS[$flag] ?? '-';
    }

    public static function color(?string $flag): ?string
    {
        return self::COLORS[$flag] ?? null;
    }

    public static function isValid(?string $flag): bool
    {
        return $flag !== null && array_key_exists($flag, self::LABELS);
    }

    /**
     * Bir nechta bayroqdan eng og'irini tanlaydi (qizil > sariq > yashil).
     *
     * @param  iterable<string|null>  $flags
     */
    public static function mostSevere(iterable $flags): ?string
    {
        $severity = array_flip(self::values());
        $best = null;

        foreach ($flags as $flag) {
            if (! self::isValid($flag)) {
                continue;
            }

            if ($best === null || $severity[$flag] < $severity[$best]) {
                $best = $flag;
            }
        }

        return $best;
    }

    /**
     * Frontendga beriladigan ro'yxat: qiymat, nom va rang.
     *
     * @return list<array{value: string, label: string, color: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (string $flag): array => [
                'value' => $flag,
                'label' => self::label($flag),
                'color' => self::color($flag),
            ],
            self::values()
        );
    }
}
