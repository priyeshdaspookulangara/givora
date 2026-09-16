<?php
// Database configuration
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'givora_db');
define('DB_USER', 'root');
define('DB_PASS', '');

function getDBConnection() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Fallback: try connecting without dbname if database doesn't exist yet
            try {
                $dsn_no_db = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
                $pdo_temp = new PDO($dsn_no_db, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $pdo_temp->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
                $pdo_temp->exec("USE " . DB_NAME);
                $sql = file_get_contents(__DIR__ . '/../schema.sql');
                $pdo_temp->exec($sql);

                $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, $options);
            } catch (PDOException $e2) {
                die("Database connection failed: " . $e2->getMessage());
            }
        }
    }
    return $pdo;
}
