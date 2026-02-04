<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolveTest extends Model
{
    protected $fillable = [
        'user_id',
        'module_id',
        'test_id',
        'test_option_id',
        'answer',
        'consequence_fake',
        'consequence_real',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    public function testOption()
    {
        return $this->belongsTo(TestOption::class);
    }
}
