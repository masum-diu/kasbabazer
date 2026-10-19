<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Listing;
use App\Models\Reaction;
use App\Services\ReactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReactionController extends Controller
{
    public function __construct(private readonly ReactionService $reactions) {}

    public function listing(Request $request, Listing $listing): RedirectResponse
    {
        $this->authorize('view', $listing);
        $this->reactions->toggle($request->user(), $listing, $this->type($request));

        return back();
    }

    public function comment(Request $request, Comment $comment): RedirectResponse
    {
        $this->reactions->toggle($request->user(), $comment, $this->type($request));

        return back();
    }

    private function type(Request $request): string
    {
        return $request->validate(['type' => ['nullable', 'in:'.implode(',', Reaction::TYPES)]])['type'] ?? 'like';
    }
}
