<?php

declare(strict_types=1);

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use App\Exceptions\HttpException;

class TokenService
{
    // The algorithm used to sign the token
    private const ALGORITHM = "HS256";

    // How long tokens are valid for in seconds (1 hour)
    private const TTL = 3600;

    public function __construct(private string $secret) {}

    // Generate a signed JWT for a given user ID
    public function generate(int $userId): string
    {
        $now = time();

        $claims = [
            // Issued at - when the token was created
            "iat" => $now,

            // Expiry - when the token stops being valid
            // The JWT library checks this automatically on decode
            "exp" => $now + self::TTL,

            // Custom claim - the user this token belongs to
            "sub" => $userId,
        ];

        return JWT::encode($claims, $this->secret, self::ALGORITHM);
    }

    // Validate a token and return the user ID from it
    // Throws an HttpException if the token is missing, expired, or tampered with
    public function validate(string $token): int
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, self::ALGORITHM));

            return (int) $decoded->sub;

        } catch (ExpiredException) {
            throw new HttpException("Token has expired", 401);
        } catch (SignatureInvalidException) {
            throw new HttpException("Token signature is invalid", 401);
        } catch (\Throwable) {
            throw new HttpException("Invalid token", 401);
        }
    }
}