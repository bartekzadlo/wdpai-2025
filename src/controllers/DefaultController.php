<?php

require_once 'AppController.php';

// Główny kontroler aplikacji - obsługuje większość stron
class DefaultController extends AppController {

    // Główna strona - pokazuje listę wydarzeń użytkownikowi
    public function index() {
        // Rozpoczęcie sesji
        session_start();
        // Sprawdzenie czy użytkownik jest zalogowany
        if (!isset($_SESSION['user'])) {
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
            return;
        }

        // Wczytanie repozytoriów wydarzeń i zainteresowań
        require_once __DIR__ . '/../repository/EventRepository.php';
        require_once __DIR__ . '/../repository/UserEventInterestRepository.php';

        // Pobranie instancji repozytoriów
        $eventRepository = EventRepository::getInstance();
        $interestRepository = UserEventInterestRepository::getInstance();

        // Pobranie wszystkich wydarzeń
        $events = $eventRepository->findAll();

        // Aktualizacja liczby zainteresowań i statusu zainteresowania dla każdego wydarzenia
        foreach ($events as $event) {
            $event->interestCount = $interestRepository->getInterestCount($event->id);
            if (isset($_SESSION['user'])) {
                $event->isInterested = $interestRepository->isInterested($_SESSION['user']['id'], $event->id);
            } else {
                $event->isInterested = false;
            }
            // Ustawienie statusu dla każdego wydarzenia, ale pozostawienie PENDING bez zmian
            if ($event->status !== EventStatus::PENDING) {
                $currentDate = date('d.m.Y');
                $event->status = (strtotime($event->date) >= strtotime($currentDate)) ? EventStatus::ACTIVE : EventStatus::INACTIVE;
            }
        }

        // Filtrowanie wydarzeń - pokazanie tylko aktywnych, ale admin zobaczy też pending
        $events = array_filter($events, function($event) {
            return $event->status === EventStatus::ACTIVE || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin' && $event->status === EventStatus::PENDING);
        });

        // Renderowanie widoku głównego z listą wydarzeń
        $this->render('main', ['events' => $events]);
    }

    // Panel admina - pokazuje statystyki i ostatnie wydarzenia
    public function dashboard() {
        // Rozpoczęcie sesji
        session_start();

        // Sprawdzenie czy użytkownik jest administratorem
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
            return;
        }

        // Wczytanie repozytoriów użytkowników, wydarzeń i zainteresowań
        require_once __DIR__ . '/../repository/UserRepository.php';
        require_once __DIR__ . '/../repository/EventRepository.php';
        require_once __DIR__ . '/../repository/UserEventInterestRepository.php';

        // Pobranie instancji repozytoriów
        $userRepository = UserRepository::getInstance();
        $eventRepository = EventRepository::getInstance();
        $interestRepository = UserEventInterestRepository::getInstance();

        // Obliczenie liczby użytkowników, wydarzeń i zainteresowań
        $userCount = count($userRepository->findAll());
        $eventCount = count($eventRepository->findAll());
        $interestCount = count($interestRepository->findAll());

        // Pobranie ostatnich 3 wydarzeń według daty utworzenia
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
    public function events() {
        // Rozpoczęcie sesji
        session_start();

        // Sprawdzenie czy użytkownik jest administratorem
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
            return;
        }

        // Wczytanie repozytorium wydarzeń i modelu statusu wydarzeń
        require_once __DIR__ . '/../repository/EventRepository.php';
        require_once __DIR__ . '/../models/EventStatus.php';

        // Pobranie instancji repozytorium wydarzeń
        $eventRepository = EventRepository::getInstance();

        // Pobranie wszystkich wydarzeń posortowanych malejąco według daty utworzenia
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
    public function users() {
        // Rozpoczęcie sesji
        session_start();

        // Sprawdzenie czy użytkownik jest administratorem
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
            return;
        }

