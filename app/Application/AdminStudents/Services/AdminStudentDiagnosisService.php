<?php

namespace App\Application\AdminStudents\Services;

use App\Ai\Agents\DiagnosisAgent;
use App\Application\AdminStudents\Queries\FindStudentDiagnosisContext;
use App\Models\Module;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AdminStudentDiagnosisService
{
    public function __construct(
        private DiagnosisAgent $diagnosisAgent,
        private FindStudentDiagnosisContext $findStudentDiagnosisContext,
    ) {}

    public function update(User $user, int $moduleId, ?string $diagnosis): int
    {
        return $user->usersTestsResults()->updateExistingPivot($moduleId, [
            'diagnosis' => $diagnosis,
        ]);
    }

    /**
     * @return array{status: int, payload: array<string, string>}
     */
    public function generate(User $user, int $moduleId): array
    {
        [$provider, $providerError] = $this->resolveProvider();

        if ($providerError !== null) {
            return [
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'payload' => ['error' => $providerError],
            ];
        }

        $context = $this->findStudentDiagnosisContext->execute($user, $moduleId);

        if ($context === null) {
            return [
                'status' => Response::HTTP_NOT_FOUND,
                'payload' => ['error' => 'Natija topilmadi.'],
            ];
        }

        $prompt = $this->buildPrompt(
            $user,
            $context['module'],
            $context['answers'],
            $context['result'],
        );

        try {
            $response = $this->diagnosisAgent->prompt($prompt, provider: $provider);
            $text = trim($response->text);

            if ($text === '') {
                return [
                    'status' => Response::HTTP_BAD_GATEWAY,
                    'payload' => ['error' => 'AI provider bo\'sh javob qaytardi. Qaytadan urinib ko\'ring.'],
                ];
            }

            return [
                'status' => Response::HTTP_OK,
                'payload' => ['diagnosis' => $text],
            ];
        } catch (\Throwable $e) {
            return [
                'status' => $this->exceptionStatus($e),
                'payload' => ['error' => $this->formatExceptionMessage($provider, $e)],
            ];
        }
    }

    /**
     * @return array{
     *     status: int,
     *     headers: array<string, string>,
     *     callback: \Closure(): void
     * }|array{status: int, payload: array<string, string>}
     */
    public function stream(User $user, int $moduleId): array
    {
        [$provider, $providerError] = $this->resolveProvider();

        if ($providerError !== null) {
            return [
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'payload' => ['error' => $providerError],
            ];
        }

        $context = $this->findStudentDiagnosisContext->execute($user, $moduleId);

        if ($context === null) {
            return [
                'status' => Response::HTTP_NOT_FOUND,
                'payload' => ['error' => 'Natija topilmadi.'],
            ];
        }

        $prompt = $this->buildPrompt(
            $user,
            $context['module'],
            $context['answers'],
            $context['result'],
        );

        return [
            'status' => Response::HTTP_OK,
            'headers' => [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache, no-transform',
                'X-Accel-Buffering' => 'no',
            ],
            'callback' => function () use ($prompt, $provider): void {
                try {
                    $stream = $this->diagnosisAgent->stream($prompt, provider: $provider);

                    foreach ($stream as $event) {
                        echo 'data: '.((string) $event)."\n\n";
                        @ob_flush();
                        flush();
                    }
                } catch (\Throwable $e) {
                    echo 'data: '.json_encode([
                        'type' => 'error',
                        'message' => $this->formatExceptionMessage($provider, $e),
                    ], JSON_UNESCAPED_UNICODE)."\n\n";
                }

                echo "data: [DONE]\n\n";
                @ob_flush();
                flush();
            },
        ];
    }

    /**
     * @return array{0: string, 1: string|null}
     */
    private function resolveProvider(): array
    {
        $provider = (string) config('ai.diagnosis_provider', 'deepseek');
        $providerKey = config("ai.providers.{$provider}.key");

        if (blank($providerKey)) {
            return [$provider, strtoupper($provider).' API kaliti sozlanmagan. .env faylida kerakli provider kalitini kiriting.'];
        }

        return [$provider, null];
    }

    private function exceptionStatus(\Throwable $e): int
    {
        $message = mb_strtolower($e->getMessage());

        if (str_contains($message, 'insufficient credits') || str_contains($message, 'quota')) {
            return Response::HTTP_PAYMENT_REQUIRED;
        }

        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    private function formatExceptionMessage(string $provider, \Throwable $e): string
    {
        $message = mb_strtolower($e->getMessage());

        if (str_contains($message, 'insufficient credits') || str_contains($message, 'quota')) {
            return strtoupper($provider).' hisobida kredit yoki quota yetarli emas. Provider kabinetida balansni to\'ldiring yoki .env da AI_DIAGNOSIS_PROVIDER ni boshqa providerga almashtiring.';
        }

        if (str_contains($message, 'rate limit')) {
            return strtoupper($provider).' vaqtinchalik so\'rov limitiga yetdi. Birozdan keyin qayta urinib ko\'ring.';
        }

        return 'AI xulosa olishda xatolik: '.Str::limit($e->getMessage(), 250);
    }

    private function buildPrompt(User $user, Module $module, Collection $answers, Module $result): string
    {
        $lines = [
            'AI uchun diagnostika konteksti:',
            "Talaba: {$user->name}",
            "Modul nomi: {$module->name}",
            'Modul tavsifi: '.($module->description ?: 'Mavjud emas'),
            'Tizimdagi avtomatik xulosa: '.($result->pivot->result_real ?: 'Mavjud emas'),
            'Psixologning oldingi xulosasi: '.($result->pivot->diagnosis ?: 'Mavjud emas'),
            '',
            'Muhim eslatma:',
            '- Har bir savolni modul tavsifi bilan birga tahlil qiling.',
            '- Tanlangan va tanlanmagan javoblarning ikkalasini ham hisobga oling.',
            "- Faqat berilgan ma'lumotga tayangan holda xulosa yozing.",
            '',
            'Test savollari va javoblar tafsiloti:',
            '',
        ];

        $valueCounts = [];

        foreach ($module->tests as $index => $test) {
            $num = $index + 1;
            $lines[] = "Savol {$num}: {$test->question}";
            $lines[] = "Savol turi: {$test->type}";

            $testAnswers = $answers->get($test->id, collect());

            if ($test->type === 'text') {
                $answer = trim((string) ($testAnswers->first()?->answer ?? 'Javob berilmagan'));
                $lines[] = "Talabaning yozma javobi: {$answer}";
                $lines[] = '';

                continue;
            }

            $selectedOptions = [];
            $unselectedOptions = [];

            foreach ($test->options as $option) {
                $selected = $testAnswers->contains('test_option_id', $option->id);
                $value = $option->option_value ?? 0;
                $optionLine = "{$option->option_text} (ball: {$value})";

                if ($selected) {
                    $valueCounts[$value] = ($valueCounts[$value] ?? 0) + 1;
                    $selectedOptions[] = $optionLine;
                } else {
                    $unselectedOptions[] = $optionLine;
                }
            }

            $lines[] = 'Tanlangan javoblar:';
            if ($selectedOptions === []) {
                $lines[] = ' - Tanlangan javob yo\'q';
            } else {
                foreach ($selectedOptions as $selectedOption) {
                    $lines[] = " - {$selectedOption}";
                }
            }

            $lines[] = 'Tanlanmagan javoblar:';
            if ($unselectedOptions === []) {
                $lines[] = ' - Tanlanmagan javob yo\'q';
            } else {
                foreach ($unselectedOptions as $unselectedOption) {
                    $lines[] = " - {$unselectedOption}";
                }
            }

            $lines[] = '';
        }

        if ($valueCounts !== []) {
            arsort($valueCounts);

            $lines[] = 'Tanlangan variantlar bo‘yicha umumiy taqsimot:';
            foreach ($valueCounts as $value => $count) {
                $lines[] = " - Ball {$value}: {$count} ta tanlov";
            }
            $lines[] = '';
        }

        $lines[] = 'Vazifa:';
        $lines[] = 'Talabaning psixologik holati haqida professional, ehtiyotkor va amaliy xulosa yozing.';
        $lines[] = 'Xulosani yozishda modul nomi, modul tavsifi, har bir savol va tanlangan/tanlanmagan barcha javoblardan foydalaning.';
        $lines[] = "Natijani faqat quyidagi 2 bo'limda yozing:";
        $lines[] = "1. E'tibor talab qiladigan jihatlar.";
        $lines[] = '2. Tavsiyalar.';
        $lines[] = "Har bir bo'lim 1-3 jumladan oshmasin.";
        $lines[] = "Medikal tashxis qo'ymang va mavjud ma'lumotdan tashqariga chiqib keskin hukm qilmang.";

        return implode("\n", $lines);
    }
}
