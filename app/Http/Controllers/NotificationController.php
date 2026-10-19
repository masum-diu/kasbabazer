<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $page = $request->user()->notifications()->latest()->paginate(20);

        return Inertia::render('notifications', [
            'notifications' => Inertia::scroll($page->through(fn ($n) => [
                'id' => $n->id,
                'kind' => $n->data['kind'] ?? 'info',
                'title' => $n->data['title'] ?? '',
                'body' => $n->data['body'] ?? null,
                'url' => $n->data['url'] ?? '/',
                'read' => $n->read_at !== null,
                'created_at' => $n->created_at?->toISOString(),
            ])),
        ]);
    }

    /**
     * Mark one notification read and go to its target.
     */
    public function read(Request $request, string $notification): RedirectResponse
    {
        $item = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $item->markAsRead();

        $url = (string) ($item->data['url'] ?? '/');

        // Only follow in-app relative links.
        return redirect(str_starts_with($url, '/') && ! str_starts_with($url, '//') ? $url : '/');
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
