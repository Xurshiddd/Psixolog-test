<?php

namespace App\Application\AdminEmployees\Data;

final class AdminEmployeeFilters
{
    public function __construct(
        public readonly ?string $search,
        public readonly ?string $department,
        public readonly ?string $employeeType,
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
            self::parseNullableString($input['department'] ?? null),
            self::parseNullableString($input['employee_type'] ?? null),
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
            'department' => $this->department,
            'employee_type' => $this->employeeType,
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
