<?php

require_once 'AppController.php';
require_once __DIR__ . '/../repository/UserEventInterestRepository.php';

class ApiController extends AppController
{
    public function toggleInterest()
    {
        session_start();
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $userId = $_SESSION['user']['id'];
        $eventId = $_POST['eventId'] ?? '';

        if (empty($eventId)) {
            http_response_code(400);
            echo json_encode(['error' => 'Event ID is required']);
            return;
        }

        $interestRepo = UserEventInterestRepository::getInstance();
        $isInterested = $interestRepo->toggleInterest($userId, $eventId);
        $interestCount = $interestRepo->getInterestCount($eventId);

        echo json_encode([
            'isInterested' => $isInterested,
            'interestCount' => $interestCount
        ]);
    }

    public function getInterestStatus()
    {
        session_start();
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $userId = $_SESSION['user']['id'];
        $eventId = $_GET['eventId'] ?? '';

        if (empty($eventId)) {
            http_response_code(400);
            echo json_encode(['error' => 'Event ID is required']);
            return;
        }

        $interestRepo = UserEventInterestRepository::getInstance();
        $isInterested = $interestRepo->isInterested($userId, $eventId);
        $interestCount = $interestRepo->getInterestCount($eventId);

        echo json_encode([
            'isInterested' => $isInterested,
            'interestCount' => $interestCount
        ]);
    }
}
