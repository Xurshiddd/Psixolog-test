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
use ZipStream\ZipStream;

/**
 * Tanlangan turdagi (talaba yoki xodim) barcha foydalanuvchilar passportlarini
 * bitta ZIP arxivga yig'adi. Saqlangan passport bo'lsa o'shasi, bo'lmasa
 * vaqtincha shakllantirilgani olinadi — bazaga hech narsa yozilmaydi.
 *
 * Foydalanuvchilar ko'p bo'lishi mumkinligi uchun: ular bo'laklab o'qiladi va
 * har bir PDF yasalishi bilan arxiv oxiriga oqim ko'rinishida yoziladi —
 * xotirada ham, diskda ham ortiqcha nusxa qolmaydi.
 */
class PassportArchiveService
{
    public const ROLES = ['student' => 'talabalar', 'employee' => 'xodimlar'];

    private const CHUNK = 50;

    /** Arxiv yozilayotganda diskda kamida shuncha joy qolishi kerak. */
    private const MIN_FREE_BYTES = 500 * 1024 * 1024;

    private const STALE_AFTER = 600;

    public function __construct(
        private UserPassportService $userPassportService,
    ) {}

    /** Zudlik bilan hal qilinmagan holati bor talaba fayli boshiga qo'yiladi. */
    public const CRITICAL_PREFIX = 'QIZIL__';

    public static function cacheKey(PassportArchiveTarget $target): string
    {
        return 'passport-archive:'.$target->scope();
    }

    public static function archivePath(PassportArchiveTarget $target): string
    {
        return storage_path('app/private/passport-archives/'.$target->scope().'.zip');
    }

    public static function downloadName(PassportArchiveTarget $target): string
    {
        $faculty = $target->faculityId !== null
            ? '_'.Str::slug(Faculity::find($target->faculityId)?->name ?? 'fakultet-'.$target->faculityId)
            : '';

        $period = $target->hasPeriod()
            ? '_'.($target->from ?? 'boshidan').'_'.($target->to ?? now()->toDateString())
            : '';

        return 'passportlar_'.self::ROLES[$target->role].$faculty.$period.'_'.now()->format('Y-m-d_H-i').'.zip';
    }

    /** @return array<string, mixed> */
    public static function status(PassportArchiveTarget $target): array
    {
        return Cache::get(self::cacheKey($target)) ?? ['status' => 'idle'];
    }

    /**
     * Jarayon uzilib qolsa holat "running"da qotib qolmasligi uchun: oxirgi
     * yangilanishdan beri STALE_AFTER soniya o'tgan bo'lsa ishlamayapti deb olinadi.
     */
    public static function isRunning(PassportArchiveTarget $target): bool
    {
        $status = self::status($target);

        return $status['status'] === 'running'
            && now()->timestamp - ($status['heartbeat'] ?? 0) < self::STALE_AFTER;
    }

    public function markStarted(PassportArchiveTarget $target): void
    {
        $this->putStatus(self::cacheKey($target), ['status' => 'running', 'total' => 0, 'processed' => 0]);
    }

