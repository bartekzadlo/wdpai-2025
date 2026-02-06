<?php

require_once 'BaseController.php';
require_once __DIR__ . '/../services/ValidationHelper.php';

// Główny kontroler aplikacji - obsługuje większość stron
class DefaultController extends BaseController {

    // Główna strona - pokazuje listę wydarzeń użytkownikowi
    public function index() {
        $this->requireLogin();

        // Wczytanie repozytoriów wydarzeń i zainteresowań
        require_once __DIR__ . '/../repository/EventRepository.php';
        require_once __DIR__ . '/../repository/UserEventInterestRepository.php';

        // Pobranie instancji repozytoriów
        $eventRepository = EventRepository::getInstance();
        $interestRepository = UserEventInterestRepository::getInstance();

        // Pobranie wszystkich wydarzeń (używa widoku v_event_statistics)
        $events = $eventRepository->findAll();

        // Aktualizacja liczby zainteresowań i statusu zainteresowania dla każdego wydarzenia
        foreach ($events as $event) {
            $event->interestCount = $interestRepository->getInterestCount($event->id);
            if (isset($_SESSION['user'])) {
                $event->isInterested = $interestRepository->isInterested($_SESSION['user']['id'], $event->id);
            } else {
                $event->isInterested = false;
            }
            $event->status = ValidationHelper::setEventStatus($event->date, $event->status);
        }

        // Filtrowanie wydarzeń - pokazanie tylko aktywnych wydarzeń
        $events = array_filter($events, function($event) {
            return $event->status === EventStatus::ACTIVE;
        });

        // Renderowanie widoku głównego z listą wydarzeń
        $this->render('main', ['events' => $events]);
    }

    // Panel admina - pokazuje statystyki i ostatnie wydarzenia
    // Używa WIDOKU v_event_statistics
    public function dashboard() {
        $this->requireAdmin();

        // Wczytanie repozytoriów użytkowników, wydarzeń i zainteresowań
        require_once __DIR__ . '/../repository/UserRepository.php';
        require_once __DIR__ . '/../repository/EventRepository.php';
        require_once __DIR__ . '/../repository/UserEventInterestRepository.php';

        // Pobranie instancji repozytoriów
        $userRepository = UserRepository::getInstance();
        $eventRepository = EventRepository::getInstance();
        $interestRepository = UserEventInterestRepository::getInstance();

        // Obliczenie liczby użytkowników, wydarzeń i zainteresowań
        // Używa widoku v_user_activity
        $userCount = count($userRepository->findAll());
        $eventCount = count($eventRepository->findAll());
        $interestCount = count($interestRepository->findAll());

        // Pobranie ostatnich wydarzeń dla sekcji "ostatnie wydarzenia"
        // Używa widoku v_event_statistics z kategoriami
        $allEvents = $eventRepository->findAll();
        usort($allEvents, function($a, $b) {
            return strtotime($b->createdAt) <=> strtotime($a->createdAt);
        });
        $recentEvents = array_slice($allEvents, 0, 3);

        // Ustalenie statusu dla każdego wydarzenia, ale pozostawienie PENDING bez zmian
        $currentDate = date('d.m.Y');
        foreach ($recentEvents as $event) {
            if ($event->status !== EventStatus::PENDING) {
                $event->status = (strtotime($event->date) >= strtotime($currentDate)) ? 'AKTYWNE' : 'NIEAKTYWNE';
            }
        }

        // Renderowanie widoku dashboard z danymi statystycznymi
        $this->render('dashboard', [
            'userCount' => $userCount,
            'eventCount' => $eventCount,
            'interestCount' => $interestCount,
            'recentEvents' => $recentEvents
        ]);
    }

    // Metoda wyświetlająca wszystkie wydarzenia dla administratora
    // Używa WIDOKU v_event_statistics
    public function events() {
        $this->requireAdmin();

        // Wczytanie repozytorium wydarzeń i modelu statusu wydarzeń
        require_once __DIR__ . '/../repository/EventRepository.php';
        require_once __DIR__ . '/../models/EventStatus.php';

        // Pobranie instancji repozytorium wydarzeń
        $eventRepository = EventRepository::getInstance();

        // Pobranie wszystkich wydarzeń posortowanych malejąco według daty utworzenia
        // Używa widoku v_event_statistics z kategoriami i statystykami
        $allEvents = $eventRepository->findAll();
        usort($allEvents, function($a, $b) {
            return strtotime($b->createdAt) <=> strtotime($a->createdAt);
        });

        // Ustalenie statusu dla każdego wydarzenia, ale pozostawienie PENDING bez zmian
        $currentDate = date('d.m.Y');
        foreach ($allEvents as $event) {
            if ($event->status !== EventStatus::PENDING) {
                $event->status = (strtotime($event->date) >= strtotime($currentDate)) ? 'AKTYWNE' : 'NIEAKTYWNE';
            }
        }

        // Renderowanie widoku administracyjnego wydarzeń
        $this->render('admin-events', ['events' => $allEvents]);
    }

