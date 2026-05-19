<?php

use App\Exports\ModuleScoreRangeExport;
use App\Models\Faculity;
use App\Models\Group;
use App\Models\Module;
use App\Models\ResultCategory;
use App\Models\SolveTest;
use App\Models\Speciality;
use App\Models\Test;
use App\Models\TestOption;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Maatwebsite\Excel\Facades\Excel;

function createDashboardUser(string $role = 'student', array $attributes = []): User
{
    $group = Group::create([
        'name' => fake()->unique()->word(),
        'code' => fake()->unique()->numberBetween(1000, 9999),
        'education_lang' => 'Uzbek',
        'education_form' => 'Full-time',
        'education_type' => 'Bachelor',
    ]);

    $speciality = Speciality::create([
        'name' => fake()->unique()->word(),
        'code' => fake()->unique()->numberBetween(1000, 9999),
    ]);

    $faculity = Faculity::create([
        'code' => fake()->unique()->lexify('FAC???'),
        'name' => fake()->unique()->company(),
    ]);

    return User::factory()->create(array_merge([
        'role' => $role,
        'group_id' => $group->id,
        'speciality_id' => $speciality->id,
        'faculity_id' => $faculity->id,
        'level' => '2',
    ], $attributes));
}

function createModuleWithOptions(string $moduleName = 'Stress testi'): array
{
    $module = Module::create([
        'name' => $moduleName,
        'description' => 'Dashboard report test module',
        'is_active' => true,
        'shuffle' => false,
    ]);

    $firstTest = Test::create([
        'question' => '1-savol',
        'module_id' => $module->id,
        'type' => 'single',
    ]);

    $secondTest = Test::create([
        'question' => '2-savol',
        'module_id' => $module->id,
        'type' => 'single',
    ]);

    $lowOptionOne = TestOption::create([
        'test_id' => $firstTest->id,
        'option_text' => 'Past 1',
        'option_value' => 1,
    ]);

    $highOptionOne = TestOption::create([
        'test_id' => $firstTest->id,
        'option_text' => 'Yuqori 4',
        'option_value' => 4,
    ]);

    $lowOptionTwo = TestOption::create([
        'test_id' => $secondTest->id,
        'option_text' => 'Past 2',
        'option_value' => 2,
    ]);

    $highOptionTwo = TestOption::create([
        'test_id' => $secondTest->id,
        'option_text' => 'Yuqori 5',
        'option_value' => 5,
    ]);

    return [$module, $firstTest, $secondTest, $lowOptionOne, $highOptionOne, $lowOptionTwo, $highOptionTwo];
}

