<?php
// api/admin/members.php
require_once __DIR__ . '/../includes/api_helpers.php';

$pdo = getDBConnection();
$auth = authenticateApiRequest($pdo, 'admin');

$search = trim($_GET['search'] ?? '');
$package_filter = trim($_GET['package'] ?? '');

$sql = "SELECT id, member_id, sponsor_id, placement_parent_id, matrix_position, name, email, phone, password, package_type, status, p2_status, created_at FROM members WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (member_id LIKE ? OR name LIKE ? OR phone LIKE ? OR email LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if (!empty($package_filter)) {
    $sql .= " AND package_type = ?";
    $params[] = $package_filter;
}

$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll();

// Add WhatsApp greeting link to each member
$baseUrl = getBaseUrl();
foreach ($members as &$m) {
    $login_url = $baseUrl . "/login.php";
    $message = "Welcome to Givora Traders LLP! Your Member ID is: " . $m['member_id'] . " and Password is: " . $m['password'] . " . Login here: " . $login_url;
    $clean_phone = preg_replace('/[^0-9]/', '', $m['phone']);
    $m['whatsapp_url'] = "https://wa.me/" . $clean_phone . "?text=" . urlencode($message);
    $m['whatsapp_text'] = $message;
}

sendJsonResponse(true, 'Admin members list fetched successfully.', [
    'count' => count($members),
    'members' => $members
]);
