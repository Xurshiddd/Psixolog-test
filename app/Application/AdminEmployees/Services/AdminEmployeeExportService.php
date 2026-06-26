<?php

namespace App\Application\AdminEmployees\Services;

use App\Application\AdminEmployees\Data\AdminEmployeeFilters;
use App\Application\AdminEmployees\Queries\AdminEmployeeQueryFactory;
use App\Exports\EmployeesExport;
use App\Services\LookupCacheService;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminEmployeeExportService
{
    public function __construct(
        private AdminEmployeeQueryFactory $adminEmployeeQueryFactory,
        private LookupCacheService $lookupCacheService,
    ) {}

    public function downloadExcel(AdminEmployeeFilters $filters): BinaryFileResponse
    {
        $query = $this->adminEmployeeQueryFactory->makeExcelExportQuery($filters);
        $modules = $this->lookupCacheService->modules();
        $timestamp = now()->format('Y-m-d_H-i-s');

        return Excel::download(new EmployeesExport($query->latest(), $modules), "hodimlar_{$timestamp}.xlsx");
    }
}
