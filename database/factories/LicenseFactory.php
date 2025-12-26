<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Brand\Models\Product;
use App\Modules\License\Enums\LicenseStatus;
use App\Modules\License\Models\License;
use App\Modules\License\Models\LicenseKey;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<License>
 */
class LicenseFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<License>
     */
    protected $model = License::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'license_key_id'  => LicenseKey::factory()->create()->id,
            'product_id'      => Product::factory()->create()->public_id,
            'status'          => LicenseStatus::VALID,
            'expires_at'      => now()->addYear(),
            'max_activations' => 5,
        ];
    }

    /**
     * Indicate that the license is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LicenseStatus::SUSPENDED,
        ]);
    }

    /**
     * Indicate that the license is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LicenseStatus::CANCELLED,
        ]);
    }

    /**
     * Indicate that the license is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'     => LicenseStatus::EXPIRED,
            'expires_at' => now()->subDay(),
        ]);
    }

    /**
     * Indicate that the license has unlimited activations.
     */
    public function unlimited(): static
    {
        return $this->state(fn (array $attributes) => [
            'max_activations' => -1,
        ]);
    }

    /**
     * Indicate that the license never expires.
     */
    public function lifetime(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => null,
        ]);
    }
}
