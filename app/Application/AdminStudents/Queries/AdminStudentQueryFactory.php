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
                'studentPassport:id,student_id',
                'usersTestsResults' => fn ($query) => $query
                    ->select('modules.id', 'modules.name')
                    ->orderBy('modules.name'),
            ]);

        $this->applyFilters($query, $filters);

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
            $query->whereHas('studentPassport');
        } elseif ($filters->passportStatus === 'not_exists') {
            $query->whereDoesntHave('studentPassport');
        }
    }
}
