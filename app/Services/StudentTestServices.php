<?php

namespace App\Services;
use App\Models\Module;
use App\Models\SolveTest;
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
    }
}
