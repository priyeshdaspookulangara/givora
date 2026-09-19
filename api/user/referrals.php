<?php
// api/user/referrals.php
require_once __DIR__ . '/../includes/api_helpers.php';

$pdo = getDBConnection();
$auth = authenticateApiRequest($pdo, 'member');
$member = $auth['user'];
$member_id = $member['member_id'];

// Direct Referral Stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE sponsor_id = ?");
$stmt->execute([$member_id]);
$total_referrals = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE sponsor_id = ? AND status = 'Active'");
$stmt->execute([$member_id]);
$active_referrals = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE member_id = ? AND type = 'Direct_Referral' AND status = 'Credit'");
$stmt->execute([$member_id]);
$total_referral_bonus = (float)($stmt->fetchColumn() ?: 0.00);

// Optional search/filters from query string
$search = trim($_GET['search'] ?? '');
$package_filter = trim($_GET['package'] ?? '');
$status_filter = trim($_GET['status'] ?? '');

$query = "SELECT m.member_id, m.name, m.email, m.phone, m.package_type, m.status, m.placement_parent_id, m.matrix_position, m.p2_status, m.created_at,
            COALESCE((SELECT SUM(t.amount) FROM transactions t WHERE t.member_id = ? AND t.type = 'Direct_Referral' AND t.status = 'Credit' AND t.description LIKE CONCAT('%', m.member_id, '%')), 0.00) as bonus_earned
          FROM members m
          WHERE m.sponsor_id = ?";
$params = [$member_id, $member_id];

if (!empty($search)) {
    $query .= " AND (m.member_id LIKE ? OR m.name LIKE ? OR m.email LIKE ? OR m.phone LIKE ?)";
    $search_param = "%{$search}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

if (!empty($package_filter)) {
    $query .= " AND m.package_type = ?";
    $params[] = $package_filter;
}

if (!empty($status_filter)) {
    $query .= " AND m.status = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY m.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$referrals = $stmt->fetchAll();

// Format response data
foreach ($referrals as &$ref) {
    $ref['bonus_earned'] = (float)$ref['bonus_earned'];
    if ($ref['matrix_position'] !== null) {
        $ref['matrix_position'] = (int)$ref['matrix_position'];
    }
}
unset($ref);

$referral_link = getBaseUrl() . "/register.php?sponsor=" . $member_id;

sendJsonResponse(true, 'Direct referrals list fetched successfully.', [
    'referral_link' => $referral_link,
    'summary' => [
        'total_referrals' => $total_referrals,
        'active_referrals' => $active_referrals,
        'total_referral_bonus' => $total_referral_bonus
    ],
    'referrals' => $referrals
]);
