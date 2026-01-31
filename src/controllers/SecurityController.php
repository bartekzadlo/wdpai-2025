<?php

require_once 'AppController.php';
require_once __DIR__ . '/../repository/UserRepository.php';

class SecurityController extends AppController
{
    private UserRepository $userRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
    }

    public function login()
    {
        if (!$this->isPost()) {
            return $this->render('login');
        }

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            return $this->render('login', ["messages" => "Fill all fields"]);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->render('login', ["messages" => "Niepoprawny email"]);
        }

        $user = $this->userRepository->findByEmail($email);

        if (!$user || !password_verify($password, $user->password)) {
            return $this->render('login', ["messages" => "Email lub hasło niepoprawne"]);
        }

        session_start();
        $_SESSION['role'] = $user->role;
        $_SESSION['user'] = $user->email;

        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/main");
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
        if (!$this->isPost()) {
            return $this->render('register');
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $surname = trim($_POST['surname'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $terms = isset($_POST['terms']);
        $rodo = isset($_POST['rodo']);

        // Walidacja
        if (empty($email) || empty($password) || empty($name) || empty($surname) || empty($city)) {
            return $this->render('register', ["messages" => "Wypełnij wszystkie pola"]);
        }

        if (!$terms || !$rodo) {
            return $this->render('register', ["messages" => "Wymagane są zgody (Regulamin i RODO)"]);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->render('register', ["messages" => "Niepoprawny email"]);
        }

        if ($this->userRepository->emailExists($email)) {
            return $this->render('register', ["messages" => "Email jest już zajęty"]);
        }

        // Tworzenie nowego użytkownika z rozszerzonymi danymi
        $newUser = new User(
            uniqid(), // Unikalne ID
            $email,
            password_hash($password, PASSWORD_BCRYPT),
            'user',
            $name,
            $surname,
            $phone,
            $city,
            [
                'rodo' => true,
                'terms' => true,
                'date' => date('Y-m-d H:i:s')
            ],
            // Domyślne ustawienia
            [
                'email_notif' => true,
                'sms_notif' => false,
                'geo_notif' => true,
                'public_profile' => false,
                'show_events' => true
            ]
        );

        $this->userRepository->save($newUser);

        // Opcjonalnie: Automatyczne logowanie po rejestracji lub przekierowanie
        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/login");
    }

    private function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
}
