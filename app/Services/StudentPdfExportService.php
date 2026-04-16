<?php

namespace App\Services;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;

class StudentPdfExportService
{
    /**
     * Generate PDF from students collection
     * 
     * @param Collection $students
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generatePdf(Collection $students)
    {
        $html = $this->buildHtml($students);
        return Pdf::loadHTML($html);
    }

    public function generateStudentPassportPdf(User $student, array $passportData)
    {
        return Pdf::loadView('pdf.student-passport', [
            'student' => $student,
            'passportData' => $passportData,
            'logoDataUri' => $this->imageToDataUri(public_path('logo-pic.svg')),
            'studentPictureDataUri' => $this->resolveStudentPictureDataUri($student->picture),
        ])->setPaper('a4');
    }

    /**
     * Build HTML table for PDF
     * 
     * @param Collection $students
     * @return string
     */
    private function buildHtml(Collection $students): string
    {
        $html = '<html><head><meta charset="utf-8"><style>';
        $html .= $this->getStyles();
        $html .= '</style></head><body>';
        $html .= '<h1>Talabalar Ro\'yxati</h1>';
        $html .= '<p>Jami talabalar: ' . $students->count() . ' ta</p>';
        $html .= '<table>';
        $html .= $this->buildTableHead();
        $html .= $this->buildTableBody($students);
        $html .= '</tbody></table>';
        $html .= '</body></html>';

        return $html;
    }

    /**
     * Get CSS styles for PDF
     * 
     * @return string
     */
    private function getStyles(): string
    {
        return 'body { font-family: Arial, sans-serif; margin: 20px; }' .
               'h1 { text-align: center; margin-bottom: 20px; }' .
               'table { width: 100%; border-collapse: collapse; margin-top: 20px; }' .
               'th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }' .
               'th { background-color: #4CAF50; color: white; }' .
               'tr:nth-child(even) { background-color: #f9f9f9; }';
    }

    /**
     * Build table header
     * 
     * @return string
     */
    private function buildTableHead(): string
    {
        return '<thead><tr>' .
               '<th>Ism Familiya</th>' .
               '<th>Login</th>' .
               '<th>Telefon</th>' .
               '<th>Guruh</th>' .
               '<th>Mutaxassislik</th>' .
               '<th>Test Statusi</th>' .
               '</tr></thead>' .
               '<tbody>';
    }

    /**
     * Build table body with student data
     * 
     * @param Collection $students
     * @return string
     */
    private function buildTableBody(Collection $students): string
    {
        $html = '';

        foreach ($students as $student) {
            $html .= '<tr>';
            $html .= '<td>' . ($student->name ?? '-') . '</td>';
            $html .= '<td>' . ($student->login ?? '-') . '</td>';
            $html .= '<td>' . ($student->phone ?? '-') . '</td>';
            $html .= '<td>' . ($student->group?->name ?? '-') . '</td>';
            $html .= '<td>' . ($student->speciality?->name ?? '-') . '</td>';
            $html .= '<td>' . $this->getTestStatus($student) . '</td>';
            $html .= '</tr>';
        }

        return $html;
    }

    /**
     * Get test status for student
     * 
     * @param object $student
     * @return string
     */
    private function getTestStatus($student): string
    {
        return $student->usersTestsResults && $student->usersTestsResults->count() > 0 
            ? 'Topshirgan' 
            : 'Topshirmagan';
    }

    private function resolveStudentPictureDataUri(?string $picture): ?string
    {
        if (blank($picture)) {
            return null;
        }

        if (str_starts_with($picture, 'data:image/')) {
            return $picture;
        }

        if (filter_var($picture, FILTER_VALIDATE_URL)) {
            try {
                $response = Http::timeout(10)->get($picture);

                if ($response->successful()) {
                    $mimeType = $response->header('Content-Type', 'image/jpeg');

                    return 'data:' . $mimeType . ';base64,' . base64_encode($response->body());
                }
            } catch (\Throwable) {
                return null;
            }

            return null;
        }

        $normalizedPath = ltrim($picture, '/');
        $candidatePaths = [
            public_path($normalizedPath),
            storage_path('app/public/' . $normalizedPath),
        ];

        foreach ($candidatePaths as $path) {
            $dataUri = $this->imageToDataUri($path);

            if ($dataUri !== null) {
                return $dataUri;
            }
        }

        return null;
    }

    private function imageToDataUri(string $path): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mimeType = match ($extension) {
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };

        return 'data:' . $mimeType . ';base64,' . base64_encode($contents);
    }
}
