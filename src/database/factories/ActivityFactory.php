<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Activity>
 */
class ActivityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'guide_id' => User::factory()->guide(),
            'name' => fake()->name(),
            'description' => fake()->text(),
            'start_time' => fake()->dateTimeBetween('+1 day', '+1 year'),
            'price' => fake()->randomNumber(5, strict: true),
        ];
    }
}
