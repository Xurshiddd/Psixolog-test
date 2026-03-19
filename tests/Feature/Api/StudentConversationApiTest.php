<?php

use App\Models\Conversation;
use App\Models\Group;
use App\Models\Message;
use App\Models\Speciality;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

function createStudentWithToken(): array {
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

    $student = User::create([
        'name' => 'Student User',
        'email' => 'student-chat@example.com',
        'login' => 44445555,
        'group_id' => $group->id,
        'speciality_id' => $speciality->id,
        'role' => 'student',
        'password' => Hash::make('secret123'),
    ]);

    return [$student, $student->issueApiToken()];
}

it('creates and lists student conversations', function () {
    [$student, $token] = createStudentWithToken();

    $createResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/student/conversations', [
            'channel' => 'psiholog',
            'subject' => 'Menga maslahat kerak',
        ]);

    $createResponse
        ->assertCreated()
        ->assertJsonPath('data.channel', 'psiholog')
        ->assertJsonPath('data.subject', 'Menga maslahat kerak');

    $listResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/student/conversations');

    $listResponse
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.channel', 'psiholog')
        ->assertJsonPath('data.0.subject', 'Menga maslahat kerak');
});

it('shows student conversation messages and marks staff messages as read', function () {
    [$student, $token] = createStudentWithToken();

    $staff = User::create([
        'name' => 'Psixolog User',
        'email' => 'psixolog-chat@example.com',
        'login' => 88889999,
        'role' => 'psiholog',
        'password' => Hash::make('secret123'),
    ]);

    $conversation = Conversation::create([
        'student_id' => $student->id,
        'channel' => 'psiholog',
        'staff_id' => $staff->id,
        'subject' => 'Suhbat',
        'status' => 'open',
        'last_message_at' => now(),
    ]);

    Message::create([
        'conversation_id' => $conversation->id,
        'sender_role' => 'staff',
        'sender_id' => $staff->id,
        'body' => 'Salom, qanday yordam kerak?',
    ]);

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson("/api/student/conversations/{$conversation->id}");

    $response
        ->assertOk()
        ->assertJsonPath('data.id', $conversation->id)
        ->assertJsonPath('data.messages.0.sender_role', 'staff')
        ->assertJsonPath('data.messages.0.body', 'Salom, qanday yordam kerak?');

    $this->assertDatabaseMissing('messages', [
        'conversation_id' => $conversation->id,
        'sender_role' => 'staff',
        'read_at' => null,
    ]);
});

it('stores a student chat message', function () {
    [$student, $token] = createStudentWithToken();

    $conversation = Conversation::create([
        'student_id' => $student->id,
        'channel' => 'admin',
        'subject' => 'Savol',
        'status' => 'open',
    ]);

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson("/api/student/conversations/{$conversation->id}/messages", [
            'body' => 'Assalomu alaykum, yordam kerak edi.',
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.messages.0.sender_role', 'student')
        ->assertJsonPath('data.messages.0.body', 'Assalomu alaykum, yordam kerak edi.');

    $this->assertDatabaseHas('messages', [
        'conversation_id' => $conversation->id,
        'sender_id' => $student->id,
        'sender_role' => 'student',
        'body' => 'Assalomu alaykum, yordam kerak edi.',
    ]);
});

it('reuses the same conversation for the same channel', function () {
    [$student, $token] = createStudentWithToken();

    $firstResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/student/conversations', [
            'channel' => 'admin',
            'subject' => 'Birinchi mavzu',
        ]);

    $conversationId = $firstResponse->json('data.id');

    $secondResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/student/conversations', [
            'channel' => 'admin',
            'subject' => 'Ikkinchi mavzu',
        ]);

    $secondResponse
        ->assertOk()
        ->assertJsonPath('data.id', $conversationId)
        ->assertJsonPath('data.subject', 'Birinchi mavzu');

    expect(
        Conversation::query()
            ->where('student_id', $student->id)
            ->where('channel', 'admin')
            ->count()
    )->toBe(1);
});
