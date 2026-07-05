<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Response;
use App\Exceptions\HttpException;

class TestController extends BaseController
{
    public function index(): Response
    {
        // Check the cache first before hitting the database
        $cached = $this->cache->get('test:index');

        if ($cached !== null) {
            return $this->respond($cached);
        }

        $response = ["username" => "csmith"];

        // Store in cache for 1 hour, then return
        $this->cache->set('test:index', $response);

        return $this->respond($response);
    }

    public function show(string $id): Response
    {
        if (false) {
            // Need a non-200 status, so we use respond() to wrap it
            throw new HttpException('Not found :(', 404);
        }

        return $this->respond(["status" => "Found!"]);
    }
}