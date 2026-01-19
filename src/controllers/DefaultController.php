<?php

require_once 'AppController.php';

class DefaultController extends AppController {
    
    public function index() {
        // Metoda wyświetlająca stronę główną (feed)
        $this->render('main');
    }

    public function dashboard() {
        session_start();

        // Sprawdzenie czy użytkownik jest zalogowany i ma rolę admina
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
            return;
        }

        // Metoda wyświetlająca panel administratora
        $this->render('dashboard');
    }
}