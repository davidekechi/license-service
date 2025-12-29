<?php

declare(strict_types=1);

namespace App\Modules\Shared\Middleware;

use App\Modules\Brand\Contracts\BrandRepositoryInterface;
use App\Modules\Shared\Support\Helpers\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateBrand
{
    /**
     * Create a new middleware instance.
     */
    public function __construct(
        private readonly BrandRepositoryInterface $brandRepository
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $this->extractApiKey($request);

        if ($apiKey === null) {
            return ApiResponse::unauthorized('API key is required');
        }

        $brand = $this->brandRepository->findByApiKey($apiKey);

        if ($brand === null) {
            return ApiResponse::unauthorized('Invalid API key');
        }

        if (!$brand->isActive()) {
            return ApiResponse::forbidden('Brand account is inactive');
        }

        // Attach brand to request for use in controllers
        $request->merge(['authenticated_brand' => $brand]);

        return $next($request);
    }

    /**
     * Extract API key from request.
     */
    private function extractApiKey(Request $request): ?string
    {
        $header = $request->header('Authorization');

        if ($header === null) {
            return null;
        }

        // Support both "Bearer token" and "token" formats
        if (\str_starts_with($header, 'Bearer ')) {
            return \substr($header, 7);
        }

        return $header;
    }
}
