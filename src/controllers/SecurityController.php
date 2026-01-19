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
        $role = 'user';

        if (empty($email) || empty($password)) {
            return $this->render('register', ["messages" => "Fill all fields"]);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->render('register', ["messages" => "Invalid email"]);
        }

        $users = self::loadUsers();

        foreach ($users as $u) {
            if (strcasecmp($u['email'], $email) === 0) {
                return $this->render('register', ["messages" => "Email is taken"]);
            }
        }

        $users[] = [
            'email' => $email,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'role' => $role
        ];

        self::saveUsers($users);

        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/login");
    }

    private function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
}
