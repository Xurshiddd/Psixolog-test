<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name',
    ];

    public function usersCategory()
    {
        return $this->belongsToMany(User::class, 'users_category', 'category_id', 'user_id');
    }
}
