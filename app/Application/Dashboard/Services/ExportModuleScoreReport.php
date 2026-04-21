<?php

namespace App\Application\Dashboard\Services;

use App\Application\Dashboard\Data\ModuleScoreReportFilters;
use App\Exports\ModuleScoreRangeExport;
use App\Models\Module;
use App\Services\ModuleScoreRangeReportService;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportModuleScoreReport
{
    public function __construct(
        private ModuleScoreRangeReportService $moduleScoreRangeReportService,
    ) {}

    public function download(ModuleScoreReportFilters $filters): BinaryFileResponse
    {
        abort_if(
            ! $filters->isReady(),
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'Ball oralig\'i noto\'g\'ri.'
        );

        $module = Module::query()->findOrFail($filters->moduleId);
        $rows = $this->moduleScoreRangeReportService->exportRows(
            $module->id,
            $filters->minScore,
            $filters->maxScore,
        );

        $timestamp = now()->format('Y-m-d_H-i-s');
        $fileName = sprintf(
            'module_ballari_%s_%s.xlsx',
            Str::slug($module->name),
            $timestamp
        );

        return Excel::download(new ModuleScoreRangeExport($rows), $fileName);
    }
}
