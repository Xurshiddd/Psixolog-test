<?php

namespace App\Exports;

use App\Models\User;
use App\Models\Module;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class StudentsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
{
    protected $students;
    protected $modules;

    public function __construct(Collection $students, ?Collection $modules = null)
    {
        $this->students = $students;
        $this->modules = $modules ?? Module::orderBy('name')->get();
    }

    public function collection(): Collection
    {
        return $this->students;
    }

    public function headings(): array
    {
        $headings = [
            'Ism Familiya',
            'Hemis ID',
            'Guruh',
        ];
        
        foreach ($this->modules as $module) {
            $headings[] = $module->name;
        }
        
        return $headings;
    }

    public function map($student): array
    {
        $row = [
            $student->name ?? '-',
            $student->login ?? '-',
            $student->group->name ?? '-',
        ];
        
        foreach ($this->modules as $module) {
            $hasResult = $student->usersTestsResults 
                && $student->usersTestsResults->where('id', $module->id)->count() > 0;
            $row[] = $hasResult ? 'HA' : 'YO\'Q';
        }
        
        return $row;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Sarlavhani ko'k rang bilan
                $event->sheet->getStyle('1:1')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '4472C4'],
                    ],
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ]
                ]);

                // Har bir satrni tekshir va HA/YO'Q ni rangla
                $row = 2;
                foreach ($this->students as $student) {
                    // D, E, F... ustunlardan boshlanadi
                    $colIndex = 3; // D ustuni (A=0, B=1, C=2, D=3)
                    
                    foreach ($this->modules as $module) {
                        $col = chr(65 + $colIndex); // ASCII: 65=A, 66=B...
                        $cell = $col . $row;

                        $hasResult = $student->usersTestsResults 
                            && $student->usersTestsResults->where('id', $module->id)->count() > 0;

                        if ($hasResult) {
                            // Yashil rang HA uchun
                            $event->sheet->getStyle($cell)->applyFromArray([
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => '70AD47'],
                                ],
                                'font' => [
                                    'bold' => true,
                                    'color' => ['rgb' => 'FFFFFF'],
                                ],
                                'alignment' => [
                                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                                    'vertical' => Alignment::VERTICAL_CENTER,
                                ]
                            ]);
                        } else {
                            // Qizil rang YO'Q uchun
                            $event->sheet->getStyle($cell)->applyFromArray([
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'FF0000'],
                                ],
                                'font' => [
                                    'bold' => true,
                                    'color' => ['rgb' => 'FFFFFF'],
                                ],
                                'alignment' => [
                                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                                    'vertical' => Alignment::VERTICAL_CENTER,
                                ]
                            ]);
                        }
                        $colIndex++;
                    }
                    $row++;
                }
            }
        ];
    }
}
