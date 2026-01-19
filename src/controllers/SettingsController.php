<?php

require_once __DIR__ . '/AppController.php';

class SettingsController extends AppController {
    
    private const USERS_FILE = __DIR__ . '/../../storage/users.json';

    public function updateSettings() {
        $this->checkAuth();
        $data = $this->getJsonInput();
        
        $users = $this->loadUsers();
        $currentUserEmail = $_SESSION['user'];
        $updated = false;

        foreach ($users as &$user) {
            if ($user['email'] === $currentUserEmail) {
                if (!isset($user['settings'])) {
                    $user['settings'] = [];
                }
                
                foreach ($data as $key => $value) {
                    $user['settings'][$key] = (bool)$value;
                }
                $updated = true;
                break;
            }
        }

        if ($updated) {
            $this->saveUsers($users);
            $this->sendJsonResponse(['status' => 'success', 'message' => 'Ustawienia zaktualizowane']);
        } else {
            $this->sendJsonResponse(['status' => 'error', 'message' => 'Użytkownik nieznaleziony'], 404);
        }
    }

    public function changePassword() {
        $this->checkAuth();
        $data = $this->getJsonInput();
        
        $oldPass = $data['old_password'] ?? '';
        $newPass = $data['new_password'] ?? '';

        if (empty($oldPass) || empty($newPass)) {
            $this->sendJsonResponse(['status' => 'error', 'message' => 'Wypełnij pola hasła'], 400);
            return;
        }

        $users = $this->loadUsers();
        $currentUserEmail = $_SESSION['user'];
        $found = false;

        foreach ($users as &$user) {
            if ($user['email'] === $currentUserEmail) {
                if (!password_verify($oldPass, $user['password'])) {
                    $this->sendJsonResponse(['status' => 'error', 'message' => 'Stare hasło jest niepoprawne'], 400);
                    return;
                }
                
                $user['password'] = password_hash($newPass, PASSWORD_BCRYPT);
                $found = true;
                break;
            }
        }

        if ($found) {
            $this->saveUsers($users);
            $this->sendJsonResponse(['status' => 'success', 'message' => 'Hasło zmienione']);
        } else {
            $this->sendJsonResponse(['status' => 'error', 'message' => 'Błąd użytkownika'], 404);
        }
    }

    public function deleteAccount() {
        $this->checkAuth();
        
        $users = $this->loadUsers();
        $currentUserEmail = $_SESSION['user'];
        
        $newUsers = array_filter($users, function($user) use ($currentUserEmail) {
            return $user['email'] !== $currentUserEmail;
        });

        $newUsers = array_values($newUsers);

        $this->saveUsers($newUsers);
        
        session_destroy();
        $this->sendJsonResponse(['status' => 'success', 'message' => 'Konto usunięte']);
    }

    public function settings() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user'])) {
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
            return;
        }

        $users = $this->loadUsers();
        $currentUser = null;
        foreach($users as $u) {
            if($u['email'] === $_SESSION['user']) {
                $currentUser = $u;
                break;
            }
        }

        $this->render('settings', ['user' => $currentUser]);
    }

    // --- Helpers ---

    private function checkAuth() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user'])) {
            $this->sendJsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 401);
            exit;
        }
    }

    private function getJsonInput() {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }

    // --- TUTAJ BYŁ BŁĄD, POPRAWIONA METODA: ---
    private function sendJsonResponse($data, $code = 200) {
        // Sprawdzamy czy bufor istnieje (ob_get_length > 0) zanim spróbujemy go wyczyścić
        if (ob_get_length()) {
            ob_clean();
        }
        
        header('Content-Type: application/json');
        http_response_code($code);
        echo json_encode($data);
        exit;
    }

    private function loadUsers(): array {
        if (!file_exists(self::USERS_FILE)) return [];
        $json = file_get_contents(self::USERS_FILE);
        return json_decode($json, true) ?: [];
    }

    private function saveUsers(array $users): void {
        $dir = dirname(self::USERS_FILE);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents(self::USERS_FILE, json_encode($users, JSON_PRETTY_PRINT));
    }
}