<?php
session_start();

// Load repositories
require_once '../../src/repository/EventRepository.php';
require_once '../../src/repository/UserEventInterestRepository.php';
require_once '../../src/models/EventStatus.php';

// Get instances
$eventRepository = EventRepository::getInstance();
$interestRepository = UserEventInterestRepository::getInstance();

// Get all events
$events = $eventRepository->findAll();

// Update interest count and status for each event
foreach ($events as $event) {
    $event->interestCount = $interestRepository->getInterestCount($event->id);
    if (isset($_SESSION['user'])) {
        $event->isInterested = $interestRepository->isInterested($_SESSION['user']['id'], $event->id);
    } else {
        $event->isInterested = false;
    }
    // Set status for each event, but leave PENDING unchanged
    if ($event->status !== EventStatus::PENDING) {
        $currentDate = date('d.m.Y');
        $event->status = (strtotime($event->date) >= strtotime($currentDate)) ? EventStatus::ACTIVE : EventStatus::INACTIVE;
    }
}

// Filter events - show only active, but admin sees pending too
$events = array_filter($events, function($event) {
    return $event->status === EventStatus::ACTIVE || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin' && $event->status === EventStatus::PENDING);
});

// Convert to array for JSON encoding
$eventsArray = array_map(function($event) {
    return get_object_vars($event);
}, $events);

echo 'const eventsData = ' . json_encode(array_values($eventsArray)) . ';';
?>
