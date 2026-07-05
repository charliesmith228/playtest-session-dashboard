<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Cache;
use App\Database;
use App\Response;
use App\Exceptions\HttpException;

abstract class BaseController
{
    public function __construct(
        protected Database $database,
        protected Cache $cache
    ){
        // Set the Content-Type header once for all API responses
        header('Content-Type: application/json');
    }

    // Convenience method so controllers can write $this->respond([...])
    // rather than new Response([...]) - keeps controller code concise
    protected function respond(array $data, int $status = 200): Response
    {
        return new Response($data, $status);
    }

    // Always returns a decoded array, or throws an HttpException if anything
    // is wrong - the router catches the exception and sends the error response
    protected function getJsonBody(array $requiredFields = []): array
    {
        $raw = file_get_contents('php://input');

        if (empty($raw)) {
            throw new HttpException('Request body is required', 400);
        }

        $decoded = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new HttpException('Invalid JSON body', 400);
        }

        foreach ($requiredFields as $field) {
            if (empty($decoded[$field])) {
                throw new HttpException("'{$field}' is required", 422);
            }
        }

        return $decoded;
    }
}