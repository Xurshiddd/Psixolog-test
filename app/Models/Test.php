<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Test extends Model
{
    use LogsActivity;

    protected $fillable = [
        'question',
        'image',
        'module_id',
        'type',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('test')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }
    public function options()
    {
        return $this->hasMany(TestOption::class);
    }
}
