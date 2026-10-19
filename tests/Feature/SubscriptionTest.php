<?php

namespace Tests\Feature;

use App\Http\Resources\ListingResource;
use App\Models\Category;
use App\Models\Listing;
use App\Models\SellerProfile;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionService;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlanSeeder::class);
    }

    private function seller(): User
    {
        $user = User::factory()->create();
        $profile = new SellerProfile(['shop_name' => 'Shop']);
        $profile->forceFill(['user_id' => $user->id, 'status' => 'verified'])->save();

        return $user;
    }

    private function payload(array $over = []): array
    {
        return array_merge([
            'title' => 'Another item', 'description' => 'Some description here.', 'category_id' => Category::factory()->create()->id,
            'price' => 100, 'condition' => 'new', 'quantity' => 1,
        ], $over);
    }

    private function pay(User $user, string $plan = 'pro', string $trx = 'ABC123XYZ'): Subscription
    {
        $this->actingAs($user)->post("/plans/{$plan}/checkout", [
            'payment_method' => 'bkash', 'sender_number' => '01712345678', 'transaction_id' => $trx,
        ])->assertRedirect(route('plans'));

        return Subscription::latest('id')->firstOrFail();
    }

    public function test_pricing_page_is_public_and_lists_plans(): void
    {
        $this->get('/plans')->assertOk()->assertInertia(fn (Assert $p) => $p->component('plans/index')->has('plans', 4)->where('current', null));
    }

    public function test_free_plan_caps_active_listings(): void
    {
        $user = $this->seller();
        Listing::factory()->count(5)->create(['user_id' => $user->id]);

        $this->actingAs($user)->post('/listings', $this->payload())->assertSessionHasErrors('plan');
        $this->assertSame(5, Listing::count());

        // Drafts do not count against the limit.
        $this->actingAs($user)->post('/listings', $this->payload(['save_as_draft' => 1]))->assertRedirect();
        $this->assertSame(6, Listing::count());

        // Re-publishing past the limit is blocked too.
        $draft = Listing::where('status', 'draft')->firstOrFail();
        $this->actingAs($user)->post("/listings/{$draft->slug}/status/publish")->assertSessionHasErrors('plan');
    }

    public function test_payment_flow_activates_plan_and_raises_limits(): void
    {
        $user = $this->seller();
        $admin = User::factory()->admin()->create();
        Listing::factory()->count(5)->create(['user_id' => $user->id]);

        $sub = $this->pay($user, 'pro');
        $this->assertSame('pending', $sub->status);
        $this->assertSame(1, $admin->unreadNotifications()->count());
        // Still on Free until an admin confirms.
        $this->assertSame('Free', app(SubscriptionService::class)->planFor($user)->name);

        $this->actingAs($user)->post("/admin/payments/{$sub->id}/confirm")->assertRedirect('/');
        $this->actingAs($admin)->post("/admin/payments/{$sub->id}/confirm")->assertRedirect();

        $this->assertSame('active', $sub->fresh()->status);
        $this->assertSame('Pro', app(SubscriptionService::class)->planFor($user)->name);
        $this->assertTrue($user->sellerProfile->fresh()->is_verified);
        $this->assertSame(1, $user->unreadNotifications()->count());

        // Limit is now 150, so a 6th listing is fine.
        $this->actingAs($user)->post('/listings', $this->payload())->assertRedirect();
        $this->assertSame(6, Listing::count());
    }

    public function test_payment_validation_and_duplicate_protection(): void
    {
        $user = $this->seller();
        $other = $this->seller();
        $this->pay($user, 'basic', 'TRX111111');

        // One pending payment at a time; transaction IDs are single-use.
        $this->actingAs($user)->post('/plans/pro/checkout', ['payment_method' => 'bkash', 'sender_number' => '01712345678', 'transaction_id' => 'TRX222222'])
            ->assertSessionHasErrors('transaction_id');
        $this->actingAs($other)->post('/plans/pro/checkout', ['payment_method' => 'bkash', 'sender_number' => '01712345678', 'transaction_id' => 'trx111111'])
            ->assertSessionHasErrors('transaction_id');
        $this->actingAs($other)->post('/plans/pro/checkout', ['payment_method' => 'cash', 'sender_number' => 'abc', 'transaction_id' => '1'])
            ->assertSessionHasErrors(['payment_method', 'sender_number', 'transaction_id']);

        // The free plan cannot be "bought".
        $this->actingAs($other)->get('/plans/free/checkout')->assertNotFound();
    }

    public function test_admin_can_reject_a_payment(): void
    {
        $user = $this->seller();
        $admin = User::factory()->admin()->create();
        $sub = $this->pay($user);

        $this->actingAs($admin)->post("/admin/payments/{$sub->id}/reject", ['note' => 'TrxID not found'])->assertRedirect();

        $this->assertSame('rejected', $sub->fresh()->status);
        $this->assertSame('Free', app(SubscriptionService::class)->planFor($user)->name);
        // Cannot be handled twice.
        $this->actingAs($admin)->post("/admin/payments/{$sub->id}/confirm")->assertStatus(422);
    }

    public function test_photo_limit_follows_the_plan(): void
    {
        $user = $this->seller();

        $files = fn (int $n) => array_map(fn ($i) => UploadedFile::fake()->createWithContent("p{$i}.png", (string) base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==')), range(1, $n));

        Storage::fake('public');
        $this->actingAs($user)->post('/listings', $this->payload(['images' => $files(4)]))->assertSessionHasErrors('images');
        $this->actingAs($user)->post('/listings', $this->payload(['images' => $files(3)]))->assertRedirect();
    }

    public function test_listing_never_holds_more_than_five_photos_and_the_seller_picks_home_feed_photos(): void
    {
        $user = $this->seller();
        $admin = User::factory()->admin()->create();
        $sub = $this->pay($user, 'shop');
        $this->actingAs($admin)->post("/admin/payments/{$sub->id}/confirm")->assertRedirect();

        $files = fn (int $n) => array_map(fn ($i) => UploadedFile::fake()->createWithContent("p{$i}.png", (string) base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==')), range(1, $n));

        Storage::fake('public');
        // Even the top plan stops at 5 photos.
        $this->actingAs($user)->post('/listings', $this->payload(['images' => $files(6)]))->assertSessionHasErrors('images');

        // Pick the 4th then the 2nd upload for the home feed: they lead, in that order.
        $this->post('/listings', $this->payload(['images' => $files(5), 'feed' => ['n:3', 'n:1']]))->assertRedirect();
        $listing = Listing::with('images')->firstOrFail();
        $created = $listing->images()->reorder('id')->pluck('id')->all();
        $this->assertCount(5, $created);
        $this->assertSame([$created[3], $created[1]], $listing->images()->where('on_feed', true)->pluck('id')->all());
        $this->assertSame($created[3], $listing->images()->first()->id);
        $this->assertCount(2, (new ListingResource($listing->fresh('images')))->resolve()['feed_images']);

        // More than 3 home photos is rejected.
        $this->post("/listings/{$listing->slug}/update", $this->payload(['feed' => ['e:'.$created[0], 'e:'.$created[1], 'e:'.$created[2], 'e:'.$created[3]]]))->assertSessionHasErrors('feed');

        // Switch to a single existing photo while editing.
        $this->post("/listings/{$listing->slug}/update", $this->payload(['feed' => ['e:'.$created[4]]]))->assertRedirect();
        $this->assertSame([$created[4]], $listing->images()->where('on_feed', true)->pluck('id')->all());
        $this->assertSame($created[4], $listing->images()->first()->id);

        // Saving without a choice keeps the existing pick.
        $this->post("/listings/{$listing->slug}/update", $this->payload())->assertRedirect();
        $this->assertSame([$created[4]], $listing->images()->where('on_feed', true)->pluck('id')->all());
    }

    public function test_quantity_is_optional_for_items_without_stock(): void
    {
        $user = $this->seller();

        $this->actingAs($user)->post('/listings', $this->payload(['quantity' => '']))->assertRedirect();
        $listing = Listing::firstOrFail();
        $this->assertNull($listing->quantity);

        $this->get("/listings/{$listing->slug}")->assertInertia(fn (Assert $p) => $p->where('listing.quantity', null));
        // A quantity, when given, must still be a positive number.
        $this->post('/listings', $this->payload(['quantity' => 0]))->assertSessionHasErrors('quantity');
    }

    public function test_boost_uses_plan_allowance_and_floats_listing_to_top(): void
    {
        $seller = $this->seller();
        $admin = User::factory()->admin()->create();
        $old = Listing::factory()->create(['user_id' => $seller->id, 'published_at' => now()->subDays(5)]);
        Listing::factory()->create(['user_id' => $seller->id, 'published_at' => now()]);

        // Free plan has no boosts.
        $this->actingAs($seller)->post("/listings/{$old->slug}/boost")->assertSessionHasErrors('boost');

        $basic = $this->pay($seller, 'basic'); // acts as the seller, so confirm as admin afterwards
        $this->actingAs($admin)->post("/admin/payments/{$basic->id}/confirm")->assertRedirect();

        $this->actingAs($seller)->post("/listings/{$old->slug}/boost")->assertRedirect();
        $this->assertNotNull($old->fresh()->boosted_until);

        // Boosting again while still boosted is refused and does not use up another boost.
        $left = app(SubscriptionService::class)->boostsRemaining($seller);
        $this->post("/listings/{$old->slug}/boost")->assertSessionHasErrors('boost');
        $this->assertSame($left, app(SubscriptionService::class)->boostsRemaining($seller));

        $this->get('/')->assertInertia(fn (Assert $p) => $p->where('listings.data.0.slug', $old->slug)->where('listings.data.0.boosted', true));

        // Basic = 2 boosts per period.
        $second = Listing::factory()->create(['user_id' => $seller->id]);
        $this->actingAs($seller)->post("/listings/{$second->slug}/boost")->assertRedirect();
        $third = Listing::factory()->create(['user_id' => $seller->id]);
        $this->actingAs($seller)->post("/listings/{$third->slug}/boost")->assertSessionHasErrors('boost');

        // Only the owner can boost.
        $this->actingAs(User::factory()->create())->post("/listings/{$third->slug}/boost")->assertForbidden();
    }

    public function test_expiry_command_lapses_plan_and_removes_paid_tick(): void
    {
        $user = $this->seller();
        $admin = User::factory()->admin()->create();
        $sub = $this->pay($user, 'pro');
        $this->actingAs($admin)->post("/admin/payments/{$sub->id}/confirm");
        $this->assertTrue($user->sellerProfile->fresh()->is_verified);

        $this->travel(31)->days();
        $this->artisan('subscriptions:expire')->assertSuccessful();

        $this->assertSame('expired', $sub->fresh()->status);
        $this->assertFalse($user->sellerProfile->fresh()->is_verified);
        $this->assertSame('Free', app(SubscriptionService::class)->planFor($user)->name);
    }

    public function test_admin_verified_tick_survives_plan_expiry(): void
    {
        $user = $this->seller();
        $user->sellerProfile->forceFill(['is_verified' => true])->save(); // granted by an admin, not by a plan
        $admin = User::factory()->admin()->create();
        $sub = $this->pay($user, 'pro');
        $this->actingAs($admin)->post("/admin/payments/{$sub->id}/confirm");

        $this->travel(31)->days();
        $this->artisan('subscriptions:expire');

        $this->assertTrue($user->sellerProfile->fresh()->is_verified);
    }
}
