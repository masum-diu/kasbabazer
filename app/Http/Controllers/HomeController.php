<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Http\Resources\ListingResource;
use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use App\Services\ListingService;
use App\Support\ViewerContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(private readonly ListingService $listings) {}

    public function __invoke(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'condition' => ['nullable', 'in:'.implode(',', Listing::CONDITIONS)],
            'sort' => ['nullable', 'in:'.implode(',', ListingService::SORTS)],
        ]);

        $page = $this->listings->search($filters, 12);
        ViewerContext::forListings($request, $page->getCollection()->pluck('id'));

        $categories = Category::query()
            ->whereNull('parent_id')->where('is_active', true)
            ->with(['children' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('sort_order')->get();

        return Inertia::render('home', [
            'listings' => Inertia::scroll(ListingResource::collection($page)),
            'total' => $page->total(),
            'topSellers' => fn () => User::query()
                ->whereHas('sellerProfile', fn ($q) => $q->where('status', 'verified'))
                ->with('sellerProfile:id,user_id,shop_name,is_verified')
                ->withAvg('reviewsReceived as rating_avg', 'rating')
                ->withCount(['reviewsReceived', 'listings as active_listings_count' => fn ($q) => $q->where('status', 'active')])
                ->orderByDesc('rating_avg')->orderByDesc('active_listings_count')->limit(5)->get()
                ->map(fn (User $u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'avatar' => $u->avatar,
                    'shop_name' => $u->sellerProfile?->shop_name,
                    'rating' => round((float) $u->getAttribute('rating_avg'), 1),
                    'listings' => (int) $u->getAttribute('active_listings_count'),
                ]),
            'facebookPageUrl' => config('services.facebook.page_url'),
            'filters' => (object) $filters,
            'categories' => CategoryResource::collection($categories)->resolve(),
        ]);
    }
}
