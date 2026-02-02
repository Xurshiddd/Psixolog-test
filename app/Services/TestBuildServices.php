<?php

namespace App\Services;
use App\Models\Module;
use App\Models\Test;
use App\Models\TestOption;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
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
                if (!isset($questionData['options']) || count($questionData['options']) === 0) {
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
            $updatedQuestionIds = [];
            foreach ($data['questions'] as $questionData) {
                if (isset($questionData['id'])) {
                    $question = Test::find($questionData['id']);
                    if ($question) {
                        if (
                            isset($questionData['question_image']) &&
                            $questionData['question_image'] instanceof \Illuminate\Http\UploadedFile
                        ) {
                            if ($question->image) {
                                Storage::disk('public')->delete($question->image);
                            }
                            $imagePath = $questionData['question_image']->store('test_images', 'public');
                            $questionData['image'] = $imagePath;
                        } else {
                            $questionData['image'] = $question->image;
                        }

                        $question->update([
                            'question' => $questionData['question'],
                            'type' => $questionData['type'],
                            'image' => $questionData['image'],
                        ]);
                        $updatedQuestionIds[] = $question->id;

                        if (!isset($questionData['options']) || count($questionData['options']) === 0) {
                            continue;
                        }
                        $this->syncOptions($question, $questionData['options']);
                    }
                } else {
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
                    if (!isset($questionData['options']) || count($questionData['options']) === 0) {
                        continue;
                    }
                    $updatedQuestionIds[] = $question->id;
                    $this->syncOptions($question, $questionData['options']);
                }
            }

            $questionImage = $module->tests()->whereNotIn('id', $updatedQuestionIds)->get();
            foreach ($questionImage as $question) {
                if ($question->image) {
                    Storage::disk('public')->delete($question->image);
                }
            }

            $module->tests()->whereNotIn('id', $updatedQuestionIds)->delete();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
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
                $updatedOptionIds[] = $option->id;
            }
        }
        $test->options()->whereNotIn('id', $updatedOptionIds)->delete();
    }
}
