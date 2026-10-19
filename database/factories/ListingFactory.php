<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Listing>
 */
class ListingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = rtrim(fake()->sentence(3), '.');

        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'title' => ucfirst($title),
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(6)),
            'description' => fake()->paragraph(),
            'price' => fake()->numberBetween(2, 900) * 50,
            'is_negotiable' => fake()->boolean(),
            'condition' => fake()->randomElement(Listing::CONDITIONS),
            'status' => 'active',
            'quantity' => 1,
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => 'draft', 'published_at' => null]);
    }
}
