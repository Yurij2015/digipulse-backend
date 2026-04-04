<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
                'description' => 'Verifies if the website returns a successful HTTP status code (e.g., 200 OK).',
                'icon' => 'heroicon-o-globe-alt',
            ],
            [
                'name' => 'Keyword Presence',
                'slug' => 'keyword',
                'description' => 'Checks if a specific string of text exists on the page.',
                'icon' => 'heroicon-o-magnifying-glass',
            ],
            [
                'name' => 'SSL Certificate',
                'slug' => 'ssl',
                'description' => 'Monitors the SSL certificate expiration date and validity.',
                'icon' => 'heroicon-o-lock-closed',
            ],
            [
                'name' => 'ICMP Ping',
                'slug' => 'ping',
                'description' => 'Measures the network latency and server availability via ICMP ping.',
                'icon' => 'heroicon-o-signal',
            ],
        ];

        foreach ($types as $type) {
            \App\Models\CheckType::updateOrCreate(['slug' => $type['slug']], $type);
        }
    }
}
