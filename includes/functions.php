<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

// Base URL calculation
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $protocol . '://' . $host;
}

// Authentication Helpers
function checkMemberLogin() {
    if (!isset($_SESSION['member_id'])) {
        header("Location: " . getBaseUrl() . "/login.php");
        exit;
    }
}

function checkAdminLogin() {
    if (!isset($_SESSION['admin_id'])) {
        header("Location: " . getBaseUrl() . "/admin_login.php");
        exit;
    }
}

function getLoggedInMember() {
    if (!isset($_SESSION['member_id'])) return null;
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM members WHERE member_id = ?");
    $stmt->execute([$_SESSION['member_id']]);
    return $stmt->fetch();
}

// Unique Member ID Generator (GT + 6 digits)
function generateMemberId($pdo) {
    do {
        $digits = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $member_id = 'GT' . $digits;
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE member_id = ?");
        $stmt->execute([$member_id]);
        $exists = $stmt->fetchColumn();
    } while ($exists > 0);

    return $member_id;
}

// Matrix Placement Logic (3-matrix)
// Finds the next open placement under $start_parent_id using Breadth-First Search (BFS)
function findMatrixPlacement($pdo, $start_parent_id) {
    if (empty($start_parent_id)) {
        $start_parent_id = 'GT100000'; // Default root
    }

    // Verify start parent exists
    $stmt = $pdo->prepare("SELECT member_id FROM members WHERE member_id = ?");
    $stmt->execute([$start_parent_id]);
    if (!$stmt->fetch()) {
        $start_parent_id = 'GT100000';
    }

    $queue = [$start_parent_id];
    $visited = [];

    while (!empty($queue)) {
        $current_parent = array_shift($queue);
        if (isset($visited[$current_parent])) continue;
        $visited[$current_parent] = true;

        // Get occupied positions under current_parent
        $stmt = $pdo->prepare("SELECT matrix_position FROM members WHERE placement_parent_id = ? ORDER BY matrix_position ASC");
        $stmt->execute([$current_parent]);
        $occupied = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Check positions 1, 2, 3
        for ($pos = 1; $pos <= 3; $pos++) {
            if (!in_array($pos, $occupied)) {
                return [
                    'parent_id' => $current_parent,
                    'position' => $pos
                ];
            }
        }

        // If all 3 positions filled, push children to queue
        $stmt = $pdo->prepare("SELECT member_id FROM members WHERE placement_parent_id = ? ORDER BY matrix_position ASC");
        $stmt->execute([$current_parent]);
        $children = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($children as $child) {
            $queue[] = $child;
        }
    }

    return ['parent_id' => 'GT100000', 'position' => 1];
}

// Phase 2 Matrix Placement Logic (3-matrix for Phase 2)
function findMatrixPlacementP2($pdo, $start_parent_id = 'GT100000') {
    if (empty($start_parent_id)) {
        $start_parent_id = 'GT100000';
    }

    $queue = [$start_parent_id];
    $visited = [];

    while (!empty($queue)) {
        $current_parent = array_shift($queue);
        if (isset($visited[$current_parent])) continue;
        $visited[$current_parent] = true;

        // Check occupied positions under current_parent in Phase 2
        $stmt = $pdo->prepare("SELECT p2_matrix_position FROM members WHERE p2_placement_parent_id = ? AND p2_status = 'Active' ORDER BY p2_matrix_position ASC");
        $stmt->execute([$current_parent]);
        $occupied = $stmt->fetchAll(PDO::FETCH_COLUMN);

        for ($pos = 1; $pos <= 3; $pos++) {
            if (!in_array($pos, $occupied)) {
                return [
                    'parent_id' => $current_parent,
                    'position' => $pos
                ];
            }
        }

        // If all 3 positions filled in P2, push P2 children to queue
        $stmt = $pdo->prepare("SELECT member_id FROM members WHERE p2_placement_parent_id = ? AND p2_status = 'Active' ORDER BY p2_matrix_position ASC");
        $stmt->execute([$current_parent]);
        $children = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($children as $child) {
            $queue[] = $child;
        }
    }

    return ['parent_id' => 'GT100000', 'position' => 1];
}

