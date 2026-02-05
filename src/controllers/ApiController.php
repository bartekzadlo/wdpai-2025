<?php

// Kontroler API - obsługuje żądania AJAX dla funkcjonalności aplikacji
require_once 'BaseController.php';
require_once __DIR__ . '/../repository/UserEventInterestRepository.php';
require_once __DIR__ . '/../repository/EventRepository.php';
require_once __DIR__ . '/../models/EventStatus.php';

class ApiController extends BaseController
{
    // Metoda przełączająca zainteresowanie użytkownika wydarzeniem (dodaje lub usuwa)
    public function toggleInterest()
    {
        $this->requireLogin();

        // Pobranie ID użytkownika z sesji
        $userId = $_SESSION['user']['id'];
        // Pobranie ID wydarzenia z POST
        $eventId = $_POST['eventId'] ?? '';

        // Walidacja - sprawdzenie czy ID wydarzenia zostało podane
        if (empty($eventId)) {
            http_response_code(400);
            echo json_encode(['error' => 'Event ID is required']);
            return;
        }

        // Pobranie instancji repozytorium zainteresowań
        $interestRepo = UserEventInterestRepository::getInstance();
        // Przełączenie zainteresowania (dodanie lub usunięcie)
        $isInterested = $interestRepo->toggleInterest($userId, $eventId);
        // Pobranie aktualnej liczby zainteresowań dla wydarzenia
        $interestCount = $interestRepo->getInterestCount($eventId);

        // Zwrócenie odpowiedzi JSON z aktualnym stanem
        echo json_encode([
            'isInterested' => $isInterested,
            'interestCount' => $interestCount
        ]);
    }

    // Metoda pobierająca status zainteresowania użytkownika dla danego wydarzenia
    public function getInterestStatus()
    {
        $this->requireLogin();

        // Pobranie ID użytkownika z sesji
        $userId = $_SESSION['user']['id'];
        // Pobranie ID wydarzenia z GET
        $eventId = $_GET['eventId'] ?? '';

        // Walidacja - sprawdzenie czy ID wydarzenia zostało podane
        if (empty($eventId)) {
            http_response_code(400);
            echo json_encode(['error' => 'Event ID is required']);
            return;
        }

        // Pobranie instancji repozytorium zainteresowań
        $interestRepo = UserEventInterestRepository::getInstance();
        // Sprawdzenie czy użytkownik jest zainteresowany wydarzeniem
        $isInterested = $interestRepo->isInterested($userId, $eventId);
        // Pobranie liczby zainteresowań dla wydarzenia
        $interestCount = $interestRepo->getInterestCount($eventId);

        // Zwrócenie odpowiedzi JSON z statusem zainteresowania
        echo json_encode([
            'isInterested' => $isInterested,
            'interestCount' => $interestCount
        ]);
    }

    public function deleteEvent()
    {
        session_start();
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }

        $eventId = $_POST['eventId'] ?? '';

        if (empty($eventId)) {
            http_response_code(400);
            echo json_encode(['error' => 'Event ID is required']);
            return;
        }

        $eventRepo = EventRepository::getInstance();
        $event = $eventRepo->findById($eventId);

        if (!$event) {
            http_response_code(404);
            echo json_encode(['error' => 'Event not found']);
            return;
        }

        $eventRepo->delete($eventId);

        echo json_encode(['success' => true]);
    }

    // Metoda akceptująca oczekujące wydarzenie (tylko dla administratorów)
    public function acceptEvent()
    {
        $this->requireAdmin();

        // Pobranie ID wydarzenia z POST
        $eventId = $_POST['eventId'] ?? '';

        // Walidacja - sprawdzenie czy ID wydarzenia zostało podane
        if (empty($eventId)) {
            http_response_code(400);
            echo json_encode(['error' => 'Event ID is required']);
            return;
        }

        // Pobranie instancji repozytorium wydarzeń
        $eventRepo = EventRepository::getInstance();
        // Sprawdzenie czy wydarzenie istnieje
        $event = $eventRepo->findById($eventId);

        // Jeśli wydarzenie nie istnieje, zwróć błąd 404
        if (!$event) {
            http_response_code(404);
            echo json_encode(['error' => 'Event not found']);
            return;
        }

        // Sprawdzenie czy wydarzenie jest w statusie oczekującym
        if ($event->status !== EventStatus::PENDING) {
            http_response_code(400);
            echo json_encode(['error' => 'Event is not pending']);
            return;
        }

        // Zmiana statusu na aktywny i zapisanie w bazie danych
        $event->status = EventStatus::ACTIVE;
        $eventRepo->save($event);

        // Zwrócenie odpowiedzi JSON z sukcesem
        echo json_encode(['success' => true]);
    }
}
