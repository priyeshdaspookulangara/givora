<?php
require_once __DIR__ . '/../includes/functions.php';
checkMemberLogin();

$member = getLoggedInMember();
$page_title = "My Matrix Team";

$pdo = getDBConnection();

// Recursive Tree Builder (up to level 3 visually)
function buildMatrixTree($pdo, $parent_id, $current_depth = 1, $max_depth = 3) {
    if ($current_depth > $max_depth) return [];

    $stmt = $pdo->prepare("SELECT member_id, name, package_type, matrix_position, created_at FROM members WHERE placement_parent_id = ? ORDER BY matrix_position ASC");
    $stmt->execute([$parent_id]);
    $children = $stmt->fetchAll();

    $tree = [1 => null, 2 => null, 3 => null];
    foreach ($children as $child) {
        $pos = (int)$child['matrix_position'];
        if ($pos >= 1 && $pos <= 3) {
            $child['sub_tree'] = buildMatrixTree($pdo, $child['member_id'], $current_depth + 1, $max_depth);
            $tree[$pos] = $child;
        }
    }
    return $tree;
}

// Downline List Builder (up to 7 levels using BFS)
function getDownlineList($pdo, $root_member_id, $max_level = 7) {
    $downline = [];
    $queue = [['id' => $root_member_id, 'level' => 0]];
    $visited = [];

    while (!empty($queue)) {
        $curr = array_shift($queue);
        $curr_id = $curr['id'];
        $curr_level = $curr['level'];

        if (isset($visited[$curr_id])) continue;
        $visited[$curr_id] = true;

        if ($curr_level > 0) {
            $stmt = $pdo->prepare("SELECT m.*, p.name as parent_name FROM members m LEFT JOIN members p ON m.placement_parent_id = p.member_id WHERE m.member_id = ?");
            $stmt->execute([$curr_id]);
            $info = $stmt->fetch();
            if ($info) {
                $info['level_depth'] = $curr_level;
                $downline[] = $info;
            }
        }

        if ($curr_level < $max_level) {
            $stmt = $pdo->prepare("SELECT member_id FROM members WHERE placement_parent_id = ? ORDER BY matrix_position ASC");
            $stmt->execute([$curr_id]);
            $children = $stmt->fetchAll(PDO::FETCH_COLUMN);
            foreach ($children as $child_id) {
                $queue[] = ['id' => $child_id, 'level' => $curr_level + 1];
            }
        }
    }
    return $downline;
}

$matrix_tree = buildMatrixTree($pdo, $member['member_id'], 1, 3);
$downline_list = getDownlineList($pdo, $member['member_id'], 7);

