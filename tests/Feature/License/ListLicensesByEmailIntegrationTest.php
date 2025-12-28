<?php

declare(strict_types=1);

use App\Modules\Brand\Models\Brand;

test('complete customer journey: multiple purchases across brands', function () {
    // Setup: Create 2 brands with products
    $rankMath = Brand::factory()->create([
        'name'      => 'RankMath',
        'slug'      => 'rankmath',
        'is_active' => true,
    ]);

    $wpRocket = Brand::factory()->create([
        'name'      => 'WP Rocket',
        'slug'      => 'wp-rocket',
        'is_active' => true,
    ]);

    $rankMathPro = $rankMath->products()->create([
        'name'      => 'RankMath Pro',
        'slug'      => 'rankmath-pro',
        'max_seats' => 5,
    ]);

    $contentAI = $rankMath->products()->create([
        'name'      => 'Content AI',
        'slug'      => 'content-ai',
        'max_seats' => 5,
    ]);

    $wpRocketProduct = $wpRocket->products()->create([
        'name'      => 'WP Rocket',
        'slug'      => 'wp-rocket',
        'max_seats' => 3,
    ]);

    // Step 1: Customer buys RankMath Pro
    $step1 = $this->withHeaders([
        'Authorization' => 'Bearer ' . $rankMath->api_key,
    ])->postJson('/api/v1/brands/licenses/provision', [
        'customer_email' => 'john@example.com',
        'products'       => [
            [
                'product_public_id' => $rankMathPro->public_id,
                'max_activations'   => 5,
            ],
        ],
    ]);

    $rankMathLicenseKey = $step1->json('data.license_key');

    // Step 2: Customer adds Content AI
    $this->withHeaders([
        'Authorization' => 'Bearer ' . $rankMath->api_key,
    ])->postJson('/api/v1/brands/licenses/provision', [
        'customer_email' => 'john@example.com',
        'license_key'    => $rankMathLicenseKey,
        'products'       => [
            [
                'product_public_id' => $contentAI->public_id,
                'max_activations'   => 5,
            ],
        ],
    ]);

    // Step 3: Customer buys WP Rocket (different brand)
    $step3 = $this->withHeaders([
        'Authorization' => 'Bearer ' . $wpRocket->api_key,
    ])->postJson('/api/v1/brands/licenses/provision', [
        'customer_email' => 'john@example.com',
        'products'       => [
            [
                'product_public_id' => $wpRocketProduct->public_id,
                'max_activations'   => 3,
            ],
        ],
    ]);

    $wpRocketLicenseKey = $step3->json('data.license_key');

    // Step 4: RankMath lists customer licenses (should see both brands)
    $listResponse = $this->withHeaders([
        'Authorization' => 'Bearer ' . $rankMath->api_key,
    ])->getJson('/api/v1/brands/customers/john@example.com/licenses');

    expect($listResponse->json('data.data'))->toHaveCount(2)
        ->and($listResponse->json('data.meta.total'))->toBe(2);

    // Verify license keys
    /** @var array<int, array<string, mixed>> $data */
    $data        = $listResponse->json('data.data');
    $licenseKeys = collect($data)
        ->pluck('license_key')
        ->toArray();

    expect($licenseKeys)->toContain($rankMathLicenseKey)
        ->and($licenseKeys)->toContain($wpRocketLicenseKey);

    // Verify RankMath key has 2 products
    /** @var array<string, mixed>|null $rankMathData */
    $rankMathData = collect($data)
        ->firstWhere('license_key', $rankMathLicenseKey);

    expect($rankMathData['licenses'])->toHaveCount(2);

    // Verify WP Rocket key has 1 product
    /** @var array<string, mixed>|null $wpRocketData */
    $wpRocketData = collect($data)
        ->firstWhere('license_key', $wpRocketLicenseKey);

    expect($wpRocketData['licenses'])->toHaveCount(1);
});

