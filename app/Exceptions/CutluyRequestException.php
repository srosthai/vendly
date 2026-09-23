<?php

namespace App\Exceptions;

use RuntimeException;

class CutluyRequestException extends RuntimeException
{
    public function __construct(
        public readonly int $status,
        public readonly string $error,
        string $message = '',
    ) {
        parent::__construct($message !== '' ? $message : $error);
    }
}
