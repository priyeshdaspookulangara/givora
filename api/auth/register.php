<?php
// api/auth/register.php
require_once __DIR__ . '/../includes/api_helpers.php';

$pdo = getDBConnection();
$input = getJsonInput();

$sponsor_id = trim($input['sponsor_id'] ?? 'GT100000');
$placement_parent_id = trim($input['placement_parent_id'] ?? '');
$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$phone = trim($input['phone'] ?? '');
$password = trim($input['password'] ?? '');
$epin_code = trim($input['epin_code'] ?? $input['epin'] ?? '');

if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($epin_code)) {
    sendJsonResponse(false, 'All basic fields are required (name, email, phone, password, epin_code).', null, 400);
}

// 1. Verify ePIN exists and is Unused
$stmt = $pdo->prepare("SELECT * FROM epins WHERE epin_code = ? AND status = 'Unused'");
$stmt->execute([$epin_code]);
$epin = $stmt->fetch();

if (!$epin) {
    sendJsonResponse(false, 'Invalid or already used ePIN code.', null, 400);
}

$package_type = $epin['package_type'];

if ($package_type === 'Recharge_Bundle_5400') {
    $mobile_1 = trim($input['mobile_1'] ?? '');
    $operator_1 = trim($input['operator_1'] ?? '');
    $mobile_2 = trim($input['mobile_2'] ?? '');
    $operator_2 = trim($input['operator_2'] ?? '');
    $gas_provider = trim($input['gas_provider'] ?? '');
    $gas_consumer_number = trim($input['gas_consumer_number'] ?? '');
    $gas_customer_name = trim($input['gas_customer_name'] ?? '');

    if (empty($mobile_1) || empty($operator_1) || empty($mobile_2) || empty($operator_2) || empty($gas_provider) || empty($gas_consumer_number) || empty($gas_customer_name)) {
        sendJsonResponse(false, 'Recharge Bundle requires mobile_1, operator_1, mobile_2, operator_2, gas_provider, gas_consumer_number, and gas_customer_name.', null, 400);
    }
}

// 2. Verify Sponsor
$stmt = $pdo->prepare("SELECT member_id FROM members WHERE member_id = ?");
$stmt->execute([$sponsor_id]);
if (!$stmt->fetch()) {
    $sponsor_id = 'GT100000';
}

// 3. Generate unique Member ID
$new_member_id = generateMemberId($pdo);

try {
    $pdo->beginTransaction();

    if ($package_type === 'Recharge_Bundle_5400') {
        // Recharge bundle: No matrix placement or level commissions
        $stmt = $pdo->prepare("INSERT INTO members (member_id, sponsor_id, placement_parent_id, matrix_position, name, email, phone, password, used_epin, package_type, status) VALUES (?, ?, NULL, NULL, ?, ?, ?, ?, ?, 'Recharge_Bundle_5400', 'Active')");
        $stmt->execute([
            $new_member_id,
            $sponsor_id,
            $name,
            $email,
            $phone,
            $password,
            $epin_code
        ]);

        // Mark ePIN as Used
        $stmt = $pdo->prepare("UPDATE epins SET status = 'Used', used_by_member_id = ? WHERE id = ?");
        $stmt->execute([$new_member_id, $epin['id']]);

        // Initialize wallet
        ensureWalletExists($pdo, $new_member_id);

        // Create recharge subscription & schedules
        createRechargeSubscription($pdo, $new_member_id, $epin_code, $mobile_1, $operator_1, $mobile_2, $operator_2, $gas_provider, $gas_consumer_number, $gas_customer_name);

    } else {
        // Standard Matrix Placement
        $placement = findMatrixPlacement($pdo, !empty($placement_parent_id) ? $placement_parent_id : $sponsor_id);
        $actual_parent_id = $placement['parent_id'];
        $matrix_pos = $placement['position'];

        // Insert new member
        $stmt = $pdo->prepare("INSERT INTO members (member_id, sponsor_id, placement_parent_id, matrix_position, name, email, phone, password, used_epin, package_type, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')");
        $stmt->execute([
            $new_member_id,
            $sponsor_id,
            $actual_parent_id,
            $matrix_pos,
            $name,
            $email,
            $phone,
            $password,
            $epin_code,
            $package_type
        ]);

        // Mark ePIN as Used
        $stmt = $pdo->prepare("UPDATE epins SET status = 'Used', used_by_member_id = ? WHERE id = ?");
        $stmt->execute([$new_member_id, $epin['id']]);

        // Initialize wallet
        ensureWalletExists($pdo, $new_member_id);

        // Distribute Commissions & Direct Referral Bonus
        distributeCommissions($pdo, $new_member_id, $sponsor_id, $package_type);
    }

    $pdo->commit();

    // Generate login token for immediate session
    $tokenData = generateApiToken($pdo, 'member', $new_member_id);

    // Fetch created user
    $stmt = $pdo->prepare("SELECT * FROM members WHERE member_id = ?");
    $stmt->execute([$new_member_id]);
    $created_member = $stmt->fetch();
    unset($created_member['password']);

    sendJsonResponse(true, "Registration successful! Your Member ID is {$new_member_id}.", [
        'member_id' => $new_member_id,
        'user' => $created_member,
        'access_token' => $tokenData['token'],
        'expires_at' => $tokenData['expires_at']
    ], 201);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    sendJsonResponse(false, 'Registration failed: ' . $e->getMessage(), null, 500);
}
