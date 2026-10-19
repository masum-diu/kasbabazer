<?php

namespace App\Http\Resources;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recursive: `replies` is a pre-assembled tree (see CommentService::tree).
 *
 * @mixin Comment
 */
class CommentResource extends JsonResource
{
    /**
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        $sellerId = $request->attributes->get('listing_owner_id');
        $reacted = (array) $request->attributes->get('reacted_comments', []);
        $viewer = $request->user();

        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'content' => $this->content,
            'created_at' => $this->created_at?->toISOString(),
            'likes_count' => $this->likes_count,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'avatar' => $this->user->avatar,
            ],
            'is_seller' => $sellerId === $this->user_id,
            'seller_badge' => $sellerId === $this->user_id ? 'Seller' : null,
            'viewer_liked' => isset($reacted[$this->id]),
            'can_edit' => $viewer?->id === $this->user_id,
            'can_delete' => $viewer !== null && ($viewer->id === $this->user_id || $viewer->isAdmin()),
            'replies' => self::collection($this->whenLoaded('replies', $this->replies, collect()))->resolve($request),
        ];
    }
}
