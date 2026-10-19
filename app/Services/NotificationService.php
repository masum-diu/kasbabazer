<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\ActivityNotification;
use Illuminate\Support\Str;

class NotificationService
{
    /**
     * Notify a user, skipping notifications for their own actions.
     */
    public function notify(?User $recipient, ?User $actor, string $kind, string $title, string $url, ?string $body = null): void
    {
        if ($recipient === null || $actor?->id === $recipient->id) {
            return;
        }

        $recipient->notify(new ActivityNotification($kind, $title, $url, $body === null ? null : Str::limit($body, 120)));
    }
}
