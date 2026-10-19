<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\Location;
use App\Models\Reaction;
use App\Models\Review;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Dummy marketplace data for local development only.
 */
class DemoSeeder extends Seeder
{
    private const NAMES = [
        'Karim Uddin', 'Rahim Mia', 'Sumon Ahmed', 'Nasrin Akter', 'Jamal Hossain', 'Rina Begum', 'Faruk Hasan',
        'Shirin Sultana', 'Mizanur Rahman', 'Tania Islam', 'Habib Khan', 'Moni Chowdhury', 'Sohel Rana', 'Lipi Akter',
        'Arif Billah', 'Nusrat Jahan', 'Delwar Hossain', 'Ayesha Siddika', 'Kamrul Islam', 'Parvin Akter',
    ];

    private const SHOPS = [
        'Kasba Mobile Corner', 'Rahim Electronics', 'Nasrin Fashion House', 'Sumon Furniture', 'Green Field Agro',
        'Book Nook Kasba', 'Jamal Auto Traders', 'Tania Home Needs', 'Habib Computer World', 'Moni Food Court',
    ];

    /** [category slug, title, price, condition, keyword, description] */
    private const PRODUCTS = [
        ['mobile', 'Samsung Galaxy A34 128GB', 27500, 'used_like_new', 'smartphone', 'Used for 4 months, box and charger included. No scratches, battery health excellent.'],
        ['mobile', 'Xiaomi Redmi Note 12', 19500, 'used_good', 'phone', 'Working perfectly. Screen guard applied, small mark on the back cover.'],
        ['mobile', 'iPhone 11 64GB', 34000, 'used_good', 'iphone', 'Factory unlocked, battery 84%. Face ID works fine.'],
        ['mobile', 'Walton Primo Feature Phone', 1800, 'new', 'mobile', 'Brand new, sealed box. Dual SIM with long battery life.'],
        ['computer', 'HP Core i5 Laptop 8GB/256GB SSD', 38000, 'used_good', 'laptop', 'Great for office work and online classes. Battery gives ~3 hours.'],
        ['computer', 'Dell 19 inch LED Monitor', 5500, 'used_good', 'monitor', 'HDMI + VGA. No dead pixels.'],
        ['computer', 'Logitech Wireless Mouse', 700, 'new', 'computer-mouse', 'Original, one year warranty card available.'],
        ['electronics', 'Walton 32 inch LED TV', 16500, 'used_good', 'television', 'Clear picture, remote included. Wall mount free.'],
        ['electronics', 'Rice Cooker 1.8L', 2200, 'new', 'kitchen', 'Non-stick pot, steam tray included. Brand new in box.'],
        ['electronics', 'Rechargeable Fan with Light', 2800, 'new', 'fan', 'Perfect for load-shedding. 6-8 hours backup.'],
        ['clothing', 'Men\'s Panjabi - Eid Collection', 1650, 'new', 'clothes', 'Cotton, size L and XL available. Fixed price for Kasba customers.'],
        ['clothing', 'Women\'s Three Piece Set', 2400, 'new', 'dress', 'Soft georgette, hand-embroidered dupatta. Free home delivery in Kasba.'],
        ['fashion', 'Leather Wallet and Belt Combo', 950, 'new', 'wallet', 'Genuine leather, makes a great gift.'],
        ['fashion', 'Ladies Handbag', 1300, 'used_like_new', 'handbag', 'Used twice, looks brand new. Spacious with zip pockets.'],
        ['furniture', 'Wooden Sofa Set (5 seats)', 28000, 'used_good', 'sofa', 'Segun wood frame, cushions recently re-covered.'],
        ['furniture', 'Study Table with Drawer', 4500, 'used_good', 'desk', 'Sturdy table, perfect for students.'],
        ['furniture', 'Steel Almirah', 9500, 'used_fair', 'cabinet', 'Two doors with lock. Some paint wear on the side.'],
        ['home-living', 'Double Bed Mattress', 7200, 'new', 'mattress', 'Firm foam, 6 inch. Still in plastic wrapper.'],
        ['vehicles', 'Hero Hunk 150cc Motorcycle (2019)', 118000, 'used_good', 'motorcycle', 'Papers up to date, tax token cleared. Serious buyers only.'],
        ['vehicles', 'Phoenix Bicycle 26 inch', 6500, 'used_good', 'bicycle', 'New tyres and brakes. Ready to ride.'],
        ['food', 'Pure Mustard Oil 5L', 1250, 'new', 'oil-bottle', 'Cold-pressed from Kasba mills. Delivery available.'],
        ['food', 'Homemade Pitha Box (12 pcs)', 450, 'new', 'cake', 'Fresh pitha made to order. Please order a day before.'],
        ['books', 'HSC Physics and Chemistry Set', 1100, 'used_good', 'books', 'Clean pages, a few highlights. Both 1st and 2nd papers.'],
        ['books', 'Bangla Novels Collection (10 books)', 1500, 'used_good', 'novel', 'Humayun Ahmed and Zafar Iqbal collection.'],
        ['services', 'Home Tutor - Class 6 to 10 Math', 3000, 'new', 'teacher', 'Experienced tutor, monthly fee. Kasba town area only.'],
        ['services', 'AC and Fridge Repair', 500, 'new', 'repair', 'Same-day service call. Charges depend on the problem.'],
        ['jobs', 'Shop Assistant Needed', 9000, 'new', 'shop', 'Full-time position at a Kasba bazar shop. Monthly salary shown.'],
        ['agriculture', 'Hybrid Tomato Seedlings (100 pcs)', 800, 'new', 'tomato', 'Healthy seedlings, ready to transplant.'],
        ['agriculture', 'Power Tiller (used)', 85000, 'used_fair', 'tractor', 'Runs well, serviced last month. Includes attachments.'],
        ['other', 'Prayer Mat and Tasbih Set', 600, 'new', 'prayer', 'Soft velvet mat, perfect gift set.'],
    ];

