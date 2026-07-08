<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Response;
use App\Exceptions\HttpException;
use App\Middleware\AuthMiddleware;

class VideoGameController extends BaseController
{
    // GET /api/video_games
    public function index(): Response
    {
        // Check the cache first before hitting the database
        $cached = $this->cache->get("video_games:index");

        // If cached copy exists, respond with that
        if ($cached !== null) {
            return $this->respond($cached);
        }

        // Get all video games from db
        $videoGames = $this->database->query(
            "SELECT id, game_name, platforms, build FROM video_games"
        );

        // Store in cache for 1 hour, then return
        $this->cache->set("video_games:index", $videoGames);

        return $this->respond($videoGames);
    }

    // GET /api/video_games/{id}
    public function show(string $id): Response
    {
        // Check the cache first before hitting the database
        $cached = $this->cache->get("video_games:index:{$id}");

        // If cached copy exists, respond with that
        if ($cached !== null) {
            return $this->respond($cached);
        }

        // Get video game from db where ID = provided parameter
        $videoGame = $this->database->queryOne(
            "SELECT id, game_name, platforms, build FROM video_games WHERE ID = :id LIMIT 1",
            ["video_game_id" => $id]
        );

        if ($videoGame === false) {
            throw new HttpException("Video game record not found", 404);
        }

        // Store in cache for 1 hour, then return
        $this->cache->set("video_games:index:{$id}", $videoGame);

        // Forget all video games cache
        $this->cache->forget("video_games:index");

        return $this->respond($videoGame);
    }

    // POST /api/video_games
    public function add(): Response
    {
        $body = $this->getJsonBody([
            "game_name",
            "platforms",
            "build"
        ]);

        $body["created_by"] = AuthMiddleware::$userId;

        // Add new video game with provided details
        $rowsUpdates = $this->database->execute(
            "INSERT INTO video_games (
                game_name,
                platforms,
                build,
                created_by
            ) VALUES (
                :game_name,
                :platforms,
                :build,
                :created_by
            )",
            $body
        );

        // Check if insert was successful
        if ($rowsUpdates === 0) {
            throw new HttpException("Unable to create video game", 500);
        }

        // If inserted, get new video game ID
        $newVideoGameID = $this->database->lastInsertId();

        $videoGame = [
            "id"    => $newVideoGameID,
            "game_name" => $body["game_name"],
            "platforms" => $body["platforms"],
            "build" => $body["build"]
        ];

        // Add new video game to the cache
        $this->cache->set("video_games:index:{$newVideoGameID}", $videoGame);

        // Forget all video games cache
        $this->cache->forget("video_games:index");

        return $this->respond($videoGame, 201);
    }

    // PUT /api/video_games
    public function edit(): Response
    {
        $body = $this->getJsonBody([
            "id",
            "game_name",
            "platforms",
            "build"
        ]);

        // Retrieve video game record by ID to check if exists
        $videoGame = $this->database->queryOne(
            "SELECT ID FROM video_games WHERE ID = :id LIMIT 1",
            ["id" => $body["id"]]
        );

        if ($videoGame === false) {
            throw new HttpException("Video game record not found", 404);
        }

        // Update existing video game with provided details
        $rowsUpdates = $this->database->execute(
            "UPDATE video_games SET
                game_name = :game_name,
                platforms = :platforms,
                build = :build,
                updated_at = NOW()
            WHERE ID = :id",
            $body
        );

        // Check if update was successful
        if ($rowsUpdates === 0) {
            throw new HttpException("Unable to update video game", 500);
        }

        $videoGame = [
            "id" => $body["id"],
            "game_name" => $body["game_name"],
            "platforms" => $body["platforms"],
            "build" => $body["build"]
        ];

        // If updated, update cache
        $this->cache->set("video_games:index:{$body["id"]}", $videoGame);

        // Forget all video games cache
        $this->cache->forget("video_games:index");

        return $this->respond($videoGame);
    }

    // DELETE /api/video_games
    public function delete(): Response
    {
        $body = $this->getJsonBody(["id"]);

        // Retrieve video game record by ID to check if exists
        $videoGame = $this->database->queryOne(
            "SELECT ID FROM video_games WHERE ID = :id LIMIT 1",
            ["id" => $body["id"]]
        );

        if ($videoGame === false) {
            throw new HttpException("Video game record not found", 404);
        }

        // Remove video game from db and cache
        $this->database->execute(
            "DELETE FROM video_games WHERE ID = :id",
            ["id" => $body["id"]]
        );

        $this->cache->forget("video_games:index:{$body["id"]}");

        // Forget all video games cache
        $this->cache->forget("video_games:index");

        return $this->respond([], 204);
    }
}