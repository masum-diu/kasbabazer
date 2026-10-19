<?php

namespace App\Http\Controllers;

use App\Http\Requests\MessageRequest;
use App\Models\Conversation;
use App\Models\Listing;
use App\Services\ConversationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConversationController extends Controller
{
    public function __construct(private readonly ConversationService $conversations) {}

    public function index(Request $request, ?Conversation $conversation = null): Response
    {
        $user = $request->user();

        $list = $user->conversations()
            ->with(['listing:id,slug,title', 'participants:id,name,avatar', 'latestMessage'])
            ->withCount(['messages as unread_count' => fn ($q) => $q->where('user_id', '!=', $user->id)->whereNull('read_at')])
            ->orderByDesc('conversations.updated_at')->limit(50)->get()
            ->map(function (Conversation $c) use ($user) {
                $other = $c->participants->firstWhere('id', '!=', $user->id);

                return [
                    'id' => $c->id,
                    'other' => $other ? ['id' => $other->id, 'name' => $other->name, 'avatar' => $other->avatar] : null,
                    'listing' => $c->listing ? ['slug' => $c->listing->slug, 'title' => $c->listing->title] : null,
                    'last_message' => $c->latestMessage?->message,
                    'last_at' => $c->latestMessage?->created_at?->toISOString(),
                    'unread' => (int) $c->unread_count,
                ];
            });

        $active = null;
        if ($conversation) {
            $this->authorize('view', $conversation);
            $this->conversations->markRead($conversation, $user);

            $conversation->load(['listing.images', 'participants:id,name,avatar']);
            $other = $conversation->participants->firstWhere('id', '!=', $user->id);
            $active = [
                'id' => $conversation->id,
                'other' => $other ? ['id' => $other->id, 'name' => $other->name, 'avatar' => $other->avatar] : null,
                'listing' => $conversation->listing ? [
                    'slug' => $conversation->listing->slug,
                    'title' => $conversation->listing->title,
                    'price' => (float) $conversation->listing->price,
                    'thumbnail' => $conversation->listing->images->first()?->url,
                ] : null,
                'messages' => $conversation->messages()->orderBy('created_at')->orderBy('id')->limit(200)->get()
                    ->map(fn ($m) => [
                        'id' => $m->id,
                        'message' => $m->message,
                        'mine' => $m->user_id === $user->id,
                        'read' => $m->read_at !== null,
                        'created_at' => $m->created_at?->toISOString(),
                    ]),
            ];
        }

        return Inertia::render('inbox', ['conversations' => $list, 'active' => $active]);
    }

    public function start(Request $request): RedirectResponse
    {
        $data = $request->validate(['listing_id' => ['required', 'integer', 'exists:listings,id']]);
        $listing = Listing::whereKey($data['listing_id'])->firstOrFail();
        $this->authorize('view', $listing);

        $conversation = $this->conversations->startForListing($request->user(), $listing);

        return to_route('inbox', $conversation);
    }

    public function send(MessageRequest $request, Conversation $conversation): RedirectResponse
    {
        $this->authorize('send', $conversation);
        $this->conversations->send($conversation, $request->user(), $request->validated('message'));

        return back();
    }
}
