<?php

namespace App\Application\AdminStudents\Queries;

use App\Application\AdminStudents\Data\AdminStudentFilters;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AdminStudentQueryFactory
{
    public function makeListQuery(AdminStudentFilters $filters): Builder
    {
        $query = User::query()
            ->where('role', 'student')
            ->select([
                'id',
                'name',
                'login',
                'phone',
                'picture',
                'level',
                'group_id',
                'speciality_id',
                'created_at',
            ])
            ->with([
                'group:id,name',
                'speciality:id,name',
                'passport:id,user_id',
                'usersTestsResults' => fn ($query) => $query
                    ->select('modules.id', 'modules.name')
                    ->orderBy('modules.name'),
            ])
            ->withMax('usersTestsResults as last_test_at', 'users_tests_results.created_at');

        $this->applyFilters($query, $filters);

        // Eng oxirgi test yechganlar tepada; hech qachon yechmaganlar
        // (last_test_at = null) ro'yxat oxirida qoladi.
        $query->orderByRaw('last_test_at is null')
            ->orderByDesc('last_test_at')
            ->orderByDesc('users.created_at');

        return $query;
    }

    public function makeExcelExportQuery(AdminStudentFilters $filters): Builder
    {
        $query = User::query()
            ->where('role', 'student')
            ->select([
                'id',
                'name',
                'login',
                'group_id',
                'faculity_id',
                'created_at',
            ])
            ->with([
                'group:id,name',
                'faculity:id,name',
                'usersTestsResults' => fn ($query) => $query->select('modules.id'),
            ]);

        $this->applyFilters($query, $filters);

        return $query;
    }

    public function makeDiagnosisExportQuery(AdminStudentFilters $filters): Builder
    {
        $query = User::query()
            ->where('role', 'student')
            ->select([
                'id',
                'name',
                'login',
                'group_id',
                'created_at',
            ])
            ->with([
                'group:id,name',
                'usersTestsResults' => fn ($query) => $query->select('modules.id'),
            ]);

        $this->applyFilters($query, $filters);

        return $query;
    }

    public function makePdfExportQuery(AdminStudentFilters $filters): Builder
    {
        $query = User::query()
            ->where('role', 'student')
            ->select([
                'id',
                'name',
                'login',
                'phone',
                'group_id',
                'speciality_id',
                'created_at',
            ])
            ->with([
                'group:id,name',
                'speciality:id,name',
            ])
            ->withExists(['usersTestsResults as has_test_results']);

        $this->applyFilters($query, $filters);

        return $query;
    }

    private function applyFilters(Builder $query, AdminStudentFilters $filters): void
    {
        if ($filters->search !== null) {
            $search = $filters->search;
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('login', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($filters->groupId !== null) {
            $query->where('group_id', $filters->groupId);
        }

        if ($filters->specialityId !== null) {
            $query->where('speciality_id', $filters->specialityId);
        }

        if ($filters->faculityId !== null) {
            $query->where('faculity_id', $filters->faculityId);
        }

        if ($filters->level !== null) {
            $query->where('level', $filters->level);
        }

        if ($filters->testStatus === 'submitted') {
            $query->whereHas('usersTestsResults');
        } elseif ($filters->testStatus === 'not_submitted') {
            $query->whereDoesntHave('usersTestsResults');
        }

        if ($filters->categoryId !== null) {
            $query->whereHas('usersCategory', function (Builder $builder) use ($filters): void {
                $builder->where('category_id', $filters->categoryId);
            });
        }

        if ($filters->passportStatus === 'exists') {
            $query->whereHas('passport');
        } elseif ($filters->passportStatus === 'not_exists') {
            $query->whereDoesntHave('passport');
        }

        if ($filters->testedFrom !== null || $filters->testedTo !== null) {
            $query->whereHas('usersTestsResults', function (Builder $builder) use ($filters): void {
                if ($filters->testedFrom !== null) {
                    $builder->where('users_tests_results.created_at', '>=', $filters->testedFrom.' 00:00:00');
                }

                if ($filters->testedTo !== null) {
                    $builder->where('users_tests_results.created_at', '<=', $filters->testedTo.' 23:59:59');
                }
            });
        }
    }
}
