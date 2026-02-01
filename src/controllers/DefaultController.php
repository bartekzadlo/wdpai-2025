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
        }

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

        // Determine status for each event
        $currentDate = date('d.m.Y');
        foreach ($recentEvents as $event) {
            $event->status = (strtotime($event->date) >= strtotime($currentDate)) ? 'AKTYWNE' : 'NIEAKTYWNE';
        }

        $this->render('dashboard', [
            'userCount' => $userCount,
            'eventCount' => $eventCount,
            'interestCount' => $interestCount,
            'recentEvents' => $recentEvents
        ]);
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
}
