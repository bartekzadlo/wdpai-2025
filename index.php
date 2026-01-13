<?php

require_once 'Routing.php';

// Pobieramy ścieżkę z URL
$path = trim($_SERVER['REQUEST_URI'], '/');
$path = parse_url($path, PHP_URL_PATH);

// var_dump($path);

// Uruchamiamy routing
Routing::run($path);
