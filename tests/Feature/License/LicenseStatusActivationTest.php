<?php

declare(strict_types=1);

use App\Modules\License\Models\LicenseActivation;

test('updateHeartbeat updates last_checked_at timestamp', function () {
    $activation = LicenseActivation::factory()->create([
        'last_checked_at' => now()->subHour(),
    ]);

    $oldTimestamp = $activation->last_checked_at;

    \sleep(1);

    $activation->updateHeartbeat();

    expect($activation->last_checked_at)->toBeGreaterThan($oldTimestamp);
});

test('updateHeartbeat saves to database', function () {
    $activation = LicenseActivation::factory()->create([
        'last_checked_at' => now()->subHour(),
    ]);

    $activation->updateHeartbeat();

    // Refresh from database
    $fresh = LicenseActivation::find($activation->id);

    expect($fresh->last_checked_at->timestamp)->toBe($activation->last_checked_at->timestamp);
});
