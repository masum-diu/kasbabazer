<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Generic in-app notification. Broadcast can be added later by returning
 * 'broadcast' from via() and implementing toBroadcast().
 */
class ActivityNotification extends Notification
{
    /**
     * @param  string  $kind  comment|reply|reaction|message|review|seller|listing|report
     */
    public function __construct(
        private readonly string $kind,
        private readonly string $title,
        private readonly string $url,
        private readonly ?string $body = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return ['kind' => $this->kind, 'title' => $this->title, 'body' => $this->body, 'url' => $this->url];
    }
}
