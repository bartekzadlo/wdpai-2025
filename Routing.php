<?php

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
        ]
        // Możesz dodać kolejne ścieżki np. 'dashboard', 'logout', 'profile' itd.
    ];

    public static function run($url)
    {
        //TODO na podstawie sciezki sprawdzamy jaki HTML zwrocic
        // Pobieramy pierwszą część URL
        $actionKey = explode("/", trim($url, '/'))[0];

        // Jeśli URL nie istnieje w tablicy routes -> 404
        if (!array_key_exists($actionKey, self::$routes)) {
            include 'public/views/404.html';
            return;
        }

        // Pobieramy kontroler i metodę
        $controllerName = self::$routes[$actionKey]['controller'];
        $actionName = self::$routes[$actionKey]['action'];

        // Tworzymy instancję kontrolera
        $controllerObj = new $controllerName();

        //TODO obsługa metod, które nie istnieją
        if (method_exists($controllerObj, $actionName)) {
            $controllerObj->$actionName();
        } else {
            include 'public/views/404.html';
        }
    }
}
