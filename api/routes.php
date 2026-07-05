<?php

declare(strict_types=1);

// All route definitions live here.
// $router is available because this file is required from index.php
// after the router has been instantiated.

// Auth routes - public, no token required
$router->post('/api/auth/login',  ['AuthController', 'login']);
$router->post('/api/auth/logout', ['AuthController', 'logout']);

// Check auth route - protected
$router->get('/api/auth/authUser', ['AuthController', 'authUser'], protected: true);


$router->get('/api/test', ['TestController', 'index'], protected: true);
$router->get('/api/test/{id}', ['TestController', 'show']);