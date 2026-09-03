<?php

namespace App\Services;

use App\Models\Block;
use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Blokdagi modullar ketma-ketligini hisoblaydi: foydalanuvchi oldingi
 * modulni yechmaguncha keyingisi ochilmaydi.
 *
 * Faqat status yoqilgan (`is_active`) va foydalanuvchi auditoriyasiga mos
 * modullar hisobga olinadi — o'chirilgan modul ketma-ketlikni to'smaydi.
 */
class BlockSequenceService
{
    /**
     * Foydalanuvchi yechgan modul id'lari.
     *
     * @return Collection<int, int>
     */
    public function solvedModuleIds(User $user): Collection
    {
        return $user->usersTestsResults()->pluck('modules.id')->map(fn ($id): int => (int) $id);
    }

    /**
     * Foydalanuvchiga ko'rinadigan modullar, bloklar bo'yicha guruhlangan.
     * Blokka biriktirilmagan modullar oxirida alohida guruhda chiqadi.
     *
     * @return list<array{
     *     id: int|null,
     *     name: string,
     *     description: string|null,
     *     modules: list<array<string, mixed>>
     * }>
     */
    public function groupedForUser(User $user, ?Collection $modules = null): array
    {
        $modules ??= $this->visibleModules($user);
        $solvedIds = $this->solvedModuleIds($user)->all();

        $blocks = Block::query()
            ->active()
            ->with('modules:id')
            ->orderBy('name')
            ->get();

        $groups = [];
        $assignedIds = [];

        foreach ($blocks as $block) {
            $blockModuleIds = $block->modules->pluck('id')->all();
            $assignedIds = array_merge($assignedIds, $blockModuleIds);

            // Blokdagi tartib: `block_module.position`.
            $ordered = $modules
                ->whereIn('id', $blockModuleIds)
                ->sortBy(fn (Module $module): int => $this->positionIn($block, $module))
                ->values();

            if ($ordered->isEmpty()) {
                continue;
            }

            $groups[] = [
                'id' => $block->id,
                'name' => $block->name,
                'description' => $block->description,
                'modules' => $this->sequencedModules($ordered, $solvedIds, true),
            ];
        }

        $standalone = $modules->whereNotIn('id', $assignedIds)->sortBy('id')->values();

        if ($standalone->isNotEmpty()) {
            $groups[] = [
                'id' => null,
                'name' => 'Boshqa modullar',
                'description' => null,
                'modules' => $this->sequencedModules($standalone, $solvedIds, false),
            ];
        }

        return $groups;
    }

    /**
     * Modul foydalanuvchi uchun yopiqmi — blokdagi undan oldingi modullardan
     * kamida bittasi yechilmagan bo'lsa, yopiq.
     */
    public function isLocked(User $user, Module $module): bool
    {
        return $this->firstUnsolvedPredecessor($user, $module) !== null;
    }

    /**
     * Blokda shu moduldan oldin turgan, hali yechilmagan birinchi modul nomi —
     * talabaga "avval nimani yechish kerak" deb ko'rsatish uchun.
     */
    public function blockingModuleName(User $user, Module $module): ?string
    {
        return $this->firstUnsolvedPredecessor($user, $module)?->name;
    }

    /**
     * Blokda shu moduldan oldin turgan va hali yechilmagan birinchi modul.
     * Faqat foydalanuvchiga ko'rinadigan modullar hisobga olinadi.
     */
    private function firstUnsolvedPredecessor(User $user, Module $module): ?Module
    {
        $block = Block::query()
            ->active()
            ->whereHas('modules', fn ($query) => $query->whereKey($module->id))
            ->with('modules:id')
            ->first();

        if ($block === null) {
            return null;
        }

        $position = $this->positionIn($block, $module);
        $solvedIds = $this->solvedModuleIds($user)->all();

        return $this->visibleModules($user)
            ->filter(fn (Module $candidate): bool => $block->modules->contains('id', $candidate->id)
                && $this->positionIn($block, $candidate) < $position
                && ! in_array((int) $candidate->id, $solvedIds, true))
            ->sortBy(fn (Module $candidate): int => $this->positionIn($block, $candidate))
            ->first();
    }

    /**
     * Foydalanuvchi ko'ra oladigan modullar: status yoqilgan va auditoriyaga mos.
     *
     * @return Collection<int, Module>
     */
    public function visibleModules(User $user): Collection
    {
        return Module::query()
            ->where('is_active', true)
            ->forAudience($user->role)
            ->get();
    }

    /**
     * @param  Collection<int, Module>  $modules
     * @param  list<int>  $solvedIds
     * @return list<array<string, mixed>>
     */
    private function sequencedModules(Collection $modules, array $solvedIds, bool $enforceSequence): array
    {
        $payload = [];
        $previousSolved = true;

        foreach ($modules as $index => $module) {
            $isSolved = in_array((int) $module->id, $solvedIds, true);
            $isLocked = $enforceSequence && ! $previousSolved;

            $payload[] = [
                'id' => $module->id,
                'name' => $module->name,
                'description' => $module->description,
                'position' => $index + 1,
                'tests_count' => $module->tests_count ?? $module->tests()->count(),
                'is_solved' => $isSolved,
                'is_locked' => $isLocked,
            ];

            if ($enforceSequence) {
                $previousSolved = $previousSolved && $isSolved;
            }
        }

        return $payload;
    }

    private function positionIn(Block $block, Module $module): int
    {
        $related = $block->modules->firstWhere('id', $module->id);

        return $related ? (int) $related->pivot->position : PHP_INT_MAX;
    }
}
