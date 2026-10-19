<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\Location;
use App\Models\Report;
use App\Models\SellerProfile;
use App\Models\User;
use App\Services\CommentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MarketplaceTest extends TestCase
{
    use RefreshDatabase;

    private function seller(): User
    {
        $user = User::factory()->create();
        $profile = new SellerProfile(['shop_name' => 'Test Shop']);
        $profile->forceFill(['user_id' => $user->id, 'status' => 'verified', 'is_verified' => true])->save();

        return $user;
    }

    /**
     * A real 1x1 PNG, so tests do not depend on the GD extension.
     */
    private function pngUpload(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, (string) base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));
    }

    private function listingFor(User $owner, array $attrs = []): Listing
    {
        return Listing::factory()->create(['user_id' => $owner->id] + $attrs);
    }

    public function test_home_lists_active_listings_with_search_and_sort(): void
    {
        $seller = $this->seller();
        $this->listingFor($seller, ['title' => 'Blue Bicycle', 'price' => 5000]);
        $this->listingFor($seller, ['title' => 'Red Sofa', 'price' => 20000]);
        $this->listingFor($seller, ['title' => 'Hidden Thing', 'status' => 'hidden']);

        $this->get('/')->assertInertia(fn (Assert $p) => $p->component('home')->has('listings.data', 2));

        $this->get('/?search=bicycle')->assertInertia(fn (Assert $p) => $p->has('listings.data', 1)
            ->where('listings.data.0.title', 'Blue Bicycle'));

        $this->get('/?sort=price_high')->assertInertia(fn (Assert $p) => $p->where('listings.data.0.title', 'Red Sofa'));
    }

    public function test_category_children_reach_the_frontend_as_plain_arrays(): void
    {
        $parent = Category::factory()->create();
        Category::factory()->create(['parent_id' => $parent->id]);
        $this->actingAs($this->seller());

        $expect = fn (Assert $p) => $p->has('categories.0.children', 1)->where('categories.0.children.0.parent_id', $parent->id);
        $this->get('/')->assertInertia($expect);
        $this->get('/sell')->assertInertia($expect);
    }

    public function test_category_filter_includes_child_categories(): void
    {
        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $parent->id]);
        $this->listingFor($this->seller(), ['category_id' => $child->id]);

        $this->get('/?category_id='.$parent->id)->assertInertia(fn (Assert $p) => $p->has('listings.data', 1));
    }

    public function test_guest_cannot_post_and_non_seller_is_sent_to_setup(): void
    {
        $this->get('/sell')->assertRedirect('/login');

        $this->actingAs(User::factory()->create())->get('/sell')->assertRedirect(route('seller.apply'));
    }

    public function test_user_can_become_seller_and_create_listing_with_images(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $location = Location::create(['name' => 'Kasba', 'slug' => 'kasba', 'type' => 'upazila']);

        $this->actingAs($user)->post('/seller/apply', ['shop_name' => 'My Shop'])->assertRedirect(route('sellers.show', $user));
        $this->assertDatabaseHas('seller_profiles', ['user_id' => $user->id, 'shop_name' => 'My Shop']);

        $this->actingAs($user)->post('/listings', [
            'title' => 'Used Phone',
            'description' => 'Works perfectly, with charger.',
            'category_id' => $category->id,
            'location_id' => $location->id,
            'price' => 12000,
            'condition' => 'used_good',
            'quantity' => 1,
            'images' => [$this->pngUpload('phone.png'), $this->pngUpload('back.png')],
        ])->assertRedirect();

        $listing = Listing::firstOrFail();
        $this->assertSame('active', $listing->status);
        $this->assertCount(2, $listing->images);
        // Stored under a random name, never the client's.
        $this->assertStringNotContainsString('phone', $listing->images->first()->path ?? '');
        Storage::disk('public')->assertExists($listing->images->first()->path);
    }

    public function test_listing_rejects_non_image_uploads(): void
    {
        $user = $this->seller();

        $this->actingAs($user)->post('/listings', [
            'title' => 'Bad upload', 'description' => 'Some description here.', 'category_id' => Category::factory()->create()->id,
            'price' => 10, 'condition' => 'new', 'quantity' => 1,
            'images' => [UploadedFile::fake()->create('evil.php', 10, 'application/x-php')],
        ])->assertSessionHasErrors('images.0');
    }

    public function test_only_owner_can_edit_delete_or_change_status(): void
    {
        $owner = $this->seller();
        $listing = $this->listingFor($owner);
        $other = $this->seller();

        $this->actingAs($other)->get("/listings/{$listing->slug}/edit")->assertForbidden();
        $this->actingAs($other)->delete("/listings/{$listing->slug}")->assertForbidden();
        $this->actingAs($other)->post("/listings/{$listing->slug}/status/sold")->assertForbidden();

        $this->actingAs($owner)->post("/listings/{$listing->slug}/status/sold")->assertRedirect();
        $this->assertSame('sold', $listing->fresh()->status);

        $this->actingAs($owner)->post("/listings/{$listing->slug}/status/hide");
        $this->assertSame('hidden', $listing->fresh()->status);
        // Hidden listings are invisible to other users.
        $this->actingAs($other)->get("/listings/{$listing->slug}")->assertForbidden();

        $this->actingAs($owner)->delete("/listings/{$listing->slug}")->assertRedirect();
        $this->assertSoftDeleted($listing);
    }

    public function test_listing_page_counts_views_once_per_user_window(): void
    {
        $listing = $this->listingFor($this->seller());
        $viewer = User::factory()->create();

        $this->actingAs($viewer)->get("/listings/{$listing->slug}")->assertOk();
        $this->actingAs($viewer)->get("/listings/{$listing->slug}")->assertOk();

        $this->assertSame(1, $listing->fresh()->views_count);
    }

    public function test_nested_comment_thread_supports_unlimited_depth(): void
    {
        $userA = $this->seller();   // listing owner
        $userB = User::factory()->create();
        $listing = $this->listingFor($userA);

        $post = fn (User $u, string $text, ?int $parent = null) => $this->actingAs($u)
            ->post("/listings/{$listing->slug}/comments", ['content' => $text, 'parent_id' => $parent])->assertRedirect();

        $post($userB, 'Bhai last price koto?');
        $c1 = Comment::firstOrFail();
        $post($userA, '43,000 final.', $c1->id);
        $c2 = Comment::where('parent_id', $c1->id)->firstOrFail();
        $post($userB, 'Thik ache, kal nibo.', $c2->id);
        $c3 = Comment::where('parent_id', $c2->id)->firstOrFail();
        $post($userA, 'Ok, welcome.', $c3->id);

        $tree = app(CommentService::class)->tree($listing);

        $this->assertCount(1, $tree);
        $this->assertSame('Bhai last price koto?', $tree[0]->content);
        $this->assertSame('43,000 final.', $tree[0]->replies[0]->content);
        $this->assertSame('Thik ache, kal nibo.', $tree[0]->replies[0]->replies[0]->content);
        $this->assertSame('Ok, welcome.', $tree[0]->replies[0]->replies[0]->replies[0]->content);
        $this->assertSame(4, $listing->fresh()->comments_count);

        // Comments are lazy-loaded (WhenVisible), so they are absent from the first response...
        $this->actingAs($userB)->get("/listings/{$listing->slug}")->assertInertia(fn (Assert $p) => $p->missing('comments'));

        // ...and delivered on demand as nested JSON, with the seller badge on the owner's replies.
        $this->actingAs($userB)->get("/listings/{$listing->slug}")->assertInertia(fn (Assert $p) => $p
            ->reloadOnly('comments', fn (Assert $reload) => $reload
                ->where('comments.0.replies.0.replies.0.replies.0.content', 'Ok, welcome.')
                ->where('comments.0.replies.0.is_seller', true)
                ->where('comments.0.replies.0.seller_badge', 'Seller')
                ->where('comments.0.is_seller', false)));
    }

    public function test_reply_must_belong_to_same_listing(): void
    {
        $user = User::factory()->create();
        $listingA = $this->listingFor($this->seller());
        $listingB = $this->listingFor($this->seller());
        $foreign = Comment::factory()->create(['listing_id' => $listingB->id]);

        $this->actingAs($user)
            ->post("/listings/{$listingA->slug}/comments", ['content' => 'sneaky', 'parent_id' => $foreign->id])
            ->assertSessionHasErrors('parent_id');

        $this->assertDatabaseMissing('comments', ['content' => 'sneaky']);
    }

    public function test_deleting_a_comment_removes_its_replies_and_only_owner_can(): void
    {
        $listing = $this->listingFor($this->seller());
        $author = User::factory()->create();
        $root = Comment::factory()->create(['listing_id' => $listing->id, 'user_id' => $author->id]);
        $child = Comment::factory()->create(['listing_id' => $listing->id, 'parent_id' => $root->id]);
        $listing->update(['comments_count' => 2]);

        $this->actingAs(User::factory()->create())->delete("/comments/{$root->id}")->assertForbidden();

        $this->actingAs($author)->delete("/comments/{$root->id}")->assertRedirect();
        $this->assertSoftDeleted($root);
        $this->assertSoftDeleted($child);
        $this->assertSame(0, $listing->fresh()->comments_count);
    }

    public function test_reactions_toggle_change_and_never_duplicate(): void
    {
        $user = User::factory()->create();
        $listing = $this->listingFor($this->seller());

        $this->actingAs($user)->post("/listings/{$listing->slug}/reaction", ['type' => 'like']);
        $this->assertSame(1, $listing->fresh()->likes_count);

        // Different type replaces the reaction rather than adding a second.
        $this->actingAs($user)->post("/listings/{$listing->slug}/reaction", ['type' => 'love']);
        $this->assertDatabaseCount('reactions', 1);
        $this->assertDatabaseHas('reactions', ['type' => 'love']);

        // Same type again removes it.
        $this->actingAs($user)->post("/listings/{$listing->slug}/reaction", ['type' => 'love']);
        $this->assertDatabaseCount('reactions', 0);
        $this->assertSame(0, $listing->fresh()->likes_count);

        $this->actingAs($user)->post("/listings/{$listing->slug}/reaction", ['type' => 'bogus'])->assertSessionHasErrors('type');
    }

    public function test_comment_likes_and_save_toggle(): void
    {
        $user = User::factory()->create();
        $listing = $this->listingFor($this->seller());
        $comment = Comment::factory()->create(['listing_id' => $listing->id]);

        $this->actingAs($user)->post("/comments/{$comment->id}/reaction");
        $this->assertSame(1, $comment->fresh()->likes_count);

        $this->actingAs($user)->post("/listings/{$listing->slug}/save");
        $this->assertTrue($user->savedListings()->whereKey($listing->id)->exists());
        $this->actingAs($user)->get('/saved')->assertInertia(fn (Assert $p) => $p->has('listings.data', 1));

        $this->actingAs($user)->post("/listings/{$listing->slug}/save");
        $this->assertFalse($user->savedListings()->whereKey($listing->id)->exists());
    }

    public function test_chat_flow_and_participant_only_access(): void
    {
        $seller = $this->seller();
        $buyer = User::factory()->create();
        $stranger = User::factory()->create();
        $listing = $this->listingFor($seller);

        $this->actingAs($seller)->post('/conversations', ['listing_id' => $listing->id])->assertSessionHasErrors('listing');

        $this->actingAs($buyer)->post('/conversations', ['listing_id' => $listing->id])->assertRedirect();
        $conversation = Conversation::firstOrFail();

        // Starting again reuses the same conversation.
        $this->actingAs($buyer)->post('/conversations', ['listing_id' => $listing->id]);
        $this->assertDatabaseCount('conversations', 1);

        $this->actingAs($buyer)->post("/conversations/{$conversation->id}/messages", ['message' => 'Is it available?'])->assertRedirect();
        $this->actingAs($seller)->get("/inbox/{$conversation->id}")->assertOk()
            ->assertInertia(fn (Assert $p) => $p->where('active.messages.0.message', 'Is it available?')->where('active.messages.0.mine', false));
        $this->assertNotNull($conversation->messages()->first()->read_at);

        $this->actingAs($stranger)->get("/inbox/{$conversation->id}")->assertForbidden();
        $this->actingAs($stranger)->post("/conversations/{$conversation->id}/messages", ['message' => 'hi'])->assertForbidden();
    }

    public function test_seller_reviews_are_unique_per_reviewer_and_average_is_computed(): void
    {
        $seller = $this->seller();
        $reviewer = User::factory()->create();

        $this->actingAs($reviewer)->post("/sellers/{$seller->id}/reviews", ['rating' => 5, 'review' => 'Great'])->assertRedirect();
        $this->actingAs($reviewer)->post("/sellers/{$seller->id}/reviews", ['rating' => 3])->assertRedirect();
        $this->assertDatabaseCount('reviews', 1);

        $other = User::factory()->create();
        $this->actingAs($other)->post("/sellers/{$seller->id}/reviews", ['rating' => 4]);

        $this->get("/sellers/{$seller->id}")->assertInertia(fn (Assert $p) => $p->where('seller.average', 3.5)->where('seller.count', 2));

        $this->actingAs($seller)->post("/sellers/{$seller->id}/reviews", ['rating' => 5])->assertSessionHasErrors('rating');
        $this->actingAs($reviewer)->post("/sellers/{$seller->id}/reviews", ['rating' => 9])->assertSessionHasErrors('rating');
    }

    public function test_reports_can_be_filed_against_listings_comments_and_users(): void
    {
        $user = User::factory()->create();
        $listing = $this->listingFor($this->seller());

        $this->actingAs($user)->post('/reports', ['type' => 'listing', 'id' => $listing->id, 'reason' => 'scam'])->assertRedirect();
        $this->actingAs($user)->post('/reports', ['type' => 'user', 'id' => $listing->user_id, 'reason' => 'spam'])->assertRedirect();
        $this->assertDatabaseCount('reports', 2);

        $this->actingAs($user)->post('/reports', ['type' => 'listing', 'id' => $listing->id, 'reason' => 'nonsense'])->assertSessionHasErrors('reason');
    }

    public function test_comment_author_can_edit_but_others_cannot(): void
    {
        $listing = $this->listingFor($this->seller());
        $author = User::factory()->create();
        $comment = Comment::factory()->create(['listing_id' => $listing->id, 'user_id' => $author->id, 'content' => 'old']);

        $this->actingAs(User::factory()->create())->put("/comments/{$comment->id}", ['content' => 'hacked'])->assertForbidden();
        $this->actingAs($author)->put("/comments/{$comment->id}", ['content' => 'new text'])->assertRedirect();

        $this->assertSame('new text', $comment->fresh()->content);
    }

    public function test_notifications_are_created_listed_and_marked_read(): void
    {
        $owner = $this->seller();
        $commenter = User::factory()->create();
        $listing = $this->listingFor($owner);

        $this->actingAs($commenter)->post("/listings/{$listing->slug}/comments", ['content' => 'Nice!']);
        $parent = Comment::firstOrFail();
        $this->actingAs($owner)->post("/listings/{$listing->slug}/comments", ['content' => 'Thanks', 'parent_id' => $parent->id]);
        $this->actingAs($commenter)->post("/listings/{$listing->slug}/reaction", ['type' => 'love']);

        // Owner: comment + reaction. Commenter: reply. No self-notifications.
        $this->assertSame(2, $owner->unreadNotifications()->count());
        $this->assertSame(1, $commenter->unreadNotifications()->count());

        $this->actingAs($owner)->get('/notifications')->assertOk()
            ->assertInertia(fn (Assert $p) => $p->component('notifications')->has('notifications.data', 2));

        $id = $owner->notifications()->first()->id;
        $this->actingAs($commenter)->get("/notifications/{$id}/open")->assertNotFound();
        $this->actingAs($owner)->get("/notifications/{$id}/open")->assertRedirect();
        $this->assertSame(1, $owner->unreadNotifications()->count());

        $this->actingAs($owner)->post('/notifications/read-all');
        $this->assertSame(0, $owner->unreadNotifications()->count());
    }

    public function test_messages_notify_the_other_participant(): void
    {
        $seller = $this->seller();
        $buyer = User::factory()->create();
        $listing = $this->listingFor($seller);

        $this->actingAs($buyer)->post('/conversations', ['listing_id' => $listing->id]);
        $conversation = Conversation::firstOrFail();
        $this->actingAs($buyer)->post("/conversations/{$conversation->id}/messages", ['message' => 'Hello']);

        $this->assertSame(1, $seller->unreadNotifications()->count());
        $this->assertSame(0, $buyer->unreadNotifications()->count());
    }

    public function test_admin_moderation_is_admin_only_and_works(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $seller = $this->seller();
        $listing = $this->listingFor($seller);
        $this->actingAs($user)->post('/reports', ['type' => 'listing', 'id' => $listing->id, 'reason' => 'scam']);
        $report = Report::firstOrFail();

        $this->actingAs($user)->get('/admin')->assertRedirect('/');
        $this->actingAs($user)->put("/admin/reports/{$report->id}", ['status' => 'resolved'])->assertRedirect('/');

        $this->actingAs($admin)->get('/admin?tab=reports')->assertOk()
            ->assertInertia(fn (Assert $p) => $p->component('admin/index')->has('reports', 1)->where('stats.pending_reports', 1));

        $this->actingAs($admin)->put("/admin/reports/{$report->id}", ['status' => 'resolved', 'remove_target' => 1])->assertRedirect();
        $this->assertSame('hidden', $listing->fresh()->status);
        $this->assertSame('resolved', $report->fresh()->status);
        $this->assertSame($admin->id, $report->fresh()->reviewed_by);

        $this->actingAs($admin)->put("/admin/sellers/{$seller->sellerProfile->id}", ['status' => 'suspended'])->assertRedirect();
        $this->assertSame('suspended', $seller->sellerProfile->fresh()->status);
        $this->assertSame(1, $seller->unreadNotifications()->count());

        $this->actingAs($admin)->post("/admin/users/{$user->id}/toggle")->assertRedirect();
        $this->assertFalse($user->fresh()->is_active);
        // Deactivated users are signed out on their next request.
        $this->actingAs($user->fresh())->get('/')->assertRedirect('/login');
        $this->actingAs($admin)->post("/admin/users/{$admin->id}/toggle")->assertStatus(422);
    }

    public function test_dashboard_shows_seller_stats_and_works_for_plain_buyers(): void
    {
        $seller = $this->seller();
        $listing = $this->listingFor($seller, ['views_count' => 40, 'likes_count' => 3]);
        $this->actingAs(User::factory()->create())->post("/listings/{$listing->slug}/comments", ['content' => 'Hi']);

        $this->actingAs($seller)->get('/dashboard')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('dashboard')->where('isSeller', true)
            ->where('stats.views', 40)->where('stats.likes', 3)->where('stats.comments', 1)
            ->where('plan.name', 'Free')->has('recent', 1));

        $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk()
            ->assertInertia(fn (Assert $p) => $p->where('isSeller', false)->has('recent', 0));

        auth()->logout();
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_admin_can_add_categories_and_sub_categories(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $root = Category::factory()->create(['parent_id' => null]);

        $this->actingAs(User::factory()->create())->post('/admin/categories', ['name' => 'Nope'])->assertRedirect('/');

        $this->actingAs($admin)->post('/admin/categories', ['name' => 'Gadgets'])->assertRedirect();
        $this->post('/admin/categories', ['name' => 'Tablets', 'parent_id' => $root->id])->assertRedirect();
        $this->assertDatabaseHas('categories', ['name' => 'Tablets', 'parent_id' => $root->id]);

        $child = Category::where('name', 'Tablets')->firstOrFail();

        $this->put("/admin/categories/{$child->id}", ['name' => 'Tabs & Pads'])->assertRedirect();
        $this->assertDatabaseHas('categories', ['id' => $child->id, 'name' => 'Tabs & Pads']);

        // A sub-category cannot be a parent (two levels only).
        $this->post('/admin/categories', ['name' => 'Deep', 'parent_id' => $child->id])->assertSessionHasErrors('parent_id');

        // Categories that hold sub-categories are protected from deletion; empty ones can go.
        $this->delete("/admin/categories/{$root->id}");
        $this->assertDatabaseHas('categories', ['id' => $root->id]);
        $this->delete("/admin/categories/{$child->id}");
        $this->assertDatabaseMissing('categories', ['id' => $child->id]);
    }
}
