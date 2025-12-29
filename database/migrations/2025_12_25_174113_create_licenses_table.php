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
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('license_key_id')->constrained('license_keys')->onDelete('cascade'); // Same module: use ID
            $table->ulid('product_id'); // Cross-module: use ULID
            $table->enum('status', ['valid', 'suspended', 'cancelled', 'expired'])->default('valid');
            $table->timestamp('expires_at')->nullable();
            $table->integer('max_activations')->default(5);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('license_key_id');
            $table->index('product_id');
            $table->index('status');
            $table->index('expires_at');

            // Foreign key to products using ULID
            $table->foreign('product_id')
                  ->references('public_id')
                  ->on('products')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
