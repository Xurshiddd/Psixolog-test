<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'login',
        'role',
        'email',
        'phone',
        'picture',
        'birth_date',
        'group_id',
        'level',
        'speciality_id',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
    public function speciality()
    {
        return $this->belongsTo(Speciality::class);
    }
    public function solveTests()
    {
        return $this->hasMany(SolveTest::class);
    }
    public function usersTestsResults()
    {
        return $this->belongsToMany(Module::class, 'users_tests_results', 'user_id', 'module_id')->withPivot('result_fake', 'result_real');
    }
    // app/Models/User.php
    public function conversations()
    {
        return $this->belongsToMany(Conversation::class)
            ->withPivot(['last_read_at'])
            ->withTimestamps();
    }
}
