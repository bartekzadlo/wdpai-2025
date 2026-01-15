<?php

require_once 'src/controllers/DefaultController.php';
require_once 'src/controllers/SecurityController.php';

class Routing
{
    // Tablica z mapowaniem URL -> kontroler + metoda
    public static $routes = [
        'login' => [
            'controller' => 'SecurityController',
            'action' => 'login'
        ],
        'register' => [
            'controller' => 'SecurityController',
            'action' => 'register'
        ],
        'main' => [
            'controller' => 'DefaultController',
            'action' => 'index'
        ],
        // --- NOWA TRASA DLA DASHBOARDU ---
        'dashboard' => [
            'controller' => 'DefaultController',
            'action' => 'dashboard'
        ]
    ];

    public static function run($url)
    {
        $actionKey = explode("/", trim($url, '/'))[0];

        // Jeśli URL nie istnieje w tablicy routes -> 404
        if (!array_key_exists($actionKey, self::$routes)) {
            include 'public/views/404.html';
            return;
        }

        $controllerName = self::$routes[$actionKey]['controller'];
        $actionName = self::$routes[$actionKey]['action'];

        $controllerObj = new $controllerName();

        if (method_exists($controllerObj, $actionName)) {
            $controllerObj->$actionName();
        } else {
            include 'public/views/404.html';
        }
    }
}