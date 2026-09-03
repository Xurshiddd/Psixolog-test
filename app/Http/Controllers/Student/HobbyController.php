<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Hobby;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * Talabaning qiziqishlari (hobby). Talaba o'z panelidan bir nechta qiziqish
 * qo'shadi; ular ijtimoiy-psixologik passportda ham ishlatiladi.
 */
class HobbyController extends Controller
{
    public function index(Request $request)
    {
        $this->ensureStudent($request);

        return Inertia::render('Student/Hobbies', [
            'hobbies' => $request->user()->hobbies()->get(['id', 'name']),
        ]);
    }

    /**
     * Qiziqishlar ro'yxatini butunligicha yangilaydi: forma bir nechta
     * maydondan iborat, saqlashda eskisi almashtiriladi.
     */
    public function store(Request $request)
    {
        $this->ensureStudent($request);

        $validated = $request->validate([
            'hobbies' => 'present|array|max:30',
            'hobbies.*' => 'nullable|string|max:120',
        ], [
            'hobbies.max' => 'Ko\'pi bilan 30 ta qiziqish kiritish mumkin.',
            'hobbies.*.max' => 'Qiziqish nomi 120 ta belgidan oshmasligi kerak.',
        ]);

        $user = $request->user();

        // Bo'sh maydonlar tashlab yuboriladi, takrorlanganlari bittaga tushadi.
        $names = collect($validated['hobbies'])
            ->map(fn (?string $name): string => trim((string) $name))
            ->filter()
            ->unique(fn (string $name): string => mb_strtolower($name))
            ->values();

        DB::transaction(function () use ($user, $names): void {
            $user->hobbies()->delete();

            $now = now();

            Hobby::insertOrIgnore(
                $names->map(fn (string $name): array => [
                    'user_id' => $user->id,
                    'name' => $name,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all()
            );
        });

        return redirect()->back()->with('success', 'Qiziqishlar saqlandi.');
    }

    /**
     * `student` middleware xodim va nomzodni ham o'tkazadi, qiziqishlar esa
     * faqat talabada bo'ladi.
     */
    private function ensureStudent(Request $request): void
    {
        abort_unless($request->user()?->role === 'student', 403);
    }
}
