<?php

require_once 'AppController.php';
require_once __DIR__ . '/../repository/UserRepository.php';

class SecurityController extends AppController
{
    private UserRepository $userRepository;

    public function __construct()
    {
        $this->userRepository = UserRepository::getInstance();
        // Ensure session cookie has HttpOnly flag
        ini_set('session.cookie_httponly', 1);
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
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            return $this->render('login', ["messages" => "Invalid CSRF token", 'csrf_token' => $_SESSION['csrf_token']]);
        }

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            return $this->render('login', ["messages" => "Fill all fields", 'csrf_token' => $_SESSION['csrf_token']]);
        }

        // Input length validation
        if (strlen($email) > 255) {
            return $this->render('login', ["messages" => "Email too long", 'csrf_token' => $_SESSION['csrf_token']]);
        }
        if (strlen($password) > 128) {
            return $this->render('login', ["messages" => "Password too long", 'csrf_token' => $_SESSION['csrf_token']]);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->render('login', ["messages" => "Niepoprawny email", 'csrf_token' => $_SESSION['csrf_token']]);
        }

        // Check for login block
        $blockKey = 'login_attempts_' . md5($email);
        $blockTimeKey = 'login_block_time_' . md5($email);
        if (isset($_SESSION[$blockTimeKey]) && time() < $_SESSION[$blockTimeKey]) {
            $remaining = $_SESSION[$blockTimeKey] - time();
            return $this->render('login', ["messages" => "Zbyt wiele nieudanych prób logowania. Spróbuj ponownie za " . ceil($remaining / 60) . " minut.", 'csrf_token' => $_SESSION['csrf_token']]);
        }

        $user = $this->userRepository->findByEmail($email);

        if (!$user || !password_verify($password, $user->password)) {
            // Increment failed attempts
            $_SESSION[$blockKey] = ($_SESSION[$blockKey] ?? 0) + 1;
            if ($_SESSION[$blockKey] >= 5) {
                $_SESSION[$blockTimeKey] = time() + 120; // Block for 2 minutes
                unset($_SESSION[$blockKey]); // Reset attempts after blocking
            }
            return $this->render('login', ["messages" => "Email lub hasło niepoprawne", 'csrf_token' => $_SESSION['csrf_token']]);
        }

        // Reset attempts on successful login
        unset($_SESSION[$blockKey]);
        unset($_SESSION[$blockTimeKey]);

        $_SESSION['role'] = $user->role;
        $_SESSION['user'] = ['id' => $user->id, 'email' => $user->email];

        // Regenerate session ID after successful login
        session_regenerate_id(true);

        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/main");
    }

    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_unset();
        session_destroy();

        // Delete the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

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
        // Password complexity validation
        if (!preg_match('/[A-Z]/', $password)) {
            return $this->render('register', ["messages" => "Hasło musi zawierać przynajmniej jedną wielką literę"]);
        }
        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
            return $this->render('register', ["messages" => "Hasło musi zawierać przynajmniej jeden znak specjalny"]);
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
            password_hash($password, PASSWORD_ARGON2ID),
            'user',
            $name,
            $surname,
            $phone,
            $city,
            '',
            [
                'rodo' => true,
                'terms' => true,
                'date' => date('Y-m-d H:i:s')
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
