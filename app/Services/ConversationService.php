<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Listing;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConversationService
{
    public function __construct(private readonly NotificationService $notifications) {}

    /**
     * Find or create the buyer<->seller conversation about a listing.
     */
    public function startForListing(User $buyer, Listing $listing): Conversation
    {
        if ($buyer->id === $listing->user_id) {
            throw ValidationException::withMessages(['listing' => ['You cannot message yourself.']]);
        }

        $existing = Conversation::where('listing_id', $listing->id)
            ->whereHas('participants', fn ($q) => $q->where('users.id', $buyer->id))
            ->whereHas('participants', fn ($q) => $q->where('users.id', $listing->user_id))
            ->first();

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($buyer, $listing) {
            $conversation = Conversation::create(['listing_id' => $listing->id]);
            $conversation->participants()->attach([
                $buyer->id => ['joined_at' => now()],
                $listing->user_id => ['joined_at' => now()],
            ]);

            return $conversation;
        });
    }

    public function send(Conversation $conversation, User $sender, string $text, string $type = 'text'): Message
    {
        $message = new Message(['message' => $text, 'type' => $type]);
        $message->forceFill(['conversation_id' => $conversation->id, 'user_id' => $sender->id])->save();
        $conversation->touch();

        foreach ($conversation->participants()->where('users.id', '!=', $sender->id)->get() as $recipient) {
            $this->notifications->notify($recipient, $sender, 'message', "New message from {$sender->name}", route('inbox', $conversation, false), $text);
        }

        return $message;
    }

    /**
     * Mark everything the other participants sent as read.
     */
    public function markRead(Conversation $conversation, User $reader): void
    {
        $conversation->messages()
            ->where('user_id', '!=', $reader->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
