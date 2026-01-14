<?php

require_once 'AppController.php';

class DefaultController extends AppController {
    
    public function index() {
        // Metoda wyświetlająca dashboard
        // Szuka pliku w public/views/main.html
        $this->render('main');
    }
}