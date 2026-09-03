<?php

namespace App\Application\AdminStudents\Services;

use App\Application\AdminStudents\Data\AdminStudentFilters;
use App\Application\AdminStudents\Queries\AdminStudentQueryFactory;
use App\Application\AdminStudents\Queries\FindStudentDiagnosisContext;
use App\Models\User;
use App\Services\LookupCacheService;
use App\Support\RiskFlag;

class BuildAdminStudentPages
{
    public function __construct(
        private AdminStudentQueryFactory $adminStudentQueryFactory,
        private LookupCacheService $lookupCacheService,
        private FindStudentDiagnosisContext $findStudentDiagnosisContext,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function indexProps(AdminStudentFilters $filters): array
    {
        $students = $this->adminStudentQueryFactory
            ->makeListQuery($filters)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return [
            'students' => $students,
            'groups' => $this->lookupCacheService->groups(),
            'specialities' => $this->lookupCacheService->specialities(),
            'faculities' => $this->lookupCacheService->faculities(),
            'categories' => $this->lookupCacheService->categories(),
            'filters' => $filters->toArray(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function showProps(User $user, AdminStudentFilters $filters): array
    {
        $user->load(['group', 'speciality', 'faculity', 'usersTestsResults', 'passport', 'usersCategory', 'hobbies']);

        // Umumiy xavf darajasi — natijalarga biriktirilgan bayroqlar
        // orasidagi eng og'iri (qizil > sariq > yashil).
        $flag = RiskFlag::mostSevere($user->usersTestsResults->pluck('pivot.flag'));

        return [
            'student' => $user,
            'results' => $user->usersTestsResults,
            'allCategories' => $this->lookupCacheService->categories(),
            'hobbies' => $user->hobbies->pluck('name')->values(),
            'riskFlag' => $flag === null ? null : [
                'value' => $flag,
                'label' => RiskFlag::label($flag),
                'color' => RiskFlag::color($flag),
            ],
            'filters' => $filters->toArray(),
            'page' => $filters->page,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function showResultProps(User $user, int $moduleId): ?array
    {
        $context = $this->findStudentDiagnosisContext->execute($user, $moduleId);

        if ($context === null) {
            return null;
        }

        /** @var \App\Models\Module $result */
        $result = $context['result'];

        return [
            'student' => $user,
            'module' => $context['module'],
            'answers' => $context['answers'],
            'diagnosis' => $result->pivot->diagnosis,
            'generatedDiagnosis' => $result->pivot->result_real,
        ];
    }
}
