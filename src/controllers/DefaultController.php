<?php

require_once 'AppController.php';

class DefaultController extends AppController {

    public function index() {
        session_start();
        if (!isset($_SESSION['user'])) {
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
            return;
        }

        $this->render('main');
    }

    public function dashboard() {
        session_start();

        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
            return;
        }

        $this->render('dashboard');
    }

    public function settings() {
        session_start();

        if (!isset($_SESSION['user'])) {
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
            return;
        }

        $this->render('settings');
    }

    public function profile() {
    session_start();

    if (!isset($_SESSION['user'])) {
        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/login");
        return;
    }

    $this->render('profile');
    }   
}