    // Metoda wyświetlająca wszystkich użytkowników dla administratora
    // Używa WIDOKU v_user_activity (JOIN 3 tabel)
    public function users() {
        $this->requireAdmin();

        // Wczytanie repozytorium użytkowników
        require_once __DIR__ . '/../repository/UserRepository.php';

        // Pobranie instancji repozytorium użytkowników
        $userRepository = UserRepository::getInstance();
        // Pobranie wszystkich użytkowników z widoku v_user_activity
        // Widok zawiera bio, login_count, total_events_interested, events_interested
        $users = $userRepository->findAll();

        // Renderowanie widoku administracyjnego użytkowników
        $this->render('admin-users', ['users' => $users]);
    }

    // Metoda obsługująca dodawanie nowego wydarzenia
    // Używa TRANSAKCJI READ COMMITTED
    public function addEvent() {
        $this->requireLogin();

        // Pobranie kategorii dla formularza
        require_once __DIR__ . '/../repository/EventRepository.php';
        $eventRepository = EventRepository::getInstance();
        $categories = $eventRepository->getAllCategories();

        // Sprawdzenie czy żądanie to POST (wysłanie formularza)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Obsługa wysłania formularza - pobranie danych z POST
            $title = trim($_POST['title'] ?? '');
            $location = trim($_POST['location'] ?? '');
            $date = trim($_POST['date'] ?? '');
            $imageUrl = trim($_POST['imageUrl'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $categoryIds = $_POST['categories'] ?? [];

            // Walidacja danych formularza
            $errors = ValidationHelper::validateEventData($_POST);

            // Jeśli nie ma błędów, utwórz nowe wydarzenie
            if (empty($errors)) {
                // Tworzenie nowego wydarzenia z TRANSAKCJĄ
                require_once __DIR__ . '/../models/Event.php';
                require_once __DIR__ . '/../models/EventStatus.php';

                // Generowanie nowego ID
                $allEvents = $eventRepository->findAll();
                $maxId = 0;
                foreach ($allEvents as $event) {
                    if (preg_match('/^event_(\d+)$/', $event->id, $matches)) {
                        $num = (int)$matches[1];
                        if ($num > $maxId) {
                            $maxId = $num;
                        }
                    }
                }
                $newId = 'event_' . ($maxId + 1);

                // Data już w formacie DD.MM.YYYY
                $dateFormatted = $date;

                // Ustawienie statusu na podstawie roli użytkownika
                $status = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') ? EventStatus::ACTIVE : EventStatus::PENDING;

                $newEvent = new Event(
                    $newId,
                    $title,
                    $location,
                    $dateFormatted,
                    date('Y-m-d H:i:s'),
                    $imageUrl,
                    $description,
                    $status
                );

                // Użycie TRANSAKCJI READ COMMITTED do utworzenia wydarzenia z kategoriami
                // Relacja N:M: events ↔ event_categories ↔ categories
                $success = $eventRepository->createEventWithCategories($newEvent, $categoryIds);

                if ($success) {
                    // Przekierowanie po pomyślnym utworzeniu
                    $url = "http://$_SERVER[HTTP_HOST]";
                    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
                        header("Location: {$url}/dashboard");
                    } else {
                        header("Location: {$url}/");
                    }
                    return;
                } else {
                    $errors[] = 'Wystąpił błąd podczas tworzenia wydarzenia';
                }
            }

            // Renderowanie formularza z błędami na podstawie roli użytkownika
            if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
                $this->render('add-event', [
                    'errors' => $errors,
                    'formData' => $_POST,
                    'categories' => $categories
                ]);
            } else {
                $this->render('add-event-user', [
                    'errors' => $errors,
                    'formData' => $_POST,
                    'categories' => $categories
                ]);
            }
            return;
        }

