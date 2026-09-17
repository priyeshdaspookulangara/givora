<?php
// api/admin/withdrawals.php
require_once __DIR__ . '/../includes/api_helpers.php';

$pdo = getDBConnection();
$auth = authenticateApiRequest($pdo, 'admin');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $status_filter = trim($_GET['status'] ?? '');

    $sql = "SELECT w.*, m.name, m.phone, m.email FROM withdrawals w JOIN members m ON w.member_id = m.member_id WHERE 1=1";
    $params = [];

    if (!empty($status_filter)) {
        $sql .= " AND w.status = ?";
        $params[] = $status_filter;
    }

    $sql .= " ORDER BY w.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $withdrawals = $stmt->fetchAll();

    sendJsonResponse(true, 'Withdrawal requests list fetched successfully.', [
        'count' => count($withdrawals),
        'withdrawals' => $withdrawals
    ]);

} elseif ($method === 'POST') {
    // Approve or Reject Withdrawal Request
    $input = getJsonInput();
    $withdrawal_id = (int)($input['withdrawal_id'] ?? 0);
    $action = trim($input['action'] ?? ''); // 'approve' or 'reject'

    if ($withdrawal_id <= 0 || !in_array($action, ['approve', 'reject'])) {
        sendJsonResponse(false, 'Invalid withdrawal_id or action (must be approve or reject).', null, 400);
    }

    $stmt = $pdo->prepare("SELECT * FROM withdrawals WHERE id = ?");
    $stmt->execute([$withdrawal_id]);
    $withdrawal = $stmt->fetch();

    if (!$withdrawal) {
        sendJsonResponse(false, 'Withdrawal request not found.', 404);
    }

    if ($withdrawal['status'] !== 'Pending') {
        sendJsonResponse(false, "Withdrawal request is already {$withdrawal['status']}.", null, 400);
    }

    try {
        $pdo->beginTransaction();

        if ($action === 'approve') {
            $stmt = $pdo->prepare("UPDATE withdrawals SET status = 'Approved', processed_date = NOW() WHERE id = ?");
            $stmt->execute([$withdrawal_id]);

            // Update transaction status
            $stmt = $pdo->prepare("UPDATE transactions SET status = 'Approved' WHERE member_id = ? AND type = 'Withdrawal_Request' AND amount = ? AND status = 'Pending' LIMIT 1");
            $stmt->execute([$withdrawal['member_id'], $withdrawal['amount']]);

            $message = "Withdrawal request of ₹{$withdrawal['amount']} approved successfully.";

        } else { // reject
            $stmt = $pdo->prepare("UPDATE withdrawals SET status = 'Rejected', processed_date = NOW() WHERE id = ?");
            $stmt->execute([$withdrawal_id]);

            // Refund User Wallet balance
            $stmt = $pdo->prepare("UPDATE wallets SET user_wallet_60 = user_wallet_60 + ? WHERE member_id = ?");
            $stmt->execute([$withdrawal['amount'], $withdrawal['member_id']]);

            // Log refund transaction
            $stmt = $pdo->prepare("INSERT INTO transactions (member_id, type, amount, wallet_type, status, description) VALUES (?, 'Admin_Adjustment', ?, 'User_Wallet', 'Credit', 'Refund for Rejected Withdrawal Request')");
            $stmt->execute([$withdrawal['member_id'], $withdrawal['amount']]);

            $message = "Withdrawal request of ₹{$withdrawal['amount']} rejected and refunded to User Wallet.";
        }

        $pdo->commit();

        sendJsonResponse(true, $message, [
            'withdrawal_id' => $withdrawal_id,
            'status' => ($action === 'approve') ? 'Approved' : 'Rejected'
        ]);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        sendJsonResponse(false, 'Failed to process withdrawal action: ' . $e->getMessage(), null, 500);
    }
} else {
    sendJsonResponse(false, 'Method not allowed.', null, 405);
}
