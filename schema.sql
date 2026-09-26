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
    package_type VARCHAR(50) NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
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
    package_type VARCHAR(50) NOT NULL,
    custom_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
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
    type VARCHAR(50) NOT NULL,
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
    user_type ENUM('member', 'admin', 'accountant') NOT NULL,
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
    package_type VARCHAR(50) NOT NULL DEFAULT 'Recharge_Bundle_5400',
    used_epin VARCHAR(50) NOT NULL,
    mobile_1 VARCHAR(20) DEFAULT NULL,
    operator_1 VARCHAR(50) DEFAULT NULL,
    mobile_2 VARCHAR(20) DEFAULT NULL,
    operator_2 VARCHAR(50) DEFAULT NULL,
    gas_provider VARCHAR(100) DEFAULT NULL,
    gas_consumer_number VARCHAR(50) DEFAULT NULL,
    gas_customer_name VARCHAR(100) DEFAULT NULL,
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

-- ====================================================================
-- DOUBLE-ENTRY ACCOUNTING & FINANCIAL MANAGEMENT TABLES (INDIAN COA & GST)
-- ====================================================================

-- Accountant Users Table
CREATE TABLE IF NOT EXISTS accountants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Chart of Accounts (COA)
CREATE TABLE IF NOT EXISTS accounts_coa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    type ENUM('Asset', 'Liability', 'Equity', 'Revenue', 'Expense') NOT NULL,
    sub_type VARCHAR(50) NOT NULL, -- e.g., Current Asset, Fixed Asset, Sundry Debtors, Direct Expense, Tax Ledger
    parent_id INT DEFAULT NULL,
    opening_balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    opening_balance_type ENUM('Debit', 'Credit') NOT NULL DEFAULT 'Debit',
    current_balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    is_system TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES accounts_coa(id) ON DELETE SET NULL
);

-- Customer / Vendor Parties
CREATE TABLE IF NOT EXISTS parties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    party_type ENUM('Customer', 'Vendor') NOT NULL,
    name VARCHAR(100) NOT NULL,
    gstin VARCHAR(15) DEFAULT NULL,
    pan VARCHAR(10) DEFAULT NULL,
    email VARCHAR(100) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    address TEXT,
    state_code VARCHAR(2) DEFAULT '27', -- e.g. 27 for Maharashtra
    state_name VARCHAR(50) DEFAULT 'Maharashtra',
    credit_limit DECIMAL(12,2) DEFAULT 0.00,
    account_id INT NOT NULL, -- Links to COA Sundry Debtors or Sundry Creditors ledger
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES accounts_coa(id)
);

-- Inventory Items
CREATE TABLE IF NOT EXISTS inventory_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    hsn_sac VARCHAR(10) NOT NULL,
    unit VARCHAR(20) DEFAULT 'NOS',
    purchase_rate DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    sales_rate DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    gst_rate DECIMAL(5,2) NOT NULL DEFAULT 18.00, -- e.g. 18.00%
    opening_qty DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    current_qty DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    valuation_method ENUM('FIFO', 'Weighted_Average') NOT NULL DEFAULT 'Weighted_Average',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Vouchers (Receipt, Payment, Contra, Journal, Debit Note, Credit Note)
CREATE TABLE IF NOT EXISTS vouchers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    voucher_number VARCHAR(50) NOT NULL UNIQUE,
    voucher_type ENUM('Receipt', 'Payment', 'Contra', 'Journal', 'Debit_Note', 'Credit_Note') NOT NULL,
    voucher_date DATE NOT NULL,
    narration TEXT,
    reference_number VARCHAR(100) DEFAULT NULL,
    total_debit DECIMAL(15,2) NOT NULL,
    total_credit DECIMAL(15,2) NOT NULL,
    status ENUM('Posted', 'Reversed', 'Draft') NOT NULL DEFAULT 'Posted',
    created_by_accountant_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by_accountant_id) REFERENCES accountants(id)
);

