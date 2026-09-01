<?php

namespace Database\Factories;

use App\Models\Digest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Digest>
 */
class DigestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'item_ids' => [],
            'channel' => 'email',
            'sent_at' => fake()->dateTimeBetween('-1 week'),
        ];
    }
}
