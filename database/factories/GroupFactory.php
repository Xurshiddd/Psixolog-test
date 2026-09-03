<?php

namespace Database\Factories;

use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Group>
 */
class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition(): array
    {
        return [
            'name' => 'Group '.fake()->unique()->numberBetween(1, 100000),
            'code' => fake()->unique()->numberBetween(1000, 999999),
            'education_lang' => 'Uzbek',
            'education_form' => 'Full-time',
            'education_type' => 'Bachelor',
        ];
    }
}
