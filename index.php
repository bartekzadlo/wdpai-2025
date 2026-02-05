<?php

// Funkcja obsługująca błędy PHP
function globalErrorHandler($errno, $errstr, $errfile, $errline) {
    // Logowanie błędu
    error_log("PHP Error [$errno]: $errstr in $errfile on line $errline");

    // Wyświetlanie strony błędu dla użytkownika (tylko dla błędów krytycznych)
    if ($errno & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR)) {
        http_response_code(500);
        include 'public/views/error.html';
        exit();
    }

    // Dla innych błędów, kontynuuj wykonywanie (mogą być obsługiwane przez aplikację)
    return false;
}

// Funkcja obsługująca nieobsługiwane wyjątki
function globalExceptionHandler($exception) {
    // Logowanie wyjątku
    error_log("Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());

    // Wyświetlanie strony błędu dla użytkownika
    http_response_code(500);
    include 'public/views/error.html';
    exit();
}

// Ustawienia obsługi błędów
error_reporting(E_ALL);
ini_set('display_errors', 0); // Nie wyświetlaj błędów bezpośrednio w przeglądarce
ini_set('log_errors', 1);
ini_set('error_log', 'storage/logs/error.log'); // Plik logów błędów

// Rejestrowanie obsługi błędów i wyjątków
set_error_handler('globalErrorHandler');
set_exception_handler('globalExceptionHandler');

// Główny punkt wejścia aplikacji - tu wszystko się zaczyna
require_once 'Routing.php';

// Wyciągam ścieżkę z URL, bo inaczej nie wiem, co użytkownik chce zobaczyć
$path = trim($_SERVER['REQUEST_URI'], '/');
$path = parse_url($path, PHP_URL_PATH);

// To czasem przydaje się do debugowania, ale na produkcji lepiej wyłączyć
// var_dump($path);

// Teraz przekazuję to do routingu, żeby zdecydował, co dalej
Routing::run($path);
