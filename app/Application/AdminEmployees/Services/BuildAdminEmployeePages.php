<?php

namespace App\Application\AdminEmployees\Services;

use App\Application\AdminEmployees\Data\AdminEmployeeFilters;
use App\Application\AdminEmployees\Queries\AdminEmployeeQueryFactory;
use App\Models\Employee;
use App\Models\User;
use App\Services\LookupCacheService;

class BuildAdminEmployeePages
{
    public function __construct(
        private AdminEmployeeQueryFactory $adminEmployeeQueryFactory,
        private LookupCacheService $lookupCacheService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function indexProps(AdminEmployeeFilters $filters): array
    {
        $employees = $this->adminEmployeeQueryFactory
            ->makeListQuery($filters)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return [
            'employees' => $employees,
            'departments' => $this->distinctValues('department_name'),
            'employeeTypes' => $this->distinctValues('employee_type_name'),
            'categories' => $this->lookupCacheService->categories(),
            'filters' => $filters->toArray(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function showProps(User $user, AdminEmployeeFilters $filters): array
    {
        $user->load(['employee', 'usersTestsResults', 'usersCategory']);

        return [
            'employee' => $user,
            'results' => $user->usersTestsResults,
            'allCategories' => $this->lookupCacheService->categories(),
            'filters' => $filters->toArray(),
            'page' => $filters->page,
        ];
    }

    /**
     * @return list<string>
     */
    private function distinctValues(string $column): array
    {
        return Employee::query()
            ->whereNotNull($column)
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->all();
    }
}
