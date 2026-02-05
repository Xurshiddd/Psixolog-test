<?php

namespace App\Services;
use App\Models\Module;
use App\Models\SolveTest;
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
            foreach ($answers as $key => $value) {
            if (is_array($value)) {
                for ($i=0; $i < count($value); $i++) { 
                    $this->solveTest->create([
                        'user_id' => $userId,
                        'module_id' => $moduleId,
                        'test_id' => $key,
                        'test_option_id' => $value[$i],
                    ]);
                }
            }elseif (is_int($value)) {
                $this->solveTest->create([
                    'user_id'=> $userId,
                    'module_id'=> $moduleId,
                    'test_id'=> $key,
                    'test_option_id'=> $value,
                ]);
            }else{
                $this->solveTest->create([
                    'user_id' => $userId,
                    'module_id' => $moduleId,
                    'test_id' => $key,
                    'answer' => $value,
                ]);
            }
        }
        $module->usersTestsResults()->attach($userId, [
            'result_fake' => TestFakeResult::NORMAL->value,
            'result_real' => null,
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
