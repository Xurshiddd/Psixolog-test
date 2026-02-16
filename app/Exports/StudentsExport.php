<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class StudentsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $students;

    public function __construct(Collection $students)
    {
        $this->students = $students;
    }

    public function collection(): Collection
    {
        return $this->students;
    }

    public function headings(): array
    {
        return [
            'Ism Familiya',
            'Login',
            'Telefon',
            'Guruh',
            'Mutaxassislik',
            'E-mail',
            'Ro\'yxatdan o\'tgan sana',
            'Test Statusi'
        ];
    }

    public function map($student): array
    {
        return [
            $student->name ?? '-',
            $student->login ?? '-',
            $student->phone ?? '-',
            $student->group->name ?? '-',
            $student->speciality->name ?? '-',
            $student->email ?? '-',
            $student->created_at ? $student->created_at->format('Y-m-d') : '-',
            $student->usersTestsResults && $student->usersTestsResults->count() > 0 ? 'Topshirgan' : 'Topshirmagan'
        ];
    }
}
