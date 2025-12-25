<?php

declare(strict_types=1);

use Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');

// Custom expectations
expect()->extend('toBeModel', function (string $class) {
    /** @var \Pest\Expectation $expectation */
    $expectation = $this; // @phpstan-ignore-line

    return $expectation->toBeInstanceOf($class);
});

expect()->extend('toHaveUlid', function () {
    /** @var \Pest\Expectation $expectation */
    $expectation = $this; // @phpstan-ignore-line
    $value       = $expectation->value;

    return $value->public_id !== null
        && \is_string($value->public_id)
        && \strlen($value->public_id) === 26;
});
