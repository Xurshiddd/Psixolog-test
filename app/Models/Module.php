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
    public function usersTestsResults()
    {
        return $this->belongsToMany(User::class, 'users_tests_results', 'module_id', 'user_id')
            ->withPivot('result_fake', 'result_real', 'diagnosis')
            ->withTimestamps();
    }
}
