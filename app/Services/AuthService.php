<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * @param  array<string, mixed>  $data
     * @return array{user: User, token: string}
     */
    public function register(array $data): array
    {
        $user = User::create($data);

        return ['user' => $user, 'token' => $this->issueToken($user)];
    }

    /**
     * @return array{user: User, token: string}
     */
    public function login(string $identifier, string $password): array
    {
        $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $user = User::where($field, $identifier)->first();

        // Hash::check runs even for unknown users to keep timing uniform.
        $valid = Hash::check($password, $user->password ?? Hash::make(str()->random(16)));

        if (! $user || ! $valid) {
            throw ValidationException::withMessages([
                'login' => ['These credentials do not match our records.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'login' => ['This account has been deactivated.'],
            ]);
        }

        return ['user' => $user, 'token' => $this->issueToken($user)];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    private function issueToken(User $user): string
    {
        return $user->createToken('api')->plainTextToken;
    }
}
