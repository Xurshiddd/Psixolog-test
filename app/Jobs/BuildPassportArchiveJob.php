<?php

namespace App\Jobs;

use App\Services\PassportArchiveService;
use App\Services\PassportArchiveTarget;
use Illuminate\Foundation\Bus\Dispatchable;

class BuildPassportArchiveJob
{
    use Dispatchable;

    public function __construct(public PassportArchiveTarget $target) {}

    /**
     * `dispatchAfterResponse()` bilan javob yuborilgandan keyin ishlaydi —
     * queue worker shart emas. Minglab PDF uzoq davom etishi mumkin, shuning
     * uchun vaqt cheklovi olib tashlanadi.
     */
    public function handle(PassportArchiveService $service): void
    {
        @set_time_limit(0);
        ignore_user_abort(true);
        @ini_set('memory_limit', '1024M');

        $service->build($this->target);
    }
}
