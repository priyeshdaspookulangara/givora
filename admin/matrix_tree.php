<?php
require_once __DIR__ . '/../includes/functions.php';
checkAdminLogin();

$pdo = getDBConnection();
$member_id = trim($_GET['member_id'] ?? '');

if (empty($member_id)) {
    header("Location: /admin/members.php");
    exit;
}

// Fetch member details
$stmt = $pdo->prepare("SELECT * FROM members WHERE member_id = ?");
$stmt->execute([$member_id]);
$member = $stmt->fetch();

if (!$member) {
    die("Member with ID '{$member_id}' not found.");
}

$page_title = "Matrix Tree - " . $member['member_id'];

// Recursive Tree Builder (up to level 3 visually)
function buildMatrixTreeAdmin($pdo, $parent_id, $current_depth = 1, $max_depth = 3) {
    if ($current_depth > $max_depth) return [];

    $stmt = $pdo->prepare("SELECT member_id, name, package_type, matrix_position, created_at FROM members WHERE placement_parent_id = ? ORDER BY matrix_position ASC");
    $stmt->execute([$parent_id]);
    $children = $stmt->fetchAll();

    $tree = [1 => null, 2 => null, 3 => null];
    foreach ($children as $child) {
        $pos = (int)$child['matrix_position'];
        if ($pos >= 1 && $pos <= 3) {
            $child['sub_tree'] = buildMatrixTreeAdmin($pdo, $child['member_id'], $current_depth + 1, $max_depth);
            $tree[$pos] = $child;
        }
    }
    return $tree;
}

// Downline List Builder (up to 7 levels using BFS)
function getDownlineListAdmin($pdo, $root_member_id, $max_level = 7) {
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

$matrix_tree = buildMatrixTreeAdmin($pdo, $member['member_id'], 1, 3);
$downline_list = getDownlineListAdmin($pdo, $member['member_id'], 7);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-white"><i class="fas fa-sitemap text-gold mr-2"></i> Admin Matrix Tree View</h1>
            <p class="text-xs text-gray-400 mt-1">Viewing 3x3 visual matrix representation and complete 7-level downline for <span class="text-gold font-mono font-bold"><?php echo htmlspecialchars($member['name']); ?> (<?php echo htmlspecialchars($member['member_id']); ?>)</span></p>
        </div>
        <a href="/admin/members.php" class="text-xs text-gold border border-gold/40 px-3 py-1.5 rounded-lg hover:bg-gold/10">← Back to Members</a>
    </div>

    <!-- Member Info Card Header -->
    <div class="bg-darkcard p-6 rounded-2xl gold-border-glow mb-8 flex flex-wrap justify-between items-center gap-4">
        <div>
            <div class="text-xs text-gray-400 uppercase tracking-wider">Member Details</div>
            <div class="text-xl font-bold text-white"><?php echo htmlspecialchars($member['name']); ?></div>
            <div class="text-xs text-gold font-mono font-bold mt-0.5">ID: <?php echo htmlspecialchars($member['member_id']); ?> | Sponsor: <?php echo htmlspecialchars($member['sponsor_id'] ?: 'GT100000'); ?></div>
        </div>
        <div class="flex gap-4">
            <div class="bg-darkbg px-4 py-2 rounded-xl border border-gold/20">
                <div class="text-[10px] text-gray-400">Package</div>
                <div class="text-xs font-bold text-white"><?php echo str_replace('_', ' ₹', $member['package_type']); ?></div>
            </div>
            <div class="bg-darkbg px-4 py-2 rounded-xl border border-gold/20">
                <div class="text-[10px] text-gray-400">Phase 1 Status</div>
                <div class="text-xs font-bold text-green-400"><?php echo htmlspecialchars($member['status']); ?></div>
            </div>
            <div class="bg-darkbg px-4 py-2 rounded-xl border border-gold/20">
                <div class="text-[10px] text-gray-400">Phase 2 Matrix Status</div>
                <div class="text-xs font-bold <?php echo $member['p2_status'] === 'Active' ? 'text-gold' : 'text-gray-400'; ?>"><?php echo htmlspecialchars($member['p2_status']); ?></div>
            </div>
        </div>
    </div>

    <!-- Visual 3-Matrix Representation -->
    <div class="bg-darkcard p-6 rounded-2xl gold-border-glow mb-12 overflow-x-auto">
        <h2 class="text-lg font-bold text-white mb-6 border-b border-gold/10 pb-3 flex items-center">
            <i class="fas fa-network-wired text-gold mr-2"></i> Visual Matrix Tree Structure
        </h2>

        <div class="min-w-[700px] text-center">
            <!-- Root Member -->
            <div class="inline-block bg-gradient-to-r from-golddark via-gold to-goldlight text-darkbg p-4 rounded-xl shadow-lg border border-gold font-bold mb-8">
                <div class="text-sm uppercase tracking-wider">ROOT MEMBER</div>
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
                            <a href="/admin/matrix_tree.php?member_id=<?php echo $n1['member_id']; ?>" class="block bg-darkbg border border-gold/50 rounded-xl p-3 w-full max-w-[200px] text-center shadow-md hover:border-gold transition">
                                <div class="text-sm font-bold text-white truncate"><?php echo htmlspecialchars($n1['name']); ?></div>
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
                                                <a href="/admin/matrix_tree.php?member_id=<?php echo $n2['member_id']; ?>" class="block bg-darkcard border border-gold/30 rounded-lg p-1.5 text-[10px] text-center hover:border-gold transition">
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

    <!-- Tabular Downline List -->
    <div class="bg-darkcard p-6 rounded-2xl gold-border-glow">
        <h2 class="text-lg font-bold text-white mb-4 flex items-center justify-between">
            <span><i class="fas fa-list text-gold mr-2"></i> Downline Members List (Levels 1 to 7)</span>
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
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gold/10">
                    <?php if (empty($downline_list)): ?>
                        <tr>
                            <td colspan="8" class="p-4 text-center text-gray-500">No downline members found under this member.</td>
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
                            <td class="p-3 text-right">
                                <a href="/admin/matrix_tree.php?member_id=<?php echo $row['member_id']; ?>" class="text-[10px] text-gold border border-gold/40 px-2.5 py-1 rounded hover:bg-gold/10">View Tree</a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
