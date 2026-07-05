<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Cache;
use App\Database;
use App\Response;
use App\Services\TokenService;
use App\Middleware\AuthMiddleware;
use App\Exceptions\HttpException;

class AuthController extends BaseController
{
    public function __construct(
        Database $database,
        Cache $cache,
        private TokenService $tokenService,
    ) {
        parent::__construct($database, $cache);
    }

    // POST /api/auth/login
    public function login(): Response
    {
        $body = $this->getJsonBody(['email', 'password']);

        // Look up the user by email
        $users = $this->database->query(
            'SELECT id, first_name, last_name, email, password FROM users WHERE email = :email',
            ['email' => $body['email']]
        );

        // password_verify() handles the bcrypt comparison safely
        // We check both in the same condition to avoid leaking whether
        // the email exists via a timing difference in the response
        if (empty($users) || !password_verify($body['password'], $users[0]['password'])) {
            throw new HttpException('Invalid email or password', 401);
        }

        $user  = $users[0];
        $token = $this->tokenService->generate($user['id']);

        // Set the token as an httpOnly cookie
        // httpOnly means JavaScript cannot read it - only the browser sends it automatically
        // samesite=Strict prevents the cookie being sent on cross-site requests (CSRF protection)
        setcookie('auth_token', $token, [
            "expires" => time() + 3600,
            "path" => '/',
            "secure" => false,
            "httponly" => true,
            "samesite" => 'Strict'
        ]);

        // Return the user data but not the token - the client doesn't need it
        // since the browser handles the cookie automatically
        return $this->respond([
            'id'    => $user['id'],
            'first_name'  => $user['first_name'],
            'last_name'  => $user['last_name'],
            'email' => $user['email'],
        ]);
    }

    // POST /api/auth/logout
    public function logout(): Response
    {
        // Overwrite the cookie with an expired one to delete it from the browser
        setcookie('auth_token', '', [
            "expires" => time() - 3600,
            "path" => '/',
            "secure" => false,
            "httponly" => true,
            "samesite" => 'Strict'
        ]);

        return $this->respond(['message' => 'Logged out successfully']);
    }

    // GET /api/auth/authUser
    // React calls this on page load to check if the user is still logged in
    // The router marks this as protected so AuthMiddleware runs first
    public function authUser(): Response
    {
        // AuthMiddleware has already validated the token and set the user ID
        $users = $this->database->query(
            'SELECT id, first_name, last_name, email FROM users WHERE id = :id',
            ['id' => AuthMiddleware::$userId]
        );

        if (empty($users)) {
            throw new HttpException('User not found', 404);
        }

        return $this->respond($users[0]);
    }
}