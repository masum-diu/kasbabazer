<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LocationResource;
use App\Models\Location;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * List locations. Filter with ?type= and ?parent_id=; defaults to top-level.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['nullable', 'in:'.implode(',', Location::TYPES)],
            'parent_id' => ['nullable', 'integer'],
        ]);

        $locations = Location::query()
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->query('type')))
            ->when(
                $request->filled('parent_id'),
                fn ($q) => $q->where('parent_id', $request->query('parent_id')),
                fn ($q) => $request->filled('type') ? $q : $q->whereNull('parent_id'),
            )
            ->orderBy('name')
            ->get();

        return ApiResponse::success(LocationResource::collection($locations));
    }

    public function show(Location $location): JsonResponse
    {
        return ApiResponse::success(new LocationResource($location->load('children')));
    }
}
