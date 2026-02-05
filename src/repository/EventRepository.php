<?php

require_once 'BaseRepository.php';
require_once __DIR__ . '/../models/Event.php';

class EventRepository extends BaseRepository
{

    /**
     * Pobiera wszystkie wydarzenia używając WIDOKU v_event_statistics (JOIN 4 tabel)
     * Widok łączy: events + user_event_interests + event_categories + categories
     */
    public function findAll(): array
    {
        $stmt = $this->db->query("
            SELECT 
                id,
                title,
                location,
                date,
                created_at as \"createdAt\",
                image_url as \"imageUrl\",
                description,
                status,
                total_interested_users as \"interestCount\",
                categories,
                category_count
            FROM v_event_statistics
            ORDER BY created_at DESC
        ");
        
        $events = [];
        while ($row = $stmt->fetch()) {
            $row['interestCount'] = (int)$row['interestCount'];
            $events[] = Event::fromArray($row);
        }
        return $events;
    }

    /**
     * Pobiera szczegóły wydarzenia używając WIDOKU v_event_statistics
     */
    public function findById(string $id): ?Event
    {
        $stmt = $this->db->prepare("
            SELECT 
                id,
                title,
                location,
                date,
                created_at as \"createdAt\",
                image_url as \"imageUrl\",
                description,
                status,
                total_interested_users as \"interestCount\",
                confirmed_participants,
                interested_users,
                maybe_users,
                categories,
                category_count
            FROM v_event_statistics
            WHERE id = :id
        ");
        $stmt->execute([':id' => $id]);
        
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        
        $row['interestCount'] = (int)$row['interestCount'];
        return Event::fromArray($row);
    }

    /**
     * Pobiera wszystkie kategorie dla formularza
     */
    public function getAllCategories(): array
    {
        $stmt = $this->db->query("
            SELECT id, name, description
            FROM categories
            ORDER BY name
        ");
        
        return $stmt->fetchAll();
    }

    /**
     * Tworzy wydarzenie z kategoriami używając TRANSAKCJI READ COMMITTED
     * Relacja N:M: events ↔ event_categories ↔ categories
     */
    public function createEventWithCategories(Event $event, array $categoryIds): bool
    {
        try {
            // Ustawienie poziomu izolacji READ COMMITTED
            $this->db->exec("SET TRANSACTION ISOLATION LEVEL READ COMMITTED");
            $this->db->beginTransaction();
            
            // 1. Wstawienie wydarzenia
            $stmt = $this->db->prepare("
                INSERT INTO events (id, title, location, date, created_at, image_url, description, status, max_participants)
                VALUES (:id, :title, :location, :date, :created_at, :image_url, :description, :status, :max_participants)
            ");
            
            $stmt->execute([
                ':id' => $event->id,
                ':title' => $event->title,
                ':location' => $event->location,
                ':date' => $event->date,
                ':created_at' => $event->createdAt,
                ':image_url' => $event->imageUrl,
                ':description' => $event->description,
                ':status' => $event->status ?? 'active',
                ':max_participants' => $event->maxParticipants ?? null
            ]);
            
            // 2. Dodanie kategorii (relacja N:M)
            if (!empty($categoryIds)) {
                $stmt = $this->db->prepare("
                    INSERT INTO event_categories (event_id, category_id)
                    VALUES (:event_id, :category_id)
                ");
                
                foreach ($categoryIds as $categoryId) {
                    $stmt->execute([
                        ':event_id' => $event->id,
                        ':category_id' => $categoryId
                    ]);
                }
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Błąd podczas tworzenia wydarzenia: " . $e->getMessage());
            return false;
        }
    }

    public function save(Event $event): void
    {
        $existing = $this->findById($event->id);
        
        if ($existing) {
            // UPDATE - trigger automatycznie zaktualizuje updated_at
            $stmt = $this->db->prepare("
                UPDATE events SET
                    title = :title,
                    location = :location,
                    date = :date,
                    image_url = :image_url,
                    description = :description,
                    status = :status,
                    max_participants = :max_participants
                WHERE id = :id
            ");
            
            $stmt->execute([
                ':id' => $event->id,
                ':title' => $event->title,
                ':location' => $event->location,
                ':date' => $event->date,
                ':image_url' => $event->imageUrl,
                ':description' => $event->description,
                ':status' => $event->status,
                ':max_participants' => $event->maxParticipants ?? null
            ]);
        } else {
            // INSERT - dla pojedynczego wydarzenia bez kategorii
            $stmt = $this->db->prepare("
                INSERT INTO events (id, title, location, date, created_at, image_url, description, status, max_participants)
                VALUES (:id, :title, :location, :date, :created_at, :image_url, :description, :status, :max_participants)
            ");
            
            $stmt->execute([
                ':id' => $event->id,
                ':title' => $event->title,
                ':location' => $event->location,
                ':date' => $event->date,
                ':created_at' => $event->createdAt,
                ':image_url' => $event->imageUrl,
                ':description' => $event->description,
                ':status' => $event->status,
                ':max_participants' => $event->maxParticipants ?? null
            ]);
        }
    }

    /**
     * Usuwa wydarzenie - CASCADE automatycznie usunie powiązania
     */
    public function delete(string $id): void
    {
        $stmt = $this->db->prepare("DELETE FROM events WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    /**
     * Pobiera kategorie wydarzenia
     */
    public function getEventCategories(string $eventId): array
    {
        $stmt = $this->db->prepare("
            SELECT c.id, c.name, c.description
            FROM categories c
            INNER JOIN event_categories ec ON c.id = ec.category_id
            WHERE ec.event_id = :event_id
            ORDER BY c.name
        ");
        $stmt->execute([':event_id' => $eventId]);

        return $stmt->fetchAll();
    }

    /**
     * Pobiera statystyki kategorii - najpierw próbuje użyć widoku, jeśli nie istnieje używa bezpośredniego zapytania
     * Łączy: categories + event_categories + events + user_event_interests
     */
    public function getCategoryStatistics(): array
    {
        try {
            // Próba użycia widoku v_category_statistics
            $stmt = $this->db->query("
                SELECT
                    id,
                    name,
                    description,
                    total_events,
                    total_interested_users
                FROM v_category_statistics
                ORDER BY total_events DESC
            ");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            // Fallback - bezpośrednie zapytanie jeśli widok nie istnieje
            $stmt = $this->db->query("
                SELECT
                    c.id,
                    c.name,
                    c.description,
                    COUNT(DISTINCT ec.event_id) AS total_events,
                    COUNT(DISTINCT uei.user_id) AS total_interested_users
                FROM categories c
                LEFT JOIN event_categories ec ON c.id = ec.category_id
                LEFT JOIN events e ON ec.event_id = e.id
                LEFT JOIN user_event_interests uei ON e.id = uei.event_id
                GROUP BY c.id, c.name, c.description
                ORDER BY total_events DESC
            ");
            return $stmt->fetchAll();
        }
    }
}
