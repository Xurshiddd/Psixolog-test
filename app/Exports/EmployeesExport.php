<?php

namespace App\Exports;

use App\Models\Module;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class EmployeesExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
{
    public function __construct(
        protected Builder $query,
        protected ?Collection $modules = null,
    ) {
        $this->modules = $modules ?? Module::orderBy('name')->get();
    }

    public function query(): Builder
    {
        return clone $this->query;
    }

    public function headings(): array
    {
        $headings = [
            'Ism Familiya',
            'Hodim ID',
            'Bo\'lim',
            'Lavozim turi',
        ];

        foreach ($this->modules as $module) {
            $headings[] = $module->name;
        }

        return $headings;
    }

    public function map($employee): array
    {
        $resultIds = $employee->usersTestsResults?->pluck('id')->flip() ?? collect();

        $row = [
            $employee->name ?? '-',
            $employee->employee?->employee_id_number ?? '-',
            $employee->employee?->department_name ?? '-',
            $employee->employee?->employee_type_name ?? '-',
        ];

        foreach ($this->modules as $module) {
            $row[] = $resultIds->has($module->id) ? 'HA' : 'YO\'Q';
        }

        return $row;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
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
                    ],
                ]);

                $highestColumn = $event->sheet->getHighestColumn();
                $highestRow = $event->sheet->getHighestRow();
                $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

                for ($row = 2; $row <= $highestRow; $row++) {
                    for ($col = 5; $col <= $highestColumnIndex; $col++) {
                        $cellCoordinate = Coordinate::stringFromColumnIndex($col).$row;
                        $value = $event->sheet->getCell($cellCoordinate)->getValue();
                        $color = $value === 'HA' ? '70AD47' : 'FF0000';

                        $event->sheet->getStyle($cellCoordinate)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => $color],
                            ],
                            'font' => [
                                'bold' => true,
                                'color' => ['rgb' => 'FFFFFF'],
                            ],
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_CENTER,
                                'vertical' => Alignment::VERTICAL_CENTER,
                            ],
                        ]);
                    }
                }
            },
        ];
    }
}
