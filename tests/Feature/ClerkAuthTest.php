<?php

namespace Tests\Feature;

use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ClerkAuthTest extends TestCase
{
    use RefreshDatabase;

    private string $privateKey = '';

    private const HOST = 'test-app.clerk.accounts.dev';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'clerk.publishable_key' => 'pk_test_'.base64_encode(self::HOST.'$'),
            'clerk.secret_key' => 'sk_test_fake',
        ]);
        Cache::flush();

        // Windows PHP builds ship an openssl.cnf that must be pointed at explicitly.
        $options = ['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA];
        $cnf = dirname(PHP_BINARY).'/extras/ssl/openssl.cnf';
        if (is_file($cnf)) {
            $options['config'] = $cnf;
        }

        $key = openssl_pkey_new($options);
        if ($key === false) {
            $this->markTestSkipped('OpenSSL could not generate a test key on this machine.');
        }
        openssl_pkey_export($key, $pem, null, $options);
        $this->privateKey = $pem;
        $details = openssl_pkey_get_details($key);

        $b64 = fn (string $bin) => rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');

        Http::fake([
            'https://'.self::HOST.'/.well-known/jwks.json' => Http::response(['keys' => [[
                'kty' => 'RSA', 'alg' => 'RS256', 'use' => 'sig', 'kid' => 'k1',
                'n' => $b64($details['rsa']['n']), 'e' => $b64($details['rsa']['e']),
            ]]]),
            'https://api.clerk.com/v1/users/user_abc' => Http::response([
                'first_name' => 'Rahim', 'last_name' => 'Mia', 'image_url' => 'https://img.example/a.png',
                'primary_email_address_id' => 'idn_1',
                'email_addresses' => [['id' => 'idn_1', 'email_address' => 'rahim@gmail.com']],
            ]),
        ]);
    }

    private function token(array $overrides = []): string
    {
        return JWT::encode(array_merge([
            'iss' => 'https://'.self::HOST, 'sub' => 'user_abc', 'iat' => time(), 'exp' => time() + 300,
        ], $overrides), $this->privateKey, 'RS256', 'k1');
    }

    public function test_valid_clerk_token_creates_user_and_logs_in(): void
    {
        $this->post('/auth/clerk', ['token' => $this->token()])->assertRedirect();

        $this->assertAuthenticated();
        $user = User::where('email', 'rahim@gmail.com')->firstOrFail();
        $this->assertSame('user_abc', $user->clerk_id);
        $this->assertSame('Rahim Mia', $user->name);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_existing_user_with_same_email_is_linked_not_duplicated(): void
    {
        $existing = User::factory()->create(['email' => 'rahim@gmail.com']);

        $this->post('/auth/clerk', ['token' => $this->token()]);

        $this->assertDatabaseCount('users', 1);
        $this->assertSame('user_abc', $existing->fresh()->clerk_id);
        $this->assertAuthenticatedAs($existing);
    }

    public function test_expired_wrong_issuer_or_forged_tokens_are_rejected(): void
    {
        $this->post('/auth/clerk', ['token' => $this->token(['exp' => time() - 600])])->assertSessionHasErrors('token');
        $this->post('/auth/clerk', ['token' => $this->token(['iss' => 'https://evil.example'])])->assertSessionHasErrors('token');

        $forged = JWT::encode(['iss' => 'https://'.self::HOST, 'sub' => 'user_abc', 'exp' => time() + 300], str_repeat('x', 32), 'HS256');
        $this->post('/auth/clerk', ['token' => $forged])->assertSessionHasErrors('token');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }
}
