<?php
// api/admin/epins.php
require_once __DIR__ . '/../includes/api_helpers.php';

$pdo = getDBConnection();
$auth = authenticateApiRequest($pdo, 'admin');
$admin = $auth['user'];

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // List ePINs
    $status_filter = trim($_GET['status'] ?? '');

    $sql = "SELECT * FROM epins WHERE 1=1";
    $params = [];

    if (!empty($status_filter)) {
        $sql .= " AND status = ?";
        $params[] = $status_filter;
    }

    $sql .= " ORDER BY id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $epins = $stmt->fetchAll();

    sendJsonResponse(true, 'ePIN list fetched successfully.', [
        'count' => count($epins),
        'epins' => $epins
    ]);

} elseif ($method === 'POST') {
    // Generate ePINs
    $input = getJsonInput();
    $package_type = trim($input['package_type'] ?? 'Foundation_5000');
    $quantity = (int)($input['quantity'] ?? 1);
    $assigned_to = trim($input['assigned_to'] ?? '');

    if (!in_array($package_type, ['Foundation_5000', 'Leadership_15000'])) {
        sendJsonResponse(false, 'Invalid package type. Must be Foundation_5000 or Leadership_15000.', null, 400);
    }

    if ($quantity < 1 || $quantity > 100) {
        sendJsonResponse(false, 'Quantity must be between 1 and 100.', null, 400);
    }

    if (!empty($assigned_to)) {
        $stmt = $pdo->prepare("SELECT member_id FROM members WHERE member_id = ?");
        $stmt->execute([$assigned_to]);
        if (!$stmt->fetch()) {
            sendJsonResponse(false, "Assigned Member ID '{$assigned_to}' does not exist.", null, 400);
        }
    }

    $generated = [];
    for ($i = 0; $i < $quantity; $i++) {
        $code = generateEpinCode();
        $stmt = $pdo->prepare("INSERT INTO epins (epin_code, package_type, status, generated_by_admin_id, assigned_to) VALUES (?, ?, 'Unused', ?, ?)");
        $stmt->execute([$code, $package_type, $admin['id'], !empty($assigned_to) ? $assigned_to : null]);
        $generated[] = $code;
    }

    sendJsonResponse(true, "Successfully generated {$quantity} ePIN(s).", [
        'generated_codes' => $generated,
        'package_type' => $package_type,
        'assigned_to' => $assigned_to
    ], 201);

} else {
    sendJsonResponse(false, 'Method not allowed.', null, 405);
}
