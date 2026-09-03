<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Faculity;
use App\Models\Group;
use App\Models\Module;
use App\Models\Speciality;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class LookupCacheService
{
    private const CACHE_TTL_MINUTES = 60;

    private const GROUPS_KEY = 'lookups:groups';

    private const SPECIALITIES_KEY = 'lookups:specialities';

    private const FACULITIES_KEY = 'lookups:faculities';

    private const CATEGORIES_KEY = 'lookups:categories';

    private const MODULES_KEY = 'lookups:modules';

    public function groups(): Collection
    {
        return $this->rememberCollection(self::GROUPS_KEY, function (): array {
            return Group::query()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get()
                ->map(fn (Group $group): array => [
                    'id' => (int) $group->id,
                    'name' => (string) $group->name,
                ])
                ->all();
        });
    }

    public function specialities(): Collection
    {
        return $this->rememberCollection(self::SPECIALITIES_KEY, function (): array {
            return Speciality::query()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get()
                ->map(fn (Speciality $speciality): array => [
                    'id' => (int) $speciality->id,
                    'name' => (string) $speciality->name,
                ])
                ->all();
        });
    }

    public function faculities(): Collection
    {
        return $this->rememberCollection(self::FACULITIES_KEY, function (): array {
            return Faculity::query()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get()
                ->map(fn (Faculity $faculity): array => [
                    'id' => (int) $faculity->id,
                    'name' => (string) $faculity->name,
                ])
                ->all();
        });
    }

    public function categories(): Collection
    {
        return $this->rememberCollection(self::CATEGORIES_KEY, function (): array {
            return Category::query()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get()
                ->map(fn (Category $category): array => [
                    'id' => (int) $category->id,
                    'name' => (string) $category->name,
                ])
                ->all();
        });
    }

    public function modules(): Collection
    {
        return $this->rememberCollection(self::MODULES_KEY, function (): array {
            return Module::query()
                ->visible()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get()
                ->map(fn (Module $module): array => [
                    'id' => (int) $module->id,
                    'name' => (string) $module->name,
                ])
                ->all();
        });
    }

    /**
     * @param  list<string>|null  $keys
     */
    public function forget(?array $keys = null): void
    {
        foreach ($keys ?? $this->allKeys() as $key) {
            Cache::forget($key);
        }
    }

    /**
     * @return list<string>
     */
    public function lookupKeysForModel(string $modelClass): array
    {
        return match ($modelClass) {
            Group::class => [self::GROUPS_KEY],
            Speciality::class => [self::SPECIALITIES_KEY],
            Faculity::class => [self::FACULITIES_KEY],
            Category::class => [self::CATEGORIES_KEY],
            Module::class => [self::MODULES_KEY],
            default => [],
        };
    }

    private function rememberCollection(string $key, \Closure $resolver): Collection
    {
        return collect(
            Cache::remember(
                $key,
                now()->addMinutes(self::CACHE_TTL_MINUTES),
                $resolver
            )
        )->map(fn (array $item): object => (object) $item);
    }

    /**
     * @return list<string>
     */
    private function allKeys(): array
    {
        return [
            self::GROUPS_KEY,
            self::SPECIALITIES_KEY,
            self::FACULITIES_KEY,
            self::CATEGORIES_KEY,
            self::MODULES_KEY,
        ];
    }
}
