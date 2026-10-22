<?php

namespace App\Http\Controllers;

use App\Jobs\BuildPassportArchiveJob;
use App\Services\PassportArchiveService;
use App\Services\PassportArchiveTarget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Tanlangan turdagi barcha foydalanuvchilar passportlarini ZIP qilib berish:
 * start → status (polling) → download.
 */
class PassportArchiveController extends Controller
{
    public function start(Request $request, PassportArchiveService $service): JsonResponse
    {
        $target = $this->target($request);

        // Bir vaqtda bitta tanlov uchun faqat bitta arxiv yasaladi.
        if (! PassportArchiveService::isRunning($target)) {
            $service->markStarted($target);
            BuildPassportArchiveJob::dispatchAfterResponse($target);
        }

        return response()->json(PassportArchiveService::status($target));
    }

    public function status(Request $request): JsonResponse
    {
        return response()->json(PassportArchiveService::status($this->target($request)));
    }

    public function download(Request $request): BinaryFileResponse
    {
        $target = $this->target($request);
        $path = PassportArchiveService::archivePath($target);

        abort_unless(
            PassportArchiveService::status($target)['status'] === 'done' && is_file($path),
            404,
            'Arxiv topilmadi. Qaytadan shakllantiring.'
        );

        // Arxiv faqat bir marta yuklab olinadi — server diskida katta fayl qolmaydi.
        Cache::forget(PassportArchiveService::cacheKey($target));

        return response()
            ->download($path, PassportArchiveService::downloadName($target), ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend();
    }

    /**
     * Fakultet faqat talabalar uchun ma'noga ega — xodimlarda e'tiborga olinmaydi.
     */
    private function target(Request $request): PassportArchiveTarget
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in(array_keys(PassportArchiveService::ROLES))],
            'faculity_id' => ['nullable', 'integer', 'exists:faculities,id'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        return new PassportArchiveTarget(
            role: $validated['role'],
            faculityId: $validated['role'] === 'student' && filled($validated['faculity_id'] ?? null)
                ? (int) $validated['faculity_id']
                : null,
            from: $validated['from'] ?? null,
            to: $validated['to'] ?? null,
        );
    }
}
