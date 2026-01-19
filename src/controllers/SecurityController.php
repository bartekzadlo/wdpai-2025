<?php

require_once 'AppController.php';

class SecurityController extends AppController
{

    public function login() {
        if (!$this->isPost()) return $this->render('login');

        $email = $_POST['email'];
        $pass = $_POST['password'];

        // SYMULACJA BAZY DANYCH
        if ($email === 'admin@event.io' && $pass === 'admin') {
            $_SESSION['user'] = $email;
            $_SESSION['role'] = 'admin';
            header("Location: /dashboard");
        } elseif ($email === 'user@event.io' && $pass === 'user') {
            $_SESSION['user'] = $email;
            $_SESSION['role'] = 'user';
            header("Location: /"); // Przenosi do feedu
        } else {
            return $this->render('login', ['messages' => 'Błędne dane!']);
        }
    }

    public function register() {
        if (!$this->isPost()) return $this->render('register');
        return $this->render('login', ['messages' => 'Konto założone!']);
    }

    public function logout() {
        session_destroy();
        header("Location: /");
    }
}