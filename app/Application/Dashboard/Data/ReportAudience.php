<?php

namespace App\Application\Dashboard\Data;

/**
 * Modul testlarini yechishi mumkin bo'lgan auditoriya rollari.
 * Hisobotda va avtomatik xulosada shu rollar bo'yicha ajratiladi.
 */
final class ReportAudience
{
    public const STUDENT = 'student';

    public const EMPLOYEE = 'employee';

    public const GUEST = 'guest';

    /**
     * @var array<string, string>
     */
    private const LABELS = [
        self::STUDENT => 'Talaba',
        self::EMPLOYEE => 'Xodim',
        self::GUEST => 'Ishga qabul qilinmagan',
    ];

    /**
     * @return list<string>
     */
    public static function roles(): array
    {
        return array_keys(self::LABELS);
    }

    public static function label(?string $role): string
    {
        return self::LABELS[$role] ?? '-';
    }

    /**
     * Faqat test yecha oladigan rollarni qoldiradi. Bo'sh bo'lsa — barchasi.
     *
     * @param  list<string>|null  $roles
     * @return list<string>
     */
    public static function normalize(?array $roles): array
    {
        if (blank($roles)) {
            return self::roles();
        }

        $normalized = array_values(array_intersect(self::roles(), $roles));

        return $normalized === [] ? self::roles() : $normalized;
    }
}
