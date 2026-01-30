<?php

namespace App\Services;
use App\Models\Module;
use App\Models\Test;
use App\Models\TestOption;
use Illuminate\Support\Facades\DB;
class TestBuildServices
{
    public function save(array $data): void
    {
        try {
            DB::beginTransaction();
            $module = Module::create([
                'name' => $data['module'],
                'description' => $data['module_description'] ?? null,
                'shuffle' => $data['shuffle']
            ]);
            foreach ($data['questions'] as $questionData) {
                if (isset($questionData['question_image'])) {
                    $imagePath = $questionData['question_image']->store('test_images', 'public');
                    $questionData['image'] = $imagePath;
                }
                $question = Test::create([
                    'module_id' => $module->id,
                    'question' => $questionData['question'],
                    'type' => $questionData['type'],
                    'image' => $questionData['image'] ?? null,
                ]);
                if (count($questionData['options']) === 0) {
                    continue;
                }
                foreach ($questionData['options'] as $optionData) {
                    TestOption::create([
                        'test_id' => $question->id,
                        'option_text' => $optionData['option_text'],
                        'option_value' => $optionData['option_value'] ?? 0,
                    ]);
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception("Module creation failed: " . $e->getMessage());
        }
    }
}
