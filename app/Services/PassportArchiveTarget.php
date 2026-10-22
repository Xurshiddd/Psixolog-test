<?php

namespace App\Services;

/**
 * Qaysi passportlar arxivga kirishi: tur, fakultet (faqat talabalarda) va
 * test topshirilgan sana oralig'i. Sanalar `Y-m-d` ko'rinishida.
 */
final readonly class PassportArchiveTarget
{
    public function __construct(
        public string $role,
        public ?int $faculityId = null,
        public ?string $from = null,
        public ?string $to = null,
    ) {}

    /**
     * Har bir tanlov uchun alohida arxiv va holat yuritiladi, shuning uchun
     * kalit barcha parametrlarni o'z ichiga oladi.
     */
    public function scope(): string
    {
        return $this->role
            .($this->faculityId !== null ? '-f'.$this->faculityId : '')
            .($this->hasPeriod() ? '-d'.($this->from ?? '0').'_'.($this->to ?? 'now') : '');
    }

    public function hasPeriod(): bool
    {
        return $this->from !== null || $this->to !== null;
    }
}
