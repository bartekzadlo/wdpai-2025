<?php

require_once 'BaseRepository.php';
require_once __DIR__ . '/../models/User.php';

class UserRepository extends BaseRepository
{

    /**
     * Pobiera wszystkich użytkowników używając WIDOKU v_user_activity (JOIN 3 tabel)
     * Widok łączy: users + user_profiles + user_event_interests
     */
    public function findAll(): array
    {
        $stmt = $this->db->query("
            SELECT 
                id, email, name, surname, phone, city, role,
                profile_picture as \"profilePicture\",
                bio,
                login_count,
                total_events_interested,
                events_attending
            FROM v_user_activity
            ORDER BY created_at DESC
        ");
        
        $users = [];
        while ($row = $stmt->fetch()) {
            $row['consents'] = [];
            $users[] = User::fromArray($row);
        }
        return $users;
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->db->prepare("
            SELECT id, email, password, role, name, surname, phone, city, 
                   profile_picture as \"profilePicture\", consents
            FROM users
            WHERE LOWER(email) = LOWER(:email)
        ");
        $stmt->execute([':email' => $email]);
        
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        
        $row['consents'] = json_decode($row['consents'], true) ?? [];
        return User::fromArray($row);
    }

    public function findById(string $id): ?User
    {
        $stmt = $this->db->prepare("
            SELECT id, email, password, role, name, surname, phone, city, 
                   profile_picture as \"profilePicture\", consents
            FROM users
            WHERE id = :id
        ");
        $stmt->execute([':id' => $id]);
        
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        
        $row['consents'] = json_decode($row['consents'], true) ?? [];
        return User::fromArray($row);
    }

    /**
     * Pobiera profil użytkownika (relacja 1:1)
     */
    public function getUserProfile(string $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT user_id, bio, last_login, login_count, preferences
            FROM user_profiles
            WHERE user_id = :user_id
        ");
        $stmt->execute([':user_id' => $userId]);
        
        return $stmt->fetch();
    }

    /**
     * Aktualizuje profil użytkownika (relacja 1:1)
     */
    public function updateUserProfile(string $userId, array $profileData): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE user_profiles
                SET bio = :bio,
                    preferences = :preferences
                WHERE user_id = :user_id
            ");
            
            $preferences = isset($profileData['preferences']) && is_array($profileData['preferences']) 
                ? json_encode($profileData['preferences']) 
                : '{}';
            
            return $stmt->execute([
                ':user_id' => $userId,
                ':bio' => $profileData['bio'] ?? null,
                ':preferences' => $preferences
            ]);
        } catch (Exception $e) {
            error_log("Błąd podczas aktualizacji profilu: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Pobiera wydarzenia użytkownika używając FUNKCJI get_user_interested_events()
     * Funkcja zwraca JOIN z events + user_event_interests + categories
     */
    public function getUserInterestedEvents(string $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM get_user_interested_events(:user_id)
        ");
        $stmt->execute([':user_id' => $userId]);
        
        return $stmt->fetchAll();
    }

    /**
     * Pobiera aktywność użytkownika z WIDOKU v_user_activity
     */
    public function getUserActivity(string $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM v_user_activity
            WHERE id = :id
        ");
        $stmt->execute([':id' => $userId]);
        
        return $stmt->fetch();
    }

    /**
     * Rejestruje użytkownika z profilem używając TRANSAKCJI SERIALIZABLE
     * Relacja 1:1: users ↔ user_profiles (trigger automatycznie tworzy profil)
     */
    public function registerUserWithProfile(User $user, array $profileData = []): bool
    {
        try {
            // Ustawienie poziomu izolacji SERIALIZABLE
            $this->db->exec("SET TRANSACTION ISOLATION LEVEL SERIALIZABLE");
            $this->db->beginTransaction();
            
            // 1. Sprawdzenie unikalności emaila (z blokowaniem)
            $checkStmt = $this->db->prepare("
                SELECT COUNT(*) FROM users 
                WHERE LOWER(email) = LOWER(:email) FOR UPDATE
            ");
            $checkStmt->execute([':email' => $user->email]);
            
            if ($checkStmt->fetchColumn() > 0) {
                $this->db->rollBack();
                return false;
            }
            
            // 2. Wstawienie użytkownika
            $stmt = $this->db->prepare("
                INSERT INTO users (id, email, password, role, name, surname, phone, city, profile_picture, consents)
                VALUES (:id, :email, :password, :role, :name, :surname, :phone, :city, :profile_picture, :consents)
            ");
            
            $consents = is_array($user->consents) ? json_encode($user->consents) : '{}';
            
            $stmt->execute([
                ':id' => $user->id,
                ':email' => $user->email,
                ':password' => $user->password,
                ':role' => $user->role ?? 'user',
                ':name' => $user->name,
                ':surname' => $user->surname,
                ':phone' => $user->phone,
                ':city' => $user->city,
                ':profile_picture' => $user->profilePicture,
                ':consents' => $consents
            ]);
            
            // 3. Trigger automatycznie utworzy profil (relacja 1:1)
            // trg_update_user_login -> update_user_login()
            
            // 4. Aktualizacja profilu jeśli podano dodatkowe dane
            if (!empty($profileData['bio'])) {
                $profileStmt = $this->db->prepare("
                    UPDATE user_profiles
                    SET bio = :bio
                    WHERE user_id = :user_id
                ");
                
                $profileStmt->execute([
                    ':user_id' => $user->id,
                    ':bio' => $profileData['bio']
                ]);
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Błąd podczas rejestracji użytkownika: " . $e->getMessage());
            return false;
        }
    }

    public function save(User $user): void
    {
        $existing = $this->findById($user->id);
        
        if ($existing) {
            // UPDATE - trigger automatycznie zaktualizuje updated_at
            $stmt = $this->db->prepare("
                UPDATE users SET
                    email = :email,
                    password = :password,
                    role = :role,
                    name = :name,
                    surname = :surname,
                    phone = :phone,
                    city = :city,
                    profile_picture = :profile_picture,
                    consents = :consents
                WHERE id = :id
            ");
        } else {
            // INSERT
            $stmt = $this->db->prepare("
                INSERT INTO users (id, email, password, role, name, surname, phone, city, profile_picture, consents)
                VALUES (:id, :email, :password, :role, :name, :surname, :phone, :city, :profile_picture, :consents)
            ");
        }
        
        $consents = is_array($user->consents) ? json_encode($user->consents) : '{}';
        
        $stmt->execute([
            ':id' => $user->id,
            ':email' => $user->email,
            ':password' => $user->password,
            ':role' => $user->role,
            ':name' => $user->name,
            ':surname' => $user->surname,
            ':phone' => $user->phone,
            ':city' => $user->city,
            ':profile_picture' => $user->profilePicture,
            ':consents' => $consents
        ]);
    }

    /**
     * Usuwa użytkownika - CASCADE automatycznie usunie profil i zainteresowania
     */
    public function delete(string $id): void
    {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    public function emailExists(string $email): bool
    {
        return $this->findByEmail($email) !== null;
    }
}
