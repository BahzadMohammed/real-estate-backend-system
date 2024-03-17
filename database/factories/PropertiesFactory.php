<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\{User, addresses, categories};

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\properties>
 */
class PropertiesFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::all()->random()->id,
            'category_id' => categories::all()->random()->id,
            'address_id' => addresses::all()->random()->id,
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'price' => fake()->numberBetween(100000, 1000000),
            'area' => fake()->numberBetween(200, 5000),
            'bedroom' => fake()->randomNumber(1),
            'bathroom' => fake()->randomNumber(1),
            'garage' => fake()->numberBetween(1, 2),
            'kitchen' => fake()->randomNumber(1),
            'image' => ['1.jpg', '2.jpg', '3.jpg']
        ];
    }
}
