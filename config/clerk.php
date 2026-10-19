<?php

return [
    // Publishable key (pk_test_... / pk_live_...). Its base64 payload encodes the Clerk Frontend API host.
    'publishable_key' => env('VITE_CLERK_PUBLISHABLE_KEY'),

    'secret_key' => env('CLERK_SECRET_KEY'),

    'api_url' => env('CLERK_API_URL', 'https://api.clerk.com'),

    // Emails that become admins when they sign in with a verified Clerk (Google) account.
    'admin_emails' => array_filter(array_map('strtolower', array_map('trim', explode(',', (string) env('ADMIN_EMAILS', ''))))),

    // How long (seconds) to cache Clerk's signing keys.
    'jwks_ttl' => 3600,
];
