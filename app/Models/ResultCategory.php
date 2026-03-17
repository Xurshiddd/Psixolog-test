<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ResultCategory extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name',
        'diagnostic',
        'fake_diagnostic',
        'value',
        'module_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('result_category')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }
}
