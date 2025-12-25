<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Brand\Models\Brand;
use App\Modules\License\Models\LicenseKey;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LicenseKey>
 */
class LicenseKeyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<LicenseKey>
     */
    protected $model = LicenseKey::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key'            => $this->generateLicenseKey(),
            'brand_id'       => Brand::factory(),
            'customer_email' => $this->faker->unique()->safeEmail(),
        ];
    }

    /**
     * Generate a license key in the format: XXXX-XXXX-XXXX-XXXX
     */
    private function generateLicenseKey(): string
    {
        return \sprintf(
            '%s-%s-%s-%s',
            \strtoupper($this->faker->lexify('????')),
            \strtoupper($this->faker->bothify('####')),
            \strtoupper($this->faker->bothify('####')),
            \strtoupper($this->faker->bothify('####'))
        );
    }
}
