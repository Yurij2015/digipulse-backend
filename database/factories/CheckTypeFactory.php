<?php

namespace Database\Factories;

use App\Models\CheckType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CheckType>
 */
class CheckTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'slug' => $this->faker->unique()->slug(),
            'description' => $this->faker->sentence(),
            'icon' => 'heroicon-o-check',
            'is_active' => true,
        ];
    }
}
