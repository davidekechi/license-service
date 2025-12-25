<?php

declare(strict_types=1);

namespace App\Modules\Shared\Support\Exceptions;

use Exception;

class LicenseException extends Exception
{
    /**
     * Create a new exception instance.
     */
    public function __construct(
        string $message = 'License error occurred',
        int $code = 400,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
