<?php

use App\Models\Employee;
use App\Models\Guest;
use App\Models\Hobby;
use App\Models\Module;
use App\Models\User;
use App\Support\RiskFlag;
use App\Models\UserPassport;

function makePassportUser(string $role, array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'role' => $role,
        'group_id' => null,
        'speciality_id' => null,
    ], $attributes));
}

function passportPayload(): array
{
    return [
        'character_traits' => ['Mas’uliyat', 'Sabr', 'Tashabbus', 'Muloqot', 'Tahlil'],
        'temperament_type' => 'Flegmatik',
        'conclusion' => 'Psixologik holati barqaror.',
    ];
}

test('admin xodim uchun faqat xulosadan iborat passport PDF tayyorlaydi', function () {
    $admin = makePassportUser('admin');
    $employee = makePassportUser('employee', ['name' => 'Xodim Test', 'picture' => null]);

    Employee::create([
        'user_id' => $employee->id,
        'employee_id_number' => '3312411057',
        'department_name' => 'Axborot texnologiyalari',
        'employee_type_name' => 'Professor-o‘qituvchi',
    ]);

    $response = $this->actingAs($admin)
        ->post(route('admin.employees.passport.pdf', $employee), [
            'conclusion' => 'Psixologik holati barqaror.',
        ]);

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');

    $this->assertDatabaseHas('user_passports', [
        'user_id' => $employee->id,
        'character_traits' => null,
        'temperament_type' => null,
        'conclusion' => 'Psixologik holati barqaror.',
    ]);
});

test('xodim passportida faqat xulosa talab qilinadi', function () {
    $admin = makePassportUser('admin');
    $employee = makePassportUser('employee');

    $this->actingAs($admin)
        ->post(route('admin.employees.passport.pdf', $employee), ['conclusion' => ''])
        ->assertSessionHasErrors('conclusion')
        ->assertSessionDoesntHaveErrors(['character_traits', 'temperament_type']);
});

test('admin talaba uchun to‘liq passport PDF tayyorlaydi', function () {
    $admin = makePassportUser('admin');
    $student = makePassportUser('student', ['name' => 'Talaba Test']);

    $response = $this->actingAs($admin)
        ->post(route('admin.students.passport.pdf', $student), passportPayload());

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');

    $this->assertDatabaseHas('user_passports', [
        'user_id' => $student->id,
        'temperament_type' => 'Flegmatik',
        'conclusion' => 'Psixologik holati barqaror.',
    ]);
});

test('admin nomzod uchun faqat xulosadan iborat passport PDF tayyorlaydi', function () {
    $admin = makePassportUser('admin');
    $guest = makePassportUser('guest', [
        'name' => 'Nomzod Test',
        'picture' => 'guests/nomzod.jpg',
    ]);

    Guest::create([
        'user_id' => $guest->id,
        'father_name' => 'Otabek',
        'address' => 'Toshkent',
        'desired_position' => 'Psixolog',
        'application_status' => 'pending',
    ]);

    $response = $this->actingAs($admin)
        ->post(route('admin.guests.passport.pdf', $guest), [
            'conclusion' => 'Nomzod lavozimga mos deb topildi.',
        ]);

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');

    $this->assertDatabaseHas('user_passports', [
        'user_id' => $guest->id,
        'character_traits' => null,
        'temperament_type' => null,
        'conclusion' => 'Nomzod lavozimga mos deb topildi.',
    ]);
});

test('nomzod passportida faqat xulosa talab qilinadi', function () {
    $admin = makePassportUser('admin');
    $guest = makePassportUser('guest');

    Guest::create(['user_id' => $guest->id]);

    $this->actingAs($admin)
        ->post(route('admin.guests.passport.pdf', $guest), ['conclusion' => ''])
        ->assertSessionHasErrors('conclusion')
        ->assertSessionDoesntHaveErrors(['character_traits', 'temperament_type']);
});

test('saqlangan passport qayta yuklab olinadi', function () {
    $admin = makePassportUser('admin');
    $employee = makePassportUser('employee');

    UserPassport::create(array_merge(['user_id' => $employee->id], passportPayload()));

    $this->actingAs($admin)
        ->get(route('admin.employees.passport.download', $employee))
        ->assertOk();
});

test('passporti yo\'q xodim uchun yuklab olish 404 qaytaradi', function () {
    $admin = makePassportUser('admin');
    $employee = makePassportUser('employee');

    $this->actingAs($admin)
        ->get(route('admin.employees.passport.download', $employee))
        ->assertNotFound();
});

test('boshqa roldagi foydalanuvchi xodim passport yo\'liga tushmaydi', function () {
    $admin = makePassportUser('admin');
    $student = makePassportUser('student');

    $this->actingAs($admin)
        ->post(route('admin.employees.passport.pdf', $student), passportPayload())
        ->assertNotFound();
});

test('talaba passportining barcha maydonlari validatsiyadan o\'tadi', function () {
    $admin = makePassportUser('admin');
    $student = makePassportUser('student');

    // Qobiliyatlar ketma-ketligi passportdan olib tashlandi — uning o'rniga
    // talabaning qiziqishlari (hobby) chiqadi, ular formada so'ralmaydi.
    $this->actingAs($admin)
        ->post(route('admin.students.passport.pdf', $student), [
            'temperament_type' => '',
            'conclusion' => '',
        ])
        ->assertSessionHasErrors(['temperament_type', 'conclusion'])
        ->assertSessionDoesntHaveErrors(['character_traits']);
});

test('talaba passportida qiziqishlar va xavf darajasi chiqadi', function () {
    $admin = makePassportUser('admin');
    $student = makePassportUser('student');

    Hobby::create(['user_id' => $student->id, 'name' => 'Shaxmat']);
    Hobby::create(['user_id' => $student->id, 'name' => 'Suzish']);

    $module = Module::create(['name' => 'Stress', 'is_active' => true, 'audiences' => ['student']]);
    $module->usersTestsResults()->attach($student->id, ['flag' => RiskFlag::RED]);

    $response = $this->actingAs($admin)
        ->post(route('admin.students.passport.pdf', $student), [
            'temperament_type' => 'Flegmatik',
            'conclusion' => 'Psixologik holati kuzatuvni talab qiladi.',
        ]);

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');

    // Qobiliyatlar ketma-ketligi endi saqlanmaydi.
    $this->assertDatabaseHas('user_passports', [
        'user_id' => $student->id,
        'character_traits' => null,
    ]);
});
