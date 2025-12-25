<?php

declare(strict_types=1);

namespace App\Modules\Shared\Support\Exceptions;

class LicenseInvalidException extends LicenseException
{
    /**
     * Create a new exception instance.
     */
    public function __construct(
        string $reason = 'License is not valid',
        ?\Throwable $previous = null
    ) {
        parent::__construct($reason, 403, $previous);
    }
}
