<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

// Shared JSON envelope for all modules (Auth, Courses, …).
// Keeps message/data/error_code shapes identical across phases.
final class ApiResponse
{
    public static function success(string $message, mixed $data = null, int $status = 200): JsonResponse
    {
        $payload = ['message' => $message];

        if ($data !== null) {
            $payload['data'] = $data;
        }

        return response()->json($payload, $status);
    }

    public static function data(mixed $data, int $status = 200): JsonResponse
    {
        return response()->json(['data' => $data], $status);
    }

    public static function error(string $message, string $errorCode, int $status = 400): JsonResponse
    {
        return response()->json(['message' => $message, 'error_code' => $errorCode], $status);
    }

    public static function jwt(string $token, mixed $user = null, ?int $expiresIn = null): JsonResponse
    {
        $payload = [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $expiresIn ?? (int) config('jwt.ttl', 60) * 60,
        ];

        if ($user !== null) {
            $payload['user'] = $user;
        }

        return response()->json($payload);
    }
}
