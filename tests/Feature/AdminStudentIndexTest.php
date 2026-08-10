<?php

use App\Exports\StudentsExport;
use App\Exports\StudentsExportWithDiagnosis;
use App\Models\Faculity;
use App\Models\Group;
use App\Models\Module;
use App\Models\Speciality;
use App\Models\User;
use App\Models\UserPassport;
use Carbon\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Maatwebsite\Excel\Facades\Excel;

function createAdminStudentIndexUser(string $role = 'student', array $attributes = []): User
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
        'level' => '2-kurs',
    ], $attributes));
}

test('admin student index preserves the expected inertia contract', function () {
    $admin = createAdminStudentIndexUser('admin');
    $student = createAdminStudentIndexUser('student', [
        'name' => 'Talaba Test',
        'login' => 777001,
        'phone' => '998901234567',
        'picture' => 'https://example.com/avatar.jpg',
    ]);

    $module = Module::create([
        'name' => 'Stress testi',
        'description' => 'Admin student index test module',
        'is_active' => true,
        'shuffle' => false,
    ]);

    $student->usersTestsResults()->attach($module->id, [
        'result_fake' => 'Qisqa xulosa',
        'result_real' => 'Avtomatik batafsil xulosa',
        'diagnosis' => 'Psixolog xulosasi',
    ]);

    UserPassport::create([
        'user_id' => $student->id,
        'character_traits' => ['A', 'B', 'C', 'D', 'E'],
        'temperament_type' => 'Sangvinik',
        'conclusion' => 'Xulosa',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.students.index'));

    $response->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Student/Index')
        ->has('students.data', 1)
        ->where('students.data.0.name', 'Talaba Test')
        ->where('students.data.0.login', 777001)
        ->where('students.data.0.phone', '998901234567')
        ->where('students.data.0.picture', 'https://example.com/avatar.jpg')
        ->where('students.data.0.group.name', $student->group->name)
        ->where('students.data.0.speciality.name', $student->speciality->name)
        ->where('students.data.0.passport.user_id', $student->id)
        ->has('students.data.0.users_tests_results', 1)
        ->where('students.data.0.users_tests_results.0.name', 'Stress testi')
        ->where('students.data.0.users_tests_results.0.pivot.diagnosis', 'Psixolog xulosasi')
        ->where('students.data.0.users_tests_results.0.pivot.result_real', 'Avtomatik batafsil xulosa')
    );
});

test('admin student exports still download successfully', function () {
    Excel::fake();

    $admin = createAdminStudentIndexUser('admin');
    $student = createAdminStudentIndexUser('student');
    $module = Module::create([
        'name' => 'Moslashuv testi',
        'description' => 'Export test module',
        'is_active' => true,
        'shuffle' => false,
    ]);

    $student->usersTestsResults()->attach($module->id, [
        'result_fake' => 'Natija',
        'result_real' => 'Avtomatik xulosa',
        'diagnosis' => 'Psixolog tavsiyasi',
    ]);

    Carbon::setTestNow('2026-04-21 13:33:17');
    $this->actingAs($admin)
        ->get(route('admin.students.export.excel'))
        ->assertOk();

    Carbon::setTestNow('2026-04-21 13:33:18');
    $this->actingAs($admin)
        ->get(route('admin.students.export.excel-with-diagnosis'))
        ->assertOk();

    Excel::assertDownloaded('talabalar_2026-04-21_13-33-17.xlsx', function ($export): bool {
        return $export instanceof StudentsExport;
    });

    Excel::assertDownloaded('talabalar_2026-04-21_13-33-18.xlsx', function ($export): bool {
        return $export instanceof StudentsExportWithDiagnosis;
    });

    Carbon::setTestNow();
});
