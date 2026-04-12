<?php

namespace Database\Seeders;

use App\Models\CheckType;
use Illuminate\Database\Seeder;

class CheckTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => 'HTTP Status',
                'slug' => 'http',
                'description' => 'Verifies if the website returns a successful HTTP status code (e.g., 200 OK). Extremely fast.',
                'icon' => 'heroicon-o-globe-alt',
            ],
            [
                'name' => 'SSL Certificate',
                'slug' => 'ssl',
                'description' => 'Monitors SSL certificate validity and upcoming expiration.',
                'icon' => 'heroicon-o-lock-closed',
            ],
            [
                'name' => 'DNS Check',
                'slug' => 'dns',
                'description' => 'Ensures your domain name resolves correctly to an IP address. Very fast.',
                'icon' => 'heroicon-o-at-symbol',
            ],
            [
                'name' => 'Port Check',
                'slug' => 'port',
                'description' => 'Verifies if a specific TCP port (like 443, 8080) is open and reachable.',
                'icon' => 'heroicon-o-hashtag',
            ],
            [
                'name' => 'ICMP Ping',
                'slug' => 'ping',
                'description' => 'Measures network latency and server availability via ICMP ping.',
                'icon' => 'heroicon-o-signal',
            ],
        ];

        foreach ($types as $type) {
            CheckType::updateOrCreate(['slug' => $type['slug']], $type);
        }
    }
}
