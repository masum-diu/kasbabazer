<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = rtrim(fake()->unique()->sentence(2), '.');

        return ['name' => ucfirst($name), 'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)), 'is_active' => true];
    }
}
