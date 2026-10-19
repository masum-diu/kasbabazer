<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ApiResponse
{
    public static function success(mixed $data = null, string $message = 'OK', int $status = 200): JsonResponse
    {
        $payload = ['success' => true, 'message' => $message];

        if ($data instanceof ResourceCollection) {
            return $data->additional($payload)->response()->setStatusCode($status);
        }

        if ($data instanceof JsonResource) {
            return $data->additional($payload)->response()->setStatusCode($status);
        }

        $payload['data'] = $data ?? (object) [];

        return response()->json($payload, $status);
    }

    public static function created(mixed $data = null, string $message = 'Created successfully'): JsonResponse
    {
        return self::success($data, $message, 201);
    }

    /**
     * @param  array<string, mixed>  $errors
     */
    public static function error(string $message, int $status = 400, array $errors = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => (object) $errors,
        ], $status);
    }
}
