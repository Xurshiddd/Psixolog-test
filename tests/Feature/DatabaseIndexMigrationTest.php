<?php

use App\Models\Category;
use App\Models\Faculity;
use App\Models\Group;
use App\Models\Module;
use App\Models\Speciality;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function createIndexedStudent(): User
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

    return User::factory()->create([
        'role' => 'student',
        'group_id' => $group->id,
        'speciality_id' => $speciality->id,
        'faculity_id' => $faculity->id,
        'level' => '2-kurs',
    ]);
}

test('student flow indexes are present after migrations', function () {
    expect(Schema::hasIndex('users_tests_results', ['user_id', 'module_id'], 'unique'))->toBeTrue();
    expect(Schema::hasIndex('users_category', ['user_id', 'category_id'], 'unique'))->toBeTrue();
    expect(Schema::hasIndex('result_categories', ['module_id', 'value'], 'unique'))->toBeTrue();

    expect(Schema::hasIndex('messages', ['conversation_id', 'sender_role', 'read_at', 'id']))->toBeTrue();
    expect(Schema::hasIndex('messages', ['sender_role', 'read_at', 'conversation_id']))->toBeTrue();
    expect(Schema::hasIndex('conversations', ['student_id', 'last_message_at', 'id']))->toBeTrue();
    expect(Schema::hasIndex('conversations', ['channel', 'last_message_at', 'id']))->toBeTrue();

    expect(Schema::hasIndex('users', ['login']))->toBeTrue();
    expect(Schema::hasIndex('users', ['role', 'created_at']))->toBeTrue();
    expect(Schema::hasIndex('users', ['role', 'group_id']))->toBeTrue();
    expect(Schema::hasIndex('users', ['role', 'speciality_id']))->toBeTrue();
    expect(Schema::hasIndex('users', ['role', 'faculity_id']))->toBeTrue();
    expect(Schema::hasIndex('users', ['role', 'level']))->toBeTrue();
});

test('new unique constraints block duplicate student flow rows', function () {
    $student = createIndexedStudent();
    $module = Module::create([
        'name' => 'Unikal test moduli',
        'description' => 'Constraint test',
        'is_active' => true,
        'shuffle' => false,
    ]);
    $category = Category::create([
        'name' => 'Talaba kategoriyasi',
    ]);

    DB::table('users_tests_results')->insert([
        'user_id' => $student->id,
        'module_id' => $module->id,
        'result_fake' => 'Natija',
        'result_real' => 'Natija',
        'diagnosis' => 'Xulosa',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('users_category')->insert([
        'user_id' => $student->id,
        'category_id' => $category->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('result_categories')->insert([
        'name' => 'Past xavf',
        'diagnostic' => 'Past',
        'fake_diagnostic' => 'Past',
        'value' => 1,
        'module_id' => $module->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn () => DB::table('users_tests_results')->insert([
        'user_id' => $student->id,
        'module_id' => $module->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);

    expect(fn () => DB::table('users_category')->insert([
        'user_id' => $student->id,
        'category_id' => $category->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);

    expect(fn () => DB::table('result_categories')->insert([
        'name' => 'Takroriy past xavf',
        'value' => 1,
        'module_id' => $module->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});
