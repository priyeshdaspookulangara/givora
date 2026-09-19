<?php
// api/user/recharge.php
require_once __DIR__ . '/../includes/api_helpers.php';

$pdo = getDBConnection();
$auth = authenticateApiRequest($pdo, 'member');
$member = $auth['user'];

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->prepare("SELECT * FROM recharge_subscriptions WHERE member_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$member['member_id']]);
    $subscription = $stmt->fetch();

    if (!$subscription) {
        sendJsonResponse(true, 'No recharge bundle subscription found for this member.', [
            'has_subscription' => false,
            'subscription' => null,
            'schedules' => []
        ]);
    }

    $stmt = $pdo->prepare("SELECT * FROM recharge_schedules WHERE subscription_id = ? ORDER BY service_type ASC, term_number ASC");
    $stmt->execute([$subscription['id']]);
    $schedules = $stmt->fetchAll();

    sendJsonResponse(true, 'Recharge subscription and schedules fetched successfully.', [
        'has_subscription' => true,
        'subscription' => $subscription,
        'schedules' => $schedules
    ]);

} elseif ($method === 'POST') {
    $input = getJsonInput();
    $action = trim($input['action'] ?? '');
    $schedule_id = (int)($input['schedule_id'] ?? 0);

    if ($action !== 'request_gas' || $schedule_id <= 0) {
        sendJsonResponse(false, 'Invalid action or schedule_id. Action must be request_gas.', null, 400);
    }

    $stmt = $pdo->prepare("SELECT rs.*, sub.member_id FROM recharge_schedules rs JOIN recharge_subscriptions sub ON rs.subscription_id = sub.id WHERE rs.id = ? AND sub.member_id = ? AND rs.service_type = 'Gas'");
    $stmt->execute([$schedule_id, $member['member_id']]);
    $schedule = $stmt->fetch();

    if (!$schedule) {
        sendJsonResponse(false, 'Gas schedule term not found or does not belong to member.', null, 404);
    }

    if ($schedule['status'] !== 'Scheduled') {
        sendJsonResponse(false, "Gas term status is already '{$schedule['status']}'.", null, 400);
    }

    $stmt = $pdo->prepare("UPDATE recharge_schedules SET status = 'Requested', due_date = NOW() WHERE id = ?");
    $stmt->execute([$schedule_id]);

    sendJsonResponse(true, "Gas refill request for Term #{$schedule['term_number']} submitted successfully.");

} else {
    sendJsonResponse(false, 'Method not allowed.', null, 405);
}
