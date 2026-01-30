<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Test extends Model
{
    protected $fillable = [
        'question',
        'image',
        'module_id',
        'type',
    ];
    public function module()
    {
        return $this->belongsTo(Module::class);
    }
    public function options()
    {
        return $this->hasMany(TestOption::class);
    }
}
