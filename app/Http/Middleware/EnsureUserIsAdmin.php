<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isAdmin()) {
            if (! $request->is('api/*')) {
                // Browser visit: send guests to Google sign-in, everyone else home.
                if (! $request->user()) {
                    return redirect()->guest(route('clerk.sign-in'));
                }

                Inertia::flash('toast', ['type' => 'error', 'message' => 'Only admins can open that page.']);

                return redirect('/');
            }

            return ApiResponse::error('This action requires administrator access.', 403);
        }

        return $next($request);
    }
}
