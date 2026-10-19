<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListingRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\CommentResource;
use App\Http\Resources\ListingResource;
use App\Models\Category;
use App\Models\Listing;
use App\Services\CommentService;
use App\Services\ListingService;
use App\Services\SubscriptionService;
use App\Support\ViewerContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ListingController extends Controller
{
    private const STATUS_ACTIONS = [
        'publish' => 'active',
        'sold' => 'sold',
        'reserved' => 'reserved',
        'hide' => 'hidden',
    ];

    public function __construct(
        private readonly ListingService $listings,
        private readonly CommentService $comments,
        private readonly SubscriptionService $subscriptions,
    ) {}

    public function show(Request $request, Listing $listing): Response
    {
        $this->authorize('view', $listing);

        $listing->load(['images', 'category', 'location', 'user' => ListingService::sellerRelation()]);
        $this->listings->recordView($listing, $request->user(), $request->ip(), $request->userAgent());

        ViewerContext::forListings($request, [$listing->id]);

        return Inertia::render('listings/show', [
            'listing' => (new ListingResource($listing))->resolve($request),
            // Heavy sections load only when scrolled into view (WhenVisible on the client).
            'comments' => Inertia::optional(function () use ($request, $listing) {
                $thread = $this->comments->tree($listing);
                $ids = collect();
                $walk = function ($nodes) use (&$walk, $ids) {
                    foreach ($nodes as $node) {
                        $ids->push($node->id);
                        $walk($node->replies);
                    }
                };
                $walk($thread);
                ViewerContext::forComments($request, $ids, $listing->user_id);

                return CommentResource::collection($thread)->resolve($request);
            }),
            'related' => Inertia::optional(function () use ($request, $listing) {
                $related = Listing::public()->with(['images', 'category', 'user' => ListingService::sellerRelation()])
                    ->where('category_id', $listing->category_id)->whereKeyNot($listing->id)
                    ->latest('published_at')->limit(4)->get();
                ViewerContext::forListings($request, $related->pluck('id'));

                return ListingResource::collection($related)->resolve($request);
            }),
            'can' => [
                'manage' => $request->user()?->can('update', $listing) ?? false,
                'boosts_left' => $request->user()?->id === $listing->user_id ? $this->subscriptions->boostsRemaining($request->user()) : 0,
                'message' => $request->user() !== null && $request->user()->id !== $listing->user_id,
            ],
        ]);
    }

    public function mine(Request $request): Response
    {
        $page = Listing::where('user_id', $request->user()->id)
            ->with(['images', 'category', 'user' => ListingService::sellerRelation()])
            ->latest()->paginate(12);

        return Inertia::render('listings/mine', [
            'listings' => Inertia::scroll(ListingResource::collection($page)),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function planInfo(Request $request): array
    {
        $user = $request->user();
        $plan = $this->subscriptions->planFor($user);

        return [
            'name' => $plan->name,
            'listing_limit' => $plan->listing_limit,
            'photo_limit' => $plan->photo_limit,
            'active_listings' => $this->subscriptions->activeListingCount($user),
            'boosts_left' => $this->subscriptions->boostsRemaining($user),
        ];
    }

    public function create(Request $request): Response|RedirectResponse
    {
        if ($redirect = $this->requireSeller($request)) {
            return $redirect;
        }

        return Inertia::render('listings/form', [
            'listing' => null,
            'plan' => $this->planInfo($request),
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function store(ListingRequest $request): RedirectResponse
    {
        if ($redirect = $this->requireSeller($request)) {
            return $redirect;
        }

        $data = $request->safe()->except(['images', 'remove_image_ids', 'save_as_draft']);

        if (! $request->boolean('save_as_draft')) {
            $this->subscriptions->assertCanPublish($request->user());
        }
        $this->assertPhotoLimit($request, 0);

        $listing = $this->listings->create(
            $request->user(),
            $data,
            $request->file('images', []),
            publish: ! $request->boolean('save_as_draft'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Listing created.']);

        return to_route('listings.show', $listing);
    }

    public function edit(Request $request, Listing $listing): Response
    {
        $this->authorize('update', $listing);

        return Inertia::render('listings/form', [
            'listing' => (new ListingResource($listing->load('images', 'category')))->resolve(),
            'plan' => $this->planInfo($request),
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function update(ListingRequest $request, Listing $listing): RedirectResponse
    {
        $this->authorize('update', $listing);

        $kept = $listing->images()->count() - count($request->input('remove_image_ids', []));
        $this->assertPhotoLimit($request, max(0, $kept));

        $this->listings->update(
            $listing,
            $request->safe()->except(['images', 'remove_image_ids', 'save_as_draft']),
            $request->file('images', []),
            array_map('intval', $request->input('remove_image_ids', [])),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Listing updated.']);

        return to_route('listings.show', $listing);
    }

    public function destroy(Listing $listing): RedirectResponse
    {
        $this->authorize('delete', $listing);
        $listing->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Listing deleted.']);

        return to_route('listings.mine');
    }

    public function status(Listing $listing, string $action): RedirectResponse
    {
        $this->authorize('update', $listing);
        abort_unless(isset(self::STATUS_ACTIONS[$action]), 404);

        // Re-activating counts against the plan's active-listing limit.
        if ($action === 'publish' && $listing->status !== 'active') {
            $this->subscriptions->assertCanPublish($listing->user);
        }

        $this->listings->setStatus($listing, self::STATUS_ACTIONS[$action]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Listing marked as '.self::STATUS_ACTIONS[$action].'.']);

        return back();
    }

    public function boost(Listing $listing): RedirectResponse
    {
        $this->authorize('update', $listing);
        $this->subscriptions->boost($listing, $listing->user);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Boosted! Your post stays on top for '.config('billing.boost_hours').' hours.']);

        return back();
    }

    private function assertPhotoLimit(Request $request, int $existing): void
    {
        $limit = $this->subscriptions->photoLimit($request->user());

        if ($existing + count($request->file('images', [])) > $limit) {
            throw ValidationException::withMessages(['images' => ["Your plan allows up to {$limit} photos per listing."]]);
        }
    }

    /**
     * Only sellers can post; others are sent to the seller setup page.
     */
    private function requireSeller(Request $request): ?RedirectResponse
    {
        if ($request->user()->sellerProfile()->exists()) {
            return null;
        }

        Inertia::flash('toast', ['type' => 'info', 'message' => 'Set up your shop first to start selling.']);

        return to_route('seller.apply');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function categoryOptions(): array
    {
        $categories = Category::whereNull('parent_id')->where('is_active', true)
            ->with(['children' => fn ($q) => $q->where('is_active', true)])->orderBy('sort_order')->get();

        return CategoryResource::collection($categories)->resolve();
    }
}
