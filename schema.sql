CREATE DATABASE IF NOT EXISTS givora_db;
USE givora_db;

CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS epins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    epin_code VARCHAR(50) NOT NULL UNIQUE,
    package_type ENUM('Foundation_5000', 'Leadership_15000', 'Recharge_Bundle_5400') NOT NULL,
    status ENUM('Unused', 'Used') NOT NULL DEFAULT 'Unused',
    generated_by_admin_id INT DEFAULT NULL,
    used_by_member_id VARCHAR(50) DEFAULT NULL,
    assigned_to VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id VARCHAR(20) NOT NULL UNIQUE,
    sponsor_id VARCHAR(20) DEFAULT NULL,
    placement_parent_id VARCHAR(20) DEFAULT NULL,
    matrix_position INT DEFAULT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    used_epin VARCHAR(50) NOT NULL,
    package_type ENUM('Foundation_5000', 'Leadership_15000', 'Recharge_Bundle_5400') NOT NULL,
    profile_image VARCHAR(255) DEFAULT NULL,
    status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    p2_status ENUM('Inactive', 'Active') NOT NULL DEFAULT 'Inactive',
    p2_placement_parent_id VARCHAR(20) DEFAULT NULL,
    p2_matrix_position INT DEFAULT NULL,
    p2_created_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sponsor (sponsor_id),
    INDEX idx_parent (placement_parent_id),
    INDEX idx_p2_parent (p2_placement_parent_id)
);

CREATE TABLE IF NOT EXISTS wallets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id VARCHAR(20) NOT NULL UNIQUE,
    balance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    user_wallet_60 DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    company_wallet_40 DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    p2_reserve_wallet DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id VARCHAR(50) NOT NULL,
    type ENUM('Direct_Referral', 'Matrix_Income_P1', 'Matrix_Income_P2', 'Phase_2_Reserve', 'Phase_2_Joining_Fee', 'Withdrawal_Request', 'Admin_Adjustment') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    wallet_type ENUM('User_Wallet', 'Company_Wallet', 'Main') NOT NULL DEFAULT 'Main',
    status ENUM('Credit', 'Debit', 'Pending', 'Approved') NOT NULL DEFAULT 'Credit',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_member (member_id)
);

CREATE TABLE IF NOT EXISTS withdrawals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id VARCHAR(20) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_date TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS api_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_type ENUM('member', 'admin') NOT NULL,
    user_id VARCHAR(50) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_user (user_id, user_type)
);

CREATE TABLE IF NOT EXISTS recharge_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id VARCHAR(20) NOT NULL,
    used_epin VARCHAR(50) NOT NULL,
    mobile_1 VARCHAR(20) NOT NULL,
    operator_1 VARCHAR(50) NOT NULL,
    mobile_2 VARCHAR(20) NOT NULL,
    operator_2 VARCHAR(50) NOT NULL,
    gas_provider VARCHAR(100) NOT NULL,
    gas_consumer_number VARCHAR(50) NOT NULL,
    gas_customer_name VARCHAR(100) NOT NULL,
    status ENUM('Active', 'Completed') NOT NULL DEFAULT 'Active',
    start_date DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_member (member_id),
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS recharge_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subscription_id INT NOT NULL,
    service_type ENUM('Mobile_1', 'Mobile_2', 'Gas') NOT NULL,
    term_number INT NOT NULL,
    due_date DATETIME DEFAULT NULL,
    status ENUM('Scheduled', 'Requested', 'Completed', 'Failed') NOT NULL DEFAULT 'Scheduled',
    completed_at DATETIME DEFAULT NULL,
    reference_number VARCHAR(100) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_subscription (subscription_id),
    INDEX idx_status (status),
    FOREIGN KEY (subscription_id) REFERENCES recharge_subscriptions(id) ON DELETE CASCADE
);

-- Initial Admin Account
INSERT INTO admins (username, password)
VALUES ('admin', 'admin123')
ON DUPLICATE KEY UPDATE id=id;

-- Initial Company Root Member for matrix top
INSERT INTO members (member_id, sponsor_id, placement_parent_id, matrix_position, name, email, phone, password, used_epin, package_type, status, p2_status)
VALUES ('GT100000', NULL, NULL, NULL, 'Givora Root', 'root@givoratraders.com', '9999999999', 'root123', 'SYSTEM_ROOT_EPIN', 'Leadership_15000', 'Active', 'Active')
ON DUPLICATE KEY UPDATE id=id;

INSERT INTO wallets (member_id, balance, user_wallet_60, company_wallet_40)
VALUES ('GT100000', 0.00, 0.00, 0.00)
ON DUPLICATE KEY UPDATE id=id;
