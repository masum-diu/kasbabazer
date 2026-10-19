<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Bangladesh > Chattogram Division > Brahmanbaria > Kasba.
     * Add further divisions/districts/upazilas by extending this tree.
     */
    public function run(): void
    {
        $tree = [
            'Bangladesh' => ['country', [
                'Chattogram Division' => ['division', [
                    'Brahmanbaria' => ['district', [
                        'Kasba' => ['upazila', []],
                    ]],
                ]],
            ]],
        ];

        $this->seed($tree, null);
    }

    /**
     * @param  array<string, array{0: string, 1: array<string, mixed>}>  $nodes
     */
    private function seed(array $nodes, ?int $parentId): void
    {
        foreach ($nodes as $name => [$type, $children]) {
            $location = Location::updateOrCreate(
                ['slug' => str($name)->slug()->toString()],
                ['name' => $name, 'type' => $type, 'parent_id' => $parentId],
            );

            $this->seed($children, $location->id);
        }
    }
}
