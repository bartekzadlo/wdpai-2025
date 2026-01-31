<?php

require_once __DIR__ . '/../models/Event.php';

class EventRepository
{
    private const EVENTS_FILE = __DIR__ . '/../../storage/events.json';
    private static ?EventRepository $instance = null;

    private function __construct() {}

    public static function getInstance(): EventRepository
    {
        if (self::$instance === null) {
            self::$instance = new EventRepository();
        }
        return self::$instance;
    }

    public function findAll(): array
    {
        $data = $this->loadEvents();
        return array_map([Event::class, 'fromArray'], $data);
    }

    public function findById(string $id): ?Event
    {
        $events = $this->findAll();
        foreach ($events as $event) {
            if ($event->id === $id) {
                return $event;
            }
        }
        return null;
    }

    public function save(Event $event): void
    {
        $events = $this->findAll();
        $found = false;
        foreach ($events as &$e) {
            if ($e->id === $event->id) {
                $e = $event;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $events[] = $event;
        }
        $this->saveEvents(array_map(fn($e) => $e->toArray(), $events));
    }

    public function delete(string $id): void
    {
        $events = $this->findAll();
        $events = array_filter($events, fn($e) => $e->id !== $id);
        $this->saveEvents(array_map(fn($e) => $e->toArray(), $events));
    }

    private function loadEvents(): array
    {
        if (!file_exists(self::EVENTS_FILE)) {
            return [];
        }
        $json = file_get_contents(self::EVENTS_FILE);
        return json_decode($json, true) ?: [];
    }

    private function saveEvents(array $events): void
    {
        $dir = dirname(self::EVENTS_FILE);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents(self::EVENTS_FILE, json_encode($events, JSON_PRETTY_PRINT));
    }
}
