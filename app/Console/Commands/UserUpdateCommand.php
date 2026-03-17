<?php

namespace App\Console\Commands;

use App\Models\Faculity;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UserUpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:user-update-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'HEMIS dan student ma’lumotlarini yangilaydi';

    public function handle(): int
    {
        $token = config('services.hemis.token');
        $baseUrl = 'https://student.ttyesi.uz/rest/v1/data/student-list';

        if (blank($token)) {
            $this->error('HEMIS_TOKEN topilmadi.');
            return self::FAILURE;
        }

        // Faqat kerakli ustunlarni olish
        $students = User::query()
            ->where('role', 'student')
            ->select('id', 'login', 'faculty_id', 'educationTypeCode', 'educationTypeName')
            ->get()
            ->keyBy('login');

        if ($students->isEmpty()) {
            $this->warn('Studentlar topilmadi.');
            return self::SUCCESS;
        }

        // Faculties ni cachega olib qo'yamiz
        $facultyMap = Cache::remember('faculties.code_name_map', now()->addHours(1), function () {
            return Faculity::query()
                ->get(['id', 'code', 'name'])
                ->keyBy('code');
        });

        $updatedCount = 0;
        $notFoundCount = 0;
        $errorCount = 0;

        foreach ($students as $login => $user) {
            try {
                $response = Http::withToken($token)
                    ->acceptJson()
                    ->timeout(15)
                    ->retry(3, 500)
                    ->get($baseUrl, [
                        'search' => $login,
                    ]);

                if (! $response->successful()) {
                    $errorCount++;
                    $this->warn("⚠️ API xatolik: {$login} [{$response->status()}]");
                    continue;
                }

                $item = data_get($response->json(), 'data.items.0');

                if (! $item) {
                    $notFoundCount++;
                    $this->warn("⚠️ Item mavjud emas: {$login}");
                    continue;
                }

                $facultyCode = data_get($item, 'department.code');
                $facultyName = data_get($item, 'department.name');
                $educationTypeCode = (int) data_get($item, 'educationType.code');
                $educationTypeName = data_get($item, 'educationType.name');
                $educationFormCode = (int) data_get($item, 'educationForm.code');
                $educationFormName = data_get($item, 'educationForm.name');

                $facultyId = null;

                if ($facultyCode) {
                    if (! isset($facultyMap[$facultyCode])) {
                        $faculty = Faculity::query()->create([
                            'code' => $facultyCode,
                            'name' => $facultyName,
                        ]);

                        // cache/memory map ni ham update qilamiz
                        $facultyMap->put($facultyCode, $faculty);

                        // umumiy cache ni ham yangilab qo'yamiz
                        Cache::put('faculties.code_name_map', $facultyMap, now()->addHours(1));
                    }

                    $facultyId = $facultyMap[$facultyCode]->id;
                }

                $dirtyData = [
                    'faculty_id' => $facultyId,
                    'education_type_code' => $educationTypeCode,
                    'education_type_name' => $educationTypeName,
                    'education_form_code' => $educationFormCode,
                    'education_form_name' => $educationFormName,
                ];

                // Keraksiz update query yubormaslik uchun
                $user->fill($dirtyData);

                if ($user->isDirty()) {
                    $user->save();
                    $updatedCount++;
                    $this->info("✅ Yangilandi: {$login} -> count {$updatedCount}");
                } else {
                    $this->line("ℹ️ O'zgarish yo'q: {$login}");
                }

                usleep(150000); // 150ms
            } catch (\Throwable $e) {
                $errorCount++;
                Log::error('Student update command error', [
                    'login' => $login,
                    'message' => $e->getMessage(),
                ]);

                $this->error("❌ Xatolik: {$login} -> {$e->getMessage()}");
            }
        }

        $deletedCount = User::query()
            ->where('role', 'student')
            ->where('education_type_code', '!=', 11)
            ->where('education_form_code', '!=', 11)
            ->delete();

        $this->newLine();
        $this->info("✅ Yakunlandi:");
        $this->line(" - Yangilanganlar: {$updatedCount}");
        $this->line(" - Topilmaganlar: {$notFoundCount}");
        $this->line(" - Xatoliklar: {$errorCount}");
        $this->line(" - O'chirilganlar (educationTypeCode != 11): {$deletedCount}");

        return self::SUCCESS;
    }
}