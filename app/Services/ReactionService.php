<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Listing;
use App\Models\Reaction;
use App\Models\User;

class ReactionService
{
    public function __construct(private readonly NotificationService $notifications) {}

    /**
     * Add, change or remove (same type again) the user's reaction. One reaction per user per target.
     *
     * @return array{reacted: bool, type: string|null, count: int}
     */
    public function toggle(User $user, Listing|Comment $target, string $type = 'like'): array
    {
        $existing = $target->reactions()->where('user_id', $user->id)->first();

        if ($existing && $existing->type === $type) {
            $existing->delete();
            $type = null;
        } elseif ($existing) {
            $existing->update(['type' => $type]);
        } else {
            $reaction = new Reaction(['type' => $type]);
            $reaction->forceFill(['user_id' => $user->id]);
            $target->reactions()->save($reaction);

            if ($target instanceof Listing) {
                $this->notifications->notify($target->user, $user, 'reaction', "{$user->name} reacted to {$target->title}", route('listings.show', $target, false));
            } else {
                $this->notifications->notify($target->user, $user, 'reaction', "{$user->name} liked your comment", route('listings.show', $target->listing, false), $target->content);
            }
        }

        $count = $target->reactions()->count();
        $target->forceFill(['likes_count' => $count])->save();

        return ['reacted' => $type !== null, 'type' => $type, 'count' => $count];
    }

    public function remove(User $user, Listing|Comment $target): void
    {
        $target->reactions()->where('user_id', $user->id)->delete();
        $target->forceFill(['likes_count' => $target->reactions()->count()])->save();
    }
}
