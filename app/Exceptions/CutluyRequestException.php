<?php

namespace App\Exceptions;

use RuntimeException;

class CutluyRequestException extends RuntimeException
{
    /**
     * @param  int  $status  The HTTP status, or 0 when CutLuy could not be reached.
     * @param  int  $retryAfter  Seconds to wait before the one retry, for a 429.
     */
    public function __construct(
        public readonly int $status,
        public readonly string $error,
        string $message = '',
        public readonly int $retryAfter = 0,
    ) {
        parent::__construct($message !== '' ? $message : $error);
    }

    public function isRateLimited(): bool
    {
        return $this->status === 429;
    }
}
