<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestOption extends Model
{
    protected $fillable = [
        'test_id',
        'option_text',
        'option_value',
    ];
    public function test()
    {
        return $this->belongsTo(Test::class);
    }
}
