<?php

namespace App\Http\Controllers;

use App\Application\Dashboard\Data\ModuleScoreReportFilters;
use App\Application\Dashboard\Services\BuildDashboardPage;
use App\Application\Dashboard\Services\ExportModuleScoreReport;
use App\Http\Requests\DashboardIndexRequest;
use App\Http\Requests\DashboardModuleScoreExportRequest;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(
        private BuildDashboardPage $buildDashboardPage,
        private ExportModuleScoreReport $exportModuleScoreReport,
    ) {}

    public function index(DashboardIndexRequest $request)
    {
        $page = $this->buildDashboardPage->forUser(
            $request->user(),
            ModuleScoreReportFilters::fromArray($request->validated())
        );

        return Inertia::render($page['component'], $page['props']);
    }

    public function exportModuleScoreReport(DashboardModuleScoreExportRequest $request)
    {
        return $this->exportModuleScoreReport->download(
            ModuleScoreReportFilters::fromArray($request->validated())
        );
    }
}
