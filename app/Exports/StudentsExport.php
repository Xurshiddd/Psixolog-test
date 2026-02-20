<?php

namespace App\Exports;

use App\Models\User;
use App\Models\Module;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class StudentsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
}
