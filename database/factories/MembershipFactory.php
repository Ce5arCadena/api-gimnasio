<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Membership>
 */
class MembershipFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'start_date' => fake()->dateTimeBetween('2025-03-01', 'now'),
            'end_date' => fake()->dateTimeBetween('2025-07-01', 'now'),
            'pay' => fake()->randomElement(['Pagado', 'Debe']),
            'balance' => fake()->randomElement([null, '20000', '15000', '30000', '100000']),
            'state' => 'ACTIVE'
        ];
    }
}
