<?php

namespace App\Application\AdminGuests\Data;

final class AdminGuestFilters
{
    public function __construct(
        public readonly ?string $search,
        public readonly ?string $desiredPosition,
        public readonly ?string $applicationStatus,
        public readonly ?string $testStatus,
        public readonly ?int $categoryId,
        public readonly int $page,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            self::parseNullableString($input['search'] ?? null),
            self::parseNullableString($input['desired_position'] ?? null),
            self::parseNullableString($input['application_status'] ?? null),
            self::parseNullableString($input['test_status'] ?? null),
            self::parsePositiveInt($input['category_id'] ?? null),
            self::parsePage($input['page'] ?? null),
        );
    }

    /**
     * @return array<string, int|string|null>
     */
    public function toArray(): array
    {
        return [
            'search' => $this->search,
            'desired_position' => $this->desiredPosition,
            'application_status' => $this->applicationStatus,
            'test_status' => $this->testStatus,
            'category_id' => $this->categoryId,
        ];
    }

    private static function parseNullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private static function parsePositiveInt(mixed $value): ?int
    {
        $intValue = filter_var($value, FILTER_VALIDATE_INT);

        if ($intValue === false || $intValue <= 0) {
            return null;
        }

        return (int) $intValue;
    }

    private static function parsePage(mixed $value): int
    {
        $intValue = filter_var($value, FILTER_VALIDATE_INT);

        if ($intValue === false || $intValue <= 0) {
            return 1;
        }

        return (int) $intValue;
    }
}
