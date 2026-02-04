<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
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
    public function tests()
    {
        return $this->hasMany(Test::class);
    }
    public function usersTestsResults()
    {
        return $this->belongsToMany(User::class, 'users_tests_results', 'module_id', 'user_id')->withPivot('result_fake', 'result_real');
    }
}
