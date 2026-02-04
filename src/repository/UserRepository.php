<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../database/Database.php';

class UserRepository
{
    private PDO $db;
    private static ?UserRepository $instance = null;

    private function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public static function getInstance(): UserRepository
    {
        if (self::$instance === null) {
            self::$instance = new UserRepository();
        }
        return self::$instance;
    }

    public function findAll(): array
    {
        $stmt = $this->db->query("
            SELECT id, email, password, role, name, surname, phone, city, 
                   profile_picture as \"profilePicture\", consents
            FROM users
            ORDER BY created_at DESC
        ");
        
        $users = [];
        while ($row = $stmt->fetch()) {
            $row['consents'] = json_decode($row['consents'], true) ?? [];
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

    public function save(User $user): void
    {
        $existing = $this->findById($user->id);
        
        if ($existing) {
            // UPDATE
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
