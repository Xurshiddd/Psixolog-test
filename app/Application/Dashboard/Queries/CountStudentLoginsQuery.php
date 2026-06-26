<?php

namespace App\Application\Dashboard\Queries;

use App\Models\User;
use Spatie\Activitylog\Models\Activity;

class CountStudentLoginsQuery
{
    public function execute(string $role = 'student'): int
    {
        return Activity::query()
            ->where('log_name', 'auth')
            ->where('event', 'login')
            ->where('causer_type', User::class)
            ->whereIn('causer_id', User::query()->where('role', $role)->select('id'))
            ->distinct('causer_id')
            ->count('causer_id');
    }
}
