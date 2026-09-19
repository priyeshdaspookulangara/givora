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
            // Fallback: try connecting without dbname if database doesn't exist yet on MySQL
            try {
                $dsn_no_db = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
                $pdo_temp = new PDO($dsn_no_db, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $pdo_temp->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
                $pdo_temp->exec("USE " . DB_NAME);
                $sql = file_get_contents(__DIR__ . '/../schema.sql');
                $pdo_temp->exec($sql);

                $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, $options);
            } catch (PDOException $e2) {
                // Fallback to SQLite database if MySQL server is unavailable
                try {
                    $sqlite_path = __DIR__ . '/../givora.sqlite';
                    $is_new = !file_exists($sqlite_path);
                    $pdo = new PDO("sqlite:" . $sqlite_path, null, null, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]);
                    if ($is_new) {
                        $sqlite_schema = "
                        CREATE TABLE IF NOT EXISTS admins (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            username TEXT NOT NULL UNIQUE,
                            password TEXT NOT NULL,
                            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                        );

                        CREATE TABLE IF NOT EXISTS epins (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            epin_code TEXT NOT NULL UNIQUE,
                            package_type TEXT NOT NULL,
                            status TEXT NOT NULL DEFAULT 'Unused',
                            generated_by_admin_id INTEGER DEFAULT NULL,
                            used_by_member_id TEXT DEFAULT NULL,
                            assigned_to TEXT DEFAULT NULL,
                            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                        );

                        CREATE TABLE IF NOT EXISTS members (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            member_id TEXT NOT NULL UNIQUE,
                            sponsor_id TEXT DEFAULT NULL,
                            placement_parent_id TEXT DEFAULT NULL,
                            matrix_position INTEGER DEFAULT NULL,
                            name TEXT NOT NULL,
                            email TEXT NOT NULL,
                            phone TEXT NOT NULL,
                            password TEXT NOT NULL,
                            used_epin TEXT NOT NULL,
                            package_type TEXT NOT NULL,
                            profile_image TEXT DEFAULT NULL,
                            status TEXT NOT NULL DEFAULT 'Active',
                            p2_status TEXT NOT NULL DEFAULT 'Inactive',
                            p2_placement_parent_id TEXT DEFAULT NULL,
                            p2_matrix_position INTEGER DEFAULT NULL,
                            p2_created_at DATETIME DEFAULT NULL,
                            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                        );

                        CREATE TABLE IF NOT EXISTS wallets (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            member_id TEXT NOT NULL UNIQUE,
                            balance REAL NOT NULL DEFAULT 0.00,
                            user_wallet_60 REAL NOT NULL DEFAULT 0.00,
                            company_wallet_40 REAL NOT NULL DEFAULT 0.00,
                            p2_reserve_wallet REAL NOT NULL DEFAULT 0.00
                        );

                        CREATE TABLE IF NOT EXISTS transactions (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            member_id TEXT NOT NULL,
                            type TEXT NOT NULL,
                            amount REAL NOT NULL,
                            wallet_type TEXT NOT NULL DEFAULT 'Main',
                            status TEXT NOT NULL DEFAULT 'Credit',
                            description TEXT,
                            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                        );

                        CREATE TABLE IF NOT EXISTS withdrawals (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            member_id TEXT NOT NULL,
                            amount REAL NOT NULL,
                            status TEXT NOT NULL DEFAULT 'Pending',
                            request_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                            processed_date DATETIME DEFAULT NULL
                        );

                        CREATE TABLE IF NOT EXISTS api_tokens (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            user_type TEXT NOT NULL,
                            user_id TEXT NOT NULL,
                            token TEXT NOT NULL UNIQUE,
                            expires_at DATETIME NOT NULL,
                            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                        );

                        CREATE TABLE IF NOT EXISTS recharge_subscriptions (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            member_id TEXT NOT NULL,
                            used_epin TEXT NOT NULL,
                            mobile_1 TEXT NOT NULL,
                            operator_1 TEXT NOT NULL,
                            mobile_2 TEXT NOT NULL,
                            operator_2 TEXT NOT NULL,
                            gas_provider TEXT NOT NULL,
                            gas_consumer_number TEXT NOT NULL,
                            gas_customer_name TEXT NOT NULL,
                            status TEXT NOT NULL DEFAULT 'Active',
                            start_date DATETIME NOT NULL,
                            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                        );

                        CREATE TABLE IF NOT EXISTS recharge_schedules (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            subscription_id INTEGER NOT NULL,
                            service_type TEXT NOT NULL,
                            term_number INTEGER NOT NULL,
                            due_date DATETIME DEFAULT NULL,
                            status TEXT NOT NULL DEFAULT 'Scheduled',
                            completed_at DATETIME DEFAULT NULL,
                            reference_number TEXT DEFAULT NULL,
                            notes TEXT DEFAULT NULL,
                            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                        );

                        INSERT OR IGNORE INTO admins (username, password) VALUES ('admin', 'admin123');
                        INSERT OR IGNORE INTO members (member_id, sponsor_id, placement_parent_id, matrix_position, name, email, phone, password, used_epin, package_type, status, p2_status) VALUES ('GT100000', NULL, NULL, NULL, 'Givora Root', 'root@givoratraders.com', '9999999999', 'root123', 'SYSTEM_ROOT_EPIN', 'Leadership_15000', 'Active', 'Active');
                        INSERT OR IGNORE INTO wallets (member_id, balance, user_wallet_60, company_wallet_40) VALUES ('GT100000', 0.00, 0.00, 0.00);
                        ";
                        $pdo->exec($sqlite_schema);
                    }
                } catch (PDOException $e3) {
                    die("Database connection failed: " . $e2->getMessage());
                }
            }
        }
    }
    return $pdo;
}
