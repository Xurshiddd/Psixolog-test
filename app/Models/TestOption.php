<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TestOption extends Model
{
    use LogsActivity;

    protected $fillable = [
        'test_id',
        'option_text',
        'option_value',
        'is_critical',
    ];

    protected $casts = [
        'is_critical' => 'boolean',
    ];

    /**
     * Xavfli variant belgisi test yechuvchiga ko'rinmasligi kerak: aks holda
     * talaba sahifa manbaidan qaysi javob ogohlantirish chiqarishini bilib,
     * uni tanlamay qo'yadi va butun zudlik bilan xabar berish tizimi
     * ishlamay qoladi. Modulni tahrirlash sahifasida `makeVisible` bilan
     * ataylab ochiladi.
     */
    protected $hidden = [
        'is_critical',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('test_option')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function test()
    {
        return $this->belongsTo(Test::class);
    }
}
