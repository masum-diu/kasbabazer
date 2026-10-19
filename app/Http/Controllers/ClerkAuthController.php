<?php

namespace App\Http\Controllers;

use App\Services\ClerkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class ClerkAuthController extends Controller
{
    public function __construct(private readonly ClerkService $clerk) {}

    /**
     * Exchange a verified Clerk session token for a Laravel session.
     */
    public function store(Request $request): RedirectResponse
    {
        $token = $request->validate(['token' => ['required', 'string', 'max:4096']])['token'];

        try {
            $user = $this->clerk->syncUser($this->clerk->verify($token));
        } catch (Throwable $e) {
            Log::warning('Clerk sign-in failed', ['error' => $e->getMessage()]);

            return back()->withErrors(['token' => 'Could not verify your Google sign-in. Please try again.']);
        }

        if (! $user->is_active) {
            return back()->withErrors(['token' => 'This account has been deactivated.']);
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended('/');
    }
}
