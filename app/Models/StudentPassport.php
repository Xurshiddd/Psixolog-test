<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentPassport extends Model
{
    protected $fillable = [
        'student_id',
        'character_traits',
        'temperament_type',
        'student_conclusion',
    ];

    protected function casts(): array
    {
        return [
            'character_traits' => 'array',
        ];
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
