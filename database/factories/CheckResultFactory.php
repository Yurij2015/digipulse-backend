<?php

namespace Database\Factories;

use App\Models\CheckResult;
use App\Models\Site;
use App\Models\SiteCheckConfiguration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CheckResult>
 */
class CheckResultFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'configuration_id' => SiteCheckConfiguration::factory(),
            'status' => $this->faker->randomElement(['up', 'down', 'slow']),
            'response_time_ms' => $this->faker->numberBetween(100, 5000),
            'error_message' => null,
            'metadata' => ['ip' => $this->faker->ipv4],
            'checked_at' => now(),
        ];
    }

    /**
     * Indicate that the result is old.
     */
    public function old(int $days = 8): static
    {
        return $this->state(fn (array $attributes) => [
            'checked_at' => now()->subDays($days),
        ]);
    }
}
