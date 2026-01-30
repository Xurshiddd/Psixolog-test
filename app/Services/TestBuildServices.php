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

    public function update(Module $module, array $data): void
    {
        try {
            DB::beginTransaction();
            $module->update([
                'name' => $data['module'],
                'description' => $data['module_description'] ?? null,
                'shuffle' => $data['shuffle']
            ]);

            // Get existing question IDs to track which ones to keep
            $updatedQuestionIds = [];

            foreach ($data['questions'] as $questionData) {
                if (isset($questionData['id'])) {
                    $question = Test::find($questionData['id']);
                    if ($question) {
                        // Update existing
                        if (isset($questionData['question_image']) && $questionData['question_image'] instanceof \Illuminate\Http\UploadedFile) {
                            $imagePath = $questionData['question_image']->store('test_images', 'public');
                            $questionData['image'] = $imagePath;
                        }

                        $question->update([
                            'question' => $questionData['question'],
                            'type' => $questionData['type'],
                            'image' => $questionData['image'] ?? $question->image,
                        ]);
                        $updatedQuestionIds[] = $question->id;

                        // Handle Options
                        $this->syncOptions($question, $questionData['options']);
                    }
                } else {
                    // Create new
                    if (isset($questionData['question_image']) && $questionData['question_image'] instanceof \Illuminate\Http\UploadedFile) {
                        $imagePath = $questionData['question_image']->store('test_images', 'public');
                        $questionData['image'] = $imagePath;
                    }
                    $question = Test::create([
                        'module_id' => $module->id,
                        'question' => $questionData['question'],
                        'type' => $questionData['type'],
                        'image' => $questionData['image'] ?? null,
                    ]);
                    $this->syncOptions($question, $questionData['options']);
                }
            }

            // Delete questions that were removed
            $module->tests()->whereNotIn('id', $updatedQuestionIds)->delete();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception("Module update failed: " . $e->getMessage());
        }
    }

    private function syncOptions(Test $test, array $optionsData)
    {
        $updatedOptionIds = [];

        foreach ($optionsData as $optionData) {
            if (isset($optionData['id'])) {
                $option = TestOption::find($optionData['id']);
                if ($option) {
                    $option->update([
                        'option_text' => $optionData['option_text'],
                        'option_value' => $optionData['option_value'] ?? 0,
                    ]);
                    $updatedOptionIds[] = $option->id;
                }
            } else {
                $option = TestOption::create([
                    'test_id' => $test->id,
                    'option_text' => $optionData['option_text'],
                    'option_value' => $optionData['option_value'] ?? 0,
                ]);
            }
        }

        $test->options()->whereNotIn('id', $updatedOptionIds)->delete();
    }
}
