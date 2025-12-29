<?php

declare(strict_types=1);

namespace App\Modules\Shared\Support\Helpers;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * Success response
     */
    public static function success(
        mixed $data = null,
        string $message = 'Success',
        int $statusCode = 200
    ): JsonResponse {
        return response()->json([
            'statusCode' => $statusCode,
            'success'    => true,
            'message'    => $message,
            'data'       => $data,
        ], $statusCode);
    }

    /**
     * Error response
     */
    public static function error(
        string $message = 'Error',
        mixed $errors = null,
        int $statusCode = 400
    ): JsonResponse {
        return response()->json([
            'statusCode' => $statusCode,
            'success'    => false,
            'message'    => $message,
            'errors'     => $errors,
        ], $statusCode);
    }

    /**
     * Validation error response
     */
    public static function validationError(
        mixed $errors,
        string $message = 'Validation failed',
        int $statusCode = 422
    ): JsonResponse {
        return self::error($message, $errors, $statusCode);
    }

    /**
     * Not found response
     */
    public static function notFound(
        string $message = 'Resource not found',
        int $statusCode = 404
    ): JsonResponse {
        return self::error($message, null, $statusCode);
    }

    /**
     * Unauthorized response
     */
    public static function unauthorized(
        string $message = 'Unauthorized',
        int $statusCode = 401
    ): JsonResponse {
        return self::error($message, null, $statusCode);
    }

    /**
     * Forbidden response
     */
    public static function forbidden(
        string $message = 'Forbidden',
        int $statusCode = 403
    ): JsonResponse {
        return self::error($message, null, $statusCode);
    }

    /**
     * Forbidden response
     */
    public static function serverError(
        string $message = 'Internal server error',
        int $statusCode = 500
    ): JsonResponse {
        return self::error($message, null, $statusCode);
    }
}
