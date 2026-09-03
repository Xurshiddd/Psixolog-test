<?php

use App\Models\Module;
use App\Models\SolveTest;
use App\Models\Test;
use App\Models\TestOption;
use App\Models\User;
use App\Support\RiskFlag;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

function makeSecUser(string $role): User
{
    return User::factory()->create([
        'role' => $role,
        'group_id' => null,
        'speciality_id' => null,
    ]);
}

function makeSecModule(): array
{
    $module = Module::create([
        'name' => 'Xavfsizlik testi',
        'is_active' => true,
        'shuffle' => false,
        'audiences' => ['student', 'employee', 'guest'],
    ]);

    $test = Test::create(['module_id' => $module->id, 'question' => 'Savol', 'type' => 'single']);
    $critical = TestOption::create([
        'test_id' => $test->id, 'option_text' => 'Ha', 'option_value' => 5, 'is_critical' => true,
    ]);

    return [$module, $test, $critical];
}

test('xavfli variant belgisi test yechuvchiga chiqmaydi', function () {
    [$module] = makeSecModule();
    $student = makeSecUser('student');

    $this->actingAs($student)
        ->get("/test/take/{$module->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->component('Student/TakeTest')
            ->missing('module.tests.0.options.0.is_critical')
        );
});

test('xavfli variant belgisi modulni tahrirlashda adminga ko\'rinadi', function () {
    [$module] = makeSecModule();
    $admin = makeSecUser('admin');

    $this->actingAs($admin)
        ->get("/test/edit/{$module->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->component('EditTest')
            ->where('module.tests.0.options.0.is_critical', true)
        );
});

test('xodim va nomzod bayroq yoza olmaydi', function () {
    [$module, $test, $critical] = makeSecModule();
    $student = makeSecUser('student');

    SolveTest::create([
        'user_id' => $student->id,
        'module_id' => $module->id,
        'test_id' => $test->id,
        'test_option_id' => $critical->id,
    ]);
    $module->usersTestsResults()->attach($student->id, ['flag' => RiskFlag::RED]);

    $payload = [
        'report_module_id' => $module->id,
        'min_score' => 0,
        'max_score' => 9999,
        'conclusions' => ['student' => ''],
        'flags' => ['student' => RiskFlag::GREEN],
        'overwrite_auto_conclusion' => false,
    ];

    foreach (['employee', 'guest', 'student'] as $role) {
        $this->actingAs(makeSecUser($role))
            ->post('/dashboard/module-score-report/conclusions', $payload)
            ->assertRedirect();

        expect(DB::table('users_tests_results')->where('user_id', $student->id)->value('flag'))
            ->toBe(RiskFlag::RED);
    }

    // Admin esa yoza oladi.
    $this->actingAs(makeSecUser('admin'))
        ->post('/dashboard/module-score-report/conclusions', $payload)
        ->assertRedirect();

    expect(DB::table('users_tests_results')->where('user_id', $student->id)->value('flag'))
        ->toBe(RiskFlag::GREEN);
});

test('xodim va nomzod ball oralig\'i hisobotini yuklab ololmaydi', function () {
    [$module] = makeSecModule();

    foreach (['employee', 'guest', 'student'] as $role) {
        $this->actingAs(makeSecUser($role))
            ->get("/dashboard/module-score-report/export?report_module_id={$module->id}&min_score=0&max_score=99")
            ->assertRedirect();
    }
});
