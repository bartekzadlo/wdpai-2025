<?php

require_once 'AppController.php';
require_once __DIR__ . '/../repository/UserRepository.php';

class SecurityController extends AppController
{
    private UserRepository $userRepository;

    public function __construct()
    {
        $this->userRepository = UserRepository::getInstance();
    }

    public function login()
    {
        if (!$this->isPost()) {
            // Generate CSRF token for GET request
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            if (!isset($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
            return $this->render('login', ['csrf_token' => $_SESSION['csrf_token']]);
        }

        // Validate CSRF token for POST request
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $csrf_token = $_POST['csrf_token'] ?? '';
        if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
            return $this->render('login', ["messages" => "Invalid CSRF token"]);
        }

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            return $this->render('login', ["messages" => "Fill all fields"]);
        }

        // Input length validation
        if (strlen($email) > 255) {
            return $this->render('login', ["messages" => "Email too long"]);
        }
        if (strlen($password) < 8) {
            return $this->render('login', ["messages" => "Password too short"]);
        }
        if (strlen($password) > 128) {
            return $this->render('login', ["messages" => "Password too long"]);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->render('login', ["messages" => "Niepoprawny email"]);
        }

        $user = $this->userRepository->findByEmail($email);

        if (!$user || !password_verify($password, $user->password)) {
            return $this->render('login', ["messages" => "Email lub hasło niepoprawne"]);
        }

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

        // Input length validation
        if (strlen($email) > 255) {
            return $this->render('register', ["messages" => "Email too long"]);
        }
        if (strlen($password) < 8) {
            return $this->render('register', ["messages" => "Password too short"]);
        }
        if (strlen($password) > 128) {
            return $this->render('register', ["messages" => "Password too long"]);
        }
        if (strlen($name) > 50) {
            return $this->render('register', ["messages" => "Name too long"]);
        }
        if (strlen($surname) > 50) {
            return $this->render('register', ["messages" => "Surname too long"]);
        }
        if (strlen($phone) > 20) {
            return $this->render('register', ["messages" => "Phone too long"]);
        }
        if (strlen($city) > 100) {
            return $this->render('register', ["messages" => "City too long"]);
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
