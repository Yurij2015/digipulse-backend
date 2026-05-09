<?php

namespace Database\Factories;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportTicket>
 */
class SupportTicketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'contact_email' => $this->faker->email(),
            'subject' => $this->faker->sentence(4),
            'message' => $this->faker->paragraph(),
            'status' => 'open',
            'priority' => $this->faker->randomElement(['low', 'medium', 'high']),
        ];
    }
}
