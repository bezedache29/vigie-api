<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source_id' => Source::factory(),
            'external_id' => fake()->unique()->uuid(),
            'title' => fake()->sentence(),
            'url' => fake()->url(),
            'raw_content' => fake()->paragraph(),
            'published_at' => fake()->dateTimeBetween('-1 week'),
            'status' => 'pending',
        ];
    }
}
