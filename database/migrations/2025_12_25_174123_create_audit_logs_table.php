<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('auditable_type'); // Polymorphic type
            $table->ulid('auditable_id'); // Polymorphic ID (using ULID for cross-module)
            $table->string('event'); // e.g., 'license_provisioned', 'license_activated'
            $table->string('actor_type'); // e.g., 'brand', 'product', 'system'
            $table->string('actor_identifier')->nullable(); // brand slug, license key, etc.
            $table->json('metadata')->nullable(); // Additional context
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at');

            // Indexes
            $table->index(['auditable_type', 'auditable_id']);
            $table->index('event');
            $table->index('actor_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};