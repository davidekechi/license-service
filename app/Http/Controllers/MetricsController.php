<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Modules\Brand\Models\Brand;
use App\Modules\Brand\Models\Product;
use App\Modules\License\Models\License;
use App\Modules\License\Models\LicenseActivation;
use App\Modules\License\Models\LicenseKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MetricsController extends Controller
{
    /**
     * Get system metrics.
     */
    public function index(): JsonResponse
    {
        // Cache metrics for 1 minute to reduce database load
        $metrics = Cache::remember('system_metrics', 60, function () {
            return [
                'brands'      => $this->getBrandMetrics(),
                'products'    => $this->getProductMetrics(),
                'licenses'    => $this->getLicenseMetrics(),
                'activations' => $this->getActivationMetrics(),
                'timestamp'   => now()->toIso8601String(),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data'   => $metrics,
        ]);
    }

    /**
     * Get brand-related metrics.
     *
     * @return array<string, mixed>
     */
    private function getBrandMetrics(): array
    {
        return [
            'total'    => Brand::count(),
            'active'   => Brand::where('is_active', true)->count(),
            'inactive' => Brand::where('is_active', false)->count(),
        ];
    }

    /**
     * Get product-related metrics.
     *
     * @return array<string, mixed>
     */
    private function getProductMetrics(): array
    {
        return [
            'total'    => Product::count(),
            'active'   => Product::where('is_active', true)->count(),
            'inactive' => Product::where('is_active', false)->count(),
        ];
    }

    /**
     * Get license-related metrics.
     *
     * @return array<string, mixed>
     */
    private function getLicenseMetrics(): array
    {
        return [
            'license_keys' => [
                'total'         => LicenseKey::count(),
                'with_licenses' => LicenseKey::has('licenses')->count(),
            ],
            'licenses' => [
                'total'     => License::count(),
                'valid'     => License::where('status', 'valid')->count(),
                'suspended' => License::where('status', 'suspended')->count(),
                'cancelled' => License::where('status', 'cancelled')->count(),
                'expired'   => License::where('expires_at', '<', now())->count(),
            ],
        ];
    }

    /**
     * Get activation-related metrics.
     *
     * @return array<string, mixed>
     */
    private function getActivationMetrics(): array
    {
        $activeActivations = LicenseActivation::whereNull('deactivated_at')->count();
        $totalActivations  = LicenseActivation::count();

        return [
            'total'       => $totalActivations,
            'active'      => $activeActivations,
            'deactivated' => $totalActivations - $activeActivations,
            'by_type'     => DB::table('license_activations')
                ->select('instance_type', DB::raw('count(*) as count'))
                ->whereNull('deactivated_at')
                ->whereNull('deleted_at')
                ->groupBy('instance_type')
                ->pluck('count', 'instance_type')
                ->toArray(),
        ];
    }
}