-- Voucher Line Items (Debit/Credit Journal Lines)
CREATE TABLE IF NOT EXISTS voucher_line_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    voucher_id INT NOT NULL,
    account_id INT NOT NULL,
    line_type ENUM('Debit', 'Credit') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    particulars VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (voucher_id) REFERENCES vouchers(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES accounts_coa(id)
);

-- Commercial Documents (PO, Purchase Bill, Sales Quotation, Tax Invoice, Sales Return)
CREATE TABLE IF NOT EXISTS commercial_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doc_number VARCHAR(50) NOT NULL UNIQUE,
    doc_type ENUM('Purchase_Order', 'Purchase_Bill', 'Sales_Quotation', 'Tax_Invoice', 'Sales_Return', 'Purchase_Return') NOT NULL,
    doc_date DATE NOT NULL,
    party_id INT NOT NULL,
    place_of_supply VARCHAR(50) DEFAULT 'Maharashtra',
    state_code VARCHAR(2) DEFAULT '27',
    is_interstate TINYINT(1) DEFAULT 0,
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_cgst DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_sgst DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_igst DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    grand_total DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    voucher_id INT DEFAULT NULL, -- Linked accounting voucher ID
    status ENUM('Active', 'Cancelled') NOT NULL DEFAULT 'Active',
    created_by_accountant_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (party_id) REFERENCES parties(id),
    FOREIGN KEY (voucher_id) REFERENCES vouchers(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by_accountant_id) REFERENCES accountants(id)
);

-- Commercial Document Line Items
CREATE TABLE IF NOT EXISTS commercial_document_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    item_id INT NOT NULL,
    qty DECIMAL(10,2) NOT NULL,
    rate DECIMAL(12,2) NOT NULL,
    taxable_value DECIMAL(15,2) NOT NULL,
    hsn_sac VARCHAR(10) NOT NULL,
    gst_rate DECIMAL(5,2) NOT NULL,
    cgst_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    sgst_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    igst_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    line_total DECIMAL(15,2) NOT NULL,
    FOREIGN KEY (document_id) REFERENCES commercial_documents(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES inventory_items(id)
);

-- Inventory Transactions Log
CREATE TABLE IF NOT EXISTS inventory_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    trans_date DATE NOT NULL,
    trans_type ENUM('Purchase', 'Sales', 'Purchase_Return', 'Sales_Return', 'Adjustment') NOT NULL,
    reference_id INT DEFAULT NULL, -- Document ID
    qty DECIMAL(10,2) NOT NULL,
    rate DECIMAL(12,2) NOT NULL,
    valuation_total DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES inventory_items(id)
);

-- Tax Ledger Transactions (GST CGST/SGST/IGST Tracking)
CREATE TABLE IF NOT EXISTS tax_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trans_date DATE NOT NULL,
    voucher_id INT DEFAULT NULL,
    document_id INT DEFAULT NULL,
    party_id INT DEFAULT NULL,
    gstin VARCHAR(15) DEFAULT NULL,
    tax_type ENUM('CGST', 'SGST', 'IGST') NOT NULL,
    supply_type ENUM('Inward', 'Outward') NOT NULL,
    hsn_sac VARCHAR(10) DEFAULT NULL,
    taxable_value DECIMAL(15,2) NOT NULL,
    tax_rate DECIMAL(5,2) NOT NULL,
    tax_amount DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (voucher_id) REFERENCES vouchers(id) ON DELETE CASCADE,
    FOREIGN KEY (document_id) REFERENCES commercial_documents(id) ON DELETE CASCADE
);

-- Bank Reconciliation Statement (BRS) Records
CREATE TABLE IF NOT EXISTS bank_reconciliations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bank_account_id INT NOT NULL,
    reconciliation_date DATE NOT NULL,
    voucher_id INT NOT NULL,
    cleared_date DATE DEFAULT NULL,
    status ENUM('Uncleared', 'Cleared') NOT NULL DEFAULT 'Uncleared',
    statement_balance DECIMAL(15,2) DEFAULT 0.00,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bank_account_id) REFERENCES accounts_coa(id),
    FOREIGN KEY (voucher_id) REFERENCES vouchers(id) ON DELETE CASCADE
);

