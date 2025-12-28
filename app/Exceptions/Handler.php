<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Modules\Shared\Support\Exceptions\LicenseException;
use App\Modules\Shared\Support\Exceptions\LicenseExpiredException;
use App\Modules\Shared\Support\Exceptions\LicenseInvalidException;
use App\Modules\Shared\Support\Exceptions\LicenseNotFoundException;
use App\Modules\Shared\Support\Exceptions\SeatLimitExceededException;
use App\Modules\Shared\Support\Helpers\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        ValidationException::class,
        AuthenticationException::class,
        LicenseException::class, // Don't report custom license exceptions as errors
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
        'api_key',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e): JsonResponse|\Illuminate\Http\Response|\Symfony\Component\HttpFoundation\Response
    {
        // Only handle JSON API requests
        if ($request->expectsJson() || $request->is('api/*') || $request->is('v1/*')) {
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * Handle API exceptions with consistent JSON responses.
     */
    protected function handleApiException(Request $request, Throwable $e): JsonResponse
    {
        // HTTP Response exceptions (rate limiting, etc.)
        if ($e instanceof HttpResponseException) {
            $response   = $e->getResponse();
            $statusCode = $response->getStatusCode();

            // If it's already a JSON response from our ApiResponse helper, return it
            if ($response instanceof JsonResponse) {
                return $response;
            }

            // Otherwise, wrap it in our consistent format
            return ApiResponse::error(
                message: $statusCode === 429 ? 'Too many requests' : 'An error occurred',
                errors: null,
                statusCode: $statusCode
            );
        }

        // Validation exceptions
        if ($e instanceof ValidationException) {
            return ApiResponse::validationError(
                errors: $e->errors(),
                message: $e->getMessage()
            );
        }

        // Custom license exceptions
        if ($e instanceof SeatLimitExceededException) {
            return ApiResponse::error(
                message: $e->getMessage(),
                errors: null,
                statusCode: 403
            );
        }

        if ($e instanceof LicenseExpiredException) {
            return ApiResponse::error(
                message: $e->getMessage(),
                errors: null,
                statusCode: 403
            );
        }

        if ($e instanceof LicenseInvalidException) {
            return ApiResponse::error(
                message: $e->getMessage(),
                errors: null,
                statusCode: 403
            );
        }

        if ($e instanceof LicenseNotFoundException) {
            return ApiResponse::notFound(
                message: $e->getMessage()
            );
        }

        // Authentication exceptions
        if ($e instanceof AuthenticationException) {
            return ApiResponse::unauthorized(
                message: $e->getMessage() ?: 'Unauthenticated'
            );
        }

        // Model not found
        if ($e instanceof ModelNotFoundException) {
            return ApiResponse::notFound(
                message: 'Resource not found'
            );
        }

        // 404 Not Found
        if ($e instanceof NotFoundHttpException) {
            return ApiResponse::notFound(
                message: 'Endpoint not found'
            );
        }

        // Rate limiting errors
        if ($e instanceof TooManyRequestsHttpException) {
            return ApiResponse::error(
                message: $e->getMessage() ?: 'Too many requests',
                errors: null,
                statusCode: 429
            );
        }

        // HTTP exceptions
        if ($e instanceof HttpException) {
            $statusCode = $e->getStatusCode();
            $message    = $e->getMessage() ?: match ($statusCode) {
                401     => 'Unauthorized',
                403     => 'Forbidden',
                404     => 'Not found',
                429     => 'Too many requests',
                default => 'An error occurred'
            };

            return ApiResponse::error(
                message: $message,
                errors: null,
                statusCode: $statusCode
            );
        }

        // Log unexpected exceptions
        Log::error('Unhandled exception in API', [
            'exception' => \get_class($e),
            'message'   => $e->getMessage(),
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
            'trace'     => $e->getTraceAsString(),
            'url'       => $request->fullUrl(),
            'method'    => $request->method(),
        ]);

        // Return generic error for unexpected exceptions
        return ApiResponse::serverError(
            message: config('app.debug')
                ? $e->getMessage()
                : 'An unexpected error occurred. Please try again later.'
        );
    }
}
