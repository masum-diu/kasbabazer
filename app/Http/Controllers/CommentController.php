<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommentRequest;
use App\Models\Comment;
use App\Models\Listing;
use App\Services\CommentService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class CommentController extends Controller
{
    public function __construct(private readonly CommentService $comments) {}

    public function store(CommentRequest $request, Listing $listing): RedirectResponse
    {
        $this->authorize('view', $listing);

        $this->comments->create($listing, $request->user(), $request->validated('content'), $request->validated('parent_id'));

        return back();
    }

    public function update(CommentRequest $request, Comment $comment): RedirectResponse
    {
        $this->authorize('update', $comment);

        $comment->update(['content' => $request->validated('content')]);

        return back();
    }

    public function destroy(Comment $comment): RedirectResponse
    {
        $this->authorize('delete', $comment);
        $this->comments->delete($comment);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Comment deleted.']);

        return back();
    }
}
