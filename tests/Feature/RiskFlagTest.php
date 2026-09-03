<?php

use App\Models\Module;
use App\Models\SolveTest;
use App\Models\Test;
use App\Models\TestOption;
use App\Models\User;
use App\Services\DashboardAggregateCacheService;
use App\Services\ModuleScoreRangeReportService;
use App\Support\RiskFlag;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

function makeFlagUser(string $role): User
{
    return User::factory()->create([
        'role' => $role,
        'group_id' => null,
        'speciality_id' => null,
    ]);
}

/**
 * Ball hisoblanishi uchun modul, savol va yechilgan javob yaratadi.
 */
function makeScoredModule(User $student, int $optionValue = 5): Module
{
    $module = Module::create([
        'name' => 'Bayroq testi',
        'is_active' => true,
        'shuffle' => false,
        'audiences' => ['student'],
    ]);

    $test = Test::create(['module_id' => $module->id, 'question' => 'Savol', 'type' => 'single']);
    $option = TestOption::create([
        'test_id' => $test->id, 'option_text' => 'Ha', 'option_value' => $optionValue,
    ]);

    SolveTest::create([
        'user_id' => $student->id,
        'module_id' => $module->id,
        'test_id' => $test->id,
        'test_option_id' => $option->id,
    ]);

    $module->usersTestsResults()->attach($student->id, ['result_fake' => 'a', 'result_real' => null]);

    return $module;
}

test('eng og\'ir bayroq tanlanadi', function () {
    expect(RiskFlag::mostSevere(['green', 'red', 'yellow']))->toBe('red')
        ->and(RiskFlag::mostSevere(['green', 'yellow']))->toBe('yellow')
        ->and(RiskFlag::mostSevere(['green']))->toBe('green')
        ->and(RiskFlag::mostSevere([null, 'nonsense']))->toBeNull();
});

test('avtomatik xulosa bilan birga bayroq biriktiriladi', function () {
    $admin = makeFlagUser('admin');
    $student = makeFlagUser('student');
    $module = makeScoredModule($student);

    $this->actingAs($admin)
        ->post('/dashboard/module-score-report/conclusions', [
            'report_module_id' => $module->id,
            'min_score' => 1,
            'max_score' => 10,
            'conclusions' => ['student' => 'Yuqori xavf'],
            'flags' => ['student' => RiskFlag::RED],
            'overwrite_auto_conclusion' => true,
        ])
        ->assertRedirect();

    $row = DB::table('users_tests_results')
        ->where('user_id', $student->id)
        ->where('module_id', $module->id)
        ->first();

    expect($row->flag)->toBe(RiskFlag::RED)
        ->and($row->result_real)->toBe('Yuqori xavf');
});

test('bayroq xulosasiz yolg\'iz ham biriktiriladi', function () {
    $admin = makeFlagUser('admin');
    $student = makeFlagUser('student');
    $module = makeScoredModule($student);

    $this->actingAs($admin)
        ->post('/dashboard/module-score-report/conclusions', [
            'report_module_id' => $module->id,
            'min_score' => 1,
            'max_score' => 10,
            'conclusions' => ['student' => ''],
            'flags' => ['student' => RiskFlag::YELLOW],
            'overwrite_auto_conclusion' => false,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(DB::table('users_tests_results')->where('user_id', $student->id)->value('flag'))
        ->toBe(RiskFlag::YELLOW);
});

test('xulosa ham, bayroq ham bo\'lmasa xatolik qaytadi', function () {
    $admin = makeFlagUser('admin');
    $student = makeFlagUser('student');
    $module = makeScoredModule($student);

    $this->actingAs($admin)
        ->post('/dashboard/module-score-report/conclusions', [
            'report_module_id' => $module->id,
            'min_score' => 1,
            'max_score' => 10,
            'conclusions' => ['student' => ''],
            'flags' => ['student' => ''],
            'overwrite_auto_conclusion' => false,
        ])
        ->assertSessionHasErrors('conclusions');
});

test('hisobot jadvalida va statistikada bayroq ko\'rinadi', function () {
    $admin = makeFlagUser('admin');
    $student = makeFlagUser('student');
    $module = makeScoredModule($student);

    DB::table('users_tests_results')
        ->where('user_id', $student->id)
        ->update(['flag' => RiskFlag::RED]);

    app(ModuleScoreRangeReportService::class)->flush();
    app(DashboardAggregateCacheService::class)->forgetAll();

    $this->actingAs($admin)
        ->get("/dashboard?report_module_id={$module->id}&min_score=1&max_score=10")
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('moduleScoreReport.data.0.flag', RiskFlag::RED)
            ->where('flagStudentStats.0.value', RiskFlag::RED)
            ->where('flagStudentStats.0.studentCount', 1)
            ->has('flagOptions', 3)
        );
});
