<?php

declare(strict_types=1);

// Load Composer's autoloader - makes all classes set in composer.json
require_once __DIR__ . "/../vendor/autoload.php";

use App\Cache;
use App\Database;
use App\Router;
use App\Services\TokenService;

// --- API Bootstrap ---

// Create the database connection here, to be used globally throughout the API
// Connects early to catch any db errors before running any functionality
$database = new Database(
    host: $_ENV["DB_HOST"],
    port: (int) $_ENV["DB_PORT"],
    name: $_ENV["DB_NAME"],
    user: $_ENV["DB_USER"],
    password: $_ENV["DB_PASS"],
);

// Create the token service here, to be used globally throughout the API
// This is used for authentication, so the API cannot function without this
$tokenService = new TokenService($_ENV["JWT_SECRET"]);

// Create the cache connection here, to be used globally throughout the API
// If the cache is unavailable, the API can still function without it
$cache = new Cache(
    host: $_ENV["REDIS_HOST"],
    port: (int) $_ENV["REDIS_PORT"],
);

// --- API Routing ---

// Get the request method (GET, POST, etc.) and the URL path
$method = $_SERVER["REQUEST_METHOD"];
$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

// Create the router and register your routes
$router = new Router(
    $method,
    $uri,
    $database,
    $cache,
    $tokenService,
);

// Load route definitions from config file
require_once __DIR__ . "/../routes.php";

// Dispatch - match the current request to a route and run the controller
$router->dispatch();
