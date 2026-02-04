<?php

require_once 'AppController.php';

class DefaultController extends AppController {

    public function index() {
        session_start();
        if (!isset($_SESSION['user'])) {
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
            return;
        }

        require_once __DIR__ . '/../repository/EventRepository.php';
        require_once __DIR__ . '/../repository/UserEventInterestRepository.php';

        $eventRepository = EventRepository::getInstance();
        $interestRepository = UserEventInterestRepository::getInstance();

        $events = $eventRepository->findAll();

        // Update interest counts and user interest status
        foreach ($events as $event) {
            $event->interestCount = $interestRepository->getInterestCount($event->id);
            if (isset($_SESSION['user'])) {
                $event->isInterested = $interestRepository->isInterested($_SESSION['user']['id'], $event->id);
            } else {
                $event->isInterested = false;
            }
            // Set status for each event, but keep PENDING as is
            if ($event->status !== EventStatus::PENDING) {
                $currentDate = date('d.m.Y');
                $event->status = (strtotime($event->date) >= strtotime($currentDate)) ? EventStatus::ACTIVE : EventStatus::INACTIVE;
            }
        }

        // Filter to show only active events, but include pending for admin
        $events = array_filter($events, function($event) {
            return $event->status === EventStatus::ACTIVE || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin' && $event->status === EventStatus::PENDING);
        });

        $this->render('main', ['events' => $events]);
    }

    public function dashboard() {
        session_start();

        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
            return;
        }

        require_once __DIR__ . '/../repository/UserRepository.php';
        require_once __DIR__ . '/../repository/EventRepository.php';
        require_once __DIR__ . '/../repository/UserEventInterestRepository.php';

        $userRepository = UserRepository::getInstance();
        $eventRepository = EventRepository::getInstance();
        $interestRepository = UserEventInterestRepository::getInstance();

        $userCount = count($userRepository->findAll());
        $eventCount = count($eventRepository->findAll());
        $interestCount = count($interestRepository->findAll());

        // Get last 3 events by creation date
        $allEvents = $eventRepository->findAll();
        usort($allEvents, function($a, $b) {
            return strtotime($b->createdAt) <=> strtotime($a->createdAt);
        });
        $recentEvents = array_slice($allEvents, 0, 3);

        // Determine status for each event, but keep PENDING as is
        $currentDate = date('d.m.Y');
        foreach ($recentEvents as $event) {
            if ($event->status !== EventStatus::PENDING) {
                $event->status = (strtotime($event->date) >= strtotime($currentDate)) ? 'AKTYWNE' : 'NIEAKTYWNE';
            }
        }

        $this->render('dashboard', [
            'userCount' => $userCount,
            'eventCount' => $eventCount,
            'interestCount' => $interestCount,
            'recentEvents' => $recentEvents
        ]);
    }

    public function addEvent() {
        session_start();

        if (!isset($_SESSION['user'])) {
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
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
                // Create new event
                require_once __DIR__ . '/../repository/EventRepository.php';
                require_once __DIR__ . '/../models/Event.php';
                require_once __DIR__ . '/../models/EventStatus.php';

                $eventRepository = EventRepository::getInstance();

                // Generate new ID
                $allEvents = $eventRepository->findAll();
                $maxId = 0;
                foreach ($allEvents as $event) {
                    if (is_numeric($event->id) && $event->id > $maxId) {
                        $maxId = $event->id;
                    }
                }
                $newId = $maxId + 1;

                // Set status based on user role
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

                $eventRepository->save($newEvent);

                // Redirect back to dashboard for admin, main for user
                $url = "http://$_SERVER[HTTP_HOST]";
                $redirectUrl = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') ? '/dashboard' : '/main';
                header("Location: {$url}{$redirectUrl}");
                return;
            } else {
                // Render form with errors
                $this->render('add-event', ['errors' => $errors, 'formData' => $_POST]);
                return;
            }
        }

        // Render form based on role
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            $this->render('add-event');
        } else {
            $this->render('add-event-user');
        }
    }

    public function profile() {
        session_start();

        if (!isset($_SESSION['user'])) {
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
            return;
        }

        require_once __DIR__ . '/../repository/EventRepository.php';
        require_once __DIR__ . '/../repository/UserRepository.php';
        require_once __DIR__ . '/../repository/UserFriendRepository.php';

        $userId = $_SESSION['user']['id'];
        $userRepo = UserRepository::getInstance();
        $user = $userRepo->findById($userId);

        // Get user's interested events
        $interestRepo = UserEventInterestRepository::getInstance();
        $userInterests = $interestRepo->findByUserId($userId);
        $interestedEventIds = array_map(fn($interest) => $interest->eventId, $userInterests);

        $eventRepo = EventRepository::getInstance();
        $interestedEvents = [];
        foreach ($interestedEventIds as $eventId) {
            $event = $eventRepo->findById($eventId);
            if ($event) {
                $interestedEvents[] = $event;
            }
        }

        // Get user's friends
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

    public function eventDetails() {
        session_start();
        if (!isset($_SESSION['user'])) {
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
        require_once __DIR__ . '/../repository/UserEventInterestRepository.php';

        $eventRepository = EventRepository::getInstance();
        $interestRepository = UserEventInterestRepository::getInstance();

        $event = $eventRepository->findById($eventId);
        if (!$event) {
            http_response_code(404);
            include 'public/views/404.html';
            return;
        }

        $event->interestCount = $interestRepository->getInterestCount($event->id);
        if (isset($_SESSION['user'])) {
            $event->isInterested = $interestRepository->isInterested($_SESSION['user']['id'], $event->id);
        } else {
            $event->isInterested = false;
        }
        // Set status for the event, but keep PENDING as is
        if ($event->status !== EventStatus::PENDING) {
            $currentDate = date('d.m.Y');
            $event->status = (strtotime($event->date) >= strtotime($currentDate)) ? 'AKTYWNE' : 'NIEAKTYWNE';
        }

        $this->render('event-details', ['event' => $event]);
    }
}
