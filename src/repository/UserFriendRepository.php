<?php

require_once __DIR__ . '/../models/UserFriend.php';

class UserFriendRepository
{
    private const FRIENDS_FILE = __DIR__ . '/../../storage/user-friends.json';
    private static ?UserFriendRepository $instance = null;

    private function __construct() {}

    public static function getInstance(): UserFriendRepository
    {
        if (self::$instance === null) {
            self::$instance = new UserFriendRepository();
        }
        return self::$instance;
    }

    public function findAll(): array
    {
        $data = $this->loadFriends();
        return array_map([UserFriend::class, 'fromArray'], $data);
    }

    public function findByUserId(string $userId): array
    {
        $friends = $this->findAll();
        return array_filter($friends, function($friend) use ($userId) {
            return ($friend->userId === $userId || $friend->friendId === $userId) && $friend->status === 'accepted';
        });
    }

    public function findPendingRequests(string $userId): array
    {
        $friends = $this->findAll();
        return array_filter($friends, function($friend) use ($userId) {
            return $friend->friendId === $userId && $friend->status === 'pending';
        });
    }

    public function findFriendship(string $userId, string $friendId): ?UserFriend
    {
        $friends = $this->findAll();
        foreach ($friends as $friend) {
            if (($friend->userId === $userId && $friend->friendId === $friendId) ||
                ($friend->userId === $friendId && $friend->friendId === $userId)) {
                return $friend;
            }
        }
        return null;
    }

    public function save(UserFriend $friend): void
    {
        $friends = $this->findAll();
        $found = false;
        foreach ($friends as &$f) {
            if ($f->id === $friend->id) {
                $f = $friend;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $friends[] = $friend;
        }
        $this->saveFriends(array_map(fn($f) => $f->toArray(), $friends));
    }

    public function delete(string $id): void
    {
        $friends = $this->findAll();
        $friends = array_filter($friends, fn($f) => $f->id !== $id);
        $this->saveFriends(array_map(fn($f) => $f->toArray(), $friends));
    }

    private function loadFriends(): array
    {
        if (!file_exists(self::FRIENDS_FILE)) {
            return [];
        }
        $json = file_get_contents(self::FRIENDS_FILE);
        return json_decode($json, true) ?: [];
    }

    private function saveFriends(array $friends): void
    {
        $dir = dirname(self::FRIENDS_FILE);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents(self::FRIENDS_FILE, json_encode($friends, JSON_PRETTY_PRINT));
    }
}
