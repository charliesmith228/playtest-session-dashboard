<?php

declare(strict_types=1);

// All route definitions live here.
// $router is available because this file is required from index.php
// after the router has been instantiated.

// Auth routes - public, no token required
$router->post("/api/auth/login",  ["AuthController", "login"]);
$router->post("/api/auth/logout", ["AuthController", "logout"]);
$router->post("/api/auth/register", ["AuthController", "register"]);

// Auth routes - private, tokens required
$router->get("/api/auth/authUser", ["AuthController", "authUser"], protected: true);

// Users routes - private, tokens required
$router->get("/api/users", ["UserController", "index"], protected: true);
$router->get("/api/users/{id}", ["UserController", "show"], protected: true);
$router->post("/api/users", ["UserController", "add"], protected: true);
$router->put("/api/users", ["UserController", "edit"], protected: true);
$router->delete("/api/users", ["UserController", "delete"], protected: true);

// Playtests routes - private, tokens required
$router->get("/api/playtests", ["PlaytestController", "index"], protected: true);
$router->get("/api/playtests/{id}", ["PlaytestController", "show"], protected: true);
$router->post("/api/playtests", ["PlaytestController", "add"], protected: true);
$router->put("/api/playtests", ["PlaytestController", "edit"], protected: true);
$router->delete("/api/playtests", ["PlaytestController", "delete"], protected: true);