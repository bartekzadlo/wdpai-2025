<?php

require_once __DIR__ . '/../database/Database.php';

abstract class BaseRepository
{
    protected PDO $db;
    private static array $instances = [];

    protected function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public static function getInstance(): static
    {
        $class = static::class;
        if (!isset(self::$instances[$class])) {
            self::$instances[$class] = new static();
        }
        return self::$instances[$class];
    }
}
