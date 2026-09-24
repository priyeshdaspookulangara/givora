<?php
require_once __DIR__ . '/../includes/functions.php';
checkMemberLogin();

$member = getLoggedInMember();
$page_title = "Direct Referrals & Bonus Statements";

$pdo = getDBConnection();

// Fetch all members directly sponsored by logged-in member
$stmt = $pdo->prepare("SELECT m.*, (SELECT SUM(amount) FROM transactions WHERE member_id = ? AND type = 'Direct_Referral' AND description LIKE CONCAT('%', m.member_id, '%')) as bonus_earned FROM members m WHERE m.sponsor_id = ? ORDER BY m.created_at DESC");
$stmt->execute([$member['member_id'], $member['member_id']]);
$direct_referrals = $stmt->fetchAll();

// Total Direct Referral Bonus Earned
$stmt = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE member_id = ? AND type = 'Direct_Referral' AND status = 'Credit'");
$stmt->execute([$member['member_id']]);
$total_direct_bonus = (float)($stmt->fetchColumn() ?: 0.00);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-white"><i class="fas fa-users text-gold mr-2"></i> Direct Referrals & Bonus Statements</h1>
            <p class="text-xs text-gray-400 mt-1">Detailed list of members directly sponsored by you and bonuses earned.</p>
        </div>
        <a href="/customer/dashboard.php" class="text-xs text-gold border border-gold/40 px-3 py-1.5 rounded-lg hover:bg-gold/10">← Dashboard</a>
    </div>

    <!-- Overview Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="bg-darkcard p-6 rounded-2xl gold-border-glow border-l-4 border-l-blue-500">
            <span class="text-xs uppercase tracking-wider text-blue-400 font-bold">Total Directly Sponsored Members</span>
            <div class="text-3xl font-extrabold text-blue-400 mt-2"><?php echo count($direct_referrals); ?> Member(s)</div>
            <p class="text-xs text-gray-400 mt-2">Direct downline referrals registered using your sponsor ID</p>
        </div>

        <div class="bg-darkcard p-6 rounded-2xl gold-border-glow border-l-4 border-l-green-500">
            <span class="text-xs uppercase tracking-wider text-green-400 font-bold">Gross Direct Referral Bonus Earned</span>
            <div class="text-3xl font-extrabold text-green-400 mt-2">₹<?php echo number_format($total_direct_bonus, 2); ?></div>
            <p class="text-xs text-green-500/80 mt-2">100% credited directly to User Wallet (minus 10% TDS)</p>
        </div>
    </div>

    <!-- Direct Referrals Table -->
    <div class="bg-darkcard p-6 rounded-2xl gold-border-glow">
        <h2 class="text-lg font-bold text-white mb-4 border-b border-gold/10 pb-2"><i class="fas fa-user-friends text-gold mr-2"></i> Sponsored Members List</h2>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-300">
                <thead class="bg-gold/10 text-gold uppercase border-b border-gold/20">
                    <tr>
                        <th class="p-3">#</th>
                        <th class="p-3">Member ID</th>
                        <th class="p-3">Full Name</th>
                        <th class="p-3">Email</th>
                        <th class="p-3">Phone</th>
                        <th class="p-3">Package Tier</th>
                        <th class="p-3">Joining Date</th>
                        <th class="p-3">Bonus Earned</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gold/10">
                    <?php if (empty($direct_referrals)): ?>
                        <tr>
                            <td colspan="8" class="p-4 text-center text-gray-500">No direct referrals sponsored yet. Share your referral link to earn bonuses!</td>
                        </tr>
                    <?php else: foreach ($direct_referrals as $idx => $ref): ?>
                        <tr class="hover:bg-gold/5 transition">
                            <td class="p-3 text-gray-500"><?php echo $idx + 1; ?></td>
                            <td class="p-3 font-mono font-bold text-gold"><?php echo htmlspecialchars($ref['member_id']); ?></td>
                            <td class="p-3 font-semibold text-white"><?php echo htmlspecialchars($ref['name']); ?></td>
                            <td class="p-3 text-gray-300"><?php echo htmlspecialchars($ref['email']); ?></td>
                            <td class="p-3 font-mono text-gray-400"><?php echo htmlspecialchars($ref['phone']); ?></td>
                            <td class="p-3 font-semibold text-goldlight"><?php echo str_replace('_', ' ₹', $ref['package_type']); ?></td>
                            <td class="p-3 font-mono text-gray-400"><?php echo date('d M Y, H:i', strtotime($ref['created_at'])); ?></td>
                            <?php
                            $bonus_val = (float)($ref['bonus_earned'] ?: (($ref['package_type'] === 'Leadership_15000') ? 1500.00 : 500.00));
                            ?>
                            <td class="p-3 font-bold text-green-400">₹<?php echo number_format($bonus_val, 2); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
