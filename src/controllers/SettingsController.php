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
        $errors = ValidationHelper::validateUserData([
            'email' => $email,
            'name' => $name,
            'surname' => $surname,
            'phone' => $phone,
            'city' => $city
        ], true);

        if (!empty($errors)) {
            $this->sendJsonResponse(['status' => 'error', 'message' => implode(', ', $errors)], 400);
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

        $passwordErrors = ValidationHelper::validatePassword($newPass);
        if (!empty($passwordErrors)) {
            $this->sendJsonResponse(['status' => 'error', 'message' => implode(', ', $passwordErrors)], 400);
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
        $this->requireLogin();

        $currentUser = $this->userRepository->findByEmail($_SESSION['user']['email']);

        $this->render('settings', ['user' => $currentUser ? $currentUser->toArray() : null]);
    }



}