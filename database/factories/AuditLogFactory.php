<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\AuditLog\Enums\AuditEventType;
use App\Modules\AuditLog\Models\AuditLog;
use App\Modules\License\Models\License;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<AuditLog>
     */
    protected $model = AuditLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'auditable_type'   => License::class,
            'auditable_id'     => fake()->regexify('[0-9A-HJKMNP-TV-Z]{26}'), // Generate a valid ULID
            'event'            => fake()->randomElement(AuditEventType::values()),
            'actor_type'       => fake()->randomElement(['brand', 'product', 'system']),
            'actor_identifier' => fake()->randomElement(['rankmath', 'wp-rocket', null]),
            'metadata'         => fake()->boolean(70) ? ['key' => 'value'] : null,
            'ip_address'       => fake()->ipv4(),
            'created_at'       => now(),
        ];
    }

    /**
     * Indicate that the audit log is for license provisioning.
     */
    public function licenseProvisioned(): static
    {
        return $this->state(fn (array $attributes) => [
            'event' => AuditEventType::LICENSE_PROVISIONED->value,
        ]);
    }

    /**
     * Indicate that the audit log is for license activation.
     */
    public function licenseActivated(): static
    {
        return $this->state(fn (array $attributes) => [
            'event' => AuditEventType::LICENSE_ACTIVATED->value,
        ]);
    }

    /**
     * Indicate that the audit log has a specific actor.
     *
     * @param string $actorType
     * @param string|null $actorIdentifier
     */
    public function actor(string $actorType, ?string $actorIdentifier = null): static
    {
        return $this->state(fn (array $attributes) => [
            'actor_type'       => $actorType,
            'actor_identifier' => $actorIdentifier,
        ]);
    }
}
