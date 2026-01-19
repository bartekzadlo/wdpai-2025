<?php

require_once 'AppController.php';

class SecurityController extends AppController
{
    private const USERS_FILE = __DIR__ . '/../../storage/users.json';

    private static function loadUsers(): array
    {
        if (!file_exists(self::USERS_FILE)) {
            return [];
        }

        $json = file_get_contents(self::USERS_FILE);
        return json_decode($json, true) ?: [];
    }

    private static function saveUsers(array $users): void
    {
        file_put_contents(self::USERS_FILE, json_encode($users, JSON_PRETTY_PRINT));
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

        $users = self::loadUsers();

        $userRow = null;
        foreach ($users as $u) {
            if (strcasecmp($u['email'], $email) === 0) {
                $userRow = $u;
                break;
            }
        }

        if (!$userRow) {
            return $this->render('login', ["messages" => "User not found"]);
        }

        if (!password_verify($password, $userRow['password'])) {
            return $this->render('login', ["messages" => "Wrong password"]);
        }

        session_start();
        $_SESSION['role'] = $userRow['role'];
        $_SESSION['user'] = $userRow['email'];

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

        $users = self::loadUsers();

        foreach ($users as $u) {
            if (strcasecmp($u['email'], $email) === 0) {
                return $this->render('register', ["messages" => "Email jest już zajęty"]);
            }
        }

        // Tworzenie nowego użytkownika z rozszerzonymi danymi
        $newUser = [
            'id' => uniqid(), // Unikalne ID
            'email' => $email,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'role' => 'user',
            'name' => $name,
            'surname' => $surname,
            'phone' => $phone,
            'city' => $city,
            'consents' => [
                'rodo' => true,
                'terms' => true,
                'date' => date('Y-m-d H:i:s')
            ],
            // Domyślne ustawienia
            'settings' => [
                'email_notif' => true,
                'sms_notif' => false,
                'geo_notif' => true,
                'public_profile' => false,
                'show_events' => true
            ]
        ];

        $users[] = $newUser;
        self::saveUsers($users);

        // Opcjonalnie: Automatyczne logowanie po rejestracji lub przekierowanie
        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/login");
    }

    private function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
}
