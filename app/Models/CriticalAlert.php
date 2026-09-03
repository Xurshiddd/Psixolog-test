<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Zudlik bilan ish olib borish kerak bo'lgan holat: talaba xavfli deb
 * belgilangan variantni tanlagan.
 */
class CriticalAlert extends Model
{
    protected $fillable = [
        'user_id',
        'module_id',
        'test_id',
        'test_option_id',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('resolved_at');
    }

    public function scopeResolved(Builder $query): Builder
    {
        return $query->whereNotNull('resolved_at');
    }

    /**
     * Statusi o'chirilgan modul platformada yo'qday ko'rinadi — uning
     * ogohlantirishlari ham ro'yxatga tushmaydi (ma'lumot saqlanib qoladi).
     */
    public function scopeVisibleModule(Builder $query): Builder
    {
        return $query->whereHas('module', fn (Builder $moduleQuery) => $moduleQuery->visible());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function testOption(): BelongsTo
    {
        return $this->belongsTo(TestOption::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
