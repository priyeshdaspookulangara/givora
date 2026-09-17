<?php
// api/admin/financials.php
require_once __DIR__ . '/../includes/api_helpers.php';

$pdo = getDBConnection();
$auth = authenticateApiRequest($pdo, 'admin');

// Total system members
$stmt = $pdo->query("SELECT COUNT(*) FROM members WHERE member_id != 'GT100000'");
$total_members = (int)$stmt->fetchColumn();

// Total package inflow calculated from registered active members
$stmt = $pdo->query("SELECT package_type, COUNT(*) AS count FROM members WHERE member_id != 'GT100000' GROUP BY package_type");
$package_counts = $stmt->fetchAll();

$total_inflow = 0.00;
foreach ($package_counts as $row) {
    $price = ($row['package_type'] === 'Leadership_15000') ? 15000.00 : 5000.00;
    $total_inflow += ($price * $row['count']);
}

// Total User Wallets (60%)
$stmt = $pdo->query("SELECT SUM(user_wallet_60) FROM wallets");
$total_user_wallets = (float)($stmt->fetchColumn() ?: 0.00);

// Total Company Wallets (40%)
$stmt = $pdo->query("SELECT SUM(company_wallet_40) FROM wallets");
$total_company_wallets = (float)($stmt->fetchColumn() ?: 0.00);

// Total Approved Withdrawals
$stmt = $pdo->query("SELECT SUM(amount) FROM withdrawals WHERE status = 'Approved'");
$total_payouts = (float)($stmt->fetchColumn() ?: 0.00);

// Total Pending Withdrawals
$stmt = $pdo->query("SELECT SUM(amount) FROM withdrawals WHERE status = 'Pending'");
$pending_withdrawals = (float)($stmt->fetchColumn() ?: 0.00);

// System liquidity reserve
$liquidity_reserves = $total_inflow - $total_payouts;

sendJsonResponse(true, 'Admin financial master ledger fetched successfully.', [
    'financials' => [
        'total_members' => $total_members,
        'total_inflow' => $total_inflow,
        'total_user_wallets_60' => $total_user_wallets,
        'total_company_wallets_40' => $total_company_wallets,
        'total_payouts_approved' => $total_payouts,
        'pending_withdrawals' => $pending_withdrawals,
        'liquidity_reserves' => $liquidity_reserves
    ]
]);
