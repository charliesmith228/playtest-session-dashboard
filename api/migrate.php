<?php

declare(strict_types=1);

// Load Composer's autoloader - makes all classes set in composer.json
require_once __DIR__ . "/vendor/autoload.php";

use App\Database;
use App\Migrator\Migrator;

// Connect to database
// Any error are caught before running migration functionality
$database = new Database(
    host: $_ENV["DB_HOST"],
    port: (int)$_ENV["DB_PORT"],
    name: $_ENV["DB_NAME"],
    user: $_ENV["DB_USER"],
    password: $_ENV["DB_PASS"]
);

$migrator = new Migrator($database);

// Read the user's command from the CLI arguments
// If the user does not supply an arg, assume migrate
$command = $argv[1] ?? "migrate";

// Check user's command against available migration methods
// Give error if command does not match
match ($command) {
    "migrate" => $migrator->migrate(),
    "reverse" => $migrator->reverse(),
    "rollback" => $migrator->rollback(),
    default => "Unknown command '{$command}'.".PHP_EOL."Commands: migrate, reverse, rollback".PHP_EOL
};