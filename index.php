<?php

// Główny punkt wejścia aplikacji - tu wszystko się zaczyna
require_once 'Routing.php';

// Wyciągam ścieżkę z URL, bo inaczej nie wiem, co użytkownik chce zobaczyć
$path = trim($_SERVER['REQUEST_URI'], '/');
$path = parse_url($path, PHP_URL_PATH);

// To czasem przydaje się do debugowania, ale na produkcji lepiej wyłączyć
// var_dump($path);

// Teraz przekazuję to do routingu, żeby zdecydował, co dalej
Routing::run($path);