// Fetch Phase 2 children if member is active in Phase 2
$p2_children = [];
if ($member['p2_status'] === 'Active') {
    $stmt = $pdo->prepare("SELECT member_id, name, package_type, p2_matrix_position, p2_created_at FROM members WHERE p2_placement_parent_id = ? AND p2_status = 'Active' ORDER BY p2_matrix_position ASC");
    $stmt->execute([$member['member_id']]);
    $p2_children = $stmt->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-white"><i class="fas fa-sitemap text-gold mr-2"></i> 3-Matrix Team Tree & Downline</h1>
            <p class="text-xs text-gray-400 mt-1">Showing 3x3 visual matrix representation and complete 7-level tabular downline.</p>
        </div>
        <a href="/customer/dashboard.php" class="text-xs text-gold border border-gold/40 px-3 py-1.5 rounded-lg hover:bg-gold/10">← Dashboard</a>
    </div>

    <!-- Phase 2 Matrix Promotion Banner -->
    <div class="bg-gradient-to-r from-amber-900/40 via-darkcard to-gold/20 p-6 rounded-2xl gold-border-glow mb-8 flex flex-col md:flex-row items-center justify-between gap-4 border border-gold/30">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-xs uppercase font-extrabold tracking-widest px-2.5 py-0.5 rounded bg-amber-500/20 text-amber-300 border border-amber-500/30">Phase 2 Matrix Status</span>
                <?php if ($member['p2_status'] === 'Active'): ?>
                    <span class="text-xs font-bold px-2 py-0.5 rounded bg-green-500/20 text-green-400">PROMOTED & ACTIVE</span>
                <?php else: ?>
                    <span class="text-xs font-bold px-2 py-0.5 rounded bg-gray-500/20 text-gray-400">PENDING PHASE 1 COMPLETION</span>
                <?php endif; ?>
            </div>
            <h3 class="text-lg font-bold text-white mt-2">
                <?php if ($member['p2_status'] === 'Active'): ?>
                    <i class="fas fa-crown text-gold mr-1.5"></i> Congratulations! You are in Phase 2 Matrix.
                <?php else: ?>
                    Complete your Phase 1 matrix (3 direct placements) to automatically qualify for Phase 2 Matrix!
                <?php endif; ?>
            </h3>
            <p class="text-xs text-gray-400 mt-1">When 3 members fill your Phase 1 matrix, you are instantly spillover-placed into the global Phase 2 Matrix to earn Phase 2 commissions.</p>
        </div>
        <?php if ($member['p2_status'] === 'Active'): ?>
            <div class="text-right flex-shrink-0">
                <div class="text-xs text-gray-400">Phase 2 Placement Parent</div>
                <div class="text-sm font-bold font-mono text-gold"><?php echo htmlspecialchars($member['p2_placement_parent_id'] ?: 'GT100000 (Root)'); ?></div>
                <div class="text-xs text-gray-400 mt-0.5">Position #<?php echo htmlspecialchars($member['p2_matrix_position']); ?></div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Visual 3-Matrix Representation -->
    <div class="bg-darkcard p-6 rounded-2xl gold-border-glow mb-12 overflow-x-auto">
        <h2 class="text-lg font-bold text-white mb-6 border-b border-gold/10 pb-3 flex items-center">
            <i class="fas fa-network-wired text-gold mr-2"></i> Visual Matrix Tree Structure
        </h2>

        <div class="min-w-[700px] text-center">
            <!-- Root Member (Self) -->
            <div class="inline-block bg-gradient-to-r from-golddark via-gold to-goldlight text-darkbg p-4 rounded-xl shadow-lg border border-gold font-bold mb-8">
                <div class="text-sm uppercase tracking-wider">Root (You)</div>
                <div class="text-lg"><?php echo htmlspecialchars($member['name']); ?></div>
                <div class="text-xs font-mono font-black"><?php echo htmlspecialchars($member['member_id']); ?></div>
            </div>

            <!-- Level 1 Children (3 Nodes) -->
            <div class="grid grid-cols-3 gap-4 border-t-2 border-gold/30 pt-6 relative">
                <?php for ($p1 = 1; $p1 <= 3; $p1++):
                    $n1 = $matrix_tree[$p1] ?? null;
                ?>
                    <div class="flex flex-col items-center">
                        <div class="text-xs font-bold text-gold mb-1">Pos #<?php echo $p1; ?></div>
                        <?php if ($n1): ?>
                            <div class="bg-darkbg border border-gold/50 rounded-xl p-3 w-full max-w-[200px] text-center shadow-md">
                                <div class="text-sm font-bold text-white truncate"><?php echo htmlspecialchars($n1['name']); ?></div>
                                <div class="text-xs text-gold font-mono font-semibold"><?php echo htmlspecialchars($n1['member_id']); ?></div>
                                <div class="text-[10px] text-gray-400 mt-1"><?php echo str_replace('_', ' ', $n1['package_type']); ?></div>
                            </div>

                            <!-- Level 2 Children under L1 -->
                            <?php if (!empty($n1['sub_tree'])): ?>
                                <div class="w-full border-t border-gold/20 mt-4 pt-4 grid grid-cols-3 gap-1">
                                    <?php for ($p2 = 1; $p2 <= 3; $p2++):
                                        $n2 = $n1['sub_tree'][$p2] ?? null;
                                    ?>
                                        <div>
                                            <?php if ($n2): ?>
                                                <div class="bg-darkcard border border-gold/30 rounded-lg p-1.5 text-[10px] text-center">
                                                    <div class="font-bold text-white truncate"><?php echo htmlspecialchars($n2['name']); ?></div>
                                                    <div class="text-gold font-mono"><?php echo htmlspecialchars($n2['member_id']); ?></div>
                                                </div>
                                            <?php else: ?>
                                                <div class="bg-darkbg/40 border border-dashed border-gray-700 rounded-lg p-1 text-[10px] text-gray-600">
                                                    Empty #<?php echo $p2; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            <?php endif; ?>

                        <?php else: ?>
                            <div class="bg-darkbg/50 border border-dashed border-gold/20 rounded-xl p-4 w-full max-w-[200px] text-center text-gray-500 text-xs">
                                <i class="fas fa-plus-circle text-gold/30 text-lg mb-1 block"></i>
                                Open Position
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <!-- Tabular Downline List (Up to 7 levels) -->
    <div class="bg-darkcard p-6 rounded-2xl gold-border-glow">
        <h2 class="text-lg font-bold text-white mb-4 flex items-center justify-between">
            <span><i class="fas fa-list text-gold mr-2"></i> Downline Matrix Members (Levels 1 to 7)</span>
            <span class="text-xs bg-gold/10 text-gold px-3 py-1 rounded-full">Total: <?php echo count($downline_list); ?> Members</span>
        </h2>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-300">
                <thead class="bg-gold/10 text-gold uppercase border-b border-gold/20">
                    <tr>
                        <th class="p-3">Level</th>
                        <th class="p-3">Member ID</th>
                        <th class="p-3">Name</th>
                        <th class="p-3">Package</th>
                        <th class="p-3">Placement Parent</th>
                        <th class="p-3">Matrix Pos</th>
                        <th class="p-3">Joined Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gold/10">
                    <?php if (empty($downline_list)): ?>
                        <tr>
                            <td colspan="7" class="p-4 text-center text-gray-500">No matrix downline members found yet. Share your referral link to build your team!</td>
                        </tr>
                    <?php else: foreach ($downline_list as $row): ?>
                        <tr class="hover:bg-gold/5 transition">
                            <td class="p-3">
                                <span class="px-2 py-0.5 rounded font-bold bg-gold/20 text-gold border border-gold/30">
                                    L<?php echo $row['level_depth']; ?>
                                </span>
                            </td>
                            <td class="p-3 font-mono font-bold text-gold"><?php echo htmlspecialchars($row['member_id']); ?></td>
                            <td class="p-3 font-semibold text-white"><?php echo htmlspecialchars($row['name']); ?></td>
                            <td class="p-3 text-gray-300"><?php echo str_replace('_', ' ₹', $row['package_type']); ?></td>
                            <td class="p-3 text-gray-400 font-mono"><?php echo htmlspecialchars($row['parent_name'] ? $row['parent_name'] . " (" . $row['placement_parent_id'] . ")" : $row['placement_parent_id']); ?></td>
                            <td class="p-3 font-bold text-goldlight">Pos #<?php echo $row['matrix_position']; ?></td>
                            <td class="p-3 font-mono text-gray-400"><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
