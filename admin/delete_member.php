<?php
require_once __DIR__ . '/../includes/functions.php';
checkAdminLogin();

$pdo = getDBConnection();
$member_id = trim($_REQUEST['member_id'] ?? '');

if (empty($member_id)) {
    $_SESSION['admin_msg_error'] = "No member specified for deletion.";
    header("Location: /admin/members.php");
    exit;
}

if ($member_id === 'GT100000') {
    $_SESSION['admin_msg_error'] = "The System Root Member (GT100000) cannot be deleted.";
    header("Location: /admin/members.php");
    exit;
}

// Verify member exists
$stmt = $pdo->prepare("SELECT * FROM members WHERE member_id = ?");
$stmt->execute([$member_id]);
$member = $stmt->fetch();

if (!$member) {
    $_SESSION['admin_msg_error'] = "Member '{$member_id}' not found.";
    header("Location: /admin/members.php");
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Reassign direct placement children to GT100000 to keep matrix tree intact
    $stmt = $pdo->prepare("UPDATE members SET placement_parent_id = 'GT100000' WHERE placement_parent_id = ?");
    $stmt->execute([$member_id]);

    $stmt = $pdo->prepare("UPDATE members SET p2_placement_parent_id = 'GT100000' WHERE p2_placement_parent_id = ?");
    $stmt->execute([$member_id]);

    $stmt = $pdo->prepare("UPDATE members SET sponsor_id = 'GT100000' WHERE sponsor_id = ?");
    $stmt->execute([$member_id]);

    // 2. Reset ePINs used or assigned to this member
    $stmt = $pdo->prepare("UPDATE epins SET status = 'Unused', used_by_member_id = NULL WHERE used_by_member_id = ?");
    $stmt->execute([$member_id]);

    $stmt = $pdo->prepare("UPDATE epins SET assigned_to = NULL WHERE assigned_to = ?");
    $stmt->execute([$member_id]);

    // 3. Delete all transactions, withdrawals, wallets, and API tokens
    $stmt = $pdo->prepare("DELETE FROM transactions WHERE member_id = ?");
    $stmt->execute([$member_id]);

    $stmt = $pdo->prepare("DELETE FROM withdrawals WHERE member_id = ?");
    $stmt->execute([$member_id]);

    $stmt = $pdo->prepare("DELETE FROM wallets WHERE member_id = ?");
    $stmt->execute([$member_id]);

    try {
        $stmt = $pdo->prepare("DELETE FROM api_tokens WHERE user_id = ? AND user_type = 'member'");
        $stmt->execute([$member_id]);
    } catch (Exception $tokenErr) {
        // Ignore if api_tokens table has not been migrated on target database
    }

    // 4. Delete member record
    $stmt = $pdo->prepare("DELETE FROM members WHERE member_id = ?");
    $stmt->execute([$member_id]);

    $pdo->commit();

    $_SESSION['admin_msg_success'] = "Member '{$member['name']}' ({$member_id}) and all associated wallet balances and transactions have been successfully deleted.";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['admin_msg_error'] = "Failed to delete member: " . $e->getMessage();
}

header("Location: /admin/members.php");
exit;
