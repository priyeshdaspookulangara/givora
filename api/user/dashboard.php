<?php
// api/user/dashboard.php
require_once __DIR__ . '/../includes/api_helpers.php';

$pdo = getDBConnection();
$auth = authenticateApiRequest($pdo, 'member');
$member = $auth['user'];
$member_id = $member['member_id'];

ensureWalletExists($pdo, $member_id);

// Wallet information
$stmt = $pdo->prepare("SELECT * FROM wallets WHERE member_id = ?");
$stmt->execute([$member_id]);
$wallet = $stmt->fetch();

// Total Earnings (Direct + Matrix Commissions)
$stmt = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE member_id = ? AND status = 'Credit' AND type IN ('Direct_Referral', 'Matrix_Income_P1', 'Matrix_Income_P2')");
$stmt->execute([$member_id]);
$total_earnings = (float)($stmt->fetchColumn() ?: 0.00);

// Total Direct Referrals Count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE sponsor_id = ?");
$stmt->execute([$member_id]);
$total_referrals = (int)$stmt->fetchColumn();

// Total Phase 1 Matrix Downline Count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE placement_parent_id = ?");
$stmt->execute([$member_id]);
$direct_matrix_count = (int)$stmt->fetchColumn();

// Total Withdrawn Approved
$stmt = $pdo->prepare("SELECT SUM(amount) FROM withdrawals WHERE member_id = ? AND status = 'Approved'");
$stmt->execute([$member_id]);
$total_withdrawn = (float)($stmt->fetchColumn() ?: 0.00);

$referral_link = getBaseUrl() . "/register.php?sponsor=" . $member_id;

unset($member['password']);

sendJsonResponse(true, 'Dashboard stats fetched successfully.', [
    'member' => $member,
    'wallet' => [
        'user_wallet_60' => (float)$wallet['user_wallet_60'],
        'company_wallet_40' => (float)$wallet['company_wallet_40'],
        'p2_reserve_wallet' => (float)$wallet['p2_reserve_wallet'],
        'p2_reserve_target' => 15000.00,
        'total_earnings' => $total_earnings,
        'total_withdrawn' => $total_withdrawn
    ],
    'matrix_stats' => [
        'total_direct_referrals' => $total_referrals,
        'direct_matrix_count' => $direct_matrix_count,
        'p1_status' => $member['status'],
        'p2_status' => $member['p2_status']
    ],
    'referral_link' => $referral_link
]);
