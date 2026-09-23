<?php
require_once __DIR__ . '/../includes/functions.php';
checkAdminLogin();

$page_title = "ePIN Management & Generator";
$pdo = getDBConnection();

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate') {
    $package_type = $_POST['package_type'] ?? 'Foundation_5000';
    $quantity = intval($_POST['quantity'] ?? 1);
    $assign_to = trim($_POST['assign_to'] ?? '');

    if ($quantity < 1 || $quantity > 100) {
        $error = "Quantity must be between 1 and 100.";
    } else {
        // Verify assign_to member exists if provided
        if (!empty($assign_to)) {
            $stmt = $pdo->prepare("SELECT member_id FROM members WHERE member_id = ?");
            $stmt->execute([$assign_to]);
            if (!$stmt->fetch()) {
                $error = "Assigned Member ID does not exist.";
            }
        } else {
            $assign_to = null;
        }

        if (empty($error)) {
            $generated_count = 0;
            $admin_id = $_SESSION['admin_id'];

            for ($i = 0; $i < $quantity; $i++) {
                $epin_code = generateEpinCode();
                $stmt = $pdo->prepare("INSERT INTO epins (epin_code, package_type, status, generated_by_admin_id, assigned_to) VALUES (?, ?, 'Unused', ?, ?)");
                if ($stmt->execute([$epin_code, $package_type, $admin_id, $assign_to])) {
                    $generated_count++;
                }
            }

            $msg = "Successfully generated {$generated_count} new ePIN(s) for package " . str_replace('_', ' ₹', $package_type) . "!";
        }
    }
}

// Fetch ePINs Inventory
$status_filter = trim($_GET['status'] ?? '');
$query = "SELECT e.*, a.username as admin_name FROM epins e LEFT JOIN admins a ON e.generated_by_admin_id = a.id WHERE 1=1";
$params = [];

if (!empty($status_filter)) {
    $query .= " AND e.status = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY e.created_at DESC LIMIT 100";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$epins = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white"><i class="fas fa-key text-gold mr-2"></i> ePIN Generator & Inventory Control</h1>
            <p class="text-xs text-gray-400 mt-1">Generate secure single-use ePINs for Foundation and Leadership packages.</p>
        </div>
        <a href="/admin/index.php" class="text-xs text-gold border border-gold/40 px-3 py-1.5 rounded-lg hover:bg-gold/10 self-start md:self-auto">← Admin Overview</a>
    </div>

    <?php if (!empty($msg)): ?>
        <div class="mb-6 p-4 rounded-xl bg-green-900/30 border border-green-500/50 text-green-300 text-sm flex items-center">
            <i class="fas fa-check-circle text-green-400 text-lg mr-3"></i>
            <span><?php echo htmlspecialchars($msg); ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="mb-6 p-4 rounded-xl bg-red-900/30 border border-red-500/50 text-red-300 text-sm flex items-center">
            <i class="fas fa-exclamation-circle text-red-400 text-lg mr-3"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <!-- Generation Form Card -->
    <div class="bg-darkcard p-6 rounded-2xl gold-border-glow mb-8">
        <h2 class="text-lg font-bold text-white mb-4 border-b border-gold/10 pb-2"><i class="fas fa-plus-circle text-gold mr-2"></i> Generate Batch ePINs</h2>

        <form method="POST" action="" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="hidden" name="action" value="generate">

            <div>
                <label class="block text-xs font-semibold text-gray-300 mb-1.5">Package Tier *</label>
                <select name="package_type" required class="w-full bg-darkbg border border-gold/30 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-gold">
                    <option value="Foundation_5000">Foundation Tier (₹5,000)</option>
                    <option value="Leadership_15000">Leadership Tier (₹15,000)</option>
                    <option value="Recharge_1200">Mobile Recharge Package (₹1,200)</option>
                    <option value="Gas_3000">Gas Connection Package (₹3,000)</option>
                    <option value="Recharge_Bundle_5400">Recharge Bundle Combo (₹5,400)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-300 mb-1.5">Quantity (1 - 100) *</label>
                <input type="number" name="quantity" min="1" max="100" value="1" required class="w-full bg-darkbg border border-gold/30 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-gold font-mono font-bold">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-300 mb-1.5">Assign to Member ID (Optional)</label>
                <input type="text" name="assign_to" placeholder="e.g., GT100001" class="w-full bg-darkbg border border-gold/30 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-gold font-mono">
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full btn-gold py-2.5 rounded-xl font-bold text-xs shadow-md flex items-center justify-center space-x-1.5">
                    <i class="fas fa-magic"></i>
                    <span>Generate Now</span>
                </button>
            </div>
        </form>
    </div>

    <!-- ePIN Inventory Table -->
    <div class="bg-darkcard p-6 rounded-2xl gold-border-glow">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-2">
            <h2 class="text-lg font-bold text-white"><i class="fas fa-list text-gold mr-2"></i> System ePIN Inventory</h2>

            <div class="flex items-center space-x-2 text-xs">
                <span class="text-gray-400">Filter Status:</span>
                <a href="/admin/epins.php" class="px-3 py-1 rounded-lg <?php echo empty($status_filter) ? 'bg-gold text-darkbg font-bold' : 'bg-darkbg text-gray-300 border border-gold/20'; ?>">All</a>
                <a href="/admin/epins.php?status=Unused" class="px-3 py-1 rounded-lg <?php echo $status_filter === 'Unused' ? 'bg-green-500 text-white font-bold' : 'bg-darkbg text-green-400 border border-green-500/20'; ?>">Unused</a>
                <a href="/admin/epins.php?status=Used" class="px-3 py-1 rounded-lg <?php echo $status_filter === 'Used' ? 'bg-red-500 text-white font-bold' : 'bg-darkbg text-red-400 border border-red-500/20'; ?>">Used</a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-300">
                <thead class="bg-gold/10 text-gold uppercase border-b border-gold/20">
                    <tr>
                        <th class="p-3">#</th>
                        <th class="p-3">ePIN Code</th>
                        <th class="p-3">Package Tier</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Assigned To</th>
                        <th class="p-3">Used By</th>
                        <th class="p-3">Generated Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gold/10">
                    <?php if (empty($epins)): ?>
                        <tr>
                            <td colspan="7" class="p-4 text-center text-gray-500">No ePIN records found.</td>
                        </tr>
                    <?php else: foreach ($epins as $idx => $pin): ?>
                        <tr class="hover:bg-gold/5 transition">
                            <td class="p-3 text-gray-500"><?php echo $idx + 1; ?></td>
                            <td class="p-3 font-mono font-extrabold text-gold text-sm"><?php echo htmlspecialchars($pin['epin_code']); ?></td>
                            <td class="p-3 font-semibold text-white"><?php echo str_replace('_', ' ₹', $pin['package_type']); ?></td>
                            <td class="p-3">
                                <?php if ($pin['status'] === 'Used'): ?>
                                    <span class="px-2 py-0.5 rounded bg-red-500/20 text-red-400 font-bold">Used</span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 rounded bg-green-500/20 text-green-400 font-bold">Unused</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3 font-mono text-gray-300"><?php echo htmlspecialchars($pin['assigned_to'] ?: 'Unassigned'); ?></td>
                            <td class="p-3 font-mono text-goldlight font-bold"><?php echo htmlspecialchars($pin['used_by_member_id'] ?: '-'); ?></td>
                            <td class="p-3 font-mono text-gray-400"><?php echo date('d M Y, H:i', strtotime($pin['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
