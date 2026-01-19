<?php

require_once 'AppController.php';

class DefaultController extends AppController {
    public function index() {
        if (isset($_SESSION['user'])) {
            // Zalogowany user widzi feed (dawne main.html)
            $this->render('feed'); 
        } else {
            // Niezalogowany widzi landing page
            $this->render('landing');
        }
    }

    public function dashboard() {
        // Metoda wyświetlająca panel administratora
        // Szuka pliku w public/views/dashboard.html
        $this->render('dashboard');
    }
}