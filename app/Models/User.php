<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, LogsActivity;

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
        'faculity_id',
        'education_type_code',
        'education_type_name',
        'education_form_code',
        'education_form_name',
        'api_token',
        'api_token_last_used_at',
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
        'api_token',
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
            'api_token_last_used_at' => 'datetime',
        ];
    }

    public function issueApiToken(): string
    {
        $plainTextToken = Str::random(80);

        $this->forceFill([
            'api_token' => hash('sha256', $plainTextToken),
            'api_token_last_used_at' => now(),
        ])->save();

        return $plainTextToken;
    }

    public function revokeApiToken(): void
    {
        $this->forceFill([
            'api_token' => null,
            'api_token_last_used_at' => null,
        ])->save();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('user')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function scopeStudents(Builder $q): Builder
    {
        return $q->where('role', 'student');
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
        return $this->belongsToMany(Module::class, 'users_tests_results', 'user_id', 'module_id')
            ->withPivot('result_fake', 'result_real', 'diagnosis')
            ->withTimestamps();
    }
    public function usersCategory()
    {
        return $this->belongsToMany(Category::class, 'users_category', 'user_id', 'category_id');
    }
    public function conversations()
    {
        return $this->belongsToMany(Conversation::class)
            ->withPivot(['last_read_at'])
            ->withTimestamps();
    }
    public function faculity()
    {
        return $this->belongsTo(Faculity::class);
    }
}
