<?php

declare(strict_types=1);

use Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');

// Custom expectations
expect()->extend('toBeModel', function (string $class) {
    return $this->toBeInstanceOf($class);
});

expect()->extend('toHaveUlid', function () {
    return $this->value->public_id !== null
        && is_string($this->value->public_id)
        && strlen($this->value->public_id) === 26;
});