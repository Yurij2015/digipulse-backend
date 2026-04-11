<?php

namespace Database\Factories;

use App\Models\CheckType;
use App\Models\Site;
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
            'site_id' => Site::factory(),
            'check_type_id' => CheckType::where('is_active', true)->first()?->id ?? CheckType::factory(),
            'params' => [],
            'is_active' => true,
        ];
    }
}
