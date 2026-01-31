<?php

require_once __DIR__ . '/AppController.php';
require_once __DIR__ . '/../repository/UserRepository.php';

class SettingsController extends AppController {

    private UserRepository $userRepository;

    public function __construct()
    {
        $this->userRepository = UserRepository::getInstance();
    }

    public function updateSettings() {
        $this->checkAuth();
        $data = $this->getJsonInput();

        $user = $this->userRepository->findByEmail($_SESSION['user']['email']);
        if (!$user) {
            $this->sendJsonResponse(['status' => 'error', 'message' => 'Użytkownik nieznaleziony'], 404);
            return;
        }

        if (!isset($user->settings)) {
            $user->settings = [];
        }

        foreach ($data as $key => $value) {
            $user->settings[$key] = (bool)$value;
        }

        $this->userRepository->save($user);
        $this->sendJsonResponse(['status' => 'success', 'message' => 'Ustawienia zaktualizowane']);
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

        $user = $this->userRepository->findByEmail($_SESSION['user']);
        if (!$user) {
            $this->sendJsonResponse(['status' => 'error', 'message' => 'Błąd użytkownika'], 404);
            return;
        }

        if (!password_verify($oldPass, $user->password)) {
            $this->sendJsonResponse(['status' => 'error', 'message' => 'Stare hasło jest niepoprawne'], 400);
            return;
        }

        $user->password = password_hash($newPass, PASSWORD_BCRYPT);
        $this->userRepository->save($user);
        $this->sendJsonResponse(['status' => 'success', 'message' => 'Hasło zmienione']);
    }

    public function deleteAccount() {
        $this->checkAuth();

        $user = $this->userRepository->findByEmail($_SESSION['user']['email']);
        if (!$user) {
            $this->sendJsonResponse(['status' => 'error', 'message' => 'Użytkownik nieznaleziony'], 404);
            return;
        }

        $this->userRepository->delete($user->id);

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

        $currentUser = $this->userRepository->findByEmail($_SESSION['user']['email']);

        $this->render('settings', ['user' => $currentUser ? $currentUser->toArray() : null]);
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


}