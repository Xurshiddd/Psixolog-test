<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Conversation;
use App\Models\Faculity;
use App\Models\Group;
use App\Models\Message;
use App\Models\Module;
use App\Models\ResultCategory;
use App\Models\Speciality;
use App\Models\Test;
use App\Models\TestOption;
use App\Models\User;
use App\Services\DashboardAggregateCacheService;
use App\Services\LookupCacheService;
use App\Services\ModuleScoreRangeReportService;
use App\Services\ResultCategoryStatsService;
use App\Services\UnreadRequestsCountService;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerActivityListeners();
        $this->registerCacheInvalidationListeners();
    }

    protected function configureDefaults(): void
    {
        Schema::defaultStringLength(195);
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }

    protected function registerActivityListeners(): void
    {
        Event::listen(Login::class, function (Login $event): void {
            activity('auth')
                ->causedBy($event->user)
                ->performedOn($event->user)
                ->event('login')
                ->withProperties([
                    'role' => $event->user->role,
                ])
                ->log('User logged in');
        });
    }

    protected function registerCacheInvalidationListeners(): void
    {
        foreach ([Group::class, Speciality::class, Faculity::class, Category::class, Module::class] as $lookupModelClass) {
            $lookupModelClass::saved(fn ($model) => $this->forgetLookupCacheFor($lookupModelClass));
            $lookupModelClass::deleted(fn ($model) => $this->forgetLookupCacheFor($lookupModelClass));
        }

        foreach ([Category::class, Faculity::class, Group::class, Module::class, ResultCategory::class, Test::class, TestOption::class, User::class] as $dashboardModelClass) {
            $dashboardModelClass::saved(fn ($model) => $this->forgetDashboardCaches());
            $dashboardModelClass::deleted(fn ($model) => $this->forgetDashboardCaches());
        }

        Message::saved(function (Message $message): void {
            $conversation = $message->relationLoaded('conversation')
                ? $message->conversation
                : $message->conversation()->first(['id', 'student_id', 'channel']);

            if ($conversation) {
                app(UnreadRequestsCountService::class)->forgetForConversation($conversation);
            }
        });

        Message::deleted(function (Message $message): void {
            $conversation = $message->relationLoaded('conversation')
                ? $message->conversation
                : $message->conversation()->first(['id', 'student_id', 'channel']);

            if ($conversation) {
                app(UnreadRequestsCountService::class)->forgetForConversation($conversation);
            }
        });

        Conversation::saved(function (Conversation $conversation): void {
            app(UnreadRequestsCountService::class)->forgetForStudentAndChannel(
                (int) $conversation->student_id,
                $conversation->channel,
            );

            if ($conversation->wasChanged(['student_id', 'channel'])) {
                app(UnreadRequestsCountService::class)->forgetForStudentAndChannel(
                    (int) $conversation->getOriginal('student_id'),
                    $conversation->getOriginal('channel'),
                );
            }
        });

        Conversation::deleted(function (Conversation $conversation): void {
            app(UnreadRequestsCountService::class)->forgetForStudentAndChannel(
                (int) $conversation->student_id,
                $conversation->channel,
            );
        });
    }

    private function forgetLookupCacheFor(string $modelClass): void
    {
        $lookupCache = app(LookupCacheService::class);
        $lookupCache->forget($lookupCache->lookupKeysForModel($modelClass));
    }

    private function forgetDashboardCaches(): void
    {
        app(DashboardAggregateCacheService::class)->forgetAll();
        app(ResultCategoryStatsService::class)->flush();
        app(ModuleScoreRangeReportService::class)->flush();
    }
}
