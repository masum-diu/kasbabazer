<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Starter pricing (BDT per 30 days). Adjust freely; limits are enforced from these rows.
     */
    public function run(): void
    {
        $plans = [
            ['slug' => 'free', 'name' => 'Free', 'price' => 0, 'listing_limit' => 5, 'photo_limit' => 3, 'boosts_per_period' => 0, 'verified_badge' => false, 'sort_order' => 0],
            ['slug' => 'basic', 'name' => 'Basic', 'price' => 149, 'listing_limit' => 30, 'photo_limit' => 5, 'boosts_per_period' => 2, 'verified_badge' => false, 'sort_order' => 1],
            ['slug' => 'pro', 'name' => 'Pro', 'price' => 399, 'listing_limit' => 150, 'photo_limit' => 5, 'boosts_per_period' => 8, 'verified_badge' => true, 'sort_order' => 2],
            ['slug' => 'shop', 'name' => 'Shop', 'price' => 799, 'listing_limit' => null, 'photo_limit' => 5, 'boosts_per_period' => 20, 'verified_badge' => true, 'sort_order' => 3],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
