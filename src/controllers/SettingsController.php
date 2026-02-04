<?php

require_once __DIR__ . '/AppController.php';
require_once __DIR__ . '/../repository/UserRepository.php';

class SettingsController extends AppController {

    private UserRepository $userRepository;

    public function __construct()
    {
        $this->userRepository = UserRepository::getInstance();
    }



    public function updateUserInfo() {
        $this->checkAuth();
        $data = $this->getJsonInput();

        $email = trim($data['email'] ?? '');
        $name = trim($data['name'] ?? '');
        $surname = trim($data['surname'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $city = trim($data['city'] ?? '');
        $profilePicture = trim($data['profile_picture'] ?? '');

        // Walidacja - identyczna jak przy rejestracji
        if (empty($email) || empty($name) || empty($surname) || empty($city)) {
            $this->sendJsonResponse(['status' => 'error', 'message' => 'Wypełnij wszystkie pola'], 400);
            return;
        }

        // Input length validation - identyczna jak przy rejestracji
        if (strlen($email) > 255) {
            $this->sendJsonResponse(['status' => 'error', 'message' => 'Email too long'], 400);
            return;
        }
        if (strlen($name) > 50) {
            $this->sendJsonResponse(['status' => 'error', 'message' => 'Name too long'], 400);
            return;
        }
        if (strlen($surname) > 50) {
            $this->sendJsonResponse(['status' => 'error', 'message' => 'Surname too long'], 400);
            return;
        }
        if (strlen($phone) > 20) {
            $this->sendJsonResponse(['status' => 'error', 'message' => 'Phone too long'], 400);
            return;
        }
        if (strlen($city) > 100) {
            $this->sendJsonResponse(['status' => 'error', 'message' => 'City too long'], 400);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->sendJsonResponse(['status' => 'error', 'message' => 'Niepoprawny email'], 400);
            return;
        }

        $user = $this->userRepository->findByEmail($_SESSION['user']['email']);
        if (!$user) {
            $this->sendJsonResponse(['status' => 'error', 'message' => 'Błąd użytkownika'], 404);
            return;
        }

        // Check if email is being changed and if it's already taken
        if ($email !== $user->email && $this->userRepository->emailExists($email)) {
            $this->sendJsonResponse(['status' => 'error', 'message' => 'Email jest już zajęty'], 400);
            return;
        }

        $user->email = $email;
        $user->name = $name;
        $user->surname = $surname;
        $user->phone = $phone;
        $user->city = $city;
        $user->profilePicture = $profilePicture;

        $this->userRepository->save($user);

        // Update session if email changed
        if ($email !== $_SESSION['user']['email']) {
            $_SESSION['user']['email'] = $email;
        }

        $this->sendJsonResponse(['status' => 'success', 'message' => 'Dane zaktualizowane']);
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

        // Password validation same as registration
        if (strlen($newPass) < 8) {
            $this->sendJsonResponse(['status' => 'error', 'message' => 'Hasło zbyt krótkie'], 400);
            return;
        }
        if (strlen($newPass) > 128) {
            $this->sendJsonResponse(['status' => 'error', 'message' => 'Hasło zbyt długie'], 400);
            return;
        }
        if (!preg_match('/[A-Z]/', $newPass)) {
            $this->sendJsonResponse(['status' => 'error', 'message' => 'Hasło musi zawierać przynajmniej jedną wielką literę'], 400);
            return;
        }
        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $newPass)) {
            $this->sendJsonResponse(['status' => 'error', 'message' => 'Hasło musi zawierać przynajmniej jeden znak specjalny'], 400);
            return;
        }

        $user = $this->userRepository->findByEmail($_SESSION['user']['email']);
        if (!$user) {
            $this->sendJsonResponse(['status' => 'error', 'message' => 'Błąd użytkownika'], 404);
            return;
        }

        if (!password_verify($oldPass, $user->password)) {
            $this->sendJsonResponse(['status' => 'error', 'message' => 'Stare hasło jest niepoprawne'], 400);
            return;
        }

        $user->password = password_hash($newPass, PASSWORD_ARGON2ID);
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