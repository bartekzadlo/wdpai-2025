<?php

require_once 'BaseRepository.php';
require_once __DIR__ . '/../models/UserFriend.php';

class UserFriendRepository extends BaseRepository
{

    public function findAll(): array
    {
        $stmt = $this->db->query("
            SELECT 
                id,
                user_id as \"userId\",
                friend_id as \"friendId\",
                status,
                created_at as \"createdAt\"
            FROM user_friends
            ORDER BY created_at DESC
        ");
        
        $friends = [];
        while ($row = $stmt->fetch()) {
            $friends[] = UserFriend::fromArray($row);
        }
        return $friends;
    }

    public function findByUserId(string $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                id,
                user_id as \"userId\",
                friend_id as \"friendId\",
                status,
                created_at as \"createdAt\"
            FROM user_friends
            WHERE (user_id = :user_id OR friend_id = :user_id) AND status = 'accepted'
            ORDER BY created_at DESC
        ");
        $stmt->execute([':user_id' => $userId]);
        
        $friends = [];
        while ($row = $stmt->fetch()) {
            $friends[] = UserFriend::fromArray($row);
        }
        return $friends;
    }

    public function findPendingRequests(string $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                id,
                user_id as \"userId\",
                friend_id as \"friendId\",
                status,
                created_at as \"createdAt\"
            FROM user_friends
            WHERE friend_id = :user_id AND status = 'pending'
            ORDER BY created_at DESC
        ");
        $stmt->execute([':user_id' => $userId]);
        
        $friends = [];
        while ($row = $stmt->fetch()) {
            $friends[] = UserFriend::fromArray($row);
        }
        return $friends;
    }

    public function findFriendship(string $userId, string $friendId): ?UserFriend
    {
        $stmt = $this->db->prepare("
            SELECT 
                id,
                user_id as \"userId\",
                friend_id as \"friendId\",
                status,
                created_at as \"createdAt\"
            FROM user_friends
            WHERE (user_id = :user_id AND friend_id = :friend_id)
               OR (user_id = :friend_id AND friend_id = :user_id)
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':friend_id' => $friendId
        ]);
        
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        
        return UserFriend::fromArray($row);
    }

    public function save(UserFriend $friend): void
    {
        $existing = $this->db->prepare("SELECT id FROM user_friends WHERE id = :id");
        $existing->execute([':id' => $friend->id]);
        
        if ($existing->fetch()) {
            // UPDATE
            $stmt = $this->db->prepare("
                UPDATE user_friends SET
                    user_id = :user_id,
                    friend_id = :friend_id,
                    status = :status
                WHERE id = :id
            ");
            
            $stmt->execute([
                ':id' => $friend->id,
                ':user_id' => $friend->userId,
                ':friend_id' => $friend->friendId,
                ':status' => $friend->status
            ]);
        } else {
            // INSERT
            $stmt = $this->db->prepare("
                INSERT INTO user_friends (id, user_id, friend_id, status, created_at)
                VALUES (:id, :user_id, :friend_id, :status, :created_at)
            ");
            
            $stmt->execute([
                ':id' => $friend->id,
                ':user_id' => $friend->userId,
                ':friend_id' => $friend->friendId,
                ':status' => $friend->status,
                ':created_at' => $friend->createdAt
            ]);
        }
    }

    public function delete(string $id): void
    {
        $stmt = $this->db->prepare("DELETE FROM user_friends WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }
}