-- Immutable Accounting Audit Trail Logs
CREATE TABLE IF NOT EXISTS accounting_audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    accountant_id INT DEFAULT NULL,
    username VARCHAR(50) DEFAULT 'System',
    action_type VARCHAR(50) NOT NULL, -- e.g. VOUCHER_CREATE, VOUCHER_REVERSE, INVOICE_POST
    target_entity VARCHAR(50) NOT NULL,
    target_id VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Initial Admin & Default Accountant Credentials
INSERT INTO admins (username, password)
VALUES ('admin', 'admin123')
ON DUPLICATE KEY UPDATE id=id;

INSERT INTO accountants (username, name, email, phone, password, status)
VALUES ('accountant', 'Senior Accountant', 'accountant@givoratraders.com', '9876543210', 'accountant123', 'Active')
ON DUPLICATE KEY UPDATE id=id;

-- Seed Standard Indian Chart of Accounts (COA)
INSERT INTO accounts_coa (code, name, type, sub_type, opening_balance, opening_balance_type, is_system) VALUES
('1000', 'Cash Account', 'Asset', 'Cash & Bank', 50000.00, 'Debit', 1),
('1010', 'HDFC Bank Account', 'Asset', 'Cash & Bank', 250000.00, 'Debit', 1),
('1020', 'Sundry Debtors Control', 'Asset', 'Sundry Debtors', 0.00, 'Debit', 1),
('1030', 'Input CGST Ledger', 'Asset', 'Tax Ledger', 0.00, 'Debit', 1),
('1031', 'Input SGST Ledger', 'Asset', 'Tax Ledger', 0.00, 'Debit', 1),
('1032', 'Input IGST Ledger', 'Asset', 'Tax Ledger', 0.00, 'Debit', 1),
('1040', 'Stock Inventory Account', 'Asset', 'Stock', 0.00, 'Debit', 1),

('2000', 'Sundry Creditors Control', 'Liability', 'Sundry Creditors', 0.00, 'Credit', 1),
('2010', 'Output CGST Ledger', 'Liability', 'Tax Ledger', 0.00, 'Credit', 1),
('2011', 'Output SGST Ledger', 'Liability', 'Tax Ledger', 0.00, 'Credit', 1),
('2012', 'Output IGST Ledger', 'Liability', 'Tax Ledger', 0.00, 'Credit', 1),
('2020', 'TDS Payable Control', 'Liability', 'Duties & Taxes', 0.00, 'Credit', 1),

('3000', 'Capital Account', 'Equity', 'Capital', 300000.00, 'Credit', 1),

('4000', 'Sales Revenue Account', 'Revenue', 'Direct Revenue', 0.00, 'Credit', 1),
('4010', 'Matrix Joining Fees Income', 'Revenue', 'Direct Revenue', 0.00, 'Credit', 1),

('5000', 'Purchase Account', 'Expense', 'Direct Expense', 0.00, 'Debit', 1),
('5010', 'Matrix Commissions Expense', 'Expense', 'Direct Expense', 0.00, 'Debit', 1),
('5020', 'Office Rent Expense', 'Expense', 'Indirect Expense', 0.00, 'Debit', 1),
('5030', 'Utility & Electricity Expense', 'Expense', 'Indirect Expense', 0.00, 'Debit', 1),
('5040', 'Staff Salaries Expense', 'Expense', 'Indirect Expense', 0.00, 'Debit', 1)
ON DUPLICATE KEY UPDATE code=code;

-- Initial Company Root Member for matrix top
INSERT INTO members (member_id, sponsor_id, placement_parent_id, matrix_position, name, email, phone, password, used_epin, package_type, status, p2_status)
VALUES ('GT100000', NULL, NULL, NULL, 'Givora Root', 'root@givoratraders.com', '9999999999', 'root123', 'SYSTEM_ROOT_EPIN', 'Leadership_15000', 'Active', 'Active')
ON DUPLICATE KEY UPDATE id=id;

INSERT INTO wallets (member_id, balance, user_wallet_60, company_wallet_40)
VALUES ('GT100000', 0.00, 0.00, 0.00)
ON DUPLICATE KEY UPDATE id=id;
