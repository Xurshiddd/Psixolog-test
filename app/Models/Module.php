<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Module extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'shuffle',
    ];

    protected $casts = [
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

    public function tests()
    {
        return $this->hasMany(Test::class);
    }
    public function usersTestsResults()
    {
        return $this->belongsToMany(User::class, 'users_tests_results', 'module_id', 'user_id')->withPivot('result_fake', 'result_real', 'diagnosis');
    }
}
