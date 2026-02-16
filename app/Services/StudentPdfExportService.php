<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Collection;
use Barryvdh\DomPDF\Facade\Pdf;

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
}