    private const TOP_COMMENTS = [
        'Bhai last price koto?', 'Is this still available?', 'Can I see it in person today?',
        'Delivery hobe Kasba bazar e?', 'Warranty ache?', 'Price ektu komano jay?',
        'Ami interested, inbox korchi.', 'Real photo dewa jabe?', 'Kon area te thaken?',
    ];

    private const REPLIES = [
        'Ji available ache.', 'Final price ta listing e dewa ache.', 'Hae, shokal theke shondha porjonto dekha jay.',
        'Okay, tahole kal dekhte ashi.', 'Thik ache bhai, taka ready rakhbo.', 'Inbox e message den, details dibo.',
        'Ektu komabo, 200 taka kom dite parbo.', 'Dhonnobad, deal done.', 'Kasba bazar er kache.',
    ];

    public function run(): void
    {
        // One transaction: far faster than per-row commits on SQLite.
        DB::transaction(fn () => $this->seedDemoData());
    }

    private function seedDemoData(): void
    {
        $kasba = Location::where('slug', 'kasba')->value('id');
        $categories = Category::pluck('id', 'slug');

        // Demo accounts created by DatabaseSeeder + 20 more users.
        $users = collect([
            User::where('email', 'seller@kasbabazer.com')->first(),
            User::where('email', 'buyer@kasbabazer.com')->first(),
        ])->filter();

        foreach (self::NAMES as $i => $name) {
            $users->push(User::factory()->create([
                'name' => $name,
                'username' => Str::slug($name, '_').$i,
                'email' => Str::slug($name, '.').$i.'@example.com',
                'location_id' => $kasba,
                'bio' => 'Living in Kasba, Brahmanbaria.',
                'avatar' => null,
            ]));
        }

        // First 10 users (incl. the demo seller) become sellers.
        $sellers = $users->take(10)->values();
        foreach ($sellers as $i => $seller) {
            SellerProfile::create([
                'user_id' => $seller->id,
                'shop_name' => self::SHOPS[$i],
                'description' => 'Trusted local shop in Kasba. Quality products, fair prices.',
                'phone' => $seller->phone,
                'address' => 'Kasba Bazar, Brahmanbaria',
                'location_id' => $kasba,
                'status' => 'verified',
                'is_verified' => $i % 3 !== 2,
            ]);
        }

        // 50 listings
        $listings = collect();
        for ($i = 0; $i < 50; $i++) {
            [$slug, $title, $price, $condition, $keyword, $description] = self::PRODUCTS[$i % count(self::PRODUCTS)];
            $seller = $sellers[$i % $sellers->count()];
            $variant = $i >= count(self::PRODUCTS) ? ' #'.(intdiv($i, count(self::PRODUCTS)) + 1) : '';
            $published = now()->subHours(rand(1, 24 * 20));

            $listing = new Listing([
                'category_id' => $categories[$slug] ?? $categories['other'],
                'location_id' => $kasba,
                'title' => $title.$variant,
                'description' => $description,
                'price' => $price + ($i >= count(self::PRODUCTS) ? rand(-5, 5) * 50 : 0),
                'is_negotiable' => (bool) rand(0, 1),
                'condition' => $condition,
                'quantity' => 1,
            ]);

            $listing->forceFill([
                'user_id' => $seller->id,
                'slug' => Str::slug($title).'-'.Str::lower(Str::random(6)),
                'status' => $i % 11 === 0 ? 'sold' : ($i % 13 === 0 ? 'reserved' : 'active'),
                'views_count' => rand(10, 600),
                'published_at' => $published,
                'created_at' => $published,
                'updated_at' => $published,
            ])->save();

            foreach (range(0, rand(0, 2)) as $n) {
                $listing->images()->create([
                    'url' => "/dummy/{$keyword}-".(($n % 3) + 1).'.svg',
                    'sort_order' => $n,
                ]);
            }

            $listings->push($listing);
        }

        // Nested comments
        foreach ($listings as $listing) {
            for ($c = 0; $c < rand(1, 4); $c++) {
                $parent = null;
                $depth = rand(1, 5);
                $at = now()->subMinutes(rand(1000, 14000));
                for ($d = 0; $d < $depth; $d++) {
                    // Alternate buyer / seller so threads read like a conversation.
                    $author = $d % 2 === 0
                        ? $users->where('id', '!=', $listing->user_id)->random()
                        : $users->firstWhere('id', $listing->user_id);

                    $at = $at->addMinutes(rand(3, 240));
                    $parent = Comment::create([
                        'listing_id' => $listing->id,
                        'user_id' => $author->id,
                        'parent_id' => $parent?->id,
                        'content' => $d % 2 === 0 ? Arr::random(self::TOP_COMMENTS) : Arr::random(self::REPLIES),
                    ]);
                    $parent->forceFill(['created_at' => $at, 'updated_at' => $at])->save();
                }
            }
        }

        // Reactions
        foreach ($listings as $listing) {
            foreach ($users->random(rand(0, 8)) as $user) {
                Reaction::firstOrCreate(
                    ['user_id' => $user->id, 'reactable_type' => $listing->getMorphClass(), 'reactable_id' => $listing->id],
                    ['type' => Arr::random(Reaction::TYPES)],
                );
            }
        }
        foreach (Comment::inRandomOrder()->limit(60)->get() as $comment) {
            Reaction::firstOrCreate(
                ['user_id' => $users->random()->id, 'reactable_type' => $comment->getMorphClass(), 'reactable_id' => $comment->id],
                ['type' => 'like'],
            );
        }

        // Saved listings
        foreach ($users as $user) {
            $user->savedListings()->syncWithoutDetaching($listings->random(rand(0, 5))->pluck('id')->all());
        }

        // Reviews
        foreach ($sellers as $seller) {
            foreach ($users->where('id', '!=', $seller->id)->random(rand(2, 5)) as $reviewer) {
                Review::create([
                    'seller_id' => $seller->id,
                    'reviewer_id' => $reviewer->id,
                    'rating' => rand(3, 5),
                    'review' => Arr::random(['Very honest seller, product as described.', 'Fast response and fair price.', 'Good experience, will buy again.', 'Product was okay, delivery a bit late.']),
                ]);
            }
        }

        // Conversations
        foreach ($listings->random(12) as $listing) {
            $buyer = $users->where('id', '!=', $listing->user_id)->random();
            $conversation = Conversation::create(['listing_id' => $listing->id]);
            $conversation->participants()->attach([
                $buyer->id => ['joined_at' => now()],
                $listing->user_id => ['joined_at' => now()],
            ]);

            $lines = [
                [$buyer->id, "Assalamu alaikum, {$listing->title} ta ki ache?"],
                [$listing->user_id, 'Walaikum assalam, ji ache.'],
                [$buyer->id, 'Last price koto hobe?'],
                [$listing->user_id, 'Listing er price e final, sorry.'],
            ];
            foreach ($lines as $k => [$sender, $text]) {
                $conversation->messages()->create(['user_id' => $sender, 'message' => $text, 'type' => 'text'])
                    ->forceFill(['created_at' => now()->subMinutes(60 - $k * 10), 'read_at' => $k < 3 ? now() : null])->save();
            }
        }

        // Denormalised counters
        DB::statement('UPDATE listings SET comments_count = (SELECT COUNT(*) FROM comments WHERE comments.listing_id = listings.id AND comments.deleted_at IS NULL)');
        DB::statement("UPDATE listings SET likes_count = (SELECT COUNT(*) FROM reactions WHERE reactions.reactable_id = listings.id AND reactions.reactable_type = 'App\\Models\\Listing')");
        DB::statement("UPDATE comments SET likes_count = (SELECT COUNT(*) FROM reactions WHERE reactions.reactable_id = comments.id AND reactions.reactable_type = 'App\\Models\\Comment')");

    }
}