function submitModuleResult(User $student, Module $module, Test $firstTest, Test $secondTest, TestOption $firstOption, TestOption $secondOption): void
{
    SolveTest::create([
        'user_id' => $student->id,
        'module_id' => $module->id,
        'test_id' => $firstTest->id,
        'test_option_id' => $firstOption->id,
    ]);

    SolveTest::create([
        'user_id' => $student->id,
        'module_id' => $module->id,
        'test_id' => $secondTest->id,
        'test_option_id' => $secondOption->id,
    ]);

    $student->usersTestsResults()->attach($module->id, [
        'result_fake' => 'Natija',
        'result_real' => 'Natija',
    ]);
}

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = createDashboardUser();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('dashboard shows filtered students by selected module score range', function () {
    Cache::forget('dashboard:student-population-stats');
    Http::fake([
        'https://student.ttyesi.uz/rest/v1/public/stat-student' => Http::response([
            'data' => [
                'education_form' => [
                    'Bakalavr' => [
                        'Kunduzgi' => [
                            'Jami' => 120,
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $admin = createDashboardUser('admin');
    [$module, $firstTest, $secondTest, $lowOptionOne, $highOptionOne, $lowOptionTwo, $highOptionTwo] = createModuleWithOptions();

    $studentInRange = createDashboardUser('student', [
        'name' => 'Balli yuqori talaba',
        'login' => 101010,
        'level' => '3',
    ]);

    $studentOutOfRange = createDashboardUser('student', [
        'name' => 'Balli past talaba',
        'login' => 202020,
        'level' => '1',
    ]);

    submitModuleResult($studentInRange, $module, $firstTest, $secondTest, $highOptionOne, $highOptionTwo);
    submitModuleResult($studentOutOfRange, $module, $firstTest, $secondTest, $lowOptionOne, $lowOptionTwo);

    $response = $this
        ->actingAs($admin)
        ->get(route('dashboard', [
            'report_module_id' => $module->id,
            'min_score' => 5,
            'max_score' => 10,
        ]));

    $response->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->where('reportFilters.module_id', $module->id)
        ->where('reportFilters.min_score', 5)
        ->where('reportFilters.max_score', 10)
        ->where('reportFilters.is_ready', true)
        ->has('moduleScoreReport.data', 1)
        ->where('moduleScoreReport.data.0.login', '101010')
        ->where('moduleScoreReport.data.0.name', 'Balli yuqori talaba')
        ->where('moduleScoreReport.data.0.level', '3')
        ->where('moduleScoreReport.data.0.score', 9)
    );
});

test('dashboard can export filtered module score range report to excel', function () {
    Carbon::setTestNow('2026-04-02 10:00:00');
    Excel::fake();

    $admin = createDashboardUser('admin');
    [$module, $firstTest, $secondTest, $lowOptionOne, $highOptionOne, $lowOptionTwo, $highOptionTwo] = createModuleWithOptions('Moslashuv testi');

    $studentInRange = createDashboardUser('student', [
        'name' => 'Export talaba',
        'login' => 303030,
        'level' => '4',
    ]);

    submitModuleResult($studentInRange, $module, $firstTest, $secondTest, $highOptionOne, $highOptionTwo);

    $this
        ->actingAs($admin)
        ->get(route('dashboard.module-score-report.export', [
            'report_module_id' => $module->id,
            'min_score' => 8,
            'max_score' => 10,
        ]))
        ->assertOk();

    Excel::assertDownloaded(
        'module_ballari_moslashuv-testi_2026-04-02_10-00-00.xlsx',
        function (ModuleScoreRangeExport $export): bool {
            return $export->collection()->count() === 1
                && $export->collection()->first()->login === 303030
                && (int) $export->collection()->first()->score === 9;
        }
    );

    Carbon::setTestNow();
});

test('dashboard updates only empty automatic conclusions by default for filtered score report students', function () {
    $admin = createDashboardUser('admin');
    [$module, $firstTest, $secondTest, $lowOptionOne, $highOptionOne, $lowOptionTwo, $highOptionTwo] = createModuleWithOptions('Xulosa testi');

    $emptyConclusionStudent = createDashboardUser('student', [
        'name' => 'Bo‘sh xulosali talaba',
        'login' => 606060,
    ]);

    $existingConclusionStudent = createDashboardUser('student', [
        'name' => 'Xulosasi bor talaba',
        'login' => 707070,
    ]);

    $outOfRangeStudent = createDashboardUser('student', [
        'name' => 'Oraliqdan tashqari talaba',
        'login' => 808080,
    ]);

    submitModuleResult($emptyConclusionStudent, $module, $firstTest, $secondTest, $highOptionOne, $highOptionTwo);
    submitModuleResult($existingConclusionStudent, $module, $firstTest, $secondTest, $highOptionOne, $highOptionTwo);
    submitModuleResult($outOfRangeStudent, $module, $firstTest, $secondTest, $lowOptionOne, $lowOptionTwo);

    DB::table('users_tests_results')
        ->where('user_id', $emptyConclusionStudent->id)
        ->where('module_id', $module->id)
        ->update(['result_real' => null]);

    $this
        ->actingAs($admin)
        ->post(route('dashboard.module-score-report.conclusions.update'), [
            'report_module_id' => $module->id,
            'min_score' => 8,
            'max_score' => 10,
            'result_real' => 'Yangi avtomatik xulosa',
            'overwrite_auto_conclusion' => false,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('users_tests_results', [
        'user_id' => $emptyConclusionStudent->id,
        'module_id' => $module->id,
        'result_real' => 'Yangi avtomatik xulosa',
    ]);

    $this->assertDatabaseHas('users_tests_results', [
        'user_id' => $existingConclusionStudent->id,
        'module_id' => $module->id,
        'result_real' => 'Natija',
    ]);

    $this->assertDatabaseHas('users_tests_results', [
        'user_id' => $outOfRangeStudent->id,
        'module_id' => $module->id,
        'result_real' => 'Natija',
    ]);
});

test('dashboard can overwrite automatic conclusions for all filtered score report students', function () {
    $admin = createDashboardUser('admin');
    [$module, $firstTest, $secondTest, $lowOptionOne, $highOptionOne, $lowOptionTwo, $highOptionTwo] = createModuleWithOptions('Overwrite xulosa testi');

    $firstStudent = createDashboardUser('student', [
        'name' => 'Birinchi talaba',
        'login' => 909090,
    ]);

    $secondStudent = createDashboardUser('student', [
        'name' => 'Ikkinchi talaba',
        'login' => 919191,
    ]);

    $outOfRangeStudent = createDashboardUser('student', [
        'name' => 'Past ball talaba',
        'login' => 929292,
    ]);

    submitModuleResult($firstStudent, $module, $firstTest, $secondTest, $highOptionOne, $highOptionTwo);
    submitModuleResult($secondStudent, $module, $firstTest, $secondTest, $highOptionOne, $highOptionTwo);
    submitModuleResult($outOfRangeStudent, $module, $firstTest, $secondTest, $lowOptionOne, $lowOptionTwo);

    $this
        ->actingAs($admin)
        ->post(route('dashboard.module-score-report.conclusions.update'), [
            'report_module_id' => $module->id,
            'min_score' => 8,
            'max_score' => 10,
            'result_real' => 'Ustidan yozilgan avtomatik xulosa',
            'overwrite_auto_conclusion' => true,
        ])
        ->assertRedirect();

    foreach ([$firstStudent, $secondStudent] as $student) {
        $this->assertDatabaseHas('users_tests_results', [
            'user_id' => $student->id,
            'module_id' => $module->id,
            'result_real' => 'Ustidan yozilgan avtomatik xulosa',
        ]);
    }

    $this->assertDatabaseHas('users_tests_results', [
        'user_id' => $outOfRangeStudent->id,
        'module_id' => $module->id,
        'result_real' => 'Natija',
    ]);
});

test('dashboard shows aggregated result category stats', function () {
    Cache::forget('dashboard:student-population-stats');
    Http::fake([
        'https://student.ttyesi.uz/rest/v1/public/stat-student' => Http::response([
            'data' => [
                'education_form' => [
                    'Bakalavr' => [
                        'Kunduzgi' => [
                            'Jami' => 120,
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $admin = createDashboardUser('admin');
    [$module, $firstTest, $secondTest, $lowOptionOne, $highOptionOne, $lowOptionTwo, $highOptionTwo] = createModuleWithOptions('Kategoriya statistikasi testi');

    $lowOptionTwo->update(['option_value' => 1]);
    $highOptionTwo->update(['option_value' => 4]);

    ResultCategory::create([
        'name' => 'Past toifa',
        'diagnostic' => 'Past',
        'fake_diagnostic' => 'Past',
        'value' => 1,
        'module_id' => $module->id,
    ]);

    ResultCategory::create([
        'name' => 'Yuqori toifa',
        'diagnostic' => 'Yuqori',
        'fake_diagnostic' => 'Yuqori',
        'value' => 4,
        'module_id' => $module->id,
    ]);

    $highStudent = createDashboardUser('student', [
        'name' => 'Yuqori statistik talaba',
        'login' => 404040,
    ]);

    $lowStudent = createDashboardUser('student', [
        'name' => 'Past statistik talaba',
        'login' => 505050,
    ]);

    submitModuleResult($highStudent, $module, $firstTest, $secondTest, $highOptionOne, $highOptionTwo->fresh());
    submitModuleResult($lowStudent, $module, $firstTest, $secondTest, $lowOptionOne, $lowOptionTwo->fresh());

    $response = $this
        ->actingAs($admin)
        ->get(route('dashboard'));

    $response->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->has('resultCategoryStats', 2)
        ->where('resultCategoryStats.0.name', 'Past toifa')
        ->where('resultCategoryStats.0.solvedCount', 1)
        ->where('resultCategoryStats.1.name', 'Yuqori toifa')
        ->where('resultCategoryStats.1.solvedCount', 1)
    );
});
