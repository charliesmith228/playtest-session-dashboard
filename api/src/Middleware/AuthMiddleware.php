<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Services\TokenService;
use App\Exceptions\HttpException;

class AuthMiddleware
{
    // Holds the authenticated user's ID for the duration of the request
    // Controllers read this via AuthMiddleware::$userId
    public static ?int $userId = null;

    public function __construct(private TokenService $tokenService) {}

    // Called by the router before dispatching any protected route
    public function checkAuth(): void
    {
        // The cookie name we'll set on login
        $token = $_COOKIE['auth_token'] ?? null;

        if ($token === null) {
            throw new HttpException('Authentication required', 401);
        }

        // validate() throws an HttpException if anything is wrong with the token
        // If it returns, the token is valid and we have the user ID
        self::$userId = $this->tokenService->validate($token);
    }
}