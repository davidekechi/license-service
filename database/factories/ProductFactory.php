<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Brand\Models\Brand;
use App\Modules\Brand\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Product>
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'brand_id'  => Brand::factory()->create()->id,
            'name'      => $this->faker->words(2, true),
            'slug'      => $this->faker->unique()->slug(),
            'max_seats' => fake()->randomElement([1, 3, 5, 10]),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the product is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the product has unlimited seats.
     */
    public function unlimited(): static
    {
        return $this->state(fn (array $attributes) => [
            'max_seats' => -1,
        ]);
    }
}