        // Renderowanie formularza na podstawie roli użytkownika
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            $this->render('add-event', ['categories' => $categories]);
        } else {
            $this->render('add-event-user', ['categories' => $categories]);
        }
    }

    // Metoda wyświetlająca profil użytkownika
    // Używa WIDOKU v_user_activity i FUNKCJI get_user_interested_events()
    public function profile() {
        $this->requireLogin();

        // Wczytanie repozytoriów
        require_once __DIR__ . '/../repository/EventRepository.php';
        require_once __DIR__ . '/../repository/UserRepository.php';

        // Pobranie ID użytkownika z sesji
        $userId = $_SESSION['user']['id'];
        
        // Pobranie danych użytkownika z WIDOKU v_user_activity
        $userRepo = UserRepository::getInstance();
        $user = $userRepo->findById($userId);
        $userActivity = $userRepo->getUserActivity($userId);
        
        // Pobranie profilu użytkownika (relacja 1:1)
        $userProfile = $userRepo->getUserProfile($userId);
        
        // Pobranie wydarzeń użytkownika używając FUNKCJI get_user_interested_events()
        // Funkcja zwraca JOIN z events + user_event_interests + categories
        $interestedEvents = $userRepo->getUserInterestedEvents($userId);

        // Renderowanie widoku profilu z danymi użytkownika
        $this->render('profile', [
            'user' => $user,
            'userActivity' => $userActivity,
            'userProfile' => $userProfile,
            'interestedEvents' => $interestedEvents
        ]);
    }

    public function editEvent() {
        $this->requireAdmin();

        $eventId = $_GET['id'] ?? '';
        if (empty($eventId)) {
            http_response_code(404);
            include 'public/views/404.html';
            return;
        }

        require_once __DIR__ . '/../repository/EventRepository.php';

        $eventRepository = EventRepository::getInstance();
        $event = $eventRepository->findById($eventId);
        $categories = $eventRepository->getAllCategories();
        $eventCategories = $eventRepository->getEventCategories($eventId);
        $selectedCategoryIds = array_column($eventCategories, 'id');

        if (!$event) {
            http_response_code(404);
            include 'public/views/404.html';
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Handle form submission
            $title = trim($_POST['title'] ?? '');
            $location = trim($_POST['location'] ?? '');
            $date = trim($_POST['date'] ?? '');
            $imageUrl = trim($_POST['imageUrl'] ?? '');
            $description = trim($_POST['description'] ?? '');

            // Validation
            $errors = ValidationHelper::validateEventData($_POST);

            if (empty($errors)) {
                // Update the event - trigger automatycznie zaktualizuje updated_at
                $event->title = $title;
                $event->location = $location;
                $event->date = $date;
                $event->imageUrl = $imageUrl;
                $event->description = $description;

                $eventRepository->save($event);

                // Redirect back to dashboard
                $url = "http://$_SERVER[HTTP_HOST]";
                header("Location: {$url}/dashboard");
                return;
            } else {
                // Render form with errors
                $this->render('edit-event', [
                    'event' => $event, 
                    'errors' => $errors,
                    'categories' => $categories,
                    'selectedCategoryIds' => $selectedCategoryIds
                ]);
                return;
            }
        }

        // Render form with current event data
        $this->render('edit-event', [
            'event' => $event,
            'categories' => $categories,
            'selectedCategoryIds' => $selectedCategoryIds
        ]);
    }

    // Metoda wyświetlająca szczegóły pojedynczego wydarzenia
    // Używa WIDOKU v_event_statistics
    public function eventDetails() {
        $this->requireLogin();

        // Pobranie ID wydarzenia z parametrów GET
        $eventId = $_GET['id'] ?? '';
        // Sprawdzenie czy ID jest podane
        if (empty($eventId)) {
            http_response_code(404);
            include 'public/views/404.html';
            return;
        }

        // Wczytanie repozytoriów wydarzeń i zainteresowań
        require_once __DIR__ . '/../repository/EventRepository.php';
        require_once __DIR__ . '/../repository/UserEventInterestRepository.php';

        // Pobranie instancji repozytoriów
        $eventRepository = EventRepository::getInstance();
        $interestRepository = UserEventInterestRepository::getInstance();

        // Pobranie wydarzenia po ID z widoku v_event_statistics
        // Zawiera statystyki: total_interested_users, confirmed_participants, categories
        $event = $eventRepository->findById($eventId);
        
        // Sprawdzenie czy wydarzenie istnieje
        if (!$event) {
            http_response_code(404);
            include 'public/views/404.html';
            return;
        }

        // Aktualizacja liczby zainteresowań dla wydarzenia
        $event->interestCount = $interestRepository->getInterestCount($event->id);
        // Sprawdzenie czy użytkownik jest zainteresowany tym wydarzeniem
        if (isset($_SESSION['user'])) {
            $event->isInterested = $interestRepository->isInterested($_SESSION['user']['id'], $event->id);
        } else {
            $event->isInterested = false;
        }
        // Ustawienie statusu dla wydarzenia, ale pozostawienie PENDING bez zmian
        if ($event->status !== EventStatus::PENDING) {
            $currentDate = date('d.m.Y');
            $event->status = (strtotime($event->date) >= strtotime($currentDate)) ? 'AKTYWNE' : 'NIEAKTYWNE';
        }

        // Renderowanie widoku szczegółów wydarzenia
        $this->render('event-details', ['event' => $event]);
    }
}
