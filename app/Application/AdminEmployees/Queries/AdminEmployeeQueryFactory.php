<?php

namespace App\Application\AdminEmployees\Queries;

use App\Application\AdminEmployees\Data\AdminEmployeeFilters;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AdminEmployeeQueryFactory
{
    public function makeListQuery(AdminEmployeeFilters $filters): Builder
    {
        $query = User::query()
            ->where('role', 'employee')
            ->select([
                'id',
                'name',
                'login',
                'phone',
                'picture',
                'created_at',
            ])
            ->with([
                'employee:id,user_id,employee_id_number,department_name,employee_type_name,staff_position',
                'usersCategory:id,name',
                'passport:id,user_id',
                'usersTestsResults' => fn ($query) => $query
                    ->select('modules.id', 'modules.name')
                    ->orderBy('modules.name'),
            ]);

        $this->applyFilters($query, $filters);

        return $query;
    }

    public function makeExcelExportQuery(AdminEmployeeFilters $filters): Builder
    {
        $query = User::query()
            ->where('role', 'employee')
            ->select([
                'id',
                'name',
                'login',
                'created_at',
            ])
            ->with([
                'employee:id,user_id,employee_id_number,department_name,employee_type_name',
                'usersTestsResults' => fn ($query) => $query->select('modules.id'),
            ]);

        $this->applyFilters($query, $filters);

        return $query;
    }

    private function applyFilters(Builder $query, AdminEmployeeFilters $filters): void
    {
        if ($filters->search !== null) {
            $search = $filters->search;
            $query->where(function (Builder $builder) use ($search): void {
                $builder->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($search).'%'])
                    ->orWhere('login', 'like', "%{$search}%")
                    ->orWhereHas('employee', function (Builder $sub) use ($search): void {
                        $sub->where('employee_id_number', 'like', "%{$search}%");
                    });
            });
        }

        if ($filters->department !== null) {
            $query->whereHas('employee', function (Builder $builder) use ($filters): void {
                $builder->where('department_name', $filters->department);
            });
        }

        if ($filters->employeeType !== null) {
            $query->whereHas('employee', function (Builder $builder) use ($filters): void {
                $builder->where('employee_type_name', $filters->employeeType);
            });
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
    }
}
