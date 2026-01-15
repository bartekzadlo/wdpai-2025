<?php

require_once 'AppController.php';

class DefaultController extends AppController {
    
    public function index() {
        // Metoda wyświetlająca stronę główną (feed)
        // Szuka pliku w public/views/main.html
        $this->render('main');
    }

    public function dashboard() {
        // Metoda wyświetlająca panel administratora
        // Szuka pliku w public/views/dashboard.html
        $this->render('dashboard');
    }
}