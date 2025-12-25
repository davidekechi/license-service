<?php

declare(strict_types=1);

namespace App\Modules\Shared\Support\Exceptions;

class LicenseExpiredException extends LicenseException
{
    /**
     * Create a new exception instance.
     */
    public function __construct(
        string $expiredAt,
        ?\Throwable $previous = null
    ) {
        $message = \sprintf('License expired on %s', $expiredAt);

        parent::__construct($message, 403, $previous);
    }
}
