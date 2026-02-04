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

    // Metoda obsługująca logowanie użytkownika z ochroną przed atakami CSRF i brute force
    public function login()
    {
        // Jeśli to nie jest żądanie POST, wyświetl formularz logowania z tokenem CSRF
        if (!$this->isPost()) {
            // Generowanie tokenu CSRF dla żądania GET
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            if (!isset($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
            return $this->render('login', ['csrf_token' => $_SESSION['csrf_token']]);
        }

        // Walidacja tokenu CSRF dla żądania POST
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $csrf_token = $_POST['csrf_token'] ?? '';
        if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            return $this->render('login', ["messages" => "Invalid CSRF token", 'csrf_token' => $_SESSION['csrf_token']]);
        }

        // Pobranie danych z formularza
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        // Sprawdzenie czy wszystkie pola są wypełnione
        if (empty($email) || empty($password)) {
            return $this->render('login', ["messages" => "Fill all fields", 'csrf_token' => $_SESSION['csrf_token']]);
        }

        // Walidacja długości danych wejściowych
        if (strlen($email) > 255) {
            return $this->render('login', ["messages" => "Email too long", 'csrf_token' => $_SESSION['csrf_token']]);
        }
        if (strlen($password) > 128) {
            return $this->render('login', ["messages" => "Password too long", 'csrf_token' => $_SESSION['csrf_token']]);
        }

        // Walidacja formatu email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->render('login', ["messages" => "Niepoprawny email", 'csrf_token' => $_SESSION['csrf_token']]);
        }

        // Sprawdzenie blokady logowania (brute force protection)
        $blockKey = 'login_attempts_' . md5($email);
        $blockTimeKey = 'login_block_time_' . md5($email);
        if (isset($_SESSION[$blockTimeKey]) && time() < $_SESSION[$blockTimeKey]) {
            $remaining = $_SESSION[$blockTimeKey] - time();
            return $this->render('login', ["messages" => "Zbyt wiele nieudanych prób logowania. Spróbuj ponownie za " . ceil($remaining / 60) . " minut.", 'csrf_token' => $_SESSION['csrf_token']]);
        }

        // Wyszukanie użytkownika po emailu
        $user = $this->userRepository->findByEmail($email);

        // Sprawdzenie czy użytkownik istnieje i hasło jest poprawne
        if (!$user || !password_verify($password, $user->password)) {
            // Zwiększenie licznika nieudanych prób
            $_SESSION[$blockKey] = ($_SESSION[$blockKey] ?? 0) + 1;
            if ($_SESSION[$blockKey] >= 5) {
                $_SESSION[$blockTimeKey] = time() + 120; // Blokada na 2 minuty
                unset($_SESSION[$blockKey]); // Reset licznika po blokadzie
            }
            return $this->render('login', ["messages" => "Email lub hasło niepoprawne", 'csrf_token' => $_SESSION['csrf_token']]);
        }

        // Reset liczników po udanym logowaniu
        unset($_SESSION[$blockKey]);
        unset($_SESSION[$blockTimeKey]);

        // Ustawienie danych sesji
        $_SESSION['role'] = $user->role;
        $_SESSION['user'] = ['id' => $user->id, 'email' => $user->email];

        // Regeneracja ID sesji po udanym logowaniu dla bezpieczeństwa
        session_regenerate_id(true);

        // Przekierowanie do strony głównej
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

    // Metoda obsługująca rejestrację nowego użytkownika z kompleksową walidacją
    public function register()
    {
        // Jeśli to nie jest żądanie POST, wyświetl formularz rejestracji
        if (!$this->isPost()) {
            return $this->render('register');
        }

        // Pobranie danych z formularza
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $surname = trim($_POST['surname'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $terms = isset($_POST['terms']);
        $rodo = isset($_POST['rodo']);

        // Walidacja - sprawdzenie czy wszystkie wymagane pola są wypełnione
        if (empty($email) || empty($password) || empty($name) || empty($surname) || empty($city)) {
            return $this->render('register', ["messages" => "Wypełnij wszystkie pola"]);
        }

        // Walidacja długości danych wejściowych
        if (strlen($email) > 255) {
            return $this->render('register', ["messages" => "Email too long"]);
        }
        if (strlen($password) < 8) {
            return $this->render('register', ["messages" => "Password too short"]);
        }
        if (strlen($password) > 128) {
            return $this->render('register', ["messages" => "Password too long"]);
        }
        // Walidacja złożoności hasła
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

        // Sprawdzenie zgód (Regulamin i RODO)
        if (!$terms || !$rodo) {
            return $this->render('register', ["messages" => "Wymagane są zgody (Regulamin i RODO)"]);
        }

        // Walidacja formatu email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->render('register', ["messages" => "Niepoprawny email"]);
        }

        // Sprawdzenie czy email już istnieje
        if ($this->userRepository->emailExists($email)) {
            return $this->render('register', ["messages" => "Email jest już zajęty"]);
        }

        // Tworzenie nowego użytkownika z rozszerzonymi danymi
        $newUser = new User(
            uniqid(), // Unikalne ID
            $email,
            password_hash($password, PASSWORD_ARGON2ID), // Haszowanie hasła
            'user', // Domyślna rola
            $name,
            $surname,
            $phone,
            $city,
            '', // Brak zdjęcia profilowego
            [
                'rodo' => true,
                'terms' => true,
                'date' => date('Y-m-d H:i:s') // Data rejestracji
            ]
        );

        // Zapisanie nowego użytkownika w bazie danych
        $this->userRepository->save($newUser);

        // Przekierowanie do strony logowania po rejestracji
        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/login");
    }

    // Prywatna metoda pomocnicza - sprawdza czy żądanie HTTP to POST
    private function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
}
