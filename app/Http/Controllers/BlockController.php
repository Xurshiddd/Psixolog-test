<?php

namespace App\Http\Controllers;

use App\Http\Requests\BlockRequest;
use App\Models\Block;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * Bloklarni boshqarish. Blok — modullardan katta birlik; blokka tanlangan
 * modullar ketma-ket joylashadi va talaba ularni shu tartibda yechadi.
 */
class BlockController extends Controller
{
    public function index()
    {
        $blocks = Block::query()
            ->with(['modules:id,name,is_active'])
            ->withCount('modules')
            ->orderBy('name')
            ->paginate(10)
            ->through(fn (Block $block): array => [
                'id' => $block->id,
                'name' => $block->name,
                'description' => $block->description,
                'is_active' => $block->is_active,
                'modules_count' => $block->modules_count,
                'modules' => $block->modules->map(fn (Module $module): array => [
                    'id' => $module->id,
                    'name' => $module->name,
                    'is_active' => $module->is_active,
                    'position' => (int) $module->pivot->position,
                ])->values(),
            ]);

        return Inertia::render('Blocks/Index', [
            'blocks' => $blocks,
            'blocksCount' => Block::count(),
            'unassignedModulesCount' => Module::query()
                ->whereDoesntHave('blocks')
                ->count(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Blocks/Create', [
            'availableModules' => $this->availableModules(),
        ]);
    }

    public function store(BlockRequest $request)
    {
        $data = $request->validated();

        DB::transaction(function () use ($data): void {
            $block = Block::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'],
            ]);

            $block->modules()->sync($this->positionedModules($data['modules']));
        });

        return redirect()->route('blocks.index')->with('success', 'Blok muvaffaqiyatli yaratildi.');
    }

    public function edit(Block $block)
    {
        $block->load('modules:id,name');

        return Inertia::render('Blocks/Edit', [
            'block' => [
                'id' => $block->id,
                'name' => $block->name,
                'description' => $block->description,
                'is_active' => $block->is_active,
                'modules' => $block->modules->map(fn (Module $module): array => [
                    'id' => $module->id,
                    'name' => $module->name,
                ])->values(),
            ],
            'availableModules' => $this->availableModules($block),
        ]);
    }

    public function update(BlockRequest $request, Block $block)
    {
        $data = $request->validated();

        DB::transaction(function () use ($block, $data): void {
            $block->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'],
            ]);

            $block->modules()->sync($this->positionedModules($data['modules']));
        });

        return redirect()->route('blocks.index')->with('success', 'Blok yangilandi.');
    }

    public function destroy(Block $block)
    {
        $block->delete();

        return redirect()->back()->with('success', 'Blok o\'chirildi.');
    }

    public function changeStatus(Request $request)
    {
        $request->validate([
            'block_id' => 'required|exists:blocks,id',
        ]);

        $block = Block::findOrFail($request->block_id);
        $block->update(['is_active' => ! $block->is_active]);

        return redirect()->back()->with('success', 'Blok holati yangilandi.');
    }

    /**
     * Tanlash uchun modullar: hech qaysi blokka biriktirilmaganlar va
     * (tahrirlashda) shu blokning o'z modullari.
     *
     * @return \Illuminate\Support\Collection<int, array{id: int, name: string, is_active: bool}>
     */
    private function availableModules(?Block $block = null)
    {
        return Module::query()
            ->select('id', 'name', 'is_active')
            ->where(function ($query) use ($block): void {
                $query->whereDoesntHave('blocks');

                if ($block) {
                    $query->orWhereHas('blocks', fn ($q) => $q->where('blocks.id', $block->id));
                }
            })
            ->orderBy('name')
            ->get()
            ->map(fn (Module $module): array => [
                'id' => $module->id,
                'name' => $module->name,
                'is_active' => (bool) $module->is_active,
            ]);
    }

    /**
     * Modul id'larini `sync` uchun tartib raqami bilan birga qaytaradi.
     *
     * @param  list<int>  $moduleIds
     * @return array<int, array{position: int}>
     */
    private function positionedModules(array $moduleIds): array
    {
        $payload = [];

        foreach (array_values($moduleIds) as $position => $moduleId) {
            $payload[(int) $moduleId] = ['position' => $position + 1];
        }

        return $payload;
    }
}
