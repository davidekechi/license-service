<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\License\Enums\InstanceType;
use App\Modules\License\Models\License;
use App\Modules\License\Models\LicenseActivation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LicenseActivation>
 */
class LicenseActivationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<LicenseActivation>
     */
    protected $model = LicenseActivation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'license_id'          => License::factory(),
            'instance_identifier' => $this->faker->url(),
            'instance_type'       => InstanceType::SITE,
            'instance_meta'       => [
                'ip'         => fake()->ipv4(),
                'user_agent' => fake()->userAgent(),
            ],
            'activated_at'    => now(),
            'last_checked_at' => now(),
            'deactivated_at'  => null,
        ];
    }

    /**
     * Indicate that the activation is deactivated.
     */
    public function deactivated(): static
    {
        return $this->state(fn (array $attributes) => [
            'deactivated_at' => now(),
        ]);
    }

    /**
     * Indicate that the activation is stale (not checked in 90+ days).
     */
    public function stale(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_checked_at' => now()->subDays(91),
        ]);
    }

    /**
     * Set the instance type.
     */
    public function instanceType(InstanceType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'instance_type' => $type,
        ]);
    }

    public function device(): static
    {
        return $this->state(fn (array $attributes) => [
            'instance_type'       => InstanceType::DEVICE,
            'instance_identifier' => fake()->uuid(),
        ]);
    }

    public function server(): static
    {
        return $this->state(fn (array $attributes) => [
            'instance_type'       => InstanceType::SERVER,
            'instance_identifier' => fake()->ipv4(),
        ]);
    }
}
