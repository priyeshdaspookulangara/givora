<?php
require_once __DIR__ . '/../includes/functions.php';
checkMemberLogin();

$member = getLoggedInMember();
$page_title = "Direct Referrals";

$pdo = getDBConnection();

// Direct Referral Stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE sponsor_id = ?");
$stmt->execute([$member['member_id']]);
$total_referrals = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE sponsor_id = ? AND status = 'Active'");
$stmt->execute([$member['member_id']]);
$active_referrals = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE member_id = ? AND type = 'Direct_Referral' AND status = 'Credit'");
$stmt->execute([$member['member_id']]);
$total_referral_bonus = $stmt->fetchColumn() ?: 0.00;

// Search & Filter parameters
$search = trim($_GET['search'] ?? '');
$package_filter = trim($_GET['package'] ?? '');
$status_filter = trim($_GET['status'] ?? '');

$query = "SELECT m.*,
            COALESCE((SELECT SUM(t.amount) FROM transactions t WHERE t.member_id = ? AND t.type = 'Direct_Referral' AND t.status = 'Credit' AND t.description LIKE CONCAT('%', m.member_id, '%')), 0.00) as bonus_earned
          FROM members m
          WHERE m.sponsor_id = ?";
$params = [$member['member_id'], $member['member_id']];

if (!empty($search)) {
    $query .= " AND (m.member_id LIKE ? OR m.name LIKE ? OR m.email LIKE ? OR m.phone LIKE ?)";
    $search_param = "%{$search}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

if (!empty($package_filter)) {
    $query .= " AND m.package_type = ?";
    $params[] = $package_filter;
}

if (!empty($status_filter)) {
    $query .= " AND m.status = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY m.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$referrals = $stmt->fetchAll();

// Referral URL
$referral_url = getBaseUrl() . "/register.php?sponsor=" . urlencode($member['member_id']);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white"><i class="fas fa-user-plus text-gold mr-2"></i> Direct Referrals Directory</h1>
            <p class="text-xs text-gray-400 mt-1">Overview of members directly sponsored by you and bonuses earned.</p>
        </div>
        <a href="/customer/dashboard.php" class="text-xs text-gold border border-gold/40 px-3 py-1.5 rounded-lg hover:bg-gold/10 self-start md:self-auto">← Dashboard</a>
    </div>

    <!-- Exclusive Referral Link Card -->
    <div class="bg-gradient-to-r from-darkcard via-darkcard to-gold/10 p-6 rounded-2xl gold-border-glow mb-8 border border-gold/30">
        <div class="flex flex-col md:flex-row items-center justify-between gap-4">
            <div>
                <h3 class="text-lg font-bold text-white"><i class="fas fa-share-alt text-gold mr-2"></i> Share Your Referral Link</h3>
                <p class="text-xs text-gray-400 mt-1">Invite new members using your direct link to earn instant Direct Referral Bonuses!</p>
            </div>
            <div class="flex items-center w-full md:w-auto space-x-2">
                <input type="text" id="refLinkInput" readonly value="<?php echo htmlspecialchars($referral_url); ?>" class="bg-darkbg border border-gold/30 rounded-xl px-4 py-2.5 text-xs text-gold font-mono w-full md:w-80 focus:outline-none">
                <button onclick="copyReferralLink()" class="btn-gold px-4 py-2.5 rounded-xl font-bold text-xs flex-shrink-0">
                    <i class="fas fa-copy mr-1"></i> Copy Link
                </button>
            </div>
        </div>
    </div>

    <!-- Summary Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Total Referrals -->
        <div class="bg-darkcard p-6 rounded-2xl gold-border-glow border-l-4 border-l-gold">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold uppercase text-gray-400">Total Sponsored</span>
                <div class="w-10 h-10 rounded-xl bg-gold/10 text-gold flex items-center justify-center text-lg">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold gold-gradient-text"><?php echo number_format($total_referrals); ?></div>
            <p class="text-xs text-gray-400 mt-2">Directly Referred Members</p>
        </div>

        <!-- Active Referrals -->
        <div class="bg-darkcard p-6 rounded-2xl gold-border-glow border-l-4 border-l-green-500">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold uppercase text-green-400">Active Referrals</span>
                <div class="w-10 h-10 rounded-xl bg-green-500/10 text-green-400 flex items-center justify-center text-lg">
                    <i class="fas fa-user-check"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-green-400"><?php echo number_format($active_referrals); ?></div>
            <p class="text-xs text-gray-400 mt-2">Active Paid Accounts</p>
        </div>

        <!-- Total Direct Income Earned -->
        <div class="bg-darkcard p-6 rounded-2xl gold-border-glow border-l-4 border-l-blue-500">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold uppercase text-blue-400">Direct Bonus Income</span>
                <div class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-400 flex items-center justify-center text-lg">
                    <i class="fas fa-wallet"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-blue-400">₹<?php echo number_format($total_referral_bonus, 2); ?></div>
            <p class="text-xs text-gray-400 mt-2">Total Direct Referral Bonus</p>
        </div>
    </div>

    <!-- Search & Filter Controls -->
    <div class="bg-darkcard p-6 rounded-2xl gold-border-glow mb-8">
        <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-300 mb-1.5">Search Referral</label>
                <input type="text" name="search" placeholder="Member ID, Name, Phone..." value="<?php echo htmlspecialchars($search); ?>" class="w-full bg-darkbg border border-gold/30 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-gold">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-300 mb-1.5">Package</label>
                <select name="package" class="w-full bg-darkbg border border-gold/30 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-gold">
                    <option value="">All Packages</option>
                    <option value="Foundation_5000" <?php echo $package_filter === 'Foundation_5000' ? 'selected' : ''; ?>>Foundation (₹5,000)</option>
                    <option value="Leadership_15000" <?php echo $package_filter === 'Leadership_15000' ? 'selected' : ''; ?>>Leadership (₹15,000)</option>
                    <option value="Recharge_Bundle_5400" <?php echo $package_filter === 'Recharge_Bundle_5400' ? 'selected' : ''; ?>>Recharge Bundle (₹5,400)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-300 mb-1.5">Status</label>
                <select name="status" class="w-full bg-darkbg border border-gold/30 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-gold">
                    <option value="">All Statuses</option>
                    <option value="Active" <?php echo $status_filter === 'Active' ? 'selected' : ''; ?>>Active</option>
                    <option value="Inactive" <?php echo $status_filter === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>

            <div class="flex items-end space-x-2">
                <button type="submit" class="btn-gold px-5 py-2.5 rounded-xl font-bold text-xs flex-grow">
                    <i class="fas fa-filter mr-1"></i> Filter
                </button>
                <a href="/customer/referrals.php" class="bg-darkbg border border-gold/30 text-gray-300 px-4 py-2.5 rounded-xl font-semibold text-xs hover:text-gold text-center">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Direct Referrals Table -->
    <div class="bg-darkcard p-6 rounded-2xl gold-border-glow">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-bold text-white flex items-center">
                <i class="fas fa-list text-gold mr-2"></i> Directly Sponsored Members List
            </h2>
            <span class="text-xs bg-gold/10 text-gold px-3 py-1 rounded-full border border-gold/20 font-mono">
                Showing: <?php echo count($referrals); ?> Record(s)
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-300">
                <thead class="bg-gold/10 text-gold uppercase border-b border-gold/20">
                    <tr>
                        <th class="p-3">Member ID</th>
                        <th class="p-3">Name & Contact</th>
                        <th class="p-3">Package</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Matrix Placement</th>
                        <th class="p-3">Phase 2 Status</th>
                        <th class="p-3">Bonus Earned</th>
                        <th class="p-3">Joined Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gold/10">
                    <?php if (empty($referrals)): ?>
                        <tr>
                            <td colspan="8" class="p-6 text-center text-gray-500">
                                <i class="fas fa-user-slash text-2xl mb-2 block text-gray-600"></i>
                                No direct referrals found. Share your referral link to start inviting members!
                            </td>
                        </tr>
                    <?php else: foreach ($referrals as $ref): ?>
                        <tr class="hover:bg-gold/5 transition">
                            <td class="p-3 font-mono font-bold text-gold">
                                <?php echo htmlspecialchars($ref['member_id']); ?>
                            </td>
                            <td class="p-3">
                                <div class="font-bold text-white text-sm"><?php echo htmlspecialchars($ref['name']); ?></div>
                                <div class="text-gray-400 text-[11px]"><i class="fas fa-phone text-gold/60 mr-1"></i><?php echo htmlspecialchars($ref['phone']); ?></div>
                                <div class="text-gray-500 text-[10px]"><i class="fas fa-envelope text-gold/60 mr-1"></i><?php echo htmlspecialchars($ref['email']); ?></div>
                            </td>
                            <td class="p-3">
                                <span class="px-2 py-1 rounded bg-gold/10 text-gold font-bold">
                                    <?php echo str_replace('_', ' ₹', $ref['package_type']); ?>
                                </span>
                            </td>
                            <td class="p-3">
                                <?php if ($ref['status'] === 'Active'): ?>
                                    <span class="px-2.5 py-0.5 rounded bg-green-500/20 text-green-400 font-bold border border-green-500/30">Active</span>
                                <?php else: ?>
                                    <span class="px-2.5 py-0.5 rounded bg-red-500/20 text-red-400 font-bold border border-red-500/30">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3 font-mono text-gray-300">
                                <?php if (!empty($ref['placement_parent_id'])): ?>
                                    <div>Parent: <span class="text-white font-bold"><?php echo htmlspecialchars($ref['placement_parent_id']); ?></span></div>
                                    <div class="text-[10px] text-gold">Pos #<?php echo htmlspecialchars($ref['matrix_position']); ?></div>
                                <?php else: ?>
                                    <span class="text-gray-500">Pending Placement</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3">
                                <?php if ($ref['p2_status'] === 'Active'): ?>
                                    <span class="px-2 py-0.5 rounded text-[10px] bg-amber-500/20 text-amber-300 font-bold border border-amber-500/30 inline-flex items-center gap-1">
                                        <i class="fas fa-crown text-gold"></i> Phase 2 Active
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 rounded text-[10px] bg-gray-500/20 text-gray-400 font-semibold">Phase 1</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3 font-bold text-green-400 text-sm">
                                +₹<?php echo number_format($ref['bonus_earned'], 2); ?>
                            </td>
                            <td class="p-3 font-mono text-gray-400">
                                <?php echo date('d M Y, H:i', strtotime($ref['created_at'])); ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function copyReferralLink() {
    const copyText = document.getElementById("refLinkInput");
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(copyText.value);
    alert("Referral link copied to clipboard!");
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
