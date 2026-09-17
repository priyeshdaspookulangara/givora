<?php
require_once __DIR__ . '/../includes/functions.php';
checkAdminLogin();

$page_title = "Member Management";
$pdo = getDBConnection();

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
            <p class="text-xs text-gray-400 mt-1">Search registered members and trigger direct WhatsApp welcome greetings.</p>
        </div>
        <a href="/admin/index.php" class="text-xs text-gold border border-gold/40 px-3 py-1.5 rounded-lg hover:bg-gold/10 self-start md:self-auto">← Admin Overview</a>
    </div>

    <!-- Flash Messages -->
    <?php if (!empty($_SESSION['admin_msg_success'])): ?>
        <div class="mb-6 p-4 rounded-xl bg-green-900/30 border border-green-500/50 text-green-300 text-xs flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-400 text-base mr-3"></i>
                <span><?php echo htmlspecialchars($_SESSION['admin_msg_success']); unset($_SESSION['admin_msg_success']); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['admin_msg_error'])): ?>
        <div class="mb-6 p-4 rounded-xl bg-red-900/30 border border-red-500/50 text-red-300 text-xs flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-red-400 text-base mr-3"></i>
                <span><?php echo htmlspecialchars($_SESSION['admin_msg_error']); unset($_SESSION['admin_msg_error']); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Search & Filter Form -->
    <div class="bg-darkcard p-6 rounded-2xl gold-border-glow mb-8">
        <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-300 mb-1.5">Search Member</label>
                <input type="text" name="search" placeholder="Member ID, Name, Phone, Email..." value="<?php echo htmlspecialchars($search); ?>" class="w-full bg-darkbg border border-gold/30 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-gold">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-300 mb-1.5">Package Filter</label>
                <select name="package" class="w-full bg-darkbg border border-gold/30 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-gold">
                    <option value="">All Packages</option>
                    <option value="Foundation_5000" <?php echo $package_filter === 'Foundation_5000' ? 'selected' : ''; ?>>Foundation (₹5,000)</option>
                    <option value="Leadership_15000" <?php echo $package_filter === 'Leadership_15000' ? 'selected' : ''; ?>>Leadership (₹15,000)</option>
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
                        <th class="p-3">Phase Status</th>
                        <th class="p-3">Joined Date</th>
                        <th class="p-3 text-center">WhatsApp Greeting</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gold/10">
                    <?php if (empty($members)): ?>
                        <tr>
                            <td colspan="7" class="p-4 text-center text-gray-500">No members found matching the specified criteria.</td>
                        </tr>
                    <?php else: foreach ($members as $m):
                        // Format WhatsApp Greeting URL as requested:
                        // https://wa.me/?text= with URL-encoded text:
                        // Welcome to Givora Traders LLP! Your Member ID is: [MEMBER_ID] and Password is: [PASSWORD] . Login here: [LOGIN_URL]
                        $wa_text = "Welcome to Givora Traders LLP! Your Member ID is: " . $m['member_id'] . " and Password is: " . $m['password'] . " . Login here: " . $login_url;

                        // Sanitize phone for wa.me link if available, or general wa.me/?text=
                        $phone_digits = preg_replace('/[^0-9]/', '', $m['phone']);
                        if (strlen($phone_digits) === 10) {
                            $phone_digits = '91' . $phone_digits;
                        }
                        $wa_url = "https://wa.me/" . (!empty($phone_digits) ? $phone_digits : "") . "?text=" . urlencode($wa_text);
                    ?>
                        <tr class="hover:bg-gold/5 transition">
                            <td class="p-3 font-mono font-extrabold text-gold text-sm">
                                <div><?php echo htmlspecialchars($m['member_id']); ?></div>
                                <a href="/admin/matrix_tree.php?member_id=<?php echo $m['member_id']; ?>" class="mt-1 text-[10px] text-gold border border-gold/40 px-2 py-0.5 rounded hover:bg-gold/10 inline-block font-sans">
                                    <i class="fas fa-sitemap mr-0.5"></i> View Matrix Tree
                                </a>
                            </td>
                            <td class="p-3">
                                <div class="font-bold text-white"><?php echo htmlspecialchars($m['name']); ?></div>
                                <div class="text-gray-400 text-[11px]"><i class="fas fa-phone text-gold/70 mr-1"></i><?php echo htmlspecialchars($m['phone']); ?></div>
                                <div class="text-gray-500 text-[10px]"><i class="fas fa-envelope text-gold/70 mr-1"></i><?php echo htmlspecialchars($m['email']); ?></div>
                            </td>
                            <td class="p-3 font-mono text-amber-300 font-semibold bg-darkbg/50 rounded px-2 py-1 inline-block my-2"><?php echo htmlspecialchars($m['password']); ?></td>
                            <td class="p-3 font-mono text-gray-400">
                                <div>Sponsor: <span class="text-white font-bold"><?php echo htmlspecialchars($m['sponsor_id'] ?: 'Root'); ?></span></div>
                                <div>Parent: <span class="text-goldlight"><?php echo htmlspecialchars($m['placement_parent_id'] ?: 'Root'); ?></span> (Pos #<?php echo $m['matrix_position']; ?>)</div>
                            </td>
                            <td class="p-3">
                                <span class="px-2 py-1 rounded bg-gold/10 text-gold font-bold">
                                    <?php echo str_replace('_', ' ₹', $m['package_type']); ?>
                                </span>
                            </td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 rounded text-[10px] bg-green-500/20 text-green-400 font-bold block mb-1">P1: Active</span>
                                <?php if ($m['p2_status'] === 'Active'): ?>
                                    <span class="px-2 py-0.5 rounded text-[10px] bg-amber-500/20 text-amber-300 font-bold border border-amber-500/30 block"><i class="fas fa-crown text-[9px] mr-0.5"></i> P2: Active</span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 rounded text-[10px] bg-gray-500/20 text-gray-400 font-semibold block">P2: Pending</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3 font-mono text-gray-400"><?php echo date('d M Y', strtotime($m['created_at'])); ?></td>
                            <td class="p-3 text-center">
                                <div class="flex items-center justify-center space-x-2">
                                    <a href="<?php echo $wa_url; ?>" target="_blank" class="px-2.5 py-1.5 rounded-xl bg-green-600 hover:bg-green-500 text-white font-bold text-[11px] inline-flex items-center space-x-1 shadow">
                                        <i class="fab fa-whatsapp text-xs"></i>
                                        <span>Greeting</span>
                                    </a>
                                    <?php if ($m['member_id'] !== 'GT100000'): ?>
                                        <a href="/admin/delete_member.php?member_id=<?php echo $m['member_id']; ?>" onclick="return confirm('Are you sure you want to permanently delete member <?php echo htmlspecialchars($m['name']); ?> (<?php echo $m['member_id']; ?>)? All associated wallets, commissions, and transaction logs will be permanently deleted!');" class="px-2.5 py-1.5 rounded-xl bg-red-600/80 hover:bg-red-600 text-white font-bold text-[11px] inline-flex items-center space-x-1 shadow border border-red-500/40">
                                            <i class="fas fa-trash text-xs"></i>
                                            <span>Delete</span>
                                        </a>
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
