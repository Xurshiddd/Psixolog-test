<?php

use App\Models\Hobby;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function makeHobbyUser(string $role = 'student'): User
{
    return User::factory()->create([
        'role' => $role,
        'group_id' => null,
        'speciality_id' => null,
    ]);
}

test('talaba bir nechta qiziqish qo\'shadi', function () {
    $student = makeHobbyUser();

    $this->actingAs($student)
        ->post('/student/hobbies', [
            'hobbies' => ['Shaxmat', 'Suzish', 'Rasm chizish'],
        ])
        ->assertRedirect();

    expect($student->hobbies()->pluck('name')->all())
        ->toBe(['Rasm chizish', 'Shaxmat', 'Suzish']);
});

test('bo\'sh va takroriy qiziqishlar tashlab yuboriladi', function () {
    $student = makeHobbyUser();

    $this->actingAs($student)
        ->post('/student/hobbies', [
            'hobbies' => ['Shaxmat', '  ', 'shaxmat', 'Suzish', ''],
        ])
        ->assertRedirect();

    expect($student->hobbies()->pluck('name')->all())->toBe(['Shaxmat', 'Suzish']);
});

test('qayta saqlash ro\'yxatni butunligicha almashtiradi', function () {
    $student = makeHobbyUser();
    Hobby::create(['user_id' => $student->id, 'name' => 'Eski']);

    $this->actingAs($student)->post('/student/hobbies', ['hobbies' => ['Yangi']]);

    expect($student->hobbies()->pluck('name')->all())->toBe(['Yangi']);
});

test('qiziqishlar sahifasi talabaga ochiladi', function () {
    $student = makeHobbyUser();
    Hobby::create(['user_id' => $student->id, 'name' => 'Shaxmat']);

    $this->actingAs($student)
        ->get('/student/hobbies')
        ->assertInertia(fn (Assert $page) => $page
            ->component('Student/Hobbies')
            ->has('hobbies', 1)
            ->where('hobbies.0.name', 'Shaxmat')
        );
});

test('qiziqishlar yo\'li faqat talabaga ochiq', function () {
    // `student` middleware xodim/nomzodni ham o'tkazadi, kontroller esa
    // qiziqishlarni faqat talabaga ochadi.
    $this->actingAs(makeHobbyUser('employee'))
        ->get('/student/hobbies')
        ->assertForbidden();

    $this->actingAs(makeHobbyUser('guest'))
        ->post('/student/hobbies', ['hobbies' => ['Shaxmat']])
        ->assertForbidden();
});
