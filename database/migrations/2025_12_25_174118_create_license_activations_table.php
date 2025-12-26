<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('license_activations', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('license_id')->constrained('licenses')->onDelete('cascade'); // Same module: use ID
            $table->string('instance_identifier'); // URL, machine ID, etc.
            $table->enum('instance_type', ['site', 'device', 'server'])->default('site');
            $table->json('instance_meta')->nullable(); // Additional metadata
            $table->timestamp('activated_at');
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('license_id');
            $table->index('instance_identifier');
            $table->index('deactivated_at');
            $table->index(['license_id', 'deactivated_at']); // For counting active seats
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('license_activations');
    }
};
