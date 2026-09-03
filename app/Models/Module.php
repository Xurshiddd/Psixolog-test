<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Module extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name',
        'description',
        'audiences',
        'is_active',
        'shuffle',
    ];

    protected $casts = [
        'audiences' => 'array',
        'is_active' => 'boolean',
        'shuffle' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('module')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Modulni berilgan auditoriya turi (student/employee/guest) ko'ra oladigan
     * qilib filtrlaydi. audiences null/bo'sh bo'lsa cheklov yo'q — hammaga.
     */
    public function scopeForAudience(Builder $query, ?string $role): Builder
    {
        if (blank($role)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($role): void {
            $q->whereJsonContains('audiences', $role)
                ->orWhereNull('audiences');
        });
    }

    /**
     * Statusi o'chirilgan modul platformada yo'qday ko'rinadi: ro'yxatlarda,
     * hisobotlarda va statistikada chiqmaydi. Ma'lumot bazada saqlanib qoladi.
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isForAudience(?string $role): bool
    {
        if (blank($this->audiences)) {
            return true;
        }

        return in_array($role, $this->audiences, true);
    }

    public function tests()
    {
        return $this->hasMany(Test::class);
    }

    /**
     * Modul tegishli bo'lgan blok. `block_module.module_id` unique bo'lgani
     * uchun modul ko'pi bilan bitta blokda bo'ladi.
     */
    public function blocks()
    {
        return $this->belongsToMany(Block::class, 'block_module')
            ->withPivot('position')
            ->withTimestamps();
    }

    public function usersTestsResults()
    {
        return $this->belongsToMany(User::class, 'users_tests_results', 'module_id', 'user_id')
            ->withPivot('result_fake', 'result_real', 'diagnosis', 'flag')
            ->withTimestamps();
    }
}
