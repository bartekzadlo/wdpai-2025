<?php

require_once __DIR__ . '/../models/UserEventInterest.php';

class UserEventInterestRepository
{
    private const INTERESTS_FILE = __DIR__ . '/../../storage/user-event-interests.json';
    private static ?UserEventInterestRepository $instance = null;

    private function __construct() {}

    public static function getInstance(): UserEventInterestRepository
    {
        if (self::$instance === null) {
            self::$instance = new UserEventInterestRepository();
        }
        return self::$instance;
    }

    public function findAll(): array
    {
        $data = $this->loadInterests();
        return array_map([UserEventInterest::class, 'fromArray'], $data);
    }

    public function toggleInterest(string $userId, string $eventId): bool
    {
        $interests = $this->findAll();
        $found = false;
        foreach ($interests as $key => $interest) {
            if ($interest->userId === $userId && $interest->eventId === $eventId) {
                unset($interests[$key]);
                $found = true;
                break;
            }
        }
        if (!$found) {
            $interests[] = new UserEventInterest($userId, $eventId);
        }
        $this->saveInterests(array_map(fn($i) => $i->toArray(), $interests));
        return !$found;
    }

    public function isInterested(string $userId, string $eventId): bool
    {
        $interests = $this->findAll();
        foreach ($interests as $interest) {
            if ($interest->userId === $userId && $interest->eventId === $eventId) {
                return true;
            }
        }
        return false;
    }

    public function getInterestCount(string $eventId): int
    {
        $interests = $this->findAll();
        $count = 0;
        foreach ($interests as $interest) {
            if ($interest->eventId === $eventId) {
                $count++;
            }
        }
        return $count;
    }

    public function findByUserId(string $userId): array
    {
        $interests = $this->findAll();
        return array_filter($interests, function($interest) use ($userId) {
            return $interest->userId === $userId;
        });
    }

    private function loadInterests(): array
    {
        if (!file_exists(self::INTERESTS_FILE)) {
            return [];
        }
        $json = file_get_contents(self::INTERESTS_FILE);
        return json_decode($json, true) ?: [];
    }

    private function saveInterests(array $interests): void
    {
        $dir = dirname(self::INTERESTS_FILE);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents(self::INTERESTS_FILE, json_encode($interests, JSON_PRETTY_PRINT));
    }
}
