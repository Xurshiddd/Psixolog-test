<?php

namespace App\Application\AdminStudents\Services;

use App\Models\StudentPassport;
use App\Models\User;
use App\Services\StudentPdfExportService;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class AdminStudentPassportService
{
    public function __construct(
        private StudentPdfExportService $studentPdfExportService,
    ) {}

    /**
     * @param  array{
     *     character_traits: array<int, string>,
     *     temperament_type: string,
     *     student_conclusion: string
     * }  $validated
     */
    public function downloadGeneratedPassport(User $user, array $validated)
    {
        abort_unless($user->role === 'student', Response::HTTP_NOT_FOUND, 'Talaba topilmadi.');

        $passport = StudentPassport::updateOrCreate(
            ['student_id' => $user->id],
            [
                'character_traits' => array_map(
                    static fn (string $trait): string => trim($trait),
                    $validated['character_traits']
                ),
                'temperament_type' => trim($validated['temperament_type']),
                'student_conclusion' => trim($validated['student_conclusion']),
            ]
        );

        $user->load(['group', 'speciality', 'usersCategory']);

        $pdf = $this->studentPdfExportService->generateStudentPassportPdf($user, $this->passportPayload($passport));
        $fileName = 'ijtimoiy-psixologik-passport_'.Str::slug($user->name ?: 'talaba').'.pdf';

        return $pdf->download($fileName);
    }

    public function downloadSavedPassport(User $user)
    {
        abort_unless($user->role === 'student', Response::HTTP_NOT_FOUND, 'Talaba topilmadi.');

        $user->load(['group', 'speciality', 'usersCategory', 'studentPassport']);

        abort_if($user->studentPassport === null, Response::HTTP_NOT_FOUND, 'Passport ma\'lumoti topilmadi.');

        $pdf = $this->studentPdfExportService->generateStudentPassportPdf(
            $user,
            $this->passportPayload($user->studentPassport)
        );

        $fileName = 'ijtimoiy-psixologik-passport_'.Str::slug($user->name ?: 'talaba').'.pdf';

        return $pdf->download($fileName);
    }

    /**
     * @return array<string, array<int, string>|string|null>
     */
    private function passportPayload(StudentPassport $passport): array
    {
        return [
            'character_traits' => $passport->character_traits ?? [],
            'temperament_type' => $passport->temperament_type,
            'student_conclusion' => $passport->student_conclusion,
        ];
    }
}
