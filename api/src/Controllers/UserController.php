<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Response;
use App\Exceptions\HttpException;

class UserController extends BaseController
{
    // GET /api/users
    public function index(): Response
    {
        // Check the cache first before hitting the database
        $cached = $this->cache->get("users:index");

        // If cached copy exists, respond with that
        if ($cached !== null) {
            return $this->respond($cached);
        }

        // Get all users from db
        $users = $this->database->query(
            "SELECT id, first_name, last_name, email FROM users"
        );

        // Store in cache for 1 hour, then return
        $this->cache->set("users:index", $users);

        return $this->respond($users);
    }

    // GET /api/users/{id}
    public function show(string $id): Response
    {
        // Check the cache first before hitting the database
        $cached = $this->cache->get("users:index:{$id}");

        // If cached copy exists, respond with that
        if ($cached !== null) {
            return $this->respond($cached);
        }

        // Get user from db where ID = provided parameter
        $user = $this->database->queryOne(
            "SELECT id, first_name, last_name, email FROM users WHERE ID = :id LIMIT 1",
            ["id" => $id]
        );

        if ($user === false) {
            throw new HttpException("User record not found", 404);
        }

        // Store in cache for 1 hour, then return
        $this->cache->set("users:index:{$id}", $user);

        // Forget all users cache
        $this->cache->forget("users:index");

        return $this->respond($user);
    }

    // POST /api/users
    public function add(): Response
    {
        $body = $this->getJsonBody([
            "first_name",
            "last_name",
            "email",
            "password",
            "playtester_focus"
        ]);

        $body["password"] = password_hash($body["password"], PASSWORD_BCRYPT);

        // Add new user with provided details
        $rowsUpdates = $this->database->execute(
            "INSERT INTO users (
                first_name,
                last_name,
                email,
                password,
                playtester_focus
            ) VALUES (
                :first_name,
                :last_name,
                :email,
                :password,
                :playtester_focus
            )",
            $body
        );

        // Check if insert was successful
        if ($rowsUpdates === 0) {
            throw new HttpException("Unable to create user", 500);
        }

        // If inserted, get new user ID
        $newUserID = $this->database->lastInsertId();

        $user = [
            "id"    => $newUserID,
            "first_name"  => $body["first_name"],
            "last_name"  => $body["last_name"],
            "email" => $body["email"],
        ];

        // Add new user to the cache
        $this->cache->set("users:index:{$newUserID}", $user);

        // Forget all users cache
        $this->cache->forget("users:index");

        return $this->respond($user, 201);
    }

    // PUT /api/users
    public function edit(): Response
    {
        $body = $this->getJsonBody([
            "id",
            "first_name",
            "last_name",
            "email",
            "playtester_focus"
        ]);

        // Retrieve user record by ID to check if exists
        $user = $this->database->queryOne(
            "SELECT ID FROM users WHERE ID = :id LIMIT 1",
            ["id" => $body["id"]]
        );

        if ($user === false) {
            throw new HttpException("User record not found", 404);
        }

        // Update existing user with provided details
        $rowsUpdates = $this->database->execute(
            "UPDATE users SET
                first_name = :first_name,
                last_name = :last_name,
                email = :email,
                playtester_focus = :playtester_focus,
                updated_at = NOW()
            WHERE ID = :id",
            $body
        );

        // Check if update was successful
        if ($rowsUpdates === 0) {
            throw new HttpException("Unable to update user", 500);
        }

        $user = [
            "id" => $body["id"],
            "first_name"  => $body["first_name"],
            "last_name"  => $body["last_name"],
            "email" => $body["email"],
        ];

        // If updated, update cache
        $this->cache->set("users:index:{$body["id"]}", $user);

        // Forget all users cache
        $this->cache->forget("users:index");

        return $this->respond($user);
    }

    // DELETE /api/users
    public function delete(): Response
    {
        $body = $this->getJsonBody(["id"]);

        // Retrieve user record by ID to check if exists
        $user = $this->database->queryOne(
            "SELECT ID FROM users WHERE ID = :id LIMIT 1",
            ["id" => $body["id"]]
        );

        if ($user === false) {
            throw new HttpException("User record not found", 404);
        }

        // Remove user from db and cache
        $this->database->execute(
            "DELETE FROM users WHERE ID = :id",
            ["id" => $body["id"]]
        );

        $this->cache->forget("users:index:{$body["id"]}");

        // Forget all users cache
        $this->cache->forget("users:index");

        return $this->respond([], 204);
    }
}