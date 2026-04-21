<?php

namespace App\Application\AdminStudents\Data;

final class AdminStudentFilters
{
    public function __construct(
        public readonly ?string $search,
        public readonly ?int $faculityId,
        public readonly ?int $specialityId,
        public readonly ?int $groupId,
        public readonly ?string $level,
        public readonly ?string $testStatus,
        public readonly ?int $categoryId,
        public readonly ?string $passportStatus,
        public readonly int $page,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            self::parseNullableString($input['search'] ?? null),
            self::parsePositiveInt($input['faculity_id'] ?? null),
            self::parsePositiveInt($input['speciality_id'] ?? null),
            self::parsePositiveInt($input['group_id'] ?? null),
            self::parseNullableString($input['level'] ?? null),
            self::parseNullableString($input['test_status'] ?? null),
            self::parsePositiveInt($input['category_id'] ?? null),
            self::parseNullableString($input['passport_status'] ?? null),
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
            'faculity_id' => $this->faculityId,
            'speciality_id' => $this->specialityId,
            'group_id' => $this->groupId,
            'level' => $this->level,
            'test_status' => $this->testStatus,
            'category_id' => $this->categoryId,
            'passport_status' => $this->passportStatus,
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
