<?php

namespace Database\Factories;

use App\Models\CheckResultArchive;
use App\Models\Site;
use App\Models\SiteCheckConfiguration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CheckResultArchive>
 */
class CheckResultArchiveFactory extends Factory
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
            'year' => (int) $this->faker->year(),
            'week' => $this->faker->numberBetween(1, 52),
            'data' => [
                ['status' => 'up', 'response_time_ms' => 200, 'checked_at' => now()->toDateTimeString()],
            ],
            'size_bytes' => 1024,
        ];
    }
}
