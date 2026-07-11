<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Response;
use App\Exceptions\HttpException;
use App\Middleware\AuthMiddleware;

class PlaytestController extends BaseController
{
    // GET /api/playtests
    public function index(): Response
    {
        // Check the cache first before hitting the database
        $cached = $this->cache->get("playtests:index");

        // If cached copy exists, respond with that
        if ($cached !== null) {
            return $this->respond($cached);
        }

        // Get all playtests from db
        $playtests = $this->database->query(
            "SELECT id, game_name, playtest_description, start_time, end_time FROM playtests",
        );

        // Store in cache for 1 hour, then return
        $this->cache->set("playtests:index", $playtests);

        return $this->respond($playtests);
    }

    // GET /api/playtests/{id}
    public function show(string $id): Response
    {
        // Check the cache first before hitting the database
        $cached = $this->cache->get("playtests:index:{$id}");

        // If cached copy exists, respond with that
        if ($cached !== null) {
            return $this->respond($cached);
        }

        // Get playtest from db where ID = provided parameter
        $playtest = $this->database->queryOne(
            "SELECT id, game_name, playtest_description, start_time, end_time FROM playtests WHERE ID = :id LIMIT 1",
            ["playtest_id" => $id],
        );

        if ($playtest === false) {
            throw new HttpException("Playtest record not found", 404);
        }

        // Store in cache for 1 hour, then return
        $this->cache->set("playtests:index:{$id}", $playtest);

        // Forget all playtests cache
        $this->cache->forget("playtests:index");

        return $this->respond($playtest);
    }

    // POST /api/playtests
    public function add(): Response
    {
        $body = $this->getJsonBody([
            "game_name",
            "playtest_description",
            "start_time",
            "end_time",
        ]);

        $body["created_by"] = AuthMiddleware::$userId;

        // Add new playtest with provided details
        $rowsUpdates = $this->database->execute(
            "INSERT INTO playtests (
                game_name,
                playtest_description,
                start_time,
                end_time,
                created_by
            ) VALUES (
                :game_name,
                :playtest_description,
                :start_time,
                :end_time,
                :created_by
            )",
            $body,
        );

        // Check if insert was successful
        if ($rowsUpdates === 0) {
            throw new HttpException("Unable to create playtest", 500);
        }

        // If inserted, get new playtest ID
        $newPlaytestID = $this->database->lastInsertId();

        $playtest = [
            "id"    => $newPlaytestID,
            "game_name" => $body["game_name"],
            "playtest_description" => $body["playtest_description"],
            "start_time" => $body["start_time"],
            "end_time" => $body["end_time"],
        ];

        // Add new playtest to the cache
        $this->cache->set("playtests:index:{$newPlaytestID}", $playtest);

        // Forget all playtests cache
        $this->cache->forget("playtests:index");

        return $this->respond($playtest, 201);
    }

    // PUT /api/playtests
    public function edit(): Response
    {
        $body = $this->getJsonBody([
            "id",
            "game_name",
            "playtest_description",
            "start_time",
            "end_time",
        ]);

        // Retrieve playtest record by ID to check if exists
        $playtest = $this->database->queryOne(
            "SELECT ID FROM playtests WHERE ID = :id LIMIT 1",
            ["id" => $body["id"]],
        );

        if ($playtest === false) {
            throw new HttpException("Playtest record not found", 404);
        }

        // Update existing playtest with provided details
        $rowsUpdates = $this->database->execute(
            "UPDATE playtests SET
                game_name = :game_name,
                playtest_description = :playtest_description,
                start_time = :start_time,
                end_time = :end_time,
                updated_at = NOW()
            WHERE ID = :id",
            $body,
        );

        // Check if update was successful
        if ($rowsUpdates === 0) {
            throw new HttpException("Unable to update playtest", 500);
        }

        $playtest = [
            "id" => $body["id"],
            "game_name" => $body["game_name"],
            "playtest_description" => $body["playtest_description"],
            "start_time" => $body["start_time"],
            "end_time" => $body["end_time"],
        ];

        // If updated, update cache
        $this->cache->set("playtests:index:{$body["id"]}", $playtest);

        // Forget all playtests cache
        $this->cache->forget("playtests:index");

        return $this->respond($playtest);
    }

    // DELETE /api/playtests
    public function delete(): Response
    {
        $body = $this->getJsonBody(["id"]);

        // Retrieve playtest record by ID to check if exists
        $playtest = $this->database->queryOne(
            "SELECT ID FROM playtests WHERE ID = :id LIMIT 1",
            ["id" => $body["id"]],
        );

        if ($playtest === false) {
            throw new HttpException("Playtest record not found", 404);
        }

        // Remove playtest from db and cache
        $this->database->execute(
            "DELETE FROM playtests WHERE ID = :id",
            ["id" => $body["id"]],
        );

        $this->cache->forget("playtests:index:{$body["id"]}");

        // Forget all playtests cache
        $this->cache->forget("playtests:index");

        return $this->respond([], 204);
    }
}
