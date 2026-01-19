<?php

require_once 'AppController.php';

class SecurityController extends AppController
{
    public function login()
    {
        // Jeśli to nie jest POST, wyświetl formularz
        if (!$this->isPost()) {
            return $this->render('login');
        }

        $email = $_POST['email'];
        $password = $_POST['password'];

        // MOCK ADMINA
        if ($email === "admin@event.io" && $password === "admin") {
            session_start();
            $_SESSION['role'] = 'admin';
            $_SESSION['user'] = $email;

            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/dashboard");
            return;
        }

        // MOCK UŻYTKOWNIKA
        if ($email === "user@event.io" && $password === "user") {
            session_start();
            $_SESSION['role'] = 'user';
            $_SESSION['user'] = $email;

            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/main");
            return;
        }

        // BŁĘDNE DANE
        return $this->render('login', ["messages" => "Błędny email lub hasło!"]);
    }

    public function logout()
    {
        session_start();
        session_unset();
        session_destroy();

        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/login");
    }

    public function register()
    {
        // TODO pobranie z formularza email i hasła
        // TODO insert do bazy danych
        // TODO zwrocenie informajci o pomyslnym zarejstrowaniu
        return $this->render("login", ["messages" => "Zarejestrowano użytkownika"]);
    }

    private function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
}