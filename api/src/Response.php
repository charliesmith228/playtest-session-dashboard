<?php

declare(strict_types=1);

namespace App;

class Response
{
    public function __construct(
        // The data to be JSON encoded and sent to the client
        public readonly array $data,

        // HTTP status code - defaults to 200 OK
        public readonly int $status = 200,
    ) {}
}
