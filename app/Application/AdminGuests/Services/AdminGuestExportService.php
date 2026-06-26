<?php

namespace App\Application\AdminGuests\Services;

use App\Application\AdminGuests\Data\AdminGuestFilters;
use App\Application\AdminGuests\Queries\AdminGuestQueryFactory;
use App\Exports\GuestsExport;
use App\Services\LookupCacheService;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminGuestExportService
{
    public function __construct(
        private AdminGuestQueryFactory $adminGuestQueryFactory,
        private LookupCacheService $lookupCacheService,
    ) {}

    public function downloadExcel(AdminGuestFilters $filters): BinaryFileResponse
    {
        $query = $this->adminGuestQueryFactory->makeExcelExportQuery($filters);
        $modules = $this->lookupCacheService->modules();
        $timestamp = now()->format('Y-m-d_H-i-s');

        return Excel::download(new GuestsExport($query->latest(), $modules), "mehmonlar_{$timestamp}.xlsx");
    }
}
