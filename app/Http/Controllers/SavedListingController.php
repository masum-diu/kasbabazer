<?php

namespace App\Http\Controllers;

use App\Http\Resources\ListingResource;
use App\Models\Listing;
use App\Services\ListingService;
use App\Support\ViewerContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SavedListingController extends Controller
{
    public function index(Request $request): Response
    {
        $page = $request->user()->savedListings()
            ->with(['images', 'category', 'user' => ListingService::sellerRelation()])
            ->wherePivotNotNull('created_at')->orderByPivot('created_at', 'desc')->paginate(12);

        ViewerContext::forListings($request, $page->getCollection()->pluck('id'));

        return Inertia::render('saved', [
            'listings' => Inertia::scroll(ListingResource::collection($page)),
            'total' => $page->total(),
        ]);
    }

    public function toggle(Request $request, Listing $listing): RedirectResponse
    {
        $this->authorize('view', $listing);

        $changes = $request->user()->savedListings()->toggle([$listing->id => ['created_at' => now()]]);

        Inertia::flash('toast', ['type' => 'success', 'message' => $changes['attached'] ? 'Saved.' : 'Removed from saved.']);

        return back();
    }
}
