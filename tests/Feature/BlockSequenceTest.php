<?php

use App\Models\Block;
use App\Models\Module;
use App\Models\Test;
use App\Models\TestOption;
use App\Models\User;
use App\Services\BlockSequenceService;
use Inertia\Testing\AssertableInertia as Assert;

function makeBlockUser(string $role): User
{
    return User::factory()->create([
        'role' => $role,
        'group_id' => null,
        'speciality_id' => null,
    ]);
}

function makeSequenceModule(string $name): Module
{
    $module = Module::create([
        'name' => $name,
        'is_active' => true,
        'shuffle' => false,
        'audiences' => ['student'],
    ]);

    $test = Test::create(['module_id' => $module->id, 'question' => "{$name} savoli", 'type' => 'single']);
    TestOption::create(['test_id' => $test->id, 'option_text' => 'Ha', 'option_value' => 1]);

    return $module;
}

test('admin blok yaratadi va modullarni ketma-ketlikda biriktiradi', function () {
    $admin = makeBlockUser('admin');
    $first = makeSequenceModule('Birinchi');
    $second = makeSequenceModule('Ikkinchi');

    $this->actingAs($admin)
        ->post('/blocks', [
            'name' => 'Kirish bloki',
            'description' => 'Ketma-ket yechiladi',
            'is_active' => true,
            'modules' => [$second->id, $first->id],
        ])
        ->assertRedirect(route('blocks.index'));

    $block = Block::firstWhere('name', 'Kirish bloki');

    expect($block->modules()->pluck('name')->all())->toBe(['Ikkinchi', 'Birinchi']);
});

test('bitta modul ikki blokka biriktirilmaydi', function () {
    $admin = makeBlockUser('admin');
    $module = makeSequenceModule('Yagona');

    $block = Block::create(['name' => 'Birinchi blok', 'is_active' => true]);
    $block->modules()->sync([$module->id => ['position' => 1]]);

    $this->actingAs($admin)
        ->post('/blocks', [
            'name' => 'Ikkinchi blok',
            'is_active' => true,
            'modules' => [$module->id],
        ])
        ->assertSessionHasErrors('modules.0');
});

test('talaba oldingi modulni yechmaguncha keyingisi yopiq turadi', function () {
    $student = makeBlockUser('student');
    $first = makeSequenceModule('Birinchi');
    $second = makeSequenceModule('Ikkinchi');

    $block = Block::create(['name' => 'Ketma-ketlik', 'is_active' => true]);
    $block->modules()->sync([
        $first->id => ['position' => 1],
        $second->id => ['position' => 2],
    ]);

    $sequence = app(BlockSequenceService::class);

    expect($sequence->isLocked($student, $first))->toBeFalse()
        ->and($sequence->isLocked($student, $second))->toBeTrue()
        ->and($sequence->blockingModuleName($student, $second))->toBe('Birinchi');

    // Testga kirishga urinilganda ro'yxatga qaytariladi.
    $this->actingAs($student)
        ->get("/test/take/{$second->id}")
        ->assertRedirect(route('student_test_index'))
        ->assertSessionHas('error');

    $first->usersTestsResults()->attach($student->id, ['result_fake' => 'x', 'result_real' => 'y']);

    expect($sequence->isLocked($student->fresh(), $second))->toBeFalse();
});

test('talaba testlar sahifasi modullarni bloklar bo\'yicha guruhlaydi', function () {
    $student = makeBlockUser('student');
    $first = makeSequenceModule('Birinchi');
    $second = makeSequenceModule('Ikkinchi');

    $block = Block::create(['name' => 'Kirish bloki', 'is_active' => true]);
    $block->modules()->sync([
        $first->id => ['position' => 1],
        $second->id => ['position' => 2],
    ]);

    $this->actingAs($student)
        ->get('/student/index')
        ->assertInertia(fn (Assert $page) => $page
            ->component('Student/Tests')
            ->has('blocks', 1)
            ->where('blocks.0.name', 'Kirish bloki')
            ->where('blocks.0.modules.0.is_locked', false)
            ->where('blocks.0.modules.1.is_locked', true)
        );
});
