<?php

require_once __DIR__ . '/../models/UserEventInterest.php';
require_once __DIR__ . '/../database/Database.php';

class UserEventInterestRepository
{
    private PDO $db;
    private static ?UserEventInterestRepository $instance = null;

    private function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public static function getInstance(): UserEventInterestRepository
    {
        if (self::$instance === null) {
            self::$instance = new UserEventInterestRepository();
        }
        return self::$instance;
    }

    public function findAll(): array
    {
        $stmt = $this->db->query("
            SELECT user_id as \"userId\", event_id as \"eventId\"
            FROM user_event_interests
            ORDER BY created_at DESC
        ");
        
        $interests = [];
        while ($row = $stmt->fetch()) {
            $interests[] = UserEventInterest::fromArray($row);
        }
        return $interests;
    }

    public function toggleInterest(string $userId, string $eventId): bool
    {
        // Sprawdź czy zainteresowanie już istnieje
        $stmt = $this->db->prepare("
            SELECT id FROM user_event_interests
            WHERE user_id = :user_id AND event_id = :event_id
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':event_id' => $eventId
        ]);

        $exists = $stmt->fetch();

        if ($exists) {
            // Usuń zainteresowanie
            $stmt = $this->db->prepare("
                DELETE FROM user_event_interests
                WHERE user_id = :user_id AND event_id = :event_id
            ");
            $stmt->execute([
                ':user_id' => $userId,
                ':event_id' => $eventId
            ]);
        } else {
            // Dodaj zainteresowanie
            $stmt = $this->db->prepare("
                INSERT INTO user_event_interests (user_id, event_id)
                VALUES (:user_id, :event_id)
            ");
            $stmt->execute([
                ':user_id' => $userId,
                ':event_id' => $eventId
            ]);
        }

        // Zwróć aktualny stan zainteresowania
        return $this->isInterested($userId, $eventId);
    }

    public function isInterested(string $userId, string $eventId): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM user_event_interests
            WHERE user_id = :user_id AND event_id = :event_id
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':event_id' => $eventId
        ]);
        
        return $stmt->fetchColumn() > 0;
    }

    public function getInterestCount(string $eventId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM user_event_interests
            WHERE event_id = :event_id
        ");
        $stmt->execute([':event_id' => $eventId]);
        
        return (int)$stmt->fetchColumn();
    }

    public function findByUserId(string $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT user_id as \"userId\", event_id as \"eventId\"
            FROM user_event_interests
            WHERE user_id = :user_id
            ORDER BY created_at DESC
        ");
        $stmt->execute([':user_id' => $userId]);
        
        $interests = [];
        while ($row = $stmt->fetch()) {
            $interests[] = UserEventInterest::fromArray($row);
        }
        return $interests;
    }

    public function findByEventId(string $eventId): array
    {
        $stmt = $this->db->prepare("
            SELECT user_id as \"userId\", event_id as \"eventId\"
            FROM user_event_interests
            WHERE event_id = :event_id
            ORDER BY created_at DESC
        ");
        $stmt->execute([':event_id' => $eventId]);
        
        $interests = [];
        while ($row = $stmt->fetch()) {
            $interests[] = UserEventInterest::fromArray($row);
        }
        return $interests;
    }
}
