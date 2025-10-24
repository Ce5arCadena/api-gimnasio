<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Member>
 */
class MemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'last_name' => fake()->lastName(),
            'registration_date' => fake()->dateTimeBetween('2025-01-01', 'now'),
            'hour' => fake()->time('H:i'),
            'state' => 'ACTIVE'
        ];
    }
}
