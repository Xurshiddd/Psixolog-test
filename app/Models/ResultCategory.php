<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResultCategory extends Model
{
    protected $fillable = [
        'name',
        'diagnostic',
        'fake_diagnostic',
        'value',
        'module_id',
    ];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }
}
