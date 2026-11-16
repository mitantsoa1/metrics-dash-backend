<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Throwable;

class ApiResponse
{
    public static function success(
        mixed $data = null,
        string $message = 'Operation successful.',
        int $statusCode = 200,
        array $headers = []
    ): JsonResponse {
        $body = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $body['data'] = $data;
        }

        return response()->json($body, $statusCode, $headers);
    }

    public static function error(
        string $message = 'An error occurred.',
        array $errors = [],
        int $statusCode = 400,
        array $headers = []
    ): JsonResponse {
        $body = [
            'success' => false,
            'message' => $message,
        ];

        if (! empty($errors)) {
            $body['errors'] = $errors;
        }

        return response()->json($body, $statusCode, $headers);
    }

    public static function paginated(
        LengthAwarePaginator $paginator,
        mixed $items,
        string $message = 'Operation successful.'
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public static function unauthorized(string $message = 'Unauthorized.'): JsonResponse
    {
        return self::error($message, [], 401);
    }

    public static function forbidden(string $message = 'Forbidden.'): JsonResponse
    {
        return self::error($message, [], 403);
    }

    public static function notFound(string $message = 'Resource not found.'): JsonResponse
    {
        return self::error($message, [], 404);
    }

    public static function validationError(array $errors, string $message = 'Validation failed.'): JsonResponse
    {
        return self::error($message, $errors, 422);
    }

    public static function tooManyRequests(string $message = 'Too many attempts. Please try again later.'): JsonResponse
    {
        return self::error($message, [], 429);
    }

    public static function fromException(Throwable $e, int $statusCode = 500): JsonResponse
    {
        $message = app()->isLocal()
            ? $e->getMessage()
            : 'An internal server error occurred.';

        return self::error($message, [], $statusCode);
    }
}
