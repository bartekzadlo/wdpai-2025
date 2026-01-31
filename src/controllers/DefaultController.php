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
        $eventRepository = EventRepository::getInstance();
        $events = $eventRepository->findAll();

        $this->render('main', ['events' => $events]);
    }

    public function dashboard() {
        session_start();

        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
            return;
        }

        $this->render('dashboard');
    }

    public function profile() {
    session_start();

    if (!isset($_SESSION['user'])) {
        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/login");
        return;
    }

    $this->render('profile');
    }   
}
