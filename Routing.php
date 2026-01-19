<?php

require_once 'src/controllers/DefaultController.php';
require_once 'src/controllers/SecurityController.php';

class Routing
{
    public static $routes = [
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
        'profile' => [
            'controller' => 'DefaultController',
            'action' => 'profile'
        ],
        'settings' => [
            'controller' => 'DefaultController',
            'action' => 'settings'
        ]
    ];

    public static function run($url)
    {
        $actionKey = explode("/", trim($url, '/'))[0];

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
