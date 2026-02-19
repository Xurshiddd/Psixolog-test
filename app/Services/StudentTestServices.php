<?php

namespace App\Services;
use App\Models\Module;
use App\Models\SolveTest;
use App\Models\ResultCategory;
use App\Models\TestOption;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\TestFakeResult;
class StudentTestServices
{
    public function __construct(private SolveTest $solveTest){}
    /**
     * Process batch test submission
     * 
     * @param int $userId
     * @param int $moduleId
     * @param array $answers - array where keys are test_ids and values are answers
     * @return array - results with consequence_fake for each test
     */
    public function processBatchSubmission(int $userId, int $moduleId, array $answers): array
    {
        $module = Module::find($moduleId);
        try {
            DB::beginTransaction();
            $testOptionIds = [];
            foreach ($answers as $key => $value) {
                if (is_array($value)) {
                    for ($i=0; $i < count($value); $i++) { 
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
                        'user_id'=> $userId,
                        'module_id'=> $moduleId,
                        'test_id'=> $key,
                        'test_option_id'=> $value,
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
            if (!empty($testOptionIds)) {
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

                if (!empty($valueCounts)) {
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
            DB::commit();
            return ['status' => 'success', 'message' => 'Test submitted successfully'];
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th->getMessage());
            return ['status' => 'error', 'message' => $th->getMessage()];
        }
    }
}
