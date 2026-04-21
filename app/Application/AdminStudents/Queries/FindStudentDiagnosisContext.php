<?php

namespace App\Application\AdminStudents\Queries;

use App\Models\Module;
use App\Models\SolveTest;
use App\Models\User;

class FindStudentDiagnosisContext
{
    /**
     * @return array{module: Module, answers: \Illuminate\Support\Collection, result: Module}|null
     */
    public function execute(User $user, int $moduleId): ?array
    {
        $module = Module::query()
            ->with(['tests.options'])
            ->findOrFail($moduleId);

        $answers = SolveTest::query()
            ->where('user_id', $user->id)
            ->where('module_id', $moduleId)
            ->get()
            ->groupBy('test_id');

        $result = $user->usersTestsResults()
            ->where('module_id', $moduleId)
            ->first();

        if (! $result) {
            return null;
        }

        return [
            'module' => $module,
            'answers' => $answers,
            'result' => $result,
        ];
    }
}