        // Wczytanie repozytorium użytkowników
        require_once __DIR__ . '/../repository/UserRepository.php';

        // Pobranie instancji repozytorium użytkowników
        $userRepository = UserRepository::getInstance();
        // Pobranie wszystkich użytkowników
        $users = $userRepository->findAll();

        // Renderowanie widoku administracyjnego użytkowników
        $this->render('admin-users', ['users' => $users]);
    }

    // Metoda obsługująca dodawanie nowego wydarzenia
    public function addEvent() {
        // Rozpoczęcie sesji
        session_start();

        // Sprawdzenie czy użytkownik jest zalogowany
        if (!isset($_SESSION['user'])) {
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
            return;
        }

        // Sprawdzenie czy żądanie to POST (wysłanie formularza)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Obsługa wysłania formularza - pobranie danych z POST
            $title = trim($_POST['title'] ?? '');
            $location = trim($_POST['location'] ?? '');
            $date = trim($_POST['date'] ?? '');
            $imageUrl = trim($_POST['imageUrl'] ?? '');
            $description = trim($_POST['description'] ?? '');

            // Walidacja danych formularza
            $errors = [];
            if (empty($title)) {
                $errors[] = 'Nazwa wydarzenia jest wymagana';
            }
            if (empty($location)) {
                $errors[] = 'Lokalizacja jest wymagana';
            }
            if (empty($date)) {
                $errors[] = 'Data jest wymagana';
            } elseif (!preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $date)) {
                $errors[] = 'Data musi być w formacie DD.MM.YYYY';
            } else {
                // Dodatkowa walidacja formatu daty i przyszłej daty
                $dateObj = DateTime::createFromFormat('d.m.Y', $date);
                if (!$dateObj) {
                    $errors[] = 'Nieprawidłowy format daty';
                } elseif ($dateObj < new DateTime()) {
                    $errors[] = 'Data musi być w przyszłości';
                }
            }
            if (empty($imageUrl)) {
                $errors[] = 'URL obrazka jest wymagany';
            } elseif (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                $errors[] = 'Nieprawidłowy URL obrazka';
            }

            // Jeśli nie ma błędów, utwórz nowe wydarzenie
            if (empty($errors)) {
                // Tworzenie nowego wydarzenia
                require_once __DIR__ . '/../repository/EventRepository.php';
                require_once __DIR__ . '/../models/Event.php';
                require_once __DIR__ . '/../models/EventStatus.php';

                $eventRepository = EventRepository::getInstance();

                // Generowanie nowego ID
                $allEvents = $eventRepository->findAll();
                $maxId = 0;
                foreach ($allEvents as $event) {
                    if (is_numeric($event->id) && $event->id > $maxId) {
                        $maxId = $event->id;
                    }
                }
                $newId = $maxId + 1;

                // Ustawienie statusu na podstawie roli użytkownika
                $status = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') ? EventStatus::ACTIVE : EventStatus::PENDING;

                $newEvent = new Event(
                    (string)$newId,
                    $title,
                    $location,
                    $date,
                    $imageUrl,
                    $description,
                    0, // interestCount
                    false, // isInterested
                    date('Y-m-d H:i:s'), // createdAt
                    $status
                );

                // Zapisanie nowego wydarzenia
                $eventRepository->save($newEvent);

                // Przekierowanie do dashboard dla admina, do głównej dla użytkownika
                $url = "http://$_SERVER[HTTP_HOST]";
                $redirectUrl = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') ? '/dashboard' : '/main';
                header("Location: {$url}{$redirectUrl}");
                return;
            } else {
                // Renderowanie formularza z błędami
                $this->render('add-event', ['errors' => $errors, 'formData' => $_POST]);
                return;
            }
        }

        // Renderowanie formularza na podstawie roli użytkownika
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            $this->render('add-event');
        } else {
            $this->render('add-event-user');
        }
    }

    // Metoda wyświetlająca profil użytkownika
    public function profile() {
        // Rozpoczęcie sesji
        session_start();

        // Sprawdzenie czy użytkownik jest zalogowany
        if (!isset($_SESSION['user'])) {
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
            return;
        }

        // Wczytanie repozytoriów wydarzeń, użytkowników i znajomych
        require_once __DIR__ . '/../repository/EventRepository.php';
        require_once __DIR__ . '/../repository/UserRepository.php';
        require_once __DIR__ . '/../repository/UserFriendRepository.php';

        // Pobranie ID użytkownika z sesji
        $userId = $_SESSION['user']['id'];
        // Pobranie danych użytkownika
        $userRepo = UserRepository::getInstance();
        $user = $userRepo->findById($userId);

        // Pobranie wydarzeń, które interesują użytkownika
        $interestRepo = UserEventInterestRepository::getInstance();
        $userInterests = $interestRepo->findByUserId($userId);
        $interestedEventIds = array_map(fn($interest) => $interest->eventId, $userInterests);

        // Pobranie szczegółów wydarzeń zainteresowań
        $eventRepo = EventRepository::getInstance();
        $interestedEvents = [];
        foreach ($interestedEventIds as $eventId) {
            $event = $eventRepo->findById($eventId);
            if ($event) {
                $interestedEvents[] = $event;
            }
        }

        // Pobranie znajomych użytkownika
        $friendRepo = UserFriendRepository::getInstance();
        $friends = $friendRepo->findByUserId($userId);
        $friendData = [];
        foreach ($friends as $friend) {
            $friendId = $friend->userId === $userId ? $friend->friendId : $friend->userId;
            $friendUser = $userRepo->findById($friendId);
            if ($friendUser) {
                $friendData[] = $friendUser;
            }
        }
        $friendData = array_filter($friendData, fn($f) => $f !== null);

        // Renderowanie widoku profilu z danymi użytkownika
        $this->render('profile', [
            'user' => $user,
            'interestedEvents' => $interestedEvents,
            'friends' => $friendData
        ]);
    }

    public function editEvent() {
        session_start();

        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
            return;
        }

        $eventId = $_GET['id'] ?? '';
        if (empty($eventId)) {
            http_response_code(404);
            include 'public/views/404.html';
            return;
        }

        require_once __DIR__ . '/../repository/EventRepository.php';

        $eventRepository = EventRepository::getInstance();
        $event = $eventRepository->findById($eventId);

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
            $errors = [];
            if (empty($title)) {
                $errors[] = 'Nazwa wydarzenia jest wymagana';
            }
            if (empty($location)) {
                $errors[] = 'Lokalizacja jest wymagana';
            }
            if (empty($date)) {
                $errors[] = 'Data jest wymagana';
            } elseif (!preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $date)) {
                $errors[] = 'Data musi być w formacie DD.MM.YYYY';
            } else {
                // Validate date format and future date
                $dateObj = DateTime::createFromFormat('d.m.Y', $date);
                if (!$dateObj) {
                    $errors[] = 'Nieprawidłowy format daty';
                } elseif ($dateObj < new DateTime()) {
                    $errors[] = 'Data musi być w przyszłości';
                }
            }
            if (empty($imageUrl)) {
                $errors[] = 'URL obrazka jest wymagany';
            } elseif (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                $errors[] = 'Nieprawidłowy URL obrazka';
            }

            if (empty($errors)) {
                // Update the event
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
                $this->render('edit-event', ['event' => $event, 'errors' => $errors]);
                return;
            }
        }

        // Render form with current event data
        $this->render('edit-event', ['event' => $event]);
    }

    // Metoda wyświetlająca szczegóły pojedynczego wydarzenia
    public function eventDetails() {
        // Rozpoczęcie sesji
        session_start();
        // Sprawdzenie czy użytkownik jest zalogowany
        if (!isset($_SESSION['user'])) {
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
            return;
        }

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

        // Pobranie wydarzenia po ID
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
