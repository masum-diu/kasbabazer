<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $roots = ['Electronics', 'Clothing', 'Home & Living', 'Vehicles', 'Food', 'Books', 'Services', 'Jobs', 'Agriculture', 'Other'];

        $children = [
            'Electronics' => ['Mobile', 'Computer'],
            'Clothing' => ['Fashion'],
            'Home & Living' => ['Furniture'],
        ];

        foreach ($roots as $i => $name) {
            $root = Category::updateOrCreate(
                ['slug' => str($name)->slug()->toString()],
                ['name' => $name, 'sort_order' => $i, 'is_active' => true],
            );

            foreach ($children[$name] ?? [] as $j => $child) {
                Category::updateOrCreate(
                    ['slug' => str($child)->slug()->toString()],
                    ['name' => $child, 'parent_id' => $root->id, 'sort_order' => $j, 'is_active' => true],
                );
            }
        }
    }
}
