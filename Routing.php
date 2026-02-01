<?php

// Upewnij się, że ścieżki są poprawne względem głównego katalogu
require_once 'src/controllers/DefaultController.php';
require_once 'src/controllers/SecurityController.php';
require_once 'src/controllers/SettingsController.php';
require_once 'src/controllers/ApiController.php';

class Routing
{
    public static $routes = [
        '' => [
            'controller' => 'DefaultController',
            'action' => 'index'
        ],
        'login' => [
            'controller' => 'SecurityController',
            'action' => 'login'
        ],
        'logout' => [
            'controller' => 'SecurityController',
            'action' => 'logout'
        ],
        'register' => [
            'controller' => 'SecurityController',
            'action' => 'register'
        ],
        'main' => [
            'controller' => 'DefaultController',
            'action' => 'index'
        ],
        'dashboard' => [
            'controller' => 'DefaultController',
            'action' => 'dashboard'
        ],
        'add-event' => [
            'controller' => 'DefaultController',
            'action' => 'addEvent'
        ],
        'profile' => [
            'controller' => 'DefaultController',
            'action' => 'profile'
        ],
        'settings' => [
            'controller' => 'SettingsController', 
            'action' => 'settings'
        ],

        // --- API Ustawień (AJAX) ---
        'api/settings/update' => [
            'controller' => 'SettingsController', 
            'action' => 'updateSettings'
        ],
        'api/settings/password' => [
            'controller' => 'SettingsController', 
            'action' => 'changePassword'
        ],
        'api/settings/delete' => [
            'controller' => 'SettingsController',
            'action' => 'deleteAccount'
        ],

        // --- API Interests ---
        'api/interest/toggle' => [
            'controller' => 'ApiController',
            'action' => 'toggleInterest'
        ],
        'api/interest/status' => [
            'controller' => 'ApiController',
            'action' => 'getInterestStatus'
        ]
    ];

    public static function run($url)
    {
        // Rozbijamy URL na części
        $urlParts = explode("/", trim($url, '/'));
        $actionKey = $urlParts[0];

        // Obsługa zagnieżdżonych ścieżek dla API (np. api/settings/update)
        // Sprawdzamy czy pierwszy segment to 'api' i czy mamy wystarczająco dużo części
        if ($actionKey === 'api' && count($urlParts) >= 3) {
            $actionKey = $urlParts[0] . '/' . $urlParts[1] . '/' . $urlParts[2];
        }

        // Jeśli ścieżka nie istnieje w tablicy routes -> 404
        if (!array_key_exists($actionKey, self::$routes)) {
            http_response_code(404);
            include 'public/views/404.html';
            return;
        }

        $controllerName = self::$routes[$actionKey]['controller'];
        $actionName = self::$routes[$actionKey]['action'];

        // Tworzymy instancję kontrolera i wywołujemy metodę
        if (class_exists($controllerName)) {
            $controllerObj = new $controllerName();
            
            if (method_exists($controllerObj, $actionName)) {
                $controllerObj->$actionName();
            } else {
                // Metoda nie istnieje w kontrolerze
                http_response_code(404);
                include 'public/views/404.html';
            }
        } else {
            // Klasa kontrolera nie znaleziona
            http_response_code(500);
            echo "Internal Server Error: Controller not found.";
        }
    }
}