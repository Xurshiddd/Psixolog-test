<?php

use App\Models\Group;
use App\Models\Module;
use App\Models\ResultCategory;
use App\Models\Speciality;
use App\Models\Test;
use App\Models\TestOption;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('logs in a student and returns a bearer token', function () {
    $group = Group::create([
        'name' => 'Group 1',
        'code' => 1001,
        'education_lang' => 'Uzbek',
        'education_form' => 'Full-time',
        'education_type' => 'Bachelor',
    ]);

    $speciality = Speciality::create([
        'name' => 'Speciality 1',
        'code' => 2001,
    ]);

    $user = User::create([
        'name' => 'Student User',
        'email' => 'student@example.com',
        'login' => 54646542,
        'group_id' => $group->id,
        'speciality_id' => $speciality->id,
        'role' => 'student',
        'password' => Hash::make('secret123'),
    ]);

    $response = $this->postJson('/api/student/login', [
        'login' => $user->login,
        'password' => 'secret123',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.user.login', $user->login)
        ->assertJsonStructure([
            'message',
            'data' => [
                'token',
                'token_type',
                'user' => [
                    'id',
                    'name',
                    'login',
                ],
            ],
        ]);

    expect($user->fresh()->api_token)->not->toBeNull();
});

it('returns only active modules for an authenticated student', function () {
    $group = Group::create([
        'name' => 'Group 1',
        'code' => 1001,
        'education_lang' => 'Uzbek',
        'education_form' => 'Full-time',
        'education_type' => 'Bachelor',
    ]);

    $speciality = Speciality::create([
        'name' => 'Speciality 1',
        'code' => 2001,
    ]);

    $user = User::create([
        'name' => 'Student User',
        'email' => 'student2@example.com',
        'login' => 12345678,
        'group_id' => $group->id,
        'speciality_id' => $speciality->id,
        'role' => 'student',
        'password' => Hash::make('secret123'),
    ]);

    $token = $user->issueApiToken();

    $activeModule = Module::create([
        'name' => 'Active module',
        'description' => 'Desc',
        'is_active' => true,
        'shuffle' => false,
    ]);

    Test::create([
        'question' => 'Savol 1',
        'module_id' => $activeModule->id,
        'type' => 'single',
    ]);

    Module::create([
        'name' => 'Inactive module',
        'description' => 'Desc',
        'is_active' => false,
        'shuffle' => false,
    ]);

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/student/modules');

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Active module')
        ->assertJsonPath('data.0.submitted', false);
});

it('submits a student module and stores the calculated result', function () {
    $group = Group::create([
        'name' => 'Group 1',
        'code' => 1001,
        'education_lang' => 'Uzbek',
        'education_form' => 'Full-time',
        'education_type' => 'Bachelor',
    ]);

    $speciality = Speciality::create([
        'name' => 'Speciality 1',
        'code' => 2001,
    ]);

    $user = User::create([
        'name' => 'Student User',
        'email' => 'student3@example.com',
        'login' => 99999999,
        'group_id' => $group->id,
        'speciality_id' => $speciality->id,
        'role' => 'student',
        'password' => Hash::make('secret123'),
    ]);

    $token = $user->issueApiToken();

    $module = Module::create([
        'name' => 'Personality Test',
        'description' => 'Desc',
        'is_active' => true,
        'shuffle' => false,
    ]);

    $test = Test::create([
        'question' => 'Savol 1',
        'module_id' => $module->id,
        'type' => 'single',
    ]);

    $option = TestOption::create([
        'test_id' => $test->id,
        'option_text' => 'Variant 1',
        'option_value' => 5,
    ]);

    ResultCategory::create([
        'name' => 'Category 1',
        'module_id' => $module->id,
        'value' => 5,
        'fake_diagnostic' => 'Normal',
        'diagnostic' => 'Real diagnosis',
    ]);

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson("/api/student/modules/{$module->id}/submit", [
            'answers' => [
                $test->id => $option->id,
            ],
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.module_id', $module->id)
        ->assertJsonPath('data.result_fake', 'Normal')
        ->assertJsonPath('data.result_real', 'Real diagnosis');

    $this->assertDatabaseHas('users_tests_results', [
        'user_id' => $user->id,
        'module_id' => $module->id,
        'result_fake' => 'Normal',
        'result_real' => 'Real diagnosis',
    ]);
});
