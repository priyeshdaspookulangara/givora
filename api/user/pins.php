<?php
// api/user/pins.php
require_once __DIR__ . '/../includes/api_helpers.php';

$pdo = getDBConnection();
$auth = authenticateApiRequest($pdo, 'member');
$member = $auth['user'];
$member_id = $member['member_id'];

// ePINs assigned to member or used by member
$stmt = $pdo->prepare("SELECT * FROM epins WHERE assigned_to = ? OR used_by_member_id = ? ORDER BY id DESC");
$stmt->execute([$member_id, $member_id]);
$pins = $stmt->fetchAll();

sendJsonResponse(true, 'Member ePINs fetched successfully.', [
    'used_epin' => $member['used_epin'],
    'pins' => $pins
]);
