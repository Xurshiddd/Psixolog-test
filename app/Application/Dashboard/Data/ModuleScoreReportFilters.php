<?php

namespace App\Application\Dashboard\Data;

final class ModuleScoreReportFilters
{
    public function __construct(
        public readonly ?int $moduleId,
        public readonly ?int $minScore,
        public readonly ?int $maxScore,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            self::parsePositiveInt($input['report_module_id'] ?? null),
            self::parseNonNegativeInt($input['min_score'] ?? null),
            self::parseNonNegativeInt($input['max_score'] ?? null),
        );
    }

    public function isReady(): bool
    {
        return $this->moduleId !== null
            && $this->minScore !== null
            && $this->maxScore !== null
            && $this->minScore <= $this->maxScore;
    }

    /**
     * @return array<string, int|null|bool>
     */
    public function toViewData(): array
    {
        return [
            'module_id' => $this->moduleId,
            'min_score' => $this->minScore,
            'max_score' => $this->maxScore,
            'is_ready' => $this->isReady(),
        ];
    }

    private static function parsePositiveInt(mixed $value): ?int
    {
        $intValue = filter_var($value, FILTER_VALIDATE_INT);

        if ($intValue === false || $intValue <= 0) {
            return null;
        }

        return (int) $intValue;
    }

    private static function parseNonNegativeInt(mixed $value): ?int
    {
        $intValue = filter_var($value, FILTER_VALIDATE_INT);

        if ($intValue === false || $intValue < 0) {
            return null;
        }

        return (int) $intValue;
    }
}
