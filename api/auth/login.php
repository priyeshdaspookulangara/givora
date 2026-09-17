<?php
// api/auth/login.php
require_once __DIR__ . '/../includes/api_helpers.php';

$pdo = getDBConnection();
$input = getJsonInput();

$account_type = trim($input['account_type'] ?? 'member'); // 'member' or 'admin'
$username_or_id = trim($input['username_or_id'] ?? $input['member_id'] ?? $input['username'] ?? '');
$password = trim($input['password'] ?? '');

if (empty($username_or_id) || empty($password)) {
    sendJsonResponse(false, 'Username/Member ID and Password are required.', null, 400);
}

if ($account_type === 'admin') {
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username_or_id]);
    $admin = $stmt->fetch();

    if ($admin && $admin['password'] === $password) {
        $tokenData = generateApiToken($pdo, 'admin', $admin['id']);
        sendJsonResponse(true, 'Admin login successful.', [
            'user_type' => 'admin',
            'user' => [
                'id' => $admin['id'],
                'username' => $admin['username'],
                'created_at' => $admin['created_at']
            ],
            'access_token' => $tokenData['token'],
            'expires_at' => $tokenData['expires_at']
        ]);
    } else {
        sendJsonResponse(false, 'Invalid admin username or password.', null, 401);
    }
} else {
    $stmt = $pdo->prepare("SELECT * FROM members WHERE member_id = ?");
    $stmt->execute([strtoupper($username_or_id)]);
    $member = $stmt->fetch();

    if ($member && $member['password'] === $password) {
        if ($member['status'] !== 'Active') {
            sendJsonResponse(false, 'Your account is currently inactive. Contact support.', null, 403);
        }

        $tokenData = generateApiToken($pdo, 'member', $member['member_id']);

        unset($member['password']); // Do not expose password
        sendJsonResponse(true, 'Member login successful.', [
            'user_type' => 'member',
            'user' => $member,
            'access_token' => $tokenData['token'],
            'expires_at' => $tokenData['expires_at']
        ]);
    } else {
        sendJsonResponse(false, 'Invalid Member ID or Password.', null, 401);
    }
}