test('list includes activation status for each license', function () {
    // Setup
    $brand   = Brand::factory()->create(['is_active' => true]);
    $product = $brand->products()->create([
        'name'      => 'Test Product',
        'slug'      => 'test-product',
        'max_seats' => 5,
    ]);

    // Provision
    $provisionResponse = $this->withHeaders([
        'Authorization' => 'Bearer ' . $brand->api_key,
    ])->postJson('/api/v1/brands/licenses/provision', [
        'customer_email' => 'customer@example.com',
        'products'       => [
            [
                'product_public_id' => $product->public_id,
                'max_activations'   => 5,
            ],
        ],
    ]);

    $licenseKey = $provisionResponse->json('data.license_key');

    // Activate on 2 sites
    $this->postJson("/api/v1/licenses/{$licenseKey}/activate", [
        'instance_identifier' => 'https://site1.com',
        'instance_type'       => 'site',
        'product_public_id'   => $product->public_id,
    ]);

    $this->postJson("/api/v1/licenses/{$licenseKey}/activate", [
        'instance_identifier' => 'https://site2.com',
        'instance_type'       => 'site',
        'product_public_id'   => $product->public_id,
    ]);

    // List licenses
    $listResponse = $this->withHeaders([
        'Authorization' => 'Bearer ' . $brand->api_key,
    ])->getJson('/api/v1/brands/customers/customer@example.com/licenses');

    $licenseData = $listResponse->json('data.data.0.licenses.0');

    expect($licenseData)->toHaveKey('activations');
});

test('brand can see customer licenses even from other brands', function () {
    // Setup 2 brands
    $brand1 = Brand::factory()->create(['is_active' => true]);
    $brand2 = Brand::factory()->create(['is_active' => true]);

    $product1 = $brand1->products()->create([
        'name'      => 'Brand 1 Product',
        'slug'      => 'brand-1-product',
        'max_seats' => 5,
    ]);

    $product2 = $brand2->products()->create([
        'name'      => 'Brand 2 Product',
        'slug'      => 'brand-2-product',
        'max_seats' => 3,
    ]);

    // Customer buys from Brand 1
    $this->withHeaders([
        'Authorization' => 'Bearer ' . $brand1->api_key,
    ])->postJson('/api/v1/brands/licenses/provision', [
        'customer_email' => 'shared@example.com',
        'products'       => [
            ['product_public_id' => $product1->public_id, 'max_activations' => 5],
        ],
    ]);

    // Customer buys from Brand 2
    $this->withHeaders([
        'Authorization' => 'Bearer ' . $brand2->api_key,
    ])->postJson('/api/v1/brands/licenses/provision', [
        'customer_email' => 'shared@example.com',
        'products'       => [
            ['product_public_id' => $product2->public_id, 'max_activations' => 3],
        ],
    ]);

    // Brand 1 queries customer licenses (sees both)
    $brand1Response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $brand1->api_key,
    ])->getJson('/api/v1/brands/customers/shared@example.com/licenses');

    expect($brand1Response->json('data.meta.total'))->toBe(2);

    // Brand 2 also queries (sees both)
    $brand2Response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $brand2->api_key,
    ])->getJson('/api/v1/brands/customers/shared@example.com/licenses');

    expect($brand2Response->json('data.meta.total'))->toBe(2);
});

test('pagination works correctly with large dataset', function () {
    $brand   = Brand::factory()->create(['is_active' => true]);
    $product = $brand->products()->create([
        'name'      => 'Test Product',
        'slug'      => 'test-product',
        'max_seats' => 5,
    ]);

    // Create 50 license keys for same customer
    for ($i = 1; $i <= 50; $i++) {
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $brand->api_key,
        ])->postJson('/api/v1/brands/licenses/provision', [
            'customer_email' => 'prolific@example.com',
            'products'       => [
                [
                    'product_public_id' => $product->public_id,
                    'max_activations'   => 5,
                ],
            ],
        ]);
    }

    // Get page 1 (10 per page)
    $page1 = $this->withHeaders([
        'Authorization' => 'Bearer ' . $brand->api_key,
    ])->getJson('/api/v1/brands/customers/prolific@example.com/licenses?per_page=10');

    expect($page1->json('data.data'))->toHaveCount(10)
        ->and($page1->json('data.meta.total'))->toBe(50)
        ->and($page1->json('data.meta.last_page'))->toBe(5);

    // Get page 3
    $page3 = $this->withHeaders([
        'Authorization' => 'Bearer ' . $brand->api_key,
    ])->getJson('/api/v1/brands/customers/prolific@example.com/licenses?per_page=10&page=3');

    expect($page3->json('data.data'))->toHaveCount(10)
        ->and($page3->json('data.meta.current_page'))->toBe(3);

    // Get last page
    $lastPage = $this->withHeaders([
        'Authorization' => 'Bearer ' . $brand->api_key,
    ])->getJson('/api/v1/brands/customers/prolific@example.com/licenses?per_page=10&page=5');

    expect($lastPage->json('data.data'))->toHaveCount(10)
        ->and($lastPage->json('data.meta.current_page'))->toBe(5);
});
