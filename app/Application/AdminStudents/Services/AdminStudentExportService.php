<?php

namespace App\Application\AdminStudents\Services;

use App\Application\AdminStudents\Data\AdminStudentFilters;
use App\Application\AdminStudents\Queries\AdminStudentQueryFactory;
use App\Exports\StudentsExport;
use App\Exports\StudentsExportWithDiagnosis;
use App\Services\LookupCacheService;
use App\Services\StudentPdfExportService;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminStudentExportService
{
    public function __construct(
        private AdminStudentQueryFactory $adminStudentQueryFactory,
        private LookupCacheService $lookupCacheService,
        private StudentPdfExportService $studentPdfExportService,
    ) {}

    public function downloadExcel(AdminStudentFilters $filters): BinaryFileResponse
    {
        $query = $this->adminStudentQueryFactory->makeExcelExportQuery($filters);
        $modules = $this->lookupCacheService->modules();
        $timestamp = now()->format('Y-m-d_H-i-s');

        return Excel::download(new StudentsExport($query->latest(), $modules), "talabalar_{$timestamp}.xlsx");
    }

    public function downloadDiagnosisExcel(AdminStudentFilters $filters): BinaryFileResponse
    {
        $query = $this->adminStudentQueryFactory->makeDiagnosisExportQuery($filters);
        $modules = $this->lookupCacheService->modules();
        $timestamp = now()->format('Y-m-d_H-i-s');

        return Excel::download(new StudentsExportWithDiagnosis($query->latest(), $modules), "talabalar_{$timestamp}.xlsx");
    }

    public function downloadPdf(AdminStudentFilters $filters)
    {
        $query = $this->adminStudentQueryFactory->makePdfExportQuery($filters);
        $studentsCount = (clone $query)->count();
        $timestamp = now()->format('Y-m-d_H-i-s');
        $pdf = $this->studentPdfExportService->generatePdf($query->latest()->lazy(200), $studentsCount);

        return $pdf->download("talabalar_{$timestamp}.pdf");
    }
}