// Phase 2 Auto-Promotion Check
function checkAndPromoteToPhase2($pdo, $member_id) {
    if (empty($member_id) || $member_id === 'GT100000') return;

    // Check if member is in Phase 1 and currently Inactive in Phase 2
    $stmt = $pdo->prepare("SELECT package_type, p2_status FROM members WHERE member_id = ?");
    $stmt->execute([$member_id]);
    $member = $stmt->fetch();

    if (!$member || $member['p2_status'] === 'Active') return;

    // Count Phase 1 direct matrix children (3-matrix completion)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE placement_parent_id = ?");
    $stmt->execute([$member_id]);
    $child_count = $stmt->fetchColumn();

    if ($child_count >= 3) {
        // Find Phase 2 placement
        $p2_placement = findMatrixPlacementP2($pdo, 'GT100000');

        // Promote member to Phase 2
        $stmt = $pdo->prepare("UPDATE members SET p2_status = 'Active', p2_placement_parent_id = ?, p2_matrix_position = ?, p2_created_at = NOW() WHERE member_id = ?");
        $stmt->execute([$p2_placement['parent_id'], $p2_placement['position'], $member_id]);

        // Distribute Phase 2 Matrix Commissions
        $package_amount = ($member['package_type'] === 'Leadership_15000') ? 15000 : 5000;
        $level_percentages = [0.05, 0.04, 0.03, 0.02, 0.015, 0.01, 0.005];

        $curr_p2_parent = $p2_placement['parent_id'];

        for ($level = 0; $level < count($level_percentages); $level++) {
            if (empty($curr_p2_parent)) break;

            $commission_amount = $package_amount * $level_percentages[$level];
            $user_part = $commission_amount * 0.60;
            $company_part = $commission_amount * 0.40;

            ensureWalletExists($pdo, $curr_p2_parent);

            // Update parent wallet
            $stmt = $pdo->prepare("UPDATE wallets SET balance = balance + ?, user_wallet_60 = user_wallet_60 + ?, company_wallet_40 = company_wallet_40 + ? WHERE member_id = ?");
            $stmt->execute([$commission_amount, $user_part, $company_part, $curr_p2_parent]);

            // Log Phase 2 transaction
            $level_num = $level + 1;
            $stmt = $pdo->prepare("INSERT INTO transactions (member_id, type, amount, wallet_type, status, description) VALUES (?, 'Matrix_Income_P2', ?, 'User_Wallet', 'Credit', ?)");
            $stmt->execute([$curr_p2_parent, $user_part, "Phase 2 Matrix L{$level_num} Commission (60%) from " . $member_id]);

            $stmt = $pdo->prepare("INSERT INTO transactions (member_id, type, amount, wallet_type, status, description) VALUES (?, 'Matrix_Income_P2', ?, 'Company_Wallet', 'Credit', ?)");
            $stmt->execute([$curr_p2_parent, $company_part, "Phase 2 Matrix L{$level_num} Commission (40%) from " . $member_id]);

            // Fetch next P2 parent
            $stmt = $pdo->prepare("SELECT p2_placement_parent_id FROM members WHERE member_id = ?");
            $stmt->execute([$curr_p2_parent]);
            $curr_p2_parent = $stmt->fetchColumn();
        }
    }
}

// ePIN Generation
function generateEpinCode() {
    return 'GIV-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));
}

// Wallet initialization
function ensureWalletExists($pdo, $member_id) {
    $stmt = $pdo->prepare("INSERT INTO wallets (member_id, balance, user_wallet_60, company_wallet_40) VALUES (?, 0.00, 0.00, 0.00) ON DUPLICATE KEY UPDATE id=id");
    $stmt->execute([$member_id]);
}

