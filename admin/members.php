<?php
require_once __DIR__ . '/../includes/functions.php';
checkAdminLogin();

$page_title = "Member Management";
$pdo = getDBConnection();

$msg = '';
$error = '';

// Handle Member Deletion Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_member') {
    $delete_id = trim($_POST['delete_member_id'] ?? '');

    try {
        if (deleteMemberAndRevertCommissions($pdo, $delete_id)) {
            $msg = "Member '{$delete_id}' deleted successfully! All commissions, transactions, and ledger entries triggered by this member have been completely reverted.";
        }
    } catch (Exception $e) {
        $error = "Failed to delete member: " . $e->getMessage();
    }
}

$search = trim($_GET['search'] ?? '');
$package_filter = trim($_GET['package'] ?? '');

$query = "SELECT * FROM members WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (member_id LIKE ? OR name LIKE ? OR email LIKE ? OR phone LIKE ? OR sponsor_id LIKE ?)";
    $search_param = "%{$search}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
}

if (!empty($package_filter)) {
    $query .= " AND package_type = ?";
    $params[] = $package_filter;
}

$query .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$members = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
$login_url = getBaseUrl() . "/login.php";
?>

<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white"><i class="fas fa-users text-gold mr-2"></i> Member Directory & WhatsApp Greetings</h1>
            <p class="text-xs text-gray-400 mt-1">Search all registered members (Matrix, Utility, and Charity Support), send greetings, or manage member accounts.</p>
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

    <!-- Search & Filter Form -->
    <div class="bg-darkcard p-6 rounded-2xl gold-border-glow mb-8">
        <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-300 mb-1.5">Search Member</label>
                <input type="text" name="search" placeholder="Member ID, Name, Phone, Email, Sponsor..." value="<?php echo htmlspecialchars($search); ?>" class="w-full bg-darkbg border border-gold/30 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-gold">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-300 mb-1.5">Package Filter</label>
                <select name="package" class="w-full bg-darkbg border border-gold/30 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-gold">
                    <option value="">All Packages</option>
                    <option value="Foundation_5000" <?php echo $package_filter === 'Foundation_5000' ? 'selected' : ''; ?>>Foundation (₹5,000)</option>
                    <option value="Leadership_15000" <?php echo $package_filter === 'Leadership_15000' ? 'selected' : ''; ?>>Leadership (₹15,000)</option>
                    <option value="Charity_10000" <?php echo $package_filter === 'Charity_10000' ? 'selected' : ''; ?>>Charity Support (₹10,000+)</option>
                    <option value="Recharge_1200" <?php echo $package_filter === 'Recharge_1200' ? 'selected' : ''; ?>>Mobile Recharge (₹1,200)</option>
                    <option value="Gas_3000" <?php echo $package_filter === 'Gas_3000' ? 'selected' : ''; ?>>Gas Connection (₹3,000)</option>
                    <option value="Recharge_Bundle_5400" <?php echo $package_filter === 'Recharge_Bundle_5400' ? 'selected' : ''; ?>>Recharge Combo (₹5,400)</option>
                </select>
            </div>

            <div class="flex items-end space-x-2">
                <button type="submit" class="btn-gold px-6 py-2.5 rounded-xl font-bold text-xs flex-grow">
                    <i class="fas fa-search mr-1"></i> Search
                </button>
                <a href="/admin/members.php" class="bg-darkbg border border-gold/30 text-gray-300 px-4 py-2.5 rounded-xl font-semibold text-xs hover:text-gold text-center">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Members Directory Table -->
    <div class="bg-darkcard p-6 rounded-2xl gold-border-glow">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-300">
                <thead class="bg-gold/10 text-gold uppercase border-b border-gold/20">
                    <tr>
                        <th class="p-3">Member ID</th>
                        <th class="p-3">Name & Contact</th>
                        <th class="p-3">Password (Plain)</th>
                        <th class="p-3">Sponsor / Parent</th>
                        <th class="p-3">Package</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Joined Date</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gold/10">
                    <?php if (empty($members)): ?>
                        <tr>
                            <td colspan="8" class="p-4 text-center text-gray-500">No members found matching the specified criteria.</td>
                        </tr>
                    <?php else: foreach ($members as $m):
                        $is_non_matrix = in_array($m['package_type'], ['Charity_10000', 'Recharge_1200', 'Gas_3000', 'Recharge_Bundle_5400']);

                        // Format WhatsApp Greeting URL:
                        $wa_text = "Welcome to Givora Traders LLP! Your Member ID is: " . $m['member_id'] . " and Password is: " . $m['password'] . " . Login here: " . $login_url;

                        $phone_digits = preg_replace('/[^0-9]/', '', $m['phone']);
                        if (strlen($phone_digits) === 10) {
                            $phone_digits = '91' . $phone_digits;
                        }
                        $wa_url = "https://wa.me/" . (!empty($phone_digits) ? $phone_digits : "") . "?text=" . urlencode($wa_text);
                    ?>
                        <tr class="hover:bg-gold/5 transition">
                            <td class="p-3 font-mono font-extrabold text-gold text-sm">
                                <div><?php echo htmlspecialchars($m['member_id']); ?></div>
                                <?php if (!$is_non_matrix): ?>
                                    <a href="/admin/matrix_tree.php?member_id=<?php echo $m['member_id']; ?>" class="mt-1 text-[10px] text-gold border border-gold/40 px-2 py-0.5 rounded hover:bg-gold/10 inline-block font-sans">
                                        <i class="fas fa-sitemap mr-0.5"></i> View Matrix Tree
                                    </a>
                                <?php else: ?>
                                    <span class="mt-1 text-[10px] text-gray-400 border border-gray-700 px-2 py-0.5 rounded inline-block font-sans">
                                        <i class="fas fa-user-tag mr-0.5"></i> Non-Matrix Account
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3">
                                <div class="font-bold text-white"><?php echo htmlspecialchars($m['name']); ?></div>
                                <div class="text-gray-400 text-[11px]"><i class="fas fa-phone text-gold/70 mr-1"></i><?php echo htmlspecialchars($m['phone']); ?></div>
                                <div class="text-gray-500 text-[10px]"><i class="fas fa-envelope text-gold/70 mr-1"></i><?php echo htmlspecialchars($m['email']); ?></div>
                            </td>
                            <td class="p-3 font-mono text-amber-300 font-semibold bg-darkbg/50 rounded px-2 py-1 inline-block my-2"><?php echo htmlspecialchars($m['password']); ?></td>
                            <td class="p-3 font-mono text-gray-400">
                                <div>Sponsor: <span class="text-white font-bold"><?php echo htmlspecialchars($m['sponsor_id'] ?: 'GT100000'); ?></span></div>
                                <?php if (!$is_non_matrix): ?>
                                    <div>Parent: <span class="text-goldlight"><?php echo htmlspecialchars($m['placement_parent_id'] ?: 'Root'); ?></span> (Pos #<?php echo $m['matrix_position']; ?>)</div>
                                <?php else: ?>
                                    <div class="text-[10px] text-gray-500 italic">Unilevel Direct Chain</div>
                                <?php endif; ?>
                            </td>
                            <td class="p-3">
                                <?php if ($m['package_type'] === 'Charity_10000'): ?>
                                    <span class="px-2 py-1 rounded bg-amber-500/20 text-amber-300 font-bold border border-amber-500/30 inline-block">
                                        <i class="fas fa-hand-holding-heart mr-1"></i> Charity (₹<?php echo number_format((float)$m['custom_amount'], 2); ?>)
                                    </span>
                                <?php elseif (in_array($m['package_type'], ['Recharge_1200', 'Gas_3000', 'Recharge_Bundle_5400'])): ?>
                                    <span class="px-2 py-1 rounded bg-blue-500/20 text-blue-300 font-bold border border-blue-500/30 inline-block">
                                        <i class="fas fa-bolt mr-1"></i> <?php echo str_replace('_', ' ₹', $m['package_type']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 py-1 rounded bg-gold/10 text-gold font-bold inline-block">
                                        <?php echo str_replace('_', ' ₹', $m['package_type']); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 rounded text-[10px] bg-green-500/20 text-green-400 font-bold block mb-1">Active</span>
                                <?php if (!$is_non_matrix): ?>
                                    <?php if ($m['p2_status'] === 'Active'): ?>
                                        <span class="px-2 py-0.5 rounded text-[10px] bg-amber-500/20 text-amber-300 font-bold border border-amber-500/30 block"><i class="fas fa-crown text-[9px] mr-0.5"></i> P2: Active</span>
                                    <?php else: ?>
                                        <span class="px-2 py-0.5 rounded text-[10px] bg-gray-500/20 text-gray-400 font-semibold block">P2: Pending</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td class="p-3 font-mono text-gray-400"><?php echo date('d M Y', strtotime($m['created_at'])); ?></td>
                            <td class="p-3 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="<?php echo $wa_url; ?>" target="_blank" title="Send WhatsApp Greeting" class="px-2.5 py-1.5 rounded-lg bg-green-600 hover:bg-green-500 text-white font-bold text-xs inline-flex items-center space-x-1 shadow">
                                        <i class="fab fa-whatsapp"></i>
                                        <span>Greeting</span>
                                    </a>

                                    <?php if ($m['member_id'] !== 'GT100000'): ?>
                                        <form method="POST" action="" onsubmit="return confirm('⚠️ WARNING: Deleting member <?php echo $m['member_id']; ?> (<?php echo htmlspecialchars($m['name']); ?>) will permanently revert ALL commissions and transactions triggered by this member across upline wallets!\n\nAre you sure you want to proceed?');">
                                            <input type="hidden" name="action" value="delete_member">
                                            <input type="hidden" name="delete_member_id" value="<?php echo $m['member_id']; ?>">
                                            <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-red-600/80 hover:bg-red-500 text-white font-bold text-xs inline-flex items-center space-x-1 transition shadow">
                                                <i class="fas fa-trash-alt"></i>
                                                <span>Delete & Revert</span>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
