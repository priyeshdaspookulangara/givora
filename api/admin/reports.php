<?php
// api/admin/reports.php
require_once __DIR__ . '/../includes/api_helpers.php';

$pdo = getDBConnection();
$auth = authenticateApiRequest($pdo, 'admin');

$type_filter = trim($_GET['type'] ?? '');
$member_filter = trim($_GET['member_id'] ?? '');

$sql = "SELECT t.*, m.name FROM transactions t LEFT JOIN members m ON t.member_id = m.member_id WHERE 1=1";
$params = [];

if (!empty($type_filter)) {
    $sql .= " AND t.type = ?";
    $params[] = $type_filter;
}

if (!empty($member_filter)) {
    $sql .= " AND t.member_id = ?";
    $params[] = $member_filter;
}

$sql .= " ORDER BY t.id DESC LIMIT 200";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Distribution breakdown across all levels and direct referrals
$stmt = $pdo->query("SELECT type, SUM(amount) AS total_amount, COUNT(*) AS count FROM transactions WHERE status = 'Credit' GROUP BY type");
$type_breakdown = $stmt->fetchAll();

sendJsonResponse(true, 'Admin audit reports fetched successfully.', [
    'summary' => $type_breakdown,
    'logs' => $logs
]);
