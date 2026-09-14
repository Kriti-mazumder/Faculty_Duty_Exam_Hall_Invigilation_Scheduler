<?php
/**
 * Database connection – returns a PDO singleton.
 * NOTE: Uses 127.0.0.1 (TCP) not 'localhost' (named pipe) to ensure
 *       UPDATE/INSERT operations commit reliably on Windows XAMPP.
 * Usage: $pdo = db();
 */
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn  = 'mysql:host=127.0.0.1;port=3306;dbname=invigilation_scheduler;charset=utf8mb4';
        $user = 'root';
        $pass = '';
        $opts = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        ];
        try {
            $pdo = new PDO($dsn, $user, $pass, $opts);
        } catch (PDOException $e) {
            http_response_code(500);
            exit('Database connection failed: ' . htmlspecialchars($e->getMessage()));
        }
    }
    return $pdo;
}

