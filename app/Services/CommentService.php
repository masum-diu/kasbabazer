<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommentService
{
    public function __construct(private readonly NotificationService $notifications) {}

    private const MAX_LOADED = 400;

    /**
     * Whole thread for a listing in ONE query, assembled into a tree in memory.
     * Unlimited depth, no N+1.
     *
     * @return Collection<int, Comment>
     */
    public function tree(Listing $listing): Collection
    {
        $comments = $listing->comments()
            ->with('user:id,name,username,avatar')
            ->orderBy('created_at')
            ->limit(self::MAX_LOADED)
            ->get();

        $byParent = $comments->groupBy(fn (Comment $c) => $c->parent_id ?? 0);

        $attach = function (Comment $comment) use (&$attach, $byParent): Comment {
            $children = ($byParent->get($comment->id) ?? collect())->map($attach)->values();
            $comment->setRelation('replies', $children);

            return $comment;
        };

        return ($byParent->get(0) ?? collect())->map($attach)->values();
    }

    public function create(Listing $listing, User $user, string $content, ?int $parentId = null): Comment
    {
        if ($parentId !== null) {
            $parentBelongs = Comment::where('id', $parentId)->where('listing_id', $listing->id)->exists();

            if (! $parentBelongs) {
                throw ValidationException::withMessages([
                    'parent_id' => ['The parent comment does not belong to this listing.'],
                ]);
            }
        }

        return DB::transaction(function () use ($listing, $user, $content, $parentId) {
            $comment = new Comment(['content' => $content]);
            $comment->forceFill([
                'listing_id' => $listing->id,
                'user_id' => $user->id,
                'parent_id' => $parentId,
            ])->save();

            $listing->increment('comments_count');

            $url = route('listings.show', $listing, false);
            $parentAuthor = $parentId !== null ? Comment::find($parentId)?->user : null;
            if ($parentAuthor) {
                $this->notifications->notify($parentAuthor, $user, 'reply', "{$user->name} replied to your comment", $url, $content);
            }
            // Don't notify the owner twice when they are also the parent comment's author.
            if ($parentAuthor?->id !== $listing->user_id) {
                $this->notifications->notify($listing->user, $user, 'comment', "{$user->name} commented on {$listing->title}", $url, $content);
            }

            return $comment;
        });
    }

    /**
     * Soft-delete a comment together with all of its descendants.
     */
    public function delete(Comment $comment): void
    {
        DB::transaction(function () use ($comment) {
            $ids = [$comment->id];
            $frontier = [$comment->id];

            while ($frontier !== []) {
                $frontier = Comment::whereIn('parent_id', $frontier)->pluck('id')->all();
                $ids = array_merge($ids, $frontier);
            }

            $deleted = Comment::whereIn('id', $ids)->delete();

            $listing = Listing::find($comment->listing_id);
            $listing?->update(['comments_count' => max(0, $listing->comments_count - $deleted)]);
        });
    }
}
