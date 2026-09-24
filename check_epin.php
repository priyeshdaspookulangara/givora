<?php
header('Content-Type: application/json');
require_once __DIR__ . '/includes/functions.php';

$epin_code = trim($_GET['epin'] ?? '');

if (empty($epin_code)) {
    echo json_encode(['valid' => false, 'message' => 'ePIN code is required.']);
    exit;
}

$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT epin_code, package_type, amount, status FROM epins WHERE epin_code = ?");
$stmt->execute([$epin_code]);
$epin = $stmt->fetch();

if (!$epin) {
    echo json_encode(['valid' => false, 'message' => 'Invalid ePIN code.']);
    exit;
}

if ($epin['status'] !== 'Unused') {
    echo json_encode(['valid' => false, 'message' => 'This ePIN has already been used.']);
    exit;
}

$is_utility_package = in_array($epin['package_type'], ['Recharge_1200', 'Gas_3000', 'Recharge_Bundle_5400']);
$is_charity_package = ($epin['package_type'] === 'Charity_10000');

echo json_encode([
    'valid' => true,
    'epin_code' => $epin['epin_code'],
    'package_type' => $epin['package_type'],
    'amount' => (float)$epin['amount'],
    'is_recharge_bundle' => ($epin['package_type'] === 'Recharge_Bundle_5400'),
    'is_utility_package' => $is_utility_package,
    'is_charity_package' => $is_charity_package
]);
