<?php

declare(strict_types=1);

namespace App\Modules\Shared\Support\Exceptions;

class InvalidProductException extends LicenseException
{
    /**
     * Create a new exception instance.
     */
    public function __construct(
        string $message = 'Product is invalid or does not belong to the brand',
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 422, $previous);
    }
}
