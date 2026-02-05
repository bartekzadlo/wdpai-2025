#!/usr/bin/env php
<?php
/**
 * Skrypt inicjalizacji bazy danych
 * Generuje prawidłowe hashe i inicjalizuje bazę
 */

echo "=== INICJALIZACJA BAZY DANYCH ===\n\n";

// Generowanie hashy
$adminPassword = 'admin';
$userPassword = 'user';

echo "Generowanie hashy...\n";
$adminHash = password_hash($adminPassword, PASSWORD_DEFAULT);
$userHash = password_hash($userPassword, PASSWORD_DEFAULT);

echo "Admin hash: $adminHash\n";
echo "User hash: $userHash\n\n";

// Połączenie z bazą
try {
    $host = getenv('DB_HOST') ?: 'db';
    $dbname = getenv('DB_NAME') ?: 'wdpai_db';
    $user = getenv('DB_USER') ?: 'postgres';
    $password = getenv('DB_PASSWORD') ?: 'postgres';
    
    $dsn = "pgsql:host=$host;port=5432;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "Połączono z bazą danych\n\n";
    
    // Aktualizacja hashy użytkowników
    echo "Aktualizacja hashy użytkowników...\n";
    
    $stmt = $pdo->prepare("
        UPDATE users SET password = :password WHERE id = 'admin_1'
    ");
    $stmt->execute([':password' => $adminHash]);
    echo "- Admin zaktualizowany\n";
    
    $stmt = $pdo->prepare("
        UPDATE users SET password = :password WHERE id IN ('user_1', 'user_2', 'user_3')
    ");
    $stmt->execute([':password' => $userHash]);
    echo "- Użytkownicy zaktualizowani\n\n";
    
    // Weryfikacja
    echo "Weryfikacja danych...\n";
    $stmt = $pdo->query("SELECT id, email, role FROM users ORDER BY id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Użytkownicy w bazie:\n";
    foreach ($users as $user) {
        echo "  - {$user['email']} ({$user['role']})\n";
    }
    
    echo "\n=== INICJALIZACJA ZAKOŃCZONA ===\n";
    echo "\nDane logowania:\n";
    echo "Admin: admin@event.io / admin\n";
    echo "User: user@event.io / user\n";
    
} catch (PDOException $e) {
    echo "BŁĄD: " . $e->getMessage() . "\n";
    exit(1);
}
