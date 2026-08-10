<?php

use App\Models\Employee;
use App\Models\Guest;
use App\Models\User;
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

test('admin xodim uchun passport PDF tayyorlaydi va saqlaydi', function () {
    $admin = makePassportUser('admin');
    $employee = makePassportUser('employee', ['name' => 'Xodim Test', 'picture' => null]);

    Employee::create([
        'user_id' => $employee->id,
        'employee_id_number' => '3312411057',
        'department_name' => 'Axborot texnologiyalari',
        'employee_type_name' => 'Professor-o‘qituvchi',
    ]);

    $response = $this->actingAs($admin)
        ->post(route('admin.employees.passport.pdf', $employee), passportPayload());

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');

    $this->assertDatabaseHas('user_passports', [
        'user_id' => $employee->id,
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

test('passport maydonlari validatsiyadan o\'tadi', function () {
    $admin = makePassportUser('admin');
    $employee = makePassportUser('employee');

    $this->actingAs($admin)
        ->post(route('admin.employees.passport.pdf', $employee), [
            'character_traits' => ['Faqat', 'Uchta', 'Xislat'],
            'temperament_type' => '',
            'conclusion' => '',
        ])
        ->assertSessionHasErrors(['character_traits', 'temperament_type', 'conclusion']);
});
