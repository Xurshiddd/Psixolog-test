<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Blok — modullardan katta birlik. Blokdagi modullar `position` bo'yicha
 * ketma-ket yechiladi: oldingisi yechilmaguncha keyingisi ochilmaydi.
 */
class Block extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('block')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'block_module')
            ->withPivot('position')
            ->withTimestamps()
            ->orderBy('block_module.position');
    }

    /**
     * Faqat platformada ko'rinadigan (status yoqilgan) modullar —
     * o'chirilgan modul blok ketma-ketligidan ham chiqib turadi.
     */
    public function activeModules(): BelongsToMany
    {
        return $this->modules()->where('modules.is_active', true);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
