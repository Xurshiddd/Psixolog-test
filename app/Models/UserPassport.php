<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPassport extends Model
{
    protected $fillable = [
        'user_id',
        'character_traits',
        'temperament_type',
        'conclusion',
    ];

    protected function casts(): array
    {
        return [
            'character_traits' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
