<?php

declare(strict_types=1);

namespace App\Modules\Shared\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddApiVersion
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Add API version header
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $response->headers->set('X-API-Version', 'v1');
            $response->headers->set('X-Application-Name', 'License Service');
        }

        return $response;
    }
}