    public function build(PassportArchiveTarget $target): void
    {
        $role = $target->role;
        $key = self::cacheKey($target);
        $archivePath = self::archivePath($target);
        $partPath = $archivePath.'.part';

        File::ensureDirectoryExists(dirname($archivePath));
        $this->cleanupLeftovers($target);

        $query = User::query()
            ->where('role', $role)
            ->when(
                $target->faculityId !== null,
                fn ($q) => $q->where('faculity_id', $target->faculityId)
            )
            // Muddat test topshirilgan sanaga qo'llanadi: "dan" bo'sh bo'lsa
            // boshidan, "gacha" bo'sh bo'lsa shu kungacha; ikkalasi bo'sh bo'lsa
            // filtr umuman qo'yilmaydi (test topshirmaganlar ham kiradi).
            ->when(
                $target->hasPeriod(),
                fn ($q) => $q->whereHas('usersTestsResults', function ($results) use ($target) {
                    if ($target->from !== null) {
                        $results->where('users_tests_results.created_at', '>=', $target->from.' 00:00:00');
                    }

                    $results->where('users_tests_results.created_at', '<=', ($target->to ?? now()->toDateString()).' 23:59:59');
                })
            )
            ->when(
                $role === 'student',
                fn ($q) => $q->withExists([
                    'criticalAlerts as has_pending_critical_alert' => fn ($alerts) => $alerts->pending()->visibleModule(),
                ])
            );
        $total = (clone $query)->count();
        $processed = 0;
        $added = 0;
        $failed = 0;
        $usedNames = [];

        $this->putStatus($key, ['status' => 'running', 'total' => $total, 'processed' => 0]);

        $handle = fopen($partPath, 'wb');

        try {
            if ($handle === false) {
                throw new RuntimeException('Arxiv faylini ochib bo\'lmadi.');
            }

            // Har bir PDF arxiv oxiriga ketma-ket yoziladi: vaqtinchalik fayl
            // ham, arxivni qayta yozish ham yo'q — diskda faqat arxivning o'zi turadi.
            $zip = new ZipStream(
                outputStream: $handle,
                sendHttpHeaders: false,
                defaultEnableZeroHeader: true,
            );

            $query->with('passport')->chunkById(self::CHUNK, function ($users) use (
                $zip, &$processed, &$added, &$failed, &$usedNames, $archivePath, $key, $total
            ) {
                $this->ensureFreeSpace(dirname($archivePath));

                foreach ($users as $user) {
                    try {
                        $zip->addFile(
                            fileName: $this->uniqueName($user, $usedNames),
                            data: $this->userPassportService->renderPdfContent($user),
                        );
                        $added++;
                    } catch (Throwable $e) {
                        $failed++;
                        Log::warning('Passport arxiv: PDF yasab bo\'lmadi', [
                            'user_id' => $user->id,
                            'message' => $e->getMessage(),
                        ]);
                    }

                    $processed++;
                }

                $this->putStatus($key, ['status' => 'running', 'total' => $total, 'processed' => $processed]);

                // DomPDF har hujjatdan keyin xotirani to'liq bo'shatmaydi.
                gc_collect_cycles();
            });

            if ($added === 0) {
                throw new RuntimeException('Arxivga birorta ham passport qo\'shilmadi.');
            }

            $zip->finish();
            fclose($handle);
            $handle = null;

            File::move($partPath, $archivePath);

            $this->putStatus($key, [
                'status' => 'done',
                'total' => $total,
                'processed' => $processed,
                'failed' => $failed,
                'size' => filesize($archivePath),
                'finished_at' => now()->toDateTimeString(),
            ]);
        } catch (Throwable $e) {
            Log::error('Passport arxiv xatosi', ['scope' => $target->scope(), 'message' => $e->getMessage()]);

            $this->putStatus($key, [
                'status' => 'failed',
                'message' => $e->getMessage(),
                'finished_at' => now()->toDateTimeString(),
            ]);
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }

            File::delete($partPath);
        }
    }

    /**
     * Disk to'lib qolsa server (baza, sessiyalar) ham ishlamay qoladi — undan
     * oldin to'xtaymiz.
     */
    private function ensureFreeSpace(string $dir): void
    {
        $free = @disk_free_space($dir);

        if ($free !== false && $free < self::MIN_FREE_BYTES) {
            throw new RuntimeException(
                'Serverda bo\'sh joy kam qoldi ('.round($free / 1048576).' MB). '
                .'Joy bo\'shating yoki fakultet bo\'yicha alohida yuklab oling.'
            );
        }
    }

    /**
     * Shu tur/fakultetning eski arxivi va avvalgi muvaffaqiyatsiz urinishlardan
     * qolgan fayllar (ZipArchive vaqtinchalik nusxalari, tmp- papkalar) o'chiriladi.
     */
    private function cleanupLeftovers(PassportArchiveTarget $target): void
    {
        $archivePath = self::archivePath($target);
        $dir = dirname($archivePath);

        File::delete([$archivePath, ...(glob($archivePath.'.*') ?: [])]);

        foreach (glob($dir.'/tmp-'.$target->scope().'-*', GLOB_ONLYDIR) ?: [] as $tmpDir) {
            File::deleteDirectory($tmpDir);
        }
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
