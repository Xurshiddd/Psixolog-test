<?php

use App\Models\Module;
use App\Models\Test;
use App\Models\TestOption;
use App\Models\User;
use App\Services\DashboardAggregateCacheService;
use App\Services\LookupCacheService;
use Illuminate\Support\Facades\DB;

function makeStatusModule(string $name, bool $active = true): Module
{
    $module = Module::create([
        'name' => $name,
        'is_active' => $active,
        'shuffle' => false,
        'audiences' => ['student'],
    ]);

    $test = Test::create(['module_id' => $module->id, 'question' => "{$name} savoli", 'type' => 'single']);
    TestOption::create(['test_id' => $test->id, 'option_text' => 'Ha', 'option_value' => 1]);

    return $module;
}

test('statusi o\'chirilgan modul platformada ko\'rinmaydi, ma\'lumot esa saqlanadi', function () {
    $student = User::factory()->create(['role' => 'student', 'group_id' => null, 'speciality_id' => null]);
    $active = makeStatusModule('Faol modul');
    $hidden = makeStatusModule('Yashiriladigan modul');

    $active->usersTestsResults()->attach($student->id, ['result_fake' => 'a', 'result_real' => 'b']);
    $hidden->usersTestsResults()->attach($student->id, ['result_fake' => 'c', 'result_real' => 'd']);

    expect($student->usersTestsResults()->pluck('modules.name')->sort()->values()->all())
        ->toBe(['Faol modul', 'Yashiriladigan modul']);

    $hidden->update(['is_active' => false]);
    app(DashboardAggregateCacheService::class)->forgetAll();
    app(LookupCacheService::class)->forget();

    // Talabaning yechgan modullari ro'yxatidan chiqib ketadi.
    expect($student->fresh()->usersTestsResults()->pluck('modules.name')->all())->toBe(['Faol modul']);

    // Lookup, hisob-kitob va statistikada ham ko'rinmaydi.
    expect(app(LookupCacheService::class)->modules()->pluck('name')->all())->toBe(['Faol modul']);

    $overview = app(DashboardAggregateCacheService::class)->overview();
    expect($overview['modules_count'])->toBe(1)
        ->and($overview['tests_count'])->toBe(1);

    expect(app(DashboardAggregateCacheService::class)->moduleSummaries()->pluck('name')->all())
        ->toBe(['Faol modul']);

    // Ma'lumot bazada saqlanib qoladi.
    expect(DB::table('users_tests_results')->where('module_id', $hidden->id)->count())->toBe(1)
        ->and(DB::table('modules')->where('id', $hidden->id)->exists())->toBeTrue();
});

test('statusi o\'chirilgan modul testiga talaba kira olmaydi', function () {
    $student = User::factory()->create(['role' => 'student', 'group_id' => null, 'speciality_id' => null]);
    $module = makeStatusModule('O\'chirilgan', false);

    $this->actingAs($student)
        ->get("/test/take/{$module->id}")
        ->assertNotFound();
});

test('status qayta yoqilganda ma\'lumot tiklanadi', function () {
    $student = User::factory()->create(['role' => 'student', 'group_id' => null, 'speciality_id' => null]);
    $module = makeStatusModule('Tiklanadigan');
    $module->usersTestsResults()->attach($student->id, ['result_fake' => 'a', 'result_real' => 'b']);

    $module->update(['is_active' => false]);
    expect($student->fresh()->usersTestsResults()->count())->toBe(0);

    $module->update(['is_active' => true]);
    expect($student->fresh()->usersTestsResults()->pluck('modules.name')->all())->toBe(['Tiklanadigan']);
});
