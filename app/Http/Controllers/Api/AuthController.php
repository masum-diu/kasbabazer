<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->auth->register($request->safe()->except('password_confirmation'));

        return ApiResponse::created($this->authPayload($request, $result), 'Registered successfully');
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->auth->login($request->validated('login'), $request->validated('password'));

        return ApiResponse::success($this->authPayload($request, $result), 'Logged in successfully');
    }

    /**
     * Serialise the freshly authenticated user as "self" so private fields are included.
     *
     * @param  array{user: User, token: string}  $result
     * @return array{user: array<string, mixed>, token: string}
     */
    private function authPayload(Request $request, array $result): array
    {
        $request->setUserResolver(fn () => $result['user']);

        return [
            'user' => (new UserResource($result['user']))->resolve($request),
            'token' => $result['token'],
        ];
    }

    public function logout(Request $request): JsonResponse
    {
        $this->auth->logout($request->user());

        return ApiResponse::success(null, 'Logged out successfully');
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success(new UserResource($request->user()->load('location')));
    }
}
