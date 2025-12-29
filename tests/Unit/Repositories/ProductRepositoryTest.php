<?php

declare(strict_types=1);

use App\Modules\Brand\Models\Brand;
use App\Modules\Brand\Models\Product;
use App\Modules\Brand\Repositories\ProductRepository;

beforeEach(function () {
    $this->repository = new ProductRepository(new Product());
});

test('can create a product', function () {
    $brand = Brand::factory()->create();

    $data = [
        'brand_id'  => $brand->id,
        'name'      => 'Test Product',
        'slug'      => 'test-product',
        'max_seats' => 5,
        'is_active' => true,
    ];

    $product = $this->repository->create($data);

    expect($product)->toBeModel(Product::class)
        ->and($product->name)->toBe('Test Product')
        ->and($product->brand_id)->toBe($brand->id);
});

test('can find product by id', function () {
    $product = Product::factory()->create();

    $found = $this->repository->findById($product->id);

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($product->id);
});

test('can find product by public id', function () {
    $product = Product::factory()->create();

    $found = $this->repository->findByPublicId($product->public_id);

    expect($found)->not->toBeNull()
        ->and($found->public_id)->toBe($product->public_id);
});

test('can find product by slug and brand id', function () {
    $brand   = Brand::factory()->create();
    $product = Product::factory()->create([
        'brand_id' => $brand->id,
        'slug'     => 'unique-product',
    ]);

    $found = $this->repository->findBySlugAndBrandId('unique-product', $brand->id);

    expect($found)->not->toBeNull()
        ->and($found->slug)->toBe('unique-product')
        ->and($found->brand_id)->toBe($brand->id);
});

test('can get products by brand id', function () {
    $brand = Brand::factory()->create();
    Product::factory()->count(3)->create(['brand_id' => $brand->id]);
    Product::factory()->count(2)->create(); // Different brand

    $products = $this->repository->getByBrandId($brand->id);

    expect($products)->toHaveCount(3);
});

test('can get active products by brand id', function () {
    $brand = Brand::factory()->create();
    Product::factory()->count(3)->create(['brand_id' => $brand->id, 'is_active' => true]);
    Product::factory()->count(2)->inactive()->create(['brand_id' => $brand->id]);

    $products = $this->repository->getActiveByBrandId($brand->id);

    expect($products)->toHaveCount(3);
});

test('can update a product', function () {
    $product = Product::factory()->create(['name' => 'Original Name']);

    $updated = $this->repository->update($product, ['name' => 'Updated Name']);

    expect($updated->name)->toBe('Updated Name')
        ->and($updated->id)->toBe($product->id);
});

test('can soft delete a product', function () {
    $product = Product::factory()->create();

    $result = $this->repository->delete($product);

    expect($result)->toBeTrue()
        ->and(Product::find($product->id))->toBeNull()
        ->and(Product::withTrashed()->find($product->id))->not->toBeNull();
});
