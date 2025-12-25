<?php

declare(strict_types=1);

namespace App\Modules\Shared\Support\Exceptions;

class LicenseNotFoundException extends LicenseException
{
    /**
     * Create a new exception instance.
     */
    public function __construct(
        string $identifier,
        ?\Throwable $previous = null
    ) {
        $message = \sprintf('License not found: %s', $identifier);

        parent::__construct($message, 404, $previous);
    }
}
