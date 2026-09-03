<?php

use App\Models\CriticalAlert;
use App\Models\Module;
use App\Models\Test;
use App\Models\TestOption;
use App\Models\User;
use App\Services\CriticalAlertService;
use App\Services\StudentTestServices;
use Inertia\Testing\AssertableInertia as Assert;

function makeAlertUser(string $role): User
{
    return User::factory()->create([
        'role' => $role,
        'group_id' => null,
        'speciality_id' => null,
    ]);
}

/**
 * @return array{0: Module, 1: TestOption, 2: TestOption}
 */
function makeCriticalModule(): array
{
    $module = Module::create([
        'name' => 'Xavf testi',
        'is_active' => true,
        'shuffle' => false,
        'audiences' => ['student'],
    ]);

    $test = Test::create(['module_id' => $module->id, 'question' => 'Savol', 'type' => 'single']);

    $safe = TestOption::create([
        'test_id' => $test->id, 'option_text' => 'Yo\'q', 'option_value' => 1, 'is_critical' => false,
    ]);
    $critical = TestOption::create([
        'test_id' => $test->id, 'option_text' => 'Ha', 'option_value' => 3, 'is_critical' => true,
    ]);

    return [$module, $safe, $critical];
}

test('xavfli variant tanlanganda ogohlantirish yoziladi', function () {
    [$module, , $critical] = makeCriticalModule();
    $student = makeAlertUser('student');

    app(StudentTestServices::class)->processBatchSubmission(
        $student->id,
        $module->id,
        [$critical->test_id => $critical->id],
    );

    expect(CriticalAlert::pending()->where('user_id', $student->id)->count())->toBe(1)
        ->and(app(CriticalAlertService::class)->pendingStudentsCount())->toBe(1);
});

test('xavfsiz variant ogohlantirish yaratmaydi', function () {
    [$module, $safe] = makeCriticalModule();
    $student = makeAlertUser('student');

    app(StudentTestServices::class)->processBatchSubmission(
        $student->id,
        $module->id,
        [$safe->test_id => $safe->id],
    );

    expect(CriticalAlert::count())->toBe(0);
});

test('xodim va nomzodda ogohlantirish yaratilmaydi', function () {
    [$module, , $critical] = makeCriticalModule();
    $employee = makeAlertUser('employee');

    app(StudentTestServices::class)->processBatchSubmission(
        $employee->id,
        $module->id,
        [$critical->test_id => $critical->id],
    );

    expect(CriticalAlert::count())->toBe(0);
});

test('hal qilindi bosilganda qizil belgi o\'chadi, ma\'lumot qoladi', function () {
    [$module, , $critical] = makeCriticalModule();
    $student = makeAlertUser('student');
    $admin = makeAlertUser('admin');

    app(StudentTestServices::class)->processBatchSubmission(
        $student->id,
        $module->id,
        [$critical->test_id => $critical->id],
    );

    $this->actingAs($admin)
        ->post("/critical-alerts/{$student->id}/resolve")
        ->assertRedirect();

    expect(app(CriticalAlertService::class)->pendingStudentsCount())->toBe(0)
        ->and(CriticalAlert::resolved()->where('user_id', $student->id)->count())->toBe(1)
        ->and(CriticalAlert::first()->resolved_by)->toBe($admin->id);
});

test('zudlik bilan sahifasi talabalarni ro\'yxatda ko\'rsatadi', function () {
    [$module, , $critical] = makeCriticalModule();
    $student = makeAlertUser('student');
    $admin = makeAlertUser('admin');

    app(StudentTestServices::class)->processBatchSubmission(
        $student->id,
        $module->id,
        [$critical->test_id => $critical->id],
    );

    $this->actingAs($admin)
        ->get('/critical-alerts')
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/CriticalAlerts/Index')
            ->where('pendingStudentsCount', 1)
            ->has('students.data', 1)
            ->where('students.data.0.id', $student->id)
            ->where('students.data.0.pending_alerts_count', 1)
            ->where('students.data.0.alerts.0.answer', 'Ha')
        );
});

test('talaba zudlik bilan sahifasiga kira olmaydi', function () {
    // `double` middleware talabani boshqaruv bo'limlaridan qaytaradi.
    $this->actingAs(makeAlertUser('student'))
        ->get('/critical-alerts')
        ->assertRedirect();
});

test('statusi o\'chirilgan modul ogohlantirishlari sanalmaydi', function () {
    [$module, , $critical] = makeCriticalModule();
    $student = makeAlertUser('student');

    app(StudentTestServices::class)->processBatchSubmission(
        $student->id,
        $module->id,
        [$critical->test_id => $critical->id],
    );

    expect(app(CriticalAlertService::class)->pendingStudentsCount())->toBe(1);

    $module->update(['is_active' => false]);
    app(CriticalAlertService::class)->flush();

    expect(app(CriticalAlertService::class)->pendingStudentsCount())->toBe(0)
        ->and(CriticalAlert::count())->toBe(1);
});
