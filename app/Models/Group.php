<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $fillable = [
        'name',
        'code',
        'education_lang',
        'education_form',
        'education_type',
    ];
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
