<?php
require_once __DIR__ . '/../includes/functions.php';
checkMemberLogin();

$member = getLoggedInMember();
$page_title = "My Matrix Team";

$pdo = getDBConnection();

// Downline List Builder (up to 20 levels using BFS)
function getDownlineList($pdo, $root_member_id, $max_level = 20) {
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

$own_member_id = $member['member_id'];
$requested_view_id = isset($_GET['view_id']) ? trim($_GET['view_id']) : $own_member_id;

$downline_list = getDownlineList($pdo, $own_member_id, 20);

// Validate view_id permission (Must be own ID or downline member)
$view_member = null;
if ($requested_view_id === $own_member_id) {
    $view_member = $member;
} else {
    foreach ($downline_list as $d_item) {
        if ($d_item['member_id'] === $requested_view_id) {
            $view_member = $d_item;
            break;
        }
    }
    if (!$view_member) {
        // Fallback to own account if unauthorized or not in downline
        $requested_view_id = $own_member_id;
        $view_member = $member;
    }
}

$matrix_tree = buildMatrixTree($pdo, $view_member['member_id'], 1, 3);

// Verify if member actually satisfies full 6-level matrix completion for Phase 2 qualification
$has_completed_6_levels = hasCompletedMatrixLevels($pdo, $member['member_id'], 6);
$is_p2_qualified = ($member['p2_status'] === 'Active' && $has_completed_6_levels);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-wrap justify-between items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white"><i class="fas fa-sitemap text-gold mr-2"></i> 3-Matrix Team Tree & Downline</h1>
            <p class="text-xs text-gray-400 mt-1">
                Showing 3x3 visual matrix representation for
                <span class="text-gold font-bold font-mono"><?php echo htmlspecialchars($view_member['name']); ?> (<?php echo htmlspecialchars($view_member['member_id']); ?>)</span>.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <?php if ($requested_view_id !== $own_member_id): ?>
                <a href="teams.php" class="text-xs bg-gold text-darkbg font-bold px-3 py-1.5 rounded-lg hover:bg-goldlight transition shadow flex items-center gap-1">
                    <i class="fas fa-home"></i> Back to My Main Tree
                </a>
            <?php endif; ?>
            <a href="/customer/dashboard.php" class="text-xs text-gold border border-gold/40 px-3 py-1.5 rounded-lg hover:bg-gold/10">← Dashboard</a>
        </div>
    </div>

    <!-- Phase 2 Matrix Promotion Banner -->
    <div class="bg-gradient-to-r from-amber-900/40 via-darkcard to-gold/20 p-6 rounded-2xl gold-border-glow mb-8 flex flex-col md:flex-row items-center justify-between gap-4 border border-gold/30">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-xs uppercase font-extrabold tracking-widest px-2.5 py-0.5 rounded bg-amber-500/20 text-amber-300 border border-amber-500/30">Phase 2 Matrix Status</span>
                <?php if ($is_p2_qualified): ?>
                    <span class="text-xs font-bold px-2 py-0.5 rounded bg-green-500/20 text-green-400">PROMOTED & ACTIVE</span>
                <?php else: ?>
                    <span class="text-xs font-bold px-2 py-0.5 rounded bg-gray-500/20 text-gray-400">PHASE 1 ACTIVE (PENDING 6 LEVELS)</span>
                <?php endif; ?>
            </div>
            <h3 class="text-lg font-bold text-white mt-2">
                <?php if ($is_p2_qualified): ?>
                    <i class="fas fa-crown text-gold mr-1.5"></i> Qualified & Promoted to Phase 2 Matrix!
                <?php else: ?>
                    Phase 1 Matrix Active
                <?php endif; ?>
            </h3>
            <p class="text-xs text-gray-400 mt-1">Note: Qualification for Phase 2 Matrix requires complete 6-level downline filling (1,092 members) under your node in Phase 1.</p>
        </div>
        <?php if ($is_p2_qualified): ?>
            <div class="text-right flex-shrink-0">
                <div class="text-xs text-gray-400">Phase 2 Placement Parent</div>
                <div class="text-sm font-bold font-mono text-gold"><?php echo htmlspecialchars($member['p2_placement_parent_id'] ?: 'GT100000 (Root)'); ?></div>
                <div class="text-xs text-gray-400 mt-0.5">Position #<?php echo htmlspecialchars($member['p2_matrix_position']); ?></div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Visual 3-Matrix Representation -->
    <div class="bg-darkcard p-6 rounded-2xl gold-border-glow mb-12 overflow-x-auto">
        <div class="flex items-center justify-between mb-6 border-b border-gold/10 pb-3">
            <h2 class="text-lg font-bold text-white flex items-center">
                <i class="fas fa-network-wired text-gold mr-2"></i> Visual Matrix Tree Structure
            </h2>
            <p class="text-xs text-gold/80 italic"><i class="fas fa-mouse-pointer mr-1"></i> Click on any child member card below to drill down into their subtree!</p>
        </div>

        <div class="min-w-[700px] text-center">
            <!-- Root Member (Focused Node) -->
            <div class="inline-block bg-gradient-to-r from-golddark via-gold to-goldlight text-darkbg p-4 rounded-xl shadow-lg border border-gold font-bold mb-8 relative group">
                <div class="text-xs uppercase tracking-wider opacity-80">
                    <?php echo ($requested_view_id === $own_member_id) ? 'Root Node (You)' : 'Selected Node'; ?>
                </div>
                <div class="text-lg"><?php echo htmlspecialchars($view_member['name']); ?></div>
                <div class="text-xs font-mono font-black"><?php echo htmlspecialchars($view_member['member_id']); ?></div>
                <?php if ($requested_view_id !== $own_member_id): ?>
                    <div class="text-[10px] bg-darkbg text-gold px-2 py-0.5 rounded-full mt-1 border border-gold/30">
                        Placement Parent: <?php echo htmlspecialchars($view_member['placement_parent_id']); ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Level 1 Children (3 Nodes) -->
            <div class="grid grid-cols-3 gap-4 border-t-2 border-gold/30 pt-6 relative">
                <?php for ($p1 = 1; $p1 <= 3; $p1++):
                    $n1 = $matrix_tree[$p1] ?? null;
                ?>
                    <div class="flex flex-col items-center">
                        <div class="text-xs font-bold text-gold mb-1">Pos #<?php echo $p1; ?></div>
                        <?php if ($n1): ?>
                            <!-- Clickable L1 Node -->
                            <a href="teams.php?view_id=<?php echo urlencode($n1['member_id']); ?>"
                               class="bg-darkbg border border-gold/50 hover:border-gold hover:scale-105 transition-all transform rounded-xl p-3 w-full max-w-[210px] text-center shadow-md block group relative cursor-pointer">
                                <div class="text-xs text-gold/60 group-hover:text-gold text-right mb-0.5 transition"><i class="fas fa-search-plus"></i> Drill Down</div>
                                <div class="text-sm font-bold text-white truncate group-hover:text-gold transition"><?php echo htmlspecialchars($n1['name']); ?></div>
                                <div class="text-xs text-gold font-mono font-semibold"><?php echo htmlspecialchars($n1['member_id']); ?></div>
                                <div class="text-[10px] text-gray-400 mt-1"><?php echo str_replace('_', ' ', $n1['package_type']); ?></div>
                            </a>

                            <!-- Level 2 Children under L1 -->
                            <?php if (!empty($n1['sub_tree'])): ?>
                                <div class="w-full border-t border-gold/20 mt-4 pt-4 grid grid-cols-3 gap-1">
                                    <?php for ($p2 = 1; $p2 <= 3; $p2++):
                                        $n2 = $n1['sub_tree'][$p2] ?? null;
                                    ?>
                                        <div>
                                            <?php if ($n2): ?>
                                                <!-- Clickable L2 Node -->
                                                <a href="teams.php?view_id=<?php echo urlencode($n2['member_id']); ?>"
                                                   class="bg-darkcard border border-gold/30 hover:border-gold hover:bg-gold/10 transition rounded-lg p-1.5 text-[10px] text-center block cursor-pointer">
                                                    <div class="font-bold text-white truncate"><?php echo htmlspecialchars($n2['name']); ?></div>
                                                    <div class="text-gold font-mono"><?php echo htmlspecialchars($n2['member_id']); ?></div>
                                                </a>
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
            <span><i class="fas fa-list text-gold mr-2"></i> Downline Matrix Members (All Levels)</span>
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
                        <th class="p-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gold/10">
                    <?php if (empty($downline_list)): ?>
                        <tr>
                            <td colspan="8" class="p-4 text-center text-gray-500">No matrix downline members found yet. Share your referral link to build your team!</td>
                        </tr>
                    <?php else: foreach ($downline_list as $row): ?>
                        <tr class="hover:bg-gold/5 transition <?php echo ($requested_view_id === $row['member_id']) ? 'bg-gold/10' : ''; ?>">
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
                            <td class="p-3 text-right">
                                <a href="teams.php?view_id=<?php echo urlencode($row['member_id']); ?>" class="text-[11px] bg-gold/20 text-gold border border-gold/30 hover:bg-gold hover:text-darkbg font-semibold px-2 py-1 rounded transition inline-flex items-center gap-1">
                                    <i class="fas fa-sitemap"></i> View Tree
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
