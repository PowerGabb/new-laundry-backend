<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Branch>
 */
class BranchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->company().' Laundry',
            'detail_address' => fake()->address(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'phone' => fake()->phoneNumber(),
            'working_hours' => fake()->numberBetween(8, 12),
            'price_per_kg' => fake()->randomElement([5000, 6000, 7000, 8000, 10000]),
            'image_url' => fake()->optional()->imageUrl(),
            'pickup_gojek' => fake()->boolean(),
            'pickup_grab' => fake()->boolean(),
            'pickup_free' => fake()->boolean(),
            'pickup_free_schedule' => fake()->optional()->randomElements(
                ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'],
                fake()->numberBetween(1, 3)
            ),
        ];
    }
}
