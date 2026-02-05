<?php

class Database
{
    private static ?Database $instance = null;
    private ?PDO $connection = null;

    private string $host = 'db';
    private string $port = '5432';
    private string $database = 'wdpai_db';
    private string $username = 'postgres';
    private string $password = 'postgres';

    private function __construct()
    {
        try {
            $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->database}";
            $this->connection = new PDO(
                $dsn,
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    // Zapobiega klonowaniu instancji
    private function __clone() {}

    // Zapobiega deserializacji instancji
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}
