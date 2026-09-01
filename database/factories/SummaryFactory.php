<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\Summary;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Summary>
 */
class SummaryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'item_id' => Item::factory(),
            'summary_text' => fake()->paragraph(),
            'tags' => [fake()->word(), fake()->word()],
            'relevance_score' => fake()->numberBetween(0, 100),
            'model_used' => 'gpt-4o-mini',
        ];
    }
}
