<?php

namespace App\Http\Controllers;

use App\Application\Dashboard\Data\ModuleScoreReportFilters;
use App\Application\Dashboard\Data\ReportAudience;
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

        $updatedByRole = $this->moduleScoreRangeReportService->updateAutomaticConclusions(
            $filters->moduleId,
            $filters->minScore,
            $filters->maxScore,
            $request->filledConclusions(),
            (bool) $validated['overwrite_auto_conclusion'],
            $request->selectedFlags(),
        );

        $summary = collect($updatedByRole)
            ->map(fn (int $count, string $role): string => ReportAudience::label($role).' — '.$count.' ta')
            ->implode(', ');

        return back()->with(
            'success',
            $summary === ''
                ? 'Avtomatik xulosa va bayroqlar yangilanmadi.'
                : "Avtomatik xulosa va bayroqlar yangilandi: {$summary}."
        );
    }
}
