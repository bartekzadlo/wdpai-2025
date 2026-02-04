<?php

require_once __DIR__ . '/../models/Event.php';
require_once __DIR__ . '/../database/Database.php';

class EventRepository
{
    private PDO $db;
    private static ?EventRepository $instance = null;

    private function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public static function getInstance(): EventRepository
    {
        if (self::$instance === null) {
            self::$instance = new EventRepository();
        }
        return self::$instance;
    }

    public function findAll(): array
    {
        $stmt = $this->db->query("
            SELECT 
                e.id,
                e.title,
                e.location,
                e.date,
                e.created_at as \"createdAt\",
                e.image_url as \"imageUrl\",
                e.description,
                e.status,
                COUNT(DISTINCT uei.id) as \"interestCount\"
            FROM events e
            LEFT JOIN user_event_interests uei ON e.id = uei.event_id
            GROUP BY e.id, e.title, e.location, e.date, e.created_at, e.image_url, e.description, e.status
            ORDER BY e.created_at DESC
        ");
        
        $events = [];
        while ($row = $stmt->fetch()) {
            $row['interestCount'] = (int)$row['interestCount'];
            $events[] = Event::fromArray($row);
        }
        return $events;
    }

    public function findById(string $id): ?Event
    {
        $stmt = $this->db->prepare("
            SELECT 
                e.id,
                e.title,
                e.location,
                e.date,
                e.created_at as \"createdAt\",
                e.image_url as \"imageUrl\",
                e.description,
                e.status,
                COUNT(DISTINCT uei.id) as \"interestCount\"
            FROM events e
            LEFT JOIN user_event_interests uei ON e.id = uei.event_id
            WHERE e.id = :id
            GROUP BY e.id, e.title, e.location, e.date, e.created_at, e.image_url, e.description, e.status
        ");
        $stmt->execute([':id' => $id]);
        
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        
        $row['interestCount'] = (int)$row['interestCount'];
        return Event::fromArray($row);
    }

    public function save(Event $event): void
    {
        $existing = $this->findById($event->id);
        
        if ($existing) {
            // UPDATE
            $stmt = $this->db->prepare("
                UPDATE events SET
                    title = :title,
                    location = :location,
                    date = :date,
                    image_url = :image_url,
                    description = :description,
                    status = :status
                WHERE id = :id
            ");
            
            $stmt->execute([
                ':id' => $event->id,
                ':title' => $event->title,
                ':location' => $event->location,
                ':date' => $event->date,
                ':image_url' => $event->imageUrl,
                ':description' => $event->description,
                ':status' => $event->status
            ]);
        } else {
            // INSERT
            $stmt = $this->db->prepare("
                INSERT INTO events (id, title, location, date, created_at, image_url, description, status)
                VALUES (:id, :title, :location, :date, :created_at, :image_url, :description, :status)
            ");
            
            $stmt->execute([
                ':id' => $event->id,
                ':title' => $event->title,
                ':location' => $event->location,
                ':date' => $event->date,
                ':created_at' => $event->createdAt,
                ':image_url' => $event->imageUrl,
                ':description' => $event->description,
                ':status' => $event->status
            ]);
        }
    }

    public function delete(string $id): void
    {
        $stmt = $this->db->prepare("DELETE FROM events WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }
}
