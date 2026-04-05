<?php

namespace Database\Factories;

use App\Models\Check;
use App\Models\SiteCheckConfiguration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Check>
 */
class CheckFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'site_check_configuration_id' => SiteCheckConfiguration::factory(),
            'is_successful' => $this->faker->boolean(80),
            'response_time' => $this->faker->numberBetween(100, 2000),
            'results' => ['status' => 200, 'method' => 'GET'],
        ];
    }
}
