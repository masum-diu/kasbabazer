<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database. Demo password is for DEVELOPMENT ONLY.
     */
    public function run(): void
    {
        $this->call([LocationSeeder::class, CategorySeeder::class, PlanSeeder::class]);

        $kasba = Location::where('slug', 'kasba')->value('id');

        User::factory()->admin()->create([
            'name' => 'Kasba Bazer Admin',
            'username' => 'admin',
            'email' => 'admin@kasbabazer.com',
            'phone' => '01700000001',
            'location_id' => $kasba,
        ]);

        User::factory()->create([
            'name' => 'Demo Seller',
            'username' => 'demo_seller',
            'email' => 'seller@kasbabazer.com',
            'phone' => '01700000002',
            'location_id' => $kasba,
        ]);

        User::factory()->create([
            'name' => 'Demo Buyer',
            'username' => 'demo_buyer',
            'email' => 'buyer@kasbabazer.com',
            'phone' => '01700000003',
            'location_id' => $kasba,
        ]);

        $this->call(DemoSeeder::class);
    }
}
