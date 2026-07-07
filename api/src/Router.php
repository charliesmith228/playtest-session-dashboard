<?php

declare(strict_types=1);

namespace App;

use App\Cache;
use App\Controllers\AuthController;
use App\Database;
use App\Response;
use App\Exceptions\HttpException;
use App\Middleware\AuthMiddleware;
use App\Services\TokenService;

class Router
{
    // Stores all registered routes as an array of [method, pattern, handler]
    private array $routes = [];

    public function __construct(
        private string $method,
        private string $uri,
        private Database $database,
        private Cache $cache,
        private TokenService $tokenService
    ){}

    // Register a GET route
    public function get(
        string $pattern,
        array $handler,
        bool $protected = false
    ): void
    {
        $this->addRoute("GET", $pattern, $handler, $protected);
    }

    // Register a POST route
    public function post(
        string $pattern,
        array $handler,
        bool $protected = false
    ): void
    {
        $this->addRoute("POST", $pattern, $handler, $protected);
    }

    // Register a PUT route
    public function put(
        string $pattern,
        array $handler,
        bool $protected = false
    ): void
    {
        $this->addRoute("PUT", $pattern, $handler, $protected);
    }

    // Register a DELETE route
    public function delete(
        string $pattern,
        array $handler,
        bool $protected = false
    ): void
    {
        $this->addRoute("DELETE", $pattern, $handler, $protected);
    }

    private function addRoute(
        string $method,
        string $pattern,
        array $handler,
        bool $protected
    ): void
    {
        $this->routes[] = [
            "method"  => $method,
            "pattern" => $pattern,
            "handler" => $handler,
            "protected" => $protected
        ];
    }

    // Process current request to find a match in registered routes
    public function dispatch(): void
    {
        foreach ($this->routes as $route)
        {
            // Skip if the HTTP method doesn't match (e.g. GET vs POST)
            if ($route["method"] !== $this->method) {
                continue;
            }

            // Convert the route pattern into a regex
            // e.g. /api/users/{id} becomes /api/users/([^/]+)
            $pattern = preg_replace("/\{[^}]+\}/", "([^/]+)", $route["pattern"]);
            $pattern = "#^" . $pattern . "$#";

            // Check if the current URI matches this pattern
            if (preg_match($pattern, $this->uri, $matches)) {

                // $matches[0] is the full match, so remove it
                // The remaining values are the captured URL parameters
                $params = array_slice($matches, 1);

                // Instantiate the controller, passing in the database connection
                [$class, $method] = $route["handler"];
                $class = "App\Controllers\\".$class;

                try
                {
                    // Check if route is protected and check auth if it is
                    // If route is protected and user isn't logged in,
                    // auth middleware will throw a httpexception
                    if ($route["protected"])
                    {
                        $middleware = new AuthMiddleware($this->tokenService);
                        $middleware->checkAuth();
                    }

                    // Check the class exists before trying to instantiate it
                    if (!class_exists($class))
                    {
                        throw new \RuntimeException("Controller class '{$class}' not found");
                    }

                    // AuthController needs TokenService - other controllers don't
                    $controller = $class === AuthController::class
                        ? new $class($this->database, $this->cache, $this->tokenService)
                        : new $class($this->database, $this->cache);


                    // Check the method in the class exists before trying to run it
                    if (!method_exists($class, $method))
                    {
                        throw new \RuntimeException("Method '{$method}' for class '{$class}' not found");
                    }

                    // Call the controller method and capture whatever it returns
                    $result = $controller->$method(...$params);
                    $this->sendResponse($result);
                }
                catch (HttpException $e)
                {
                    // A known, intentional error - send the message and httpCode back
                    $this->sendResponse(new Response(["error" => $e->getMessage()], $e->httpCode));
                }
                catch (\Throwable $e)
                {
                    // Something unexpected went wrong - log it but don't expose
                    // internal details to the client
                    error_log("Unhandled error: " . $e->getMessage());
                    $this->sendResponse(new Response(["error" => "Internal server error", "message" => $e->getMessage()], 500));
                }

                // Handle the return value and send the response
                $this->sendResponse($result);
                return;
            }
        }

        // No route matched
        $this->sendResponse(new Response(["error" => "Not found"], 404));
    }

    // Accepts either a Response object and outputs the JSON to the client
    private function sendResponse(Response $result): void
    {
        http_response_code($result->status);
        echo json_encode($result->data);
        die;
    }
}