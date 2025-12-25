<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Brand\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Brand>
 */
class BrandFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Brand>
     */
    protected $model = Brand::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $slug = $this->faker->unique()->slug();

        return [
            'name'      => $this->faker->company(),
            'slug'      => $slug,
            'api_key'   => Brand::generateApiKey($slug),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the brand is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
