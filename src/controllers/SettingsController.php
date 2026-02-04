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

    // Metoda obsługująca usunięcie konta użytkownika
    public function deleteAccount() {
        // Sprawdzenie autoryzacji użytkownika
        $this->checkAuth();

        // Pobranie użytkownika z bazy danych
        $user = $this->userRepository->findByEmail($_SESSION['user']['email']);
        if (!$user) {
            $this->sendJsonResponse(['status' => 'error', 'message' => 'Użytkownik nieznaleziony'], 404);
            return;
        }

        // Usunięcie użytkownika z bazy danych
        $this->userRepository->delete($user->id);

        // Zniszczenie sesji
        session_destroy();
        // Zwrócenie odpowiedzi sukcesu
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

    // --- Metody pomocnicze ---

    // Prywatna metoda sprawdzająca autoryzację użytkownika
    private function checkAuth() {
        // Rozpoczęcie sesji jeśli nie jest już rozpoczęta
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Sprawdzenie czy użytkownik jest zalogowany
        if (!isset($_SESSION['user'])) {
            $this->sendJsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 401);
            exit;
        }
    }

    // Prywatna metoda pobierająca dane JSON z ciała żądania
    private function getJsonInput() {
        // Pobranie danych z wejścia php://input
        $input = file_get_contents('php://input');
        // Dekodowanie JSON na tablicę asocjacyjną
        return json_decode($input, true) ?? [];
    }

    // Prywatna metoda wysyłająca odpowiedź JSON
    private function sendJsonResponse($data, $code = 200) {
        // Sprawdzenie czy bufor wyjścia istnieje przed jego wyczyszczeniem
        if (ob_get_length()) {
            ob_clean();
        }

        // Ustawienie nagłówka Content-Type na application/json
        header('Content-Type: application/json');
        // Ustawienie kodu odpowiedzi HTTP
        http_response_code($code);
        // Wysłanie danych JSON
        echo json_encode($data);
        // Zakończenie wykonania skryptu
        exit;
    }


}