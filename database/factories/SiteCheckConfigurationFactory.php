<?php

namespace Database\Factories;

use App\Models\SiteCheckConfiguration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SiteCheckConfiguration>
 */
class SiteCheckConfigurationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'site_id' => \App\Models\Site::factory(),
            'check_type_id' => \App\Models\CheckType::where('is_active', true)->first()?->id ?? 1,
            'params' => [],
            'is_active' => true,
        ];
    }
}
