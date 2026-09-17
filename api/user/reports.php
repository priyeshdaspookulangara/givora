<?php
// api/user/reports.php
require_once __DIR__ . '/../includes/api_helpers.php';

$pdo = getDBConnection();
$auth = authenticateApiRequest($pdo, 'member');
$member = $auth['user'];
$member_id = $member['member_id'];

// Filter by type if provided
$type = trim($_GET['type'] ?? '');

$sql = "SELECT * FROM transactions WHERE member_id = ?";
$params = [$member_id];

if (!empty($type)) {
    $sql .= " AND type = ?";
    $params[] = $type;
}

$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll();

// Earnings breakdown summary
$stmt = $pdo->prepare("SELECT type, SUM(amount) AS total_amount FROM transactions WHERE member_id = ? AND status = 'Credit' GROUP BY type");
$stmt->execute([$member_id]);
$summary = $stmt->fetchAll();

sendJsonResponse(true, 'Reports statement fetched successfully.', [
    'summary' => $summary,
    'reports' => $reports
]);
