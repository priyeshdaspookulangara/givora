<?php
require_once __DIR__ . '/../includes/functions.php';
checkMemberLogin();

$member = getLoggedInMember();
$page_title = "Customer Dashboard";

require_once __DIR__ . '/../includes/header.php';

$pdo = getDBConnection();

// Fetch Wallet Summary
ensureWalletExists($pdo, $member['member_id']);
$stmt = $pdo->prepare("SELECT * FROM wallets WHERE member_id = ?");
$stmt->execute([$member['member_id']]);
$wallet = $stmt->fetch();

// Calculate Referral Stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE sponsor_id = ?");
$stmt->execute([$member['member_id']]);
$total_direct_referrals = $stmt->fetchColumn();

// Total Matrix Team Count under this parent recursively or down levels
$stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE placement_parent_id = ?");
$stmt->execute([$member['member_id']]);
$direct_matrix_children = $stmt->fetchColumn();

// Total Earnings (sum of direct referral + matrix transactions)
$stmt = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE member_id = ? AND status = 'Credit'");
$stmt->execute([$member['member_id']]);
$total_earnings = $stmt->fetchColumn() ?: 0.00;

// Referral URL
$referral_url = getBaseUrl() . "/register.php?sponsor=" . urlencode($member['member_id']);
?>

<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Top Customer Nav Bar -->
    <div class="bg-darkcard border border-gold/20 rounded-2xl p-4 mb-8 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center space-x-4">
            <div class="w-12 h-12 rounded-full overflow-hidden border-2 border-gold bg-darkbg flex items-center justify-center text-gold text-xl font-bold">
                <?php if (!empty($member['profile_image'])): ?>
                    <img src="<?php echo htmlspecialchars($member['profile_image']); ?>" class="w-full h-full object-cover" alt="Profile">
                <?php else: ?>
                    <i class="fas fa-user"></i>
                <?php endif; ?>
            </div>
            <div>
                <h1 class="text-xl font-bold text-white"><?php echo htmlspecialchars($member['name']); ?></h1>
                <p class="text-xs text-gold">Member ID: <span class="font-mono text-white font-semibold"><?php echo htmlspecialchars($member['member_id']); ?></span> | Tier: <span class="font-semibold text-goldlight"><?php echo str_replace('_', ' ₹', $member['package_type']); ?></span></p>
            </div>
        </div>

        <div class="flex flex-wrap gap-2 text-sm">
            <a href="/customer/dashboard.php" class="px-4 py-2 rounded-lg bg-gold/20 border border-gold text-gold font-medium"><i class="fas fa-chart-line mr-1.5"></i> Dashboard</a>
            <a href="/customer/profile.php" class="px-4 py-2 rounded-lg bg-darkbg border border-gold/20 text-gray-300 hover:text-gold transition"><i class="fas fa-user-edit mr-1.5"></i> Profile</a>
            <a href="/customer/teams.php" class="px-4 py-2 rounded-lg bg-darkbg border border-gold/20 text-gray-300 hover:text-gold transition"><i class="fas fa-sitemap mr-1.5"></i> My Matrix Team</a>
            <a href="/customer/pins.php" class="px-4 py-2 rounded-lg bg-darkbg border border-gold/20 text-gray-300 hover:text-gold transition"><i class="fas fa-ticket-alt mr-1.5"></i> My ePINs</a>
            <a href="/customer/wallet.php" class="px-4 py-2 rounded-lg bg-darkbg border border-gold/20 text-gray-300 hover:text-gold transition"><i class="fas fa-wallet mr-1.5"></i> Wallet & Payout</a>
            <a href="/customer/reports.php" class="px-4 py-2 rounded-lg bg-darkbg border border-gold/20 text-gray-300 hover:text-gold transition"><i class="fas fa-file-invoice-dollar mr-1.5"></i> Reports</a>
        </div>
    </div>

    <!-- Referral Link Box -->
    <div class="bg-gradient-to-r from-darkcard via-darkcard to-gold/10 p-6 rounded-2xl gold-border-glow mb-8">
        <div class="flex flex-col md:flex-row items-center justify-between gap-4">
            <div>
                <h3 class="text-lg font-bold text-white"><i class="fas fa-share-alt text-gold mr-2"></i> Your Exclusive Referral Link</h3>
                <p class="text-xs text-gray-400 mt-1">Share this link with potential members to earn Direct Referral Bonuses and build your matrix.</p>
            </div>
            <div class="flex items-center w-full md:w-auto space-x-2">
                <input type="text" id="refLink" readonly value="<?php echo htmlspecialchars($referral_url); ?>" class="bg-darkbg border border-gold/30 rounded-xl px-4 py-2.5 text-xs text-gold font-mono w-full md:w-80 focus:outline-none">
                <button onclick="copyRefLink()" class="btn-gold px-4 py-2.5 rounded-xl font-bold text-xs flex-shrink-0">
                    <i class="fas fa-copy mr-1"></i> Copy Link
                </button>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Total Earnings -->
        <div class="bg-darkcard p-6 rounded-2xl gold-border-glow">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold uppercase text-gray-400">Total Earnings</span>
                <div class="w-10 h-10 rounded-xl bg-gold/10 text-gold flex items-center justify-center text-lg">
                    <i class="fas fa-coins"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold gold-gradient-text">₹<?php echo number_format($total_earnings, 2); ?></div>
            <p class="text-xs text-gray-500 mt-2">Combined Direct + Matrix Bonus</p>
        </div>

        <!-- User Wallet (60%) -->
        <div class="bg-darkcard p-6 rounded-2xl gold-border-glow">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold uppercase text-gray-400">User Wallet (60%)</span>
                <div class="w-10 h-10 rounded-xl bg-green-500/10 text-green-400 flex items-center justify-center text-lg">
                    <i class="fas fa-wallet"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-green-400">₹<?php echo number_format($wallet['user_wallet_60'], 2); ?></div>
            <p class="text-xs text-green-500/80 mt-2">Available for Withdrawal (Min ₹500)</p>
        </div>

        <!-- Company Wallet (40%) -->
        <div class="bg-darkcard p-6 rounded-2xl gold-border-glow">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold uppercase text-gray-400">Company Wallet (40%)</span>
                <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center text-lg">
                    <i class="fas fa-building"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-amber-400">₹<?php echo number_format($wallet['company_wallet_40'], 2); ?></div>
            <p class="text-xs text-amber-500/80 mt-2">Company Reserve Allocation</p>
        </div>

        <!-- Direct Referrals -->
        <div class="bg-darkcard p-6 rounded-2xl gold-border-glow">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold uppercase text-gray-400">Direct Referrals</span>
                <div class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-400 flex items-center justify-center text-lg">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-white"><?php echo $total_direct_referrals; ?></div>
            <p class="text-xs text-gray-500 mt-2">Directly Sponsored Members</p>
        </div>
    </div>

    <!-- Recent Activity & Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Recent Earnings Table -->
        <div class="lg:col-span-2 bg-darkcard p-6 rounded-2xl gold-border-glow">
            <h3 class="text-lg font-bold text-white mb-4"><i class="fas fa-history text-gold mr-2"></i> Recent Income Transactions</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-gray-300">
                    <thead class="bg-gold/10 text-gold uppercase border-b border-gold/20">
                        <tr>
                            <th class="p-3">Date</th>
                            <th class="p-3">Type</th>
                            <th class="p-3">Wallet</th>
                            <th class="p-3">Amount</th>
                            <th class="p-3">Description</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gold/10">
                        <?php
                        $stmt = $pdo->prepare("SELECT * FROM transactions WHERE member_id = ? ORDER BY created_at DESC LIMIT 5");
                        $stmt->execute([$member['member_id']]);
                        $recent_txs = $stmt->fetchAll();
                        if (empty($recent_txs)):
                        ?>
                            <tr>
                                <td colspan="5" class="p-4 text-center text-gray-500">No income transactions recorded yet.</td>
                            </tr>
                        <?php else: foreach ($recent_txs as $tx): ?>
                            <tr>
                                <td class="p-3 font-mono"><?php echo date('d M Y, H:i', strtotime($tx['created_at'])); ?></td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded text-xs bg-gold/10 text-gold font-medium">
                                        <?php echo str_replace('_', ' ', $tx['type']); ?>
                                    </span>
                                </td>
                                <td class="p-3 font-semibold text-gray-400"><?php echo htmlspecialchars($tx['wallet_type']); ?></td>
                                <td class="p-3 font-bold <?php echo $tx['status'] === 'Credit' ? 'text-green-400' : 'text-red-400'; ?>">
                                    <?php echo $tx['status'] === 'Credit' ? '+' : '-'; ?>₹<?php echo number_format($tx['amount'], 2); ?>
                                </td>
                                <td class="p-3 text-gray-400"><?php echo htmlspecialchars($tx['description']); ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-4 text-right">
                <a href="/customer/reports.php" class="text-xs text-gold hover:underline font-semibold">View All Income Logs →</a>
            </div>
        </div>

        <!-- Account Summary Box -->
        <div class="bg-darkcard p-6 rounded-2xl gold-border-glow space-y-4">
            <h3 class="text-lg font-bold text-white mb-2"><i class="fas fa-id-card-alt text-gold mr-2"></i> Account Details</h3>
            <div class="space-y-3 text-xs">
                <div class="flex justify-between border-b border-gold/10 pb-2">
                    <span class="text-gray-400">Sponsor ID:</span>
                    <span class="text-white font-bold font-mono"><?php echo htmlspecialchars($member['sponsor_id'] ?: 'None (Root)'); ?></span>
                </div>
                <div class="flex justify-between border-b border-gold/10 pb-2">
                    <span class="text-gray-400">Matrix Parent ID:</span>
                    <span class="text-white font-bold font-mono"><?php echo htmlspecialchars($member['placement_parent_id'] ?: 'None (Root)'); ?></span>
                </div>
                <div class="flex justify-between border-b border-gold/10 pb-2">
                    <span class="text-gray-400">Matrix Position:</span>
                    <span class="text-gold font-bold">Position #<?php echo htmlspecialchars($member['matrix_position'] ?: 'Root'); ?></span>
                </div>
                <div class="flex justify-between border-b border-gold/10 pb-2">
                    <span class="text-gray-400">Used ePIN Code:</span>
                    <span class="text-white font-mono"><?php echo htmlspecialchars($member['used_epin']); ?></span>
                </div>
                <div class="flex justify-between border-b border-gold/10 pb-2">
                    <span class="text-gray-400">Phase 1 Status:</span>
                    <span class="px-2 py-0.5 rounded bg-green-500/20 text-green-400 font-bold">Active</span>
                </div>
                <div class="flex justify-between border-b border-gold/10 pb-2">
                    <span class="text-gray-400">Phase 2 Matrix Status:</span>
                    <?php if ($member['p2_status'] === 'Active'): ?>
                        <span class="px-2 py-0.5 rounded bg-amber-500/20 text-amber-400 font-bold border border-amber-500/30"><i class="fas fa-crown mr-1"></i> Promoted (Active)</span>
                    <?php else: ?>
                        <span class="px-2 py-0.5 rounded bg-gray-500/20 text-gray-400 font-semibold">Pending Completion (<?php echo $direct_matrix_children; ?>/3)</span>
                    <?php endif; ?>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Joined Date:</span>
                    <span class="text-gray-300"><?php echo date('d M Y', strtotime($member['created_at'])); ?></span>
                </div>
            </div>

            <div class="pt-4 border-t border-gold/10 flex flex-col gap-2">
                <a href="/customer/wallet.php" class="btn-gold py-2.5 rounded-xl font-bold text-center text-xs">
                    <i class="fas fa-money-bill-wave mr-1"></i> Request Withdrawal
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function copyRefLink() {
    const copyText = document.getElementById("refLink");
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(copyText.value);
    alert("Referral link copied to clipboard!");
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
