<?php

require_once __DIR__ . '/../models/User.php';

class UserRepository
{
    private const USERS_FILE = __DIR__ . '/../../storage/users.json';

    public function findAll(): array
    {
        $data = $this->loadUsers();
        return array_map([User::class, 'fromArray'], $data);
    }

    public function findByEmail(string $email): ?User
    {
        $users = $this->findAll();
        foreach ($users as $user) {
            if (strcasecmp($user->email, $email) === 0) {
                return $user;
            }
        }
        return null;
    }

    public function findById(string $id): ?User
    {
        $users = $this->findAll();
        foreach ($users as $user) {
            if ($user->id === $id) {
                return $user;
            }
        }
        return null;
    }

    public function save(User $user): void
    {
        $users = $this->findAll();
        $found = false;
        foreach ($users as &$u) {
            if ($u->id === $user->id) {
                $u = $user;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $users[] = $user;
        }
        $this->saveUsers(array_map(fn($u) => $u->toArray(), $users));
    }

    public function delete(string $id): void
    {
        $users = $this->findAll();
        $users = array_filter($users, fn($u) => $u->id !== $id);
        $this->saveUsers(array_map(fn($u) => $u->toArray(), $users));
    }

    public function emailExists(string $email): bool
    {
        return $this->findByEmail($email) !== null;
    }

    private function loadUsers(): array
    {
        if (!file_exists(self::USERS_FILE)) {
            return [];
        }
        $json = file_get_contents(self::USERS_FILE);
        return json_decode($json, true) ?: [];
    }

    private function saveUsers(array $users): void
    {
        $dir = dirname(self::USERS_FILE);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents(self::USERS_FILE, json_encode($users, JSON_PRETTY_PRINT));
    }
}
