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
        $role = $this->role($request);

        // Bir vaqtda bitta tur uchun faqat bitta arxiv yasaladi.
        if (! PassportArchiveService::isRunning($role)) {
            $service->markStarted($role);
            BuildPassportArchiveJob::dispatchAfterResponse($role);
        }

        return response()->json(PassportArchiveService::status($role));
    }

    public function status(Request $request): JsonResponse
    {
        return response()->json(PassportArchiveService::status($this->role($request)));
    }

    public function download(Request $request): BinaryFileResponse
    {
        $role = $this->role($request);
        $path = PassportArchiveService::archivePath($role);

        abort_unless(
            PassportArchiveService::status($role)['status'] === 'done' && is_file($path),
            404,
            'Arxiv topilmadi. Qaytadan shakllantiring.'
        );

        // Arxiv faqat bir marta yuklab olinadi — server diskida katta fayl qolmaydi.
        Cache::forget(PassportArchiveService::cacheKey($role));

        return response()
            ->download($path, PassportArchiveService::downloadName($role), ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend();
    }

    private function role(Request $request): string
    {
        return $request->validate([
            'role' => ['required', Rule::in(array_keys(PassportArchiveService::ROLES))],
        ])['role'];
    }
}