// Commission & Bonus Processing
function distributeCommissions($pdo, $new_member_id, $sponsor_id, $package_type) {
    // Package parameters
    $package_amount = ($package_type === 'Leadership_15000') ? 15000 : 5000;

    // 1. Direct Referral Bonus (10% of package)
    if (!empty($sponsor_id)) {
        $stmt = $pdo->prepare("SELECT member_id FROM members WHERE member_id = ?");
        $stmt->execute([$sponsor_id]);
        if ($stmt->fetch()) {
            $direct_bonus = ($package_type === 'Leadership_15000') ? 1500.00 : 500.00;
            $user_part = $direct_bonus * 0.60;
            $company_part = $direct_bonus * 0.40;

            ensureWalletExists($pdo, $sponsor_id);

            // Update sponsor wallet
            $stmt = $pdo->prepare("UPDATE wallets SET balance = balance + ?, user_wallet_60 = user_wallet_60 + ?, company_wallet_40 = company_wallet_40 + ? WHERE member_id = ?");
            $stmt->execute([$direct_bonus, $user_part, $company_part, $sponsor_id]);

            // Log transactions
            $stmt = $pdo->prepare("INSERT INTO transactions (member_id, type, amount, wallet_type, status, description) VALUES (?, 'Direct_Referral', ?, 'User_Wallet', 'Credit', ?)");
            $stmt->execute([$sponsor_id, $user_part, "Direct Referral Bonus (60%) for " . $new_member_id]);

            $stmt = $pdo->prepare("INSERT INTO transactions (member_id, type, amount, wallet_type, status, description) VALUES (?, 'Direct_Referral', ?, 'Company_Wallet', 'Credit', ?)");
            $stmt->execute([$sponsor_id, $company_part, "Direct Referral Bonus (40%) for " . $new_member_id]);
        }
    }

    // 2. 3-Matrix Level Income (Up to 7 levels up the matrix parent chain)
    // Level commissions: L1: 5%, L2: 4%, L3: 3%, L4: 2%, L5: 1.5%, L6: 1%, L7: 0.5%
    $level_percentages = [0.05, 0.04, 0.03, 0.02, 0.015, 0.01, 0.005];

    $stmt = $pdo->prepare("SELECT placement_parent_id FROM members WHERE member_id = ?");
    $stmt->execute([$new_member_id]);
    $direct_placement_parent = $stmt->fetchColumn();
    $curr_parent = $direct_placement_parent;

    for ($level = 0; $level < count($level_percentages); $level++) {
        if (empty($curr_parent)) break;

        $commission_amount = $package_amount * $level_percentages[$level];
        $user_part = $commission_amount * 0.60;
        $company_part = $commission_amount * 0.40;

        ensureWalletExists($pdo, $curr_parent);

        // Update parent wallet
        $stmt = $pdo->prepare("UPDATE wallets SET balance = balance + ?, user_wallet_60 = user_wallet_60 + ?, company_wallet_40 = company_wallet_40 + ? WHERE member_id = ?");
        $stmt->execute([$commission_amount, $user_part, $company_part, $curr_parent]);

        // Log transactions
        $level_num = $level + 1;
        $stmt = $pdo->prepare("INSERT INTO transactions (member_id, type, amount, wallet_type, status, description) VALUES (?, 'Matrix_Income_P1', ?, 'User_Wallet', 'Credit', ?)");
        $stmt->execute([$curr_parent, $user_part, "Phase 1 Matrix L{$level_num} Commission (60%) from " . $new_member_id]);

        $stmt = $pdo->prepare("INSERT INTO transactions (member_id, type, amount, wallet_type, status, description) VALUES (?, 'Matrix_Income_P1', ?, 'Company_Wallet', 'Credit', ?)");
        $stmt->execute([$curr_parent, $company_part, "Phase 1 Matrix L{$level_num} Commission (40%) from " . $new_member_id]);

        // Fetch next parent up
        $stmt = $pdo->prepare("SELECT placement_parent_id FROM members WHERE member_id = ?");
        $stmt->execute([$curr_parent]);
        $curr_parent = $stmt->fetchColumn();
    }

    // 3. Check if the direct placement parent has now completed Phase 1 matrix (3 children) to promote to Phase 2
    if (!empty($direct_placement_parent)) {
        checkAndPromoteToPhase2($pdo, $direct_placement_parent);
    }
}
