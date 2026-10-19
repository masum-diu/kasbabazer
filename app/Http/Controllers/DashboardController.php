<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Listing;
use App\Models\Message;
use App\Services\ReviewService;
use App\Services\SubscriptionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptions,
        private readonly ReviewService $reviews,
    ) {}

    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $isSeller = $user->sellerProfile()->exists();
        $plan = $this->subscriptions->planFor($user);
        $subscription = $this->subscriptions->activeSubscription($user);

        $mine = fn (): Builder => Listing::query()->where('user_id', $user->id);

        return Inertia::render('dashboard', [
            'isSeller' => $isSeller,
            'stats' => [
                'active_listings' => $this->subscriptions->activeListingCount($user),
                'views' => (int) $mine()->sum('views_count'),
                'likes' => (int) $mine()->sum('likes_count'),
                'comments' => Comment::whereIn('listing_id', $mine()->select('id'))->where('user_id', '!=', $user->id)->count(),
                'saves' => DB::table('saved_listings')->whereIn('listing_id', $mine()->select('id'))->count(),
                'unread_messages' => Message::whereHas('conversation.participants', fn ($q) => $q->where('users.id', $user->id))
                    ->where('user_id', '!=', $user->id)->whereNull('read_at')->count(),
                'saved_by_me' => $user->savedListings()->count(),
                ...$this->reviews->summary($user),
            ],
            'plan' => [
                'name' => $plan->name,
                'listing_limit' => $plan->listing_limit,
                'boosts_left' => $this->subscriptions->boostsRemaining($user),
                'boosts_total' => $plan->boosts_per_period,
                'ends_at' => $subscription?->ends_at?->toISOString(),
            ],
            'recent' => $mine()->with('images')->latest()->limit(5)->get()->map(fn (Listing $l) => [
                'slug' => $l->slug,
                'title' => $l->title,
                'status' => $l->status,
                'price' => (float) $l->price,
                'views' => $l->views_count,
                'likes' => $l->likes_count,
                'comments' => $l->comments_count,
                'thumbnail' => $l->images->first()?->url,
                'boosted' => $l->boosted_until?->isFuture() ?? false,
            ]),
            'activity' => $user->notifications()->latest()->limit(6)->get()->map(fn ($n) => [
                'id' => $n->id,
                'title' => $n->data['title'] ?? '',
                'read' => $n->read_at !== null,
                'created_at' => $n->created_at?->toISOString(),
            ]),
        ]);
    }
}
