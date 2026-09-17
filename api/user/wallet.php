<?php
// api/user/wallet.php
require_once __DIR__ . '/../includes/api_helpers.php';

$pdo = getDBConnection();
$auth = authenticateApiRequest($pdo, 'member');
$member = $auth['user'];
$member_id = $member['member_id'];

ensureWalletExists($pdo, $member_id);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Fetch wallet balances
    $stmt = $pdo->prepare("SELECT * FROM wallets WHERE member_id = ?");
    $stmt->execute([$member_id]);
    $wallet = $stmt->fetch();

    // Fetch transaction ledger
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE member_id = ? ORDER BY id DESC LIMIT 100");
    $stmt->execute([$member_id]);
    $transactions = $stmt->fetchAll();

    // Fetch withdrawal requests
    $stmt = $pdo->prepare("SELECT * FROM withdrawals WHERE member_id = ? ORDER BY id DESC");
    $stmt->execute([$member_id]);
    $withdrawals = $stmt->fetchAll();

    sendJsonResponse(true, 'Wallet summary fetched successfully.', [
        'wallet' => [
            'balance' => (float)$wallet['balance'],
            'user_wallet_60' => (float)$wallet['user_wallet_60'],
            'company_wallet_40' => (float)$wallet['company_wallet_40'],
            'p2_reserve_wallet' => (float)$wallet['p2_reserve_wallet']
        ],
        'transactions' => $transactions,
        'withdrawals' => $withdrawals
    ]);

} elseif ($method === 'POST') {
    // Process withdrawal request
    $input = getJsonInput();
    $amount = (float)($input['amount'] ?? 0.00);

    if ($amount < 500.00) {
        sendJsonResponse(false, 'Minimum withdrawal request amount is ₹500.00.', null, 400);
    }

    $stmt = $pdo->prepare("SELECT user_wallet_60 FROM wallets WHERE member_id = ?");
    $stmt->execute([$member_id]);
    $user_wallet_60 = (float)($stmt->fetchColumn() ?: 0.00);

    if ($amount > $user_wallet_60) {
        sendJsonResponse(false, 'Insufficient User Wallet balance (60% split) for this request.', null, 400);
    }

    try {
        $pdo->beginTransaction();

        // Deduct from User Wallet balance
        $stmt = $pdo->prepare("UPDATE wallets SET user_wallet_60 = user_wallet_60 - ? WHERE member_id = ?");
        $stmt->execute([$amount, $member_id]);

        // Create pending withdrawal record
        $stmt = $pdo->prepare("INSERT INTO withdrawals (member_id, amount, status) VALUES (?, ?, 'Pending')");
        $stmt->execute([$member_id, $amount]);

        // Log transaction
        $stmt = $pdo->prepare("INSERT INTO transactions (member_id, type, amount, wallet_type, status, description) VALUES (?, 'Withdrawal_Request', ?, 'User_Wallet', 'Pending', 'Withdrawal Request Submitted')");
        $stmt->execute([$member_id, $amount]);

        $pdo->commit();

        sendJsonResponse(true, 'Withdrawal request submitted successfully.', [
            'requested_amount' => $amount,
            'remaining_user_wallet' => $user_wallet_60 - $amount
        ]);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        sendJsonResponse(false, 'Failed to process withdrawal request: ' . $e->getMessage(), null, 500);
    }
} else {
    sendJsonResponse(false, 'Method not allowed.', null, 405);
}
