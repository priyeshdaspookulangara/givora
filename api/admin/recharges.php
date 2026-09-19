<?php
// api/admin/recharges.php
require_once __DIR__ . '/../includes/api_helpers.php';

$pdo = getDBConnection();
$auth = authenticateApiRequest($pdo, 'admin');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $service = trim($_GET['service'] ?? '');
    $status = trim($_GET['status'] ?? '');

    $sql = "SELECT rs.*, sub.member_id, sub.mobile_1, sub.operator_1, sub.mobile_2, sub.operator_2, sub.gas_provider, sub.gas_consumer_number, sub.gas_customer_name, m.name as member_name
            FROM recharge_schedules rs
            JOIN recharge_subscriptions sub ON rs.subscription_id = sub.id
            JOIN members m ON sub.member_id = m.member_id
            WHERE 1=1";
    $params = [];

    if (!empty($service)) {
        $sql .= " AND rs.service_type = ?";
        $params[] = $service;
    }

    if (!empty($status)) {
        $sql .= " AND rs.status = ?";
        $params[] = $status;
    }

    $sql .= " ORDER BY rs.id DESC LIMIT 100";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $schedules = $stmt->fetchAll();

    sendJsonResponse(true, 'Recharge schedules fetched successfully.', [
        'count' => count($schedules),
        'schedules' => $schedules
    ]);

} elseif ($method === 'POST') {
    $input = getJsonInput();
    $schedule_id = (int)($input['schedule_id'] ?? 0);
    $status = trim($input['status'] ?? 'Completed');
    $reference_number = trim($input['reference_number'] ?? '');
    $notes = trim($input['notes'] ?? '');

    if ($schedule_id <= 0 || !in_array($status, ['Completed', 'Failed'])) {
        sendJsonResponse(false, 'Invalid schedule_id or status. Status must be Completed or Failed.', null, 400);
    }

    $stmt = $pdo->prepare("UPDATE recharge_schedules SET status = ?, reference_number = ?, notes = ?, completed_at = NOW() WHERE id = ?");
    if ($stmt->execute([$status, $reference_number, $notes, $schedule_id])) {
        sendJsonResponse(true, "Recharge schedule #{$schedule_id} updated to {$status}.");
    } else {
        sendJsonResponse(false, "Failed to update recharge schedule.", null, 500);
    }

} else {
    sendJsonResponse(false, 'Method not allowed.', null, 405);
}
