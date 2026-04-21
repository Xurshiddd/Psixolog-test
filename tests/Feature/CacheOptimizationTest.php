<?php

use App\Models\Category;
use App\Models\Conversation;
use App\Models\Group;
use App\Models\Message;
use App\Models\Speciality;
use App\Models\User;
use App\Services\LookupCacheService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

function createCacheTestStudent(array $attributes = []): User
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

    return User::factory()->create(array_merge([
        'role' => 'student',
        'group_id' => $group->id,
        'speciality_id' => $speciality->id,
    ], $attributes));
}

it('refreshes cached lookup data after categories change', function () {
    Cache::flush();

    $lookupCache = app(LookupCacheService::class);

    Category::create(['name' => 'Birinchi kategoriya']);

    expect($lookupCache->categories()->pluck('name')->all())
        ->toBe(['Birinchi kategoriya']);

    Category::create(['name' => 'Ikkinchi kategoriya']);

    expect($lookupCache->categories()->pluck('name')->all())
        ->toBe(['Birinchi kategoriya', 'Ikkinchi kategoriya']);
});

it('recomputes cached student unread request count after messages are marked as read', function () {
    Cache::flush();

    $student = createCacheTestStudent();

    $staff = User::create([
        'name' => 'Psixolog cache user',
        'email' => 'psixolog-cache@example.com',
        'login' => 900001,
        'role' => 'psiholog',
        'password' => Hash::make('secret123'),
    ]);

    $conversation = Conversation::create([
        'student_id' => $student->id,
        'channel' => 'psiholog',
        'staff_id' => $staff->id,
        'subject' => 'Cache test suhbati',
        'status' => 'open',
        'last_message_at' => now(),
    ]);

    Message::create([
        'conversation_id' => $conversation->id,
        'sender_role' => 'staff',
        'sender_id' => $staff->id,
        'body' => 'Yangi xabar',
    ]);

    $this->actingAs($student)
        ->get(route('student.requests.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('unread_requests_count', 1));

    $this->actingAs($student)
        ->get(route('student.requests.index', ['conversation' => $conversation->id]))
        ->assertOk();

    $this->actingAs($student)
        ->get(route('student.requests.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('unread_requests_count', 0));
});

it('invalidates cached admin unread request count after a new student message arrives', function () {
    Cache::flush();

    $student = createCacheTestStudent([
        'name' => 'Student cache sender',
        'email' => 'student-cache@example.com',
        'login' => 900002,
    ]);

    $admin = User::create([
        'name' => 'Admin cache user',
        'email' => 'admin-cache@example.com',
        'login' => 900003,
        'role' => 'admin',
        'password' => Hash::make('secret123'),
        'email_verified_at' => now(),
    ]);

    $conversation = Conversation::create([
        'student_id' => $student->id,
        'channel' => 'admin',
        'staff_id' => $admin->id,
        'subject' => 'Admin kanal',
        'status' => 'open',
        'last_message_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.requests.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('unread_requests_count', 0));

    Message::create([
        'conversation_id' => $conversation->id,
        'sender_role' => 'student',
        'sender_id' => $student->id,
        'body' => 'Admin uchun yangi xabar',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.requests.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('unread_requests_count', 1));
});
