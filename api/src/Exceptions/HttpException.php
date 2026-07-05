<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class HttpException extends RuntimeException
{
    public function __construct(
        string $message,
        // The HTTP status code to send back to the client
        public readonly int $httpCode = 400,
    )
    {
        parent::__construct($message);
    }
}