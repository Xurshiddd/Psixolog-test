<?php

namespace App\Exports;

use App\Models\Module;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class StudentsExportWithDiagnosis implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
{
    public function __construct(
        protected Builder $query,
        protected ?Collection $modules = null,
    )
    {
        $this->modules = $modules ?? Module::orderBy('name')->get();
    }

    public function query(): Builder
    {
        return clone $this->query;
    }

    public function headings(): array
    {
        return array_merge(
            ['Ism Familiya', 'Hemis ID', 'Guruh'],
            $this->modules->pluck('name')->toArray()
        );
    }

    public function map($student): array
    {
        $row = [
            $student->name ?? '-',
            $student->login ?? '-',
            $student->group->name ?? '-',
        ];

        $results = $student->usersTestsResults ? $student->usersTestsResults->keyBy('id') : collect();

        foreach ($this->modules as $module) {
            $moduleResult = $results->get($module->id);
            if ($moduleResult && $moduleResult->pivot->diagnosis) {
                $row[] = $moduleResult->pivot->diagnosis ? $moduleResult->pivot->diagnosis : 'YO\'Q';
            } else {
                $row[] = 'YO\'Q';
            }
        }

        return $row;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;
                $highestColumn = $sheet->getHighestColumn();
                $highestRow = $sheet->getHighestRow();

                // 1. Sarlavha uslubi (A1 dan oxirgi ustungacha)
                $sheet->getStyle("A1:{$highestColumn}1")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '4472C4'],
                    ],
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ]
                ]);

                // 2. Ma'lumotlarni ranglash (D ustunidan boshlab)
                for ($row = 2; $row <= $highestRow; $row++) {
                    // D ustunidan boshlab oxirigacha har bir katakni tekshiramiz
                    for ($col = 4; $col <= \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn); $col++) {
                        $cellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $row;
                        $value = $sheet->getCell($cellCoordinate)->getValue();

                        $color = ($value !== 'YO\'Q') ? '70AD47' : 'FF0000'; // Yashil yoki Qizil

                        $sheet->getStyle($cellCoordinate)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => $color],
                            ],
                            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
                        ]);
                    }
                }
            }
        ];
    }
}
