<?php

namespace App\Services;

use App\Models\Module;
use App\Models\ResultCategory;
use App\Models\SolveTest;
use App\Models\TestOption;
use App\Models\User;
use App\TestFakeResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentTestServices
{
    public function __construct(
        private SolveTest $solveTest,
        private DashboardAggregateCacheService $dashboardAggregateCacheService,
        private CriticalAlertService $criticalAlertService,
    ) {}

    /**
     * Process batch test submission
     *
     * @param  array  $answers  - array where keys are test_ids and values are answers
     * @param  string  $source  - submission source such as web or mobile_app
     * @return array - results with consequence_fake for each test
     */
    public function processBatchSubmission(int $userId, int $moduleId, array $answers, string $source = 'web'): array
    {
        $module = Module::find($moduleId);
        $student = User::find($userId);

        try {
            DB::beginTransaction();
            $testOptionIds = [];
            foreach ($answers as $key => $value) {
                if (is_array($value)) {
                    for ($i = 0; $i < count($value); $i++) {
                        $this->solveTest->create([
                            'user_id' => $userId,
                            'module_id' => $moduleId,
                            'test_id' => $key,
                            'test_option_id' => $value[$i],
                        ]);
                        $testOptionIds[] = $value[$i];
                    }
                } elseif (is_int($value)) {
                    $this->solveTest->create([
                        'user_id' => $userId,
                        'module_id' => $moduleId,
                        'test_id' => $key,
                        'test_option_id' => $value,
                    ]);
                    $testOptionIds[] = $value;
                } else {
                    $this->solveTest->create([
                        'user_id' => $userId,
                        'module_id' => $moduleId,
                        'test_id' => $key,
                        'answer' => $value,
                    ]);
                }
            }

            // Map submitted test_option_ids to their option_value and find the most frequent option_value
            $resultFake = TestFakeResult::NORMAL->value;
            $realResult = null;
            if (! empty($testOptionIds)) {
                $countedOptions = array_count_values($testOptionIds); // counts per option id

                // Fetch option_value for each option id
                $optionMap = TestOption::whereIn('id', array_keys($countedOptions))
                    ->pluck('option_value', 'id')
                    ->toArray();

                // Aggregate counts per option_value
                $valueCounts = [];
                foreach ($countedOptions as $optionId => $cnt) {
                    $optValue = $optionMap[$optionId] ?? null;
                    if ($optValue === null) {
                        continue;
                    }
                    $valueCounts[$optValue] = ($valueCounts[$optValue] ?? 0) + $cnt;
                }

                if (! empty($valueCounts)) {
                    // Find the most frequent option_value
                    $mostFrequentValue = null;
                    $maxCount = -1;
                    foreach ($valueCounts as $val => $cnt) {
                        if ($cnt > $maxCount) {
                            $maxCount = $cnt;
                            $mostFrequentValue = $val;
                        }
                    }

                    // Find ResultCategory with this value for the module
                    $resultCategory = ResultCategory::where('module_id', $moduleId)
                        ->where('value', $mostFrequentValue)
                        ->first();

                    if ($resultCategory) {
                        $resultFake = $resultCategory->fake_diagnostic;
                        $realResult = $resultCategory->diagnostic;
                    }
                }
            }

            $module->usersTestsResults()->attach($userId, [
                'result_fake' => $resultFake,
                'result_real' => $realResult,
            ]);
            // Xavfli deb belgilangan variant tanlangan bo'lsa, zudlik bilan
            // ish olib borish uchun ogohlantirish yoziladi.
            $criticalCount = $student
                ? $this->criticalAlertService->recordForSubmission($student, $moduleId, $testOptionIds)
                : 0;

            DB::commit();
            $this->dashboardAggregateCacheService->forgetAll();
            app(ResultCategoryStatsService::class)->flush();
            app(ModuleScoreRangeReportService::class)->flush();

            if ($student && $module) {
                $description = $source === 'mobile_app'
                    ? 'Student submitted a test via mobile app'
                    : 'Student submitted a test';

                activity('student_test')
                    ->causedBy($student)
                    ->performedOn($module)
                    ->event('test_submitted')
                    ->withProperties([
                        'source' => $source,
                        'module_id' => $module->id,
                        'module_name' => $module->name,
                        'answers_count' => count($answers),
                        'result_fake' => $resultFake,
                        'result_real' => $realResult,
                        'critical_alerts' => $criticalCount,
                    ])
                    ->log($description);
            }

            // Notify admin and psiholog users
            $usersToNotify = \App\Models\User::whereIn('role', ['admin', 'psiholog'])->get();
            \Illuminate\Support\Facades\Notification::send($usersToNotify, new \App\Notifications\ModuleCompletedNotification($student, $module));

            return ['status' => 'success', 'message' => 'Test submitted successfully'];
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th->getMessage());

            return ['status' => 'error', 'message' => $th->getMessage()];
        }
    }
}
