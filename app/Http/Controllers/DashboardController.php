<?php

namespace App\Http\Controllers;

use App\Application\Dashboard\Data\ModuleScoreReportFilters;
use App\Application\Dashboard\Services\BuildDashboardPage;
use App\Application\Dashboard\Services\ExportModuleScoreReport;
use App\Http\Requests\DashboardIndexRequest;
use App\Http\Requests\DashboardModuleScoreConclusionUpdateRequest;
use App\Http\Requests\DashboardModuleScoreExportRequest;
use App\Services\ModuleScoreRangeReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(
        private BuildDashboardPage $buildDashboardPage,
        private ExportModuleScoreReport $exportModuleScoreReport,
        private ModuleScoreRangeReportService $moduleScoreRangeReportService,
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

    public function updateModuleScoreReportConclusions(DashboardModuleScoreConclusionUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $filters = ModuleScoreReportFilters::fromArray($validated);

        abort_if(
            ! $filters->isReady(),
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'Ball oralig\'i noto\'g\'ri.'
        );

        $updated = $this->moduleScoreRangeReportService->updateAutomaticConclusions(
            $filters->moduleId,
            $filters->minScore,
            $filters->maxScore,
            $validated['result_real'],
            (bool) $validated['overwrite_auto_conclusion'],
        );

        return back()->with('success', "Avtomatik xulosa {$updated} ta talaba uchun yangilandi.");
    }
}
