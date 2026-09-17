<?php
// api/admin/members.php
require_once __DIR__ . '/../includes/api_helpers.php';

$pdo = getDBConnection();
$auth = authenticateApiRequest($pdo, 'admin');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'DELETE' || (isset($_GET['action']) && $_GET['action'] === 'delete')) {
    $input = getJsonInput();
    $member_id = trim($input['member_id'] ?? $_GET['member_id'] ?? '');

    if (empty($member_id)) {
        sendJsonResponse(false, 'member_id is required for deletion.', null, 400);
    }

    if ($member_id === 'GT100000') {
        sendJsonResponse(false, 'Root member (GT100000) cannot be deleted.', null, 400);
    }

    $stmt = $pdo->prepare("SELECT * FROM members WHERE member_id = ?");
    $stmt->execute([$member_id]);
    $member = $stmt->fetch();

    if (!$member) {
        sendJsonResponse(false, "Member '{$member_id}' not found.", null, 404);
    }

    try {
        $pdo->beginTransaction();

        // 1. Reassign placement children to Root
        $stmt = $pdo->prepare("UPDATE members SET placement_parent_id = 'GT100000' WHERE placement_parent_id = ?");
        $stmt->execute([$member_id]);

        $stmt = $pdo->prepare("UPDATE members SET p2_placement_parent_id = 'GT100000' WHERE p2_placement_parent_id = ?");
        $stmt->execute([$member_id]);

        $stmt = $pdo->prepare("UPDATE members SET sponsor_id = 'GT100000' WHERE sponsor_id = ?");
        $stmt->execute([$member_id]);

        // 2. Reset ePINs
        $stmt = $pdo->prepare("UPDATE epins SET status = 'Unused', used_by_member_id = NULL WHERE used_by_member_id = ?");
        $stmt->execute([$member_id]);

        $stmt = $pdo->prepare("UPDATE epins SET assigned_to = NULL WHERE assigned_to = ?");
        $stmt->execute([$member_id]);

        // 3. Delete transactions, withdrawals, wallets, API tokens
        $stmt = $pdo->prepare("DELETE FROM transactions WHERE member_id = ?");
        $stmt->execute([$member_id]);

        $stmt = $pdo->prepare("DELETE FROM withdrawals WHERE member_id = ?");
        $stmt->execute([$member_id]);

        $stmt = $pdo->prepare("DELETE FROM wallets WHERE member_id = ?");
        $stmt->execute([$member_id]);

        $stmt = $pdo->prepare("DELETE FROM api_tokens WHERE user_id = ? AND user_type = 'member'");
        $stmt->execute([$member_id]);

        // 4. Delete member
        $stmt = $pdo->prepare("DELETE FROM members WHERE member_id = ?");
        $stmt->execute([$member_id]);

        $pdo->commit();

        sendJsonResponse(true, "Member '{$member_id}' and all financial records deleted successfully.", [
            'deleted_member_id' => $member_id
        ]);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        sendJsonResponse(false, 'Deletion failed: ' . $e->getMessage(), null, 500);
    }
}

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

// Add WhatsApp greeting link and matrix tree URL to each member
$baseUrl = getBaseUrl();
foreach ($members as &$m) {
    $login_url = $baseUrl . "/login.php";
    $message = "Welcome to Givora Traders LLP! Your Member ID is: " . $m['member_id'] . " and Password is: " . $m['password'] . " . Login here: " . $login_url;
    $clean_phone = preg_replace('/[^0-9]/', '', $m['phone']);
    $m['whatsapp_url'] = "https://wa.me/" . $clean_phone . "?text=" . urlencode($message);
    $m['whatsapp_text'] = $message;
    $m['matrix_tree_url'] = $baseUrl . "/admin/matrix_tree.php?member_id=" . $m['member_id'];
}

sendJsonResponse(true, 'Admin members list fetched successfully.', [
    'count' => count($members),
    'members' => $members
]);
