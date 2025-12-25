<?php

declare(strict_types=1);

namespace App\Modules\Shared\Support\Exceptions;

class SeatLimitExceededException extends LicenseException
{
    /**
     * Create a new exception instance.
     */
    public function __construct(
        int $seatsUsed,
        int $seatsTotal,
        ?\Throwable $previous = null
    ) {
        $message = \sprintf(
            'License has reached its maximum activation limit. %d of %d seats used.',
            $seatsUsed,
            $seatsTotal
        );

        parent::__construct($message, 403, $previous);
    }
}
