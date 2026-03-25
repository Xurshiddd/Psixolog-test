<?php

use App\Models\Group;
use App\Models\Module;
use App\Models\Speciality;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function createStaffUser(string $role = 'admin'): User
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

    return User::create([
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'login' => fake()->unique()->numberBetween(10000000, 99999999),
        'group_id' => $group->id,
        'speciality_id' => $speciality->id,
        'role' => $role,
        'password' => bcrypt('secret123'),
    ]);
}

function createStudentUser(): User
{
    return createStaffUser('student');
}

it('shows generated and psychologist diagnoses on the result page', function () {
    $admin = createStaffUser();
    $student = createStudentUser();

    $module = Module::create([
        'name' => 'Stress testi',
        'description' => 'Diagnostika moduli',
        'is_active' => true,
        'shuffle' => false,
    ]);

    $student->usersTestsResults()->attach($module->id, [
        'result_fake' => 'Tizim xulosasi',
        'result_real' => 'Avtomatik tahlil xulosasi',
        'diagnosis' => 'Psixologning yakuniy xulosasi',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.students.results.show', [$student, $module]));

    $response->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Student/Result')
        ->where('diagnosis', 'Psixologning yakuniy xulosasi')
        ->where('generatedDiagnosis', 'Avtomatik tahlil xulosasi')
    );
});

it('stores psychologist diagnosis for a student result', function () {
    $admin = createStaffUser();
    $student = createStudentUser();

    $module = Module::create([
        'name' => 'Moslashuv testi',
        'description' => 'Diagnostika moduli',
        'is_active' => true,
        'shuffle' => false,
    ]);

    $student->usersTestsResults()->attach($module->id, [
        'result_fake' => 'Normal',
        'result_real' => 'Avtomatik xulosa',
    ]);

    $response = $this->actingAs($admin)->post(route('admin.students.results.diagnosis', [$student, $module]), [
        'diagnosis' => 'Talaba bilan individual suhbat tavsiya etiladi.',
    ]);

    $response
        ->assertRedirect()
        ->assertSessionHas('success', 'Diagnostika muvaffaqiyatli saqlandi');

    $this->assertDatabaseHas('users_tests_results', [
        'user_id' => $student->id,
        'module_id' => $module->id,
        'diagnosis' => 'Talaba bilan individual suhbat tavsiya etiladi.',
    ]);
});

it('returns a helpful error when deepseek is not configured', function () {
    config()->set('ai.diagnosis_provider', 'deepseek');
    config()->set('ai.providers.deepseek.key', null);

    $admin = createStaffUser();
    $student = createStudentUser();

    $module = Module::create([
        'name' => 'Hissiy holat testi',
        'description' => 'Diagnostika moduli',
        'is_active' => true,
        'shuffle' => false,
    ]);

    $student->usersTestsResults()->attach($module->id, [
        'result_fake' => 'Normal',
        'result_real' => 'Avtomatik xulosa',
    ]);

    $response = $this->actingAs($admin)->postJson(route('admin.students.results.ai-diagnosis', [$student, $module]));

    $response
        ->assertStatus(422)
        ->assertJsonPath('error', 'DEEPSEEK API kaliti sozlanmagan. .env faylida kerakli provider kalitini kiriting.');
});

it('returns a helpful error for the stream endpoint when provider is not configured', function () {
    config()->set('ai.diagnosis_provider', 'deepseek');
    config()->set('ai.providers.deepseek.key', null);

    $admin = createStaffUser();
    $student = createStudentUser();

    $module = Module::create([
        'name' => 'Kommunikatsiya testi',
        'description' => 'Diagnostika moduli',
        'is_active' => true,
        'shuffle' => false,
    ]);

    $student->usersTestsResults()->attach($module->id, [
        'result_fake' => 'Normal',
        'result_real' => 'Avtomatik xulosa',
    ]);

    $response = $this->actingAs($admin)->postJson(route('admin.students.results.ai-diagnosis-stream', [$student, $module]));

    $response
        ->assertStatus(422)
        ->assertJsonPath('error', 'DEEPSEEK API kaliti sozlanmagan. .env faylida kerakli provider kalitini kiriting.');
});
