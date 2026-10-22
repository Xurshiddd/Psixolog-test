<?php

namespace App\Services;

use App\Models\Faculity;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Tanlangan turdagi (talaba yoki xodim) barcha foydalanuvchilar passportlarini
 * bitta ZIP arxivga yig'adi. Saqlangan passport bo'lsa o'shasi, bo'lmasa
 * vaqtincha shakllantirilgani olinadi — bazaga hech narsa yozilmaydi.
 *
 * Foydalanuvchilar ko'p bo'lishi mumkinligi uchun: ular bo'laklab o'qiladi,
 * har bir PDF xotirada emas, vaqtinchalik faylda turadi va arxiv har
 * FLUSH_EVERY ta fayldan keyin diskka yozilib, vaqtinchalik fayllar o'chiriladi.
 */
class PassportArchiveService
{
    public const ROLES = ['student' => 'talabalar', 'employee' => 'xodimlar'];

    private const CHUNK = 50;

    private const FLUSH_EVERY = 100;

    private const STALE_AFTER = 600;

    public function __construct(
        private UserPassportService $userPassportService,
    ) {}

    /** Zudlik bilan hal qilinmagan holati bor talaba fayli boshiga qo'yiladi. */
    public const CRITICAL_PREFIX = 'QIZIL__';

    /**
     * Har bir tur + fakultet juftligi uchun alohida arxiv va holat yuritiladi.
     * Fakultet faqat talabalarda bor, xodimlarda e'tiborga olinmaydi.
     */
    private static function scope(string $role, ?int $faculityId): string
    {
        return $role === 'student' && $faculityId !== null ? $role.'-f'.$faculityId : $role;
    }

    public static function cacheKey(string $role, ?int $faculityId = null): string
    {
        return 'passport-archive:'.self::scope($role, $faculityId);
    }

    public static function archivePath(string $role, ?int $faculityId = null): string
    {
        return storage_path('app/private/passport-archives/'.self::scope($role, $faculityId).'.zip');
    }

    public static function downloadName(string $role, ?int $faculityId = null): string
    {
        $faculty = $role === 'student' && $faculityId !== null
            ? '_'.Str::slug(Faculity::find($faculityId)?->name ?? 'fakultet-'.$faculityId)
            : '';

        return 'passportlar_'.self::ROLES[$role].$faculty.'_'.now()->format('Y-m-d_H-i').'.zip';
    }

    /** @return array<string, mixed> */
    public static function status(string $role, ?int $faculityId = null): array
    {
        return Cache::get(self::cacheKey($role, $faculityId)) ?? ['status' => 'idle'];
    }

    /**
     * Jarayon uzilib qolsa holat "running"da qotib qolmasligi uchun: oxirgi
     * yangilanishdan beri STALE_AFTER soniya o'tgan bo'lsa ishlamayapti deb olinadi.
     */
    public static function isRunning(string $role, ?int $faculityId = null): bool
    {
        $status = self::status($role, $faculityId);

        return $status['status'] === 'running'
            && now()->timestamp - ($status['heartbeat'] ?? 0) < self::STALE_AFTER;
    }

    public function markStarted(string $role, ?int $faculityId = null): void
    {
        $this->putStatus(self::cacheKey($role, $faculityId), ['status' => 'running', 'total' => 0, 'processed' => 0]);
    }

