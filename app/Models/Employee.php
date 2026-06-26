<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employee extends Model
{
    protected $fillable = [
        'user_id',
        'external_id',
        'employee_id_number',
        'staff_position',
        'employee_type_name',
        'department_name',
        'image',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'staff_position' => 'array',
            'synced_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
