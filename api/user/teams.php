<?php
// api/user/teams.php
require_once __DIR__ . '/../includes/api_helpers.php';

$pdo = getDBConnection();
$auth = authenticateApiRequest($pdo, 'member');
$member = $auth['user'];
$member_id = $member['member_id'];

// Get Phase 1 Direct Matrix Positions (Level 1 visual tree)
$stmt = $pdo->prepare("SELECT member_id, name, package_type, status, created_at, matrix_position FROM members WHERE placement_parent_id = ? ORDER BY matrix_position ASC");
$stmt->execute([$member_id]);
$p1_directs = $stmt->fetchAll();

// Get Phase 2 Direct Matrix Positions (Level 1 visual tree in Phase 2)
$stmt = $pdo->prepare("SELECT member_id, name, package_type, status, p2_status, p2_created_at, p2_matrix_position FROM members WHERE p2_placement_parent_id = ? AND p2_status = 'Active' ORDER BY p2_matrix_position ASC");
$stmt->execute([$member_id]);
$p2_directs = $stmt->fetchAll();

// Helper to retrieve BFS downline up to N levels
function getDownlineLevels($pdo, $root_id, $max_levels = 7, $is_phase_2 = false) {
    $levels = [];
    $current_level_parents = [$root_id];

    for ($lvl = 1; $lvl <= $max_levels; $lvl++) {
        if (empty($current_level_parents)) break;

        $in_clause = implode(',', array_fill(0, count($current_level_parents), '?'));

        if ($is_phase_2) {
            $sql = "SELECT member_id, name, email, phone, package_type, p2_status AS status, p2_placement_parent_id AS parent_id, p2_matrix_position AS position, p2_created_at AS join_date FROM members WHERE p2_placement_parent_id IN ($in_clause) AND p2_status = 'Active' ORDER BY p2_matrix_position ASC";
        } else {
            $sql = "SELECT member_id, name, email, phone, package_type, status, placement_parent_id AS parent_id, matrix_position AS position, created_at AS join_date FROM members WHERE placement_parent_id IN ($in_clause) ORDER BY matrix_position ASC";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($current_level_parents);
        $children = $stmt->fetchAll();

        if (empty($children)) break;

        $levels[$lvl] = $children;
        $current_level_parents = array_column($children, 'member_id');
    }

    return $levels;
}

$downline_p1 = getDownlineLevels($pdo, $member_id, 6, false);
$downline_p2 = getDownlineLevels($pdo, $member_id, 7, true);

sendJsonResponse(true, 'Team tree structure and downline fetched successfully.', [
    'member_id' => $member_id,
    'phase_1' => [
        'status' => $member['status'],
        'direct_tree' => $p1_directs,
        'downline_levels' => $downline_p1
    ],
    'phase_2' => [
        'status' => $member['p2_status'],
        'direct_tree' => $p2_directs,
        'downline_levels' => $downline_p2
    ]
]);