    public function build(string $role, ?int $faculityId = null): void
    {
        $key = self::cacheKey($role, $faculityId);
        $archivePath = self::archivePath($role, $faculityId);
        $workDir = storage_path('app/private/passport-archives/tmp-'.self::scope($role, $faculityId).'-'.uniqid());

        File::ensureDirectoryExists(dirname($archivePath));
        File::ensureDirectoryExists($workDir);
        File::delete($archivePath);

        $query = User::query()
            ->where('role', $role)
            ->when(
                $role === 'student' && $faculityId !== null,
                fn ($q) => $q->where('faculity_id', $faculityId)
            )
            ->when(
                $role === 'student',
                fn ($q) => $q->withExists([
                    'criticalAlerts as has_pending_critical_alert' => fn ($alerts) => $alerts->pending()->visibleModule(),
                ])
            );
        $total = (clone $query)->count();
        $processed = 0;
        $failed = 0;
        $usedNames = [];
        $pending = [];

        $this->putStatus($key, ['status' => 'running', 'total' => $total, 'processed' => 0]);

        $zip = $this->openZip($archivePath);

        try {
            $query->with('passport')->chunkById(self::CHUNK, function ($users) use (
                &$zip, &$processed, &$failed, &$usedNames, &$pending, $archivePath, $workDir, $key, $total
            ) {
                foreach ($users as $user) {
                    try {
                        $tmpFile = $workDir.'/'.$user->id.'.pdf';
                        file_put_contents($tmpFile, $this->userPassportService->renderPdfContent($user));

                        $zip->addFile($tmpFile, $this->uniqueName($user, $usedNames));
                        $pending[] = $tmpFile;
                    } catch (Throwable $e) {
                        $failed++;
                        Log::warning('Passport arxiv: PDF yasab bo\'lmadi', [
                            'user_id' => $user->id,
                            'message' => $e->getMessage(),
                        ]);
                    }

                    $processed++;
                }

                if (count($pending) >= self::FLUSH_EVERY) {
                    $zip = $this->flush($zip, $archivePath, $pending);
                }

                $this->putStatus($key, ['status' => 'running', 'total' => $total, 'processed' => $processed]);

                // DomPDF har hujjatdan keyin xotirani to'liq bo'shatmaydi.
                gc_collect_cycles();
            });

            $this->flush($zip, $archivePath, $pending, reopen: false);

            if (! is_file($archivePath)) {
                throw new RuntimeException('Arxivga birorta ham passport qo\'shilmadi.');
            }

            $this->putStatus($key, [
                'status' => 'done',
                'total' => $total,
                'processed' => $processed,
                'failed' => $failed,
                'size' => filesize($archivePath),
                'finished_at' => now()->toDateTimeString(),
            ]);
        } catch (Throwable $e) {
            Log::error('Passport arxiv xatosi', ['role' => $role, 'message' => $e->getMessage()]);
            File::delete($archivePath);

            $this->putStatus($key, [
                'status' => 'failed',
                'message' => $e->getMessage(),
                'finished_at' => now()->toDateTimeString(),
            ]);
        } finally {
            File::deleteDirectory($workDir);
        }
    }

    private function openZip(string $path): ZipArchive
    {
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::CREATE) !== true) {
            throw new RuntimeException('ZIP arxivni ochib bo\'lmadi.');
        }

        return $zip;
    }

    /**
     * ZipArchive fayllarni faqat close() paytida o'qiydi — shu yerda yozib,
     * vaqtinchalik PDF'larni o'chiramiz va arxivni davom ettirish uchun qayta ochamiz.
     *
     * @param  list<string>  $pending
     */
    private function flush(ZipArchive $zip, string $path, array &$pending, bool $reopen = true): ZipArchive
    {
        if ($zip->numFiles > 0 && ! $zip->close()) {
            throw new RuntimeException('ZIP arxivni yozib bo\'lmadi.');
        }

        File::delete($pending);
        $pending = [];

        return $reopen ? $this->openZip($path) : $zip;
    }

    /** @param  array<string, true>  $usedNames */
    private function uniqueName(User $user, array &$usedNames): string
    {
        $name = $this->userPassportService->fileName($user);

        if ($user->has_pending_critical_alert) {
            $name = self::CRITICAL_PREFIX.$name;
        }

        if (isset($usedNames[$name])) {
            $name = preg_replace('/\.pdf$/', '_'.$user->id.'.pdf', $name);
        }

        $usedNames[$name] = true;

        return $name;
    }

    /** @param  array<string, mixed>  $data */
    private function putStatus(string $key, array $data): void
    {
        Cache::put($key, $data + ['heartbeat' => now()->timestamp], now()->addDay());
    }
}
