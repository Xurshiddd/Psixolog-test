<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Http;

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
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $token = env('HEMIS_TOKEN');
        $baseUrl = 'https://student.ttyesi.uz/rest/v1/data/student-list';
        $students = User::where('role', 'student')->pluck('login');
        $count = 0;
        foreach ($students as $student) {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
                ])->get("{$baseUrl}?search={$student}");
                if ($response->successful()) {
                    $data = $response->json();
                    $item = $data['data']['items'][0] ?? null;
                    if ($item) {
                        $count++;
                        $user = User::where('login', $student)->firstOrFail();
                        $user->update([
                            'faculty_id' => $item['faculty_id'],
                        ]);
                        $this->info("✅ Ma'lumot saqlandi: {$student} -> count {$count}");
                        
                    } else {
                        $this->warn("⚠️ Item mavjud emas: {$student}");
                    }
                    
                    // 🔁 So'rovlar oralig'iga delay qo'shish (rate limit uchun)
                    usleep(150000);
                }
            }
        }

}
