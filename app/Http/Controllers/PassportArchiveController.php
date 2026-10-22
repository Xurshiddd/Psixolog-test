<?php

namespace App\Http\Controllers;

use App\Jobs\BuildPassportArchiveJob;
use App\Services\PassportArchiveService;
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
        [$role, $faculityId] = $this->target($request);

        // Bir vaqtda bitta tur (va fakultet) uchun faqat bitta arxiv yasaladi.
        if (! PassportArchiveService::isRunning($role, $faculityId)) {
            $service->markStarted($role, $faculityId);
            BuildPassportArchiveJob::dispatchAfterResponse($role, $faculityId);
        }

        return response()->json(PassportArchiveService::status($role, $faculityId));
    }

    public function status(Request $request): JsonResponse
    {
        return response()->json(PassportArchiveService::status(...$this->target($request)));
    }

    public function download(Request $request): BinaryFileResponse
    {
        [$role, $faculityId] = $this->target($request);
        $path = PassportArchiveService::archivePath($role, $faculityId);

        abort_unless(
            PassportArchiveService::status($role, $faculityId)['status'] === 'done' && is_file($path),
            404,
            'Arxiv topilmadi. Qaytadan shakllantiring.'
        );

        // Arxiv faqat bir marta yuklab olinadi — server diskida katta fayl qolmaydi.
        Cache::forget(PassportArchiveService::cacheKey($role, $faculityId));

        return response()
            ->download($path, PassportArchiveService::downloadName($role, $faculityId), ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend();
    }

    /**
     * Fakultet faqat talabalar uchun ma'noga ega — xodimlarda e'tiborga olinmaydi.
     *
     * @return array{0: string, 1: int|null}
     */
    private function target(Request $request): array
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in(array_keys(PassportArchiveService::ROLES))],
            'faculity_id' => ['nullable', 'integer', 'exists:faculities,id'],
        ]);

        $faculityId = $validated['role'] === 'student' && filled($validated['faculity_id'] ?? null)
            ? (int) $validated['faculity_id']
            : null;

        return [$validated['role'], $faculityId];
    }
}
