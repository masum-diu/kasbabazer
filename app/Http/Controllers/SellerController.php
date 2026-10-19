<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewRequest;
use App\Http\Requests\SellerProfileRequest;
use App\Http\Resources\ListingResource;
use App\Models\Listing;
use App\Models\Location;
use App\Models\SellerProfile;
use App\Models\User;
use App\Services\ListingService;
use App\Services\ReviewService;
use App\Support\ViewerContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SellerController extends Controller
{
    public function __construct(private readonly ReviewService $reviews) {}

    public function show(Request $request, User $seller): Response
    {
        abort_unless($seller->sellerProfile()->exists(), 404);

        $seller->load('sellerProfile', 'location');
        $page = Listing::public()->where('user_id', $seller->id)
            ->with(['images', 'category', 'user' => ListingService::sellerRelation()])
            ->latest('published_at')->paginate(12);
        ViewerContext::forListings($request, $page->getCollection()->pluck('id'));

        $reviews = $seller->reviewsReceived()->where('status', 'visible')->with('reviewer:id,name,avatar')
            ->latest()->limit(20)->get()->map(fn ($r) => [
                'id' => $r->id,
                'rating' => $r->rating,
                'review' => $r->review,
                'created_at' => $r->created_at?->toISOString(),
                'reviewer' => ['id' => $r->reviewer->id, 'name' => $r->reviewer->name, 'avatar' => $r->reviewer->avatar],
            ]);

        $mine = $request->user() ? $seller->reviewsReceived()->where('reviewer_id', $request->user()->id)->first() : null;

        return Inertia::render('sellers/show', [
            'seller' => [
                'id' => $seller->id,
                'name' => $seller->name,
                'avatar' => $seller->avatar,
                'shop_name' => $seller->sellerProfile->shop_name,
                'description' => $seller->sellerProfile->description,
                'address' => $seller->sellerProfile->address,
                'is_verified' => $seller->sellerProfile->is_verified,
                'location' => $seller->location?->name,
                'joined' => $seller->created_at?->toISOString(),
                ...$this->reviews->summary($seller),
            ],
            'listings' => Inertia::scroll(ListingResource::collection($page)),
            'total' => $page->total(),
            'reviews' => $reviews,
            'myReview' => $mine ? ['rating' => $mine->rating, 'review' => $mine->review] : null,
            'isOwner' => $request->user()?->id === $seller->id,
            'canReview' => $request->user() !== null && $request->user()->id !== $seller->id,
        ]);
    }

    public function apply(Request $request): Response|RedirectResponse
    {
        $profile = $request->user()->sellerProfile;

        return Inertia::render('sellers/apply', [
            'profile' => $profile,
            'locations' => Location::whereIn('type', ['upazila', 'area'])->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(SellerProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $existing = $user->sellerProfile;

        if ($existing) {
            $existing->update($request->validated());
            $message = 'Shop profile updated.';
        } else {
            $profile = new SellerProfile($request->validated());
            // New shops are active immediately; the verified tick comes from a paid plan or an admin.
            $profile->forceFill(['user_id' => $user->id, 'status' => 'verified', 'is_verified' => false])->save();
            $message = 'You are now a seller!';
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return to_route('sellers.show', $user);
    }

    public function review(ReviewRequest $request, User $seller): RedirectResponse
    {
        $this->reviews->upsert($request->user(), $seller, (int) $request->validated('rating'), $request->validated('review'));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Thanks for your review.']);

        return back();
    }
}
