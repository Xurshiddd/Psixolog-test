<?php

use Laravel\Fortify\Features;

// Bu loyihada ro'yxatdan o'tish o'chirilgan (config/fortify.php) — kirish
// HEMIS orqali bo'ladi. Feature yoqilsa testlar avtomatik ishga tushadi.
beforeEach(function () {
    if (! Features::enabled(Features::registration())) {
        $this->markTestSkipped('Fortify registration feature is disabled.');
    }
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});
