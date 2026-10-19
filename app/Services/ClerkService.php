<?php

namespace App\Services;

use App\Models\User;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Verifies Clerk session tokens and maps Clerk users onto local users.
 */
class ClerkService
{
    /**
     * Clerk Frontend API host, decoded from the publishable key.
     */
    public function frontendApi(): string
    {
        $key = (string) config('clerk.publishable_key');
        $encoded = Str::after($key, 'pk_test_');
        $encoded = $encoded === $key ? Str::after($key, 'pk_live_') : $encoded;

        $host = rtrim((string) base64_decode($encoded, true), '$');

        if ($host === '') {
            throw new RuntimeException('Clerk is not configured.');
        }

        return $host;
    }

    /**
     * Validate a Clerk session JWT and return the Clerk user id (`sub`).
     */
    public function verify(string $token): string
    {
        $host = $this->frontendApi();

        $keys = Cache::remember('clerk.jwks', (int) config('clerk.jwks_ttl'), function () use ($host) {
            return Http::timeout(10)->throw()->get("https://{$host}/.well-known/jwks.json")->json();
        });

        $claims = JWT::decode($token, JWK::parseKeySet($keys, 'RS256'));

        if (($claims->iss ?? null) !== "https://{$host}" || empty($claims->sub)) {
            throw new RuntimeException('Invalid Clerk token.');
        }

        return (string) $claims->sub;
    }

    /**
     * Find or create the local user for a Clerk user id.
     */
    public function syncUser(string $clerkId): User
    {
        $existing = User::where('clerk_id', $clerkId)->first();

        if ($existing) {
            return $this->refreshAvatar($this->promoteIfAdmin($existing));
        }

        $profile = $this->fetchProfile($clerkId);

        $primaryId = $profile['primary_email_address_id'] ?? null;
        $email = null;
        foreach ((array) ($profile['email_addresses'] ?? []) as $address) {
            $email ??= $address['email_address'] ?? null; // fall back to the first address
            if (($address['id'] ?? null) === $primaryId) {
                $email = $address['email_address'] ?? $email;
                break;
            }
        }

        if (! $email) {
            throw new RuntimeException('Clerk account has no email address.');
        }

        // Link to an existing account with the same email, otherwise create one.
        $user = User::where('email', $email)->first();

        if (! $user) {
            $name = trim(($profile['first_name'] ?? '').' '.($profile['last_name'] ?? '')) ?: Str::before($email, '@');
            $user = new User(['name' => $name, 'email' => $email, 'username' => $this->uniqueUsername($profile['username'] ?? Str::before($email, '@')), 'password' => Str::random(40)]);
            $user->forceFill(['email_verified_at' => now()]);
        }

        $user->clerk_id = $clerkId;
        $user->is_admin = $user->is_admin || $this->isAdminEmail($email);
        $user->avatar ??= $profile['image_url'] ?? null;
        $user->save();

        // Reload so database defaults (is_active, ...) are present on the model.
        return $user->refresh();
    }

    private function isAdminEmail(string $email): bool
    {
        return in_array(strtolower($email), (array) config('clerk.admin_emails'), true);
    }

    private function promoteIfAdmin(User $user): User
    {
        if (! $user->is_admin && $this->isAdminEmail($user->email)) {
            $user->forceFill(['is_admin' => true])->save();
        }

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchProfile(string $clerkId): array
    {
        return (array) Http::withToken((string) config('clerk.secret_key'))
            ->timeout(10)->throw()
            ->get(rtrim((string) config('clerk.api_url'), '/')."/v1/users/{$clerkId}")
            ->json();
    }

    /**
     * Clerk picture URLs change when the Google photo changes, so keep a Clerk-sourced avatar current.
     * Avatars the user uploaded themselves (not a Clerk URL) are left alone.
     */
    private function refreshAvatar(User $user): User
    {
        if ($user->avatar && ! str_contains($user->avatar, 'img.clerk.com')) {
            return $user;
        }

        try {
            $image = $this->fetchProfile((string) $user->clerk_id)['image_url'] ?? null;
        } catch (\Throwable) {
            return $user; // never block sign-in because the picture could not be refreshed
        }

        if ($image && $image !== $user->avatar) {
            $user->forceFill(['avatar' => $image])->save();
        }

        return $user;
    }

    private function uniqueUsername(string $base): string
    {
        $base = Str::slug($base, '_') ?: 'user';
        $username = $base;

        while (User::withTrashed()->where('username', $username)->exists()) {
            $username = $base.'_'.random_int(100, 99999);
        }

        return $username;
    }
}
