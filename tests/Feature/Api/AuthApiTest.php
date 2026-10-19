<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Karim Uddin',
            'username' => 'karim_99',
            'email' => 'karim@example.com',
            'phone' => '01712345678',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $overrides);
    }

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/auth/register', $this->payload());

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['user' => ['id', 'name', 'username', 'email'], 'token']])
            ->assertJsonMissingPath('data.user.password');

        $this->assertDatabaseHas('users', ['email' => 'karim@example.com', 'phone' => '01712345678']);
        $this->assertNotSame('password123', User::first()->password);
    }

    public function test_registration_validates_input(): void
    {
        User::factory()->create(['email' => 'karim@example.com']);

        $this->postJson('/api/auth/register', $this->payload(['password_confirmation' => 'nope']))
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation failed')
            ->assertJsonValidationErrors(['email', 'password'], 'errors');
    }

    public function test_user_can_login_with_email_or_phone(): void
    {
        User::factory()->create(['email' => 'a@example.com', 'phone' => '01711111111', 'password' => 'secret-pass']);

        $this->postJson('/api/auth/login', ['login' => 'a@example.com', 'password' => 'secret-pass'])
            ->assertOk()->assertJsonStructure(['data' => ['token']]);

        $this->postJson('/api/auth/login', ['phone' => '01711111111', 'password' => 'secret-pass'])
            ->assertOk()->assertJsonStructure(['data' => ['token']]);
    }

    public function test_login_fails_with_wrong_password_or_inactive_account(): void
    {
        User::factory()->create(['email' => 'a@example.com', 'password' => 'secret-pass']);
        User::factory()->inactive()->create(['email' => 'b@example.com', 'password' => 'secret-pass']);

        $this->postJson('/api/auth/login', ['login' => 'a@example.com', 'password' => 'wrong'])
            ->assertStatus(422)->assertJsonPath('success', false);

        $this->postJson('/api/auth/login', ['login' => 'b@example.com', 'password' => 'secret-pass'])
            ->assertStatus(422);
    }

    public function test_me_requires_authentication_and_hides_password(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized()->assertJsonPath('success', false);

        $user = User::factory()->create();
        $token = $user->createToken('t')->plainTextToken;

        $this->withToken($token)->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonMissingPath('data.password');
    }

    public function test_user_can_logout_and_token_is_revoked(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('t')->plainTextToken;

        $this->withToken($token)->postJson('/api/auth/logout')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
