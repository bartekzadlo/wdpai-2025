<?php

require_once 'AppController.php';

class SecurityController extends AppController
{
    // ======= LOKALNA "BAZA" UŻYTKOWNIKÓW =======
    private static array $users = [
        [
            'email' => 'admin@event.io',
            'password' => '$2y$10$bRpNssdcYkU6pG8eUwY.peOVFt6W2.cnyCvSwL3p4kFB/bQTXnLAi', // admin
            'role' => 'admin'
        ],
        [
            'email' => 'user@event.io',
            'password' => '$2y$10$yGzUXvMGRd8IQM9nYYutp.CwJKETOOCxJubq8acqFYtJxuOa3XRBG', // user
            'role' => 'user'
        ],
    ];

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

        $userRow = null;
        foreach (self::$users as $u) {
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
        header("Location: {$url}/" . ($userRow['role'] === 'admin' ? 'dashboard' : 'main'));
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
        $role = $_POST['role'] ?? 'user';

        if (empty($email) || empty($password)) {
            return $this->render('register', ["messages" => "Fill all fields"]);
        }

        foreach (self::$users as $u) {
            if (strcasecmp($u['email'], $email) === 0) {
                return $this->render('register', ["messages" => "Email is taken"]);
            }
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        self::$users[] = [
            'email' => $email,
            'password' => $hashedPassword,
            'role' => $role
        ];

        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/login");
    }

    private function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
}
