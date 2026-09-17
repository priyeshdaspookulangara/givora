<?php
require_once __DIR__ . '/../includes/functions.php';
checkAdminLogin();

$page_title = "Admin Portal - Dashboard";
require_once __DIR__ . '/../includes/header.php';

$pdo = getDBConnection();

// Summary Stats
$stmt = $pdo->query("SELECT COUNT(*) FROM members");
$total_members = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM epins WHERE status = 'Unused'");
$unused_epins = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT SUM(amount) FROM withdrawals WHERE status = 'Pending'");
$pending_withdrawals_amount = $stmt->fetchColumn() ?: 0.00;

$stmt = $pdo->query("SELECT SUM(company_wallet_40) FROM wallets");
$total_company_wallet_reserve = $stmt->fetchColumn() ?: 0.00;

$stmt = $pdo->query("SELECT SUM(balance) FROM wallets");
$total_system_inflow = $stmt->fetchColumn() ?: 0.00;
?>

<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Admin Header -->
    <div class="bg-darkcard border border-gold/30 rounded-2xl p-6 mb-8 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white flex items-center">
                <i class="fas fa-user-shield text-gold mr-3"></i> Administrator Control Center
            </h1>
            <p class="text-xs text-goldlight mt-1">Givora Traders LLP Master Management Console</p>
        </div>

        <div class="flex flex-wrap gap-2 text-sm">
            <a href="/admin/index.php" class="px-4 py-2 rounded-lg bg-gold/20 border border-gold text-gold font-medium"><i class="fas fa-chart-pie mr-1"></i> Overview</a>
            <a href="/admin/members.php" class="px-4 py-2 rounded-lg bg-darkbg border border-gold/20 text-gray-300 hover:text-gold transition"><i class="fas fa-users mr-1"></i> Members</a>
            <a href="/admin/kyc.php" class="px-4 py-2 rounded-lg bg-darkbg border border-gold/20 text-gray-300 hover:text-gold transition"><i class="fas fa-user-check mr-1"></i> KYC Reviews</a>
            <a href="/admin/epins.php" class="px-4 py-2 rounded-lg bg-darkbg border border-gold/20 text-gray-300 hover:text-gold transition"><i class="fas fa-key mr-1"></i> ePIN Generator</a>
            <a href="/admin/wallet.php" class="px-4 py-2 rounded-lg bg-darkbg border border-gold/20 text-gray-300 hover:text-gold transition"><i class="fas fa-university mr-1"></i> Financials & Payouts</a>
            <a href="/admin/reports.php" class="px-4 py-2 rounded-lg bg-darkbg border border-gold/20 text-gray-300 hover:text-gold transition"><i class="fas fa-file-alt mr-1"></i> Audit Reports</a>
        </div>
    </div>

    <!-- Overview Stat Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-darkcard p-6 rounded-2xl gold-border-glow">
            <span class="text-xs uppercase font-bold text-gray-400">Total Registered Members</span>
            <div class="text-3xl font-extrabold text-white mt-2"><?php echo $total_members; ?></div>
            <a href="/admin/members.php" class="text-xs text-gold hover:underline block mt-3 font-semibold">Manage Members →</a>
        </div>

        <div class="bg-darkcard p-6 rounded-2xl gold-border-glow">
            <span class="text-xs uppercase font-bold text-gray-400">Active Unused ePINs</span>
            <div class="text-3xl font-extrabold text-gold mt-2"><?php echo $unused_epins; ?></div>
            <a href="/admin/epins.php" class="text-xs text-gold hover:underline block mt-3 font-semibold">Generate ePINs →</a>
        </div>

        <div class="bg-darkcard p-6 rounded-2xl gold-border-glow border-l-4 border-l-amber-500">
            <span class="text-xs uppercase font-bold text-amber-400">Company Wallet Reserve (40%)</span>
            <div class="text-3xl font-extrabold text-amber-400 mt-2">₹<?php echo number_format($total_company_wallet_reserve, 2); ?></div>
            <a href="/admin/financials.php" class="text-xs text-amber-400 hover:underline block mt-3 font-semibold">View Company Wallet →</a>
        </div>

        <div class="bg-darkcard p-6 rounded-2xl gold-border-glow border-l-4 border-l-red-500">
            <span class="text-xs uppercase font-bold text-red-400">Pending Withdrawal Requests</span>
            <div class="text-3xl font-extrabold text-red-400 mt-2">₹<?php echo number_format($pending_withdrawals_amount, 2); ?></div>
            <a href="/admin/wallet.php" class="text-xs text-red-400 hover:underline block mt-3 font-semibold">Process Payouts →</a>
        </div>
    </div>

    <!-- Quick Management Table -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Recent Registered Members -->
        <div class="bg-darkcard p-6 rounded-2xl gold-border-glow">
            <h3 class="text-lg font-bold text-white mb-4"><i class="fas fa-user-plus text-gold mr-2"></i> Latest Member Registrations</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-gray-300">
                    <thead class="bg-gold/10 text-gold uppercase border-b border-gold/20">
                        <tr>
                            <th class="p-2.5">Member ID</th>
                            <th class="p-2.5">Name</th>
                            <th class="p-2.5">Package</th>
                            <th class="p-2.5">Sponsor</th>
                            <th class="p-2.5">Joined Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gold/10">
                        <?php
                        $stmt = $pdo->query("SELECT * FROM members ORDER BY created_at DESC LIMIT 5");
                        $latest_members = $stmt->fetchAll();
                        if (empty($latest_members)):
                        ?>
                            <tr><td colspan="5" class="p-4 text-center text-gray-500">No members registered yet.</td></tr>
                        <?php else: foreach ($latest_members as $m): ?>
                            <tr>
                                <td class="p-2.5 font-mono text-gold font-bold"><?php echo htmlspecialchars($m['member_id']); ?></td>
                                <td class="p-2.5 font-semibold text-white"><?php echo htmlspecialchars($m['name']); ?></td>
                                <td class="p-2.5"><?php echo str_replace('_', ' ₹', $m['package_type']); ?></td>
                                <td class="p-2.5 font-mono text-gray-400"><?php echo htmlspecialchars($m['sponsor_id'] ?: 'Root'); ?></td>
                                <td class="p-2.5 text-gray-400"><?php echo date('d M Y', strtotime($m['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pending Payout Requests -->
        <div class="bg-darkcard p-6 rounded-2xl gold-border-glow">
            <h3 class="text-lg font-bold text-white mb-4"><i class="fas fa-clock text-gold mr-2"></i> Pending Payout Requests</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-gray-300">
                    <thead class="bg-gold/10 text-gold uppercase border-b border-gold/20">
                        <tr>
                            <th class="p-2.5">Req #</th>
                            <th class="p-2.5">Member ID</th>
                            <th class="p-2.5">Amount</th>
                            <th class="p-2.5">Request Date</th>
                            <th class="p-2.5">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gold/10">
                        <?php
                        $stmt = $pdo->query("SELECT * FROM withdrawals WHERE status = 'Pending' ORDER BY request_date ASC LIMIT 5");
                        $pending = $stmt->fetchAll();
                        if (empty($pending)):
                        ?>
                            <tr><td colspan="5" class="p-4 text-center text-gray-500">No pending withdrawal requests.</td></tr>
                        <?php else: foreach ($pending as $w): ?>
                            <tr>
                                <td class="p-2.5 font-mono text-gold">#<?php echo $w['id']; ?></td>
                                <td class="p-2.5 font-mono font-bold text-white"><?php echo htmlspecialchars($w['member_id']); ?></td>
                                <td class="p-2.5 font-bold text-green-400">₹<?php echo number_format($w['amount'], 2); ?></td>
                                <td class="p-2.5 text-gray-400"><?php echo date('d M Y', strtotime($w['request_date'])); ?></td>
                                <td class="p-2.5">
                                    <a href="/admin/wallet.php" class="px-2.5 py-1 rounded bg-gold text-darkbg font-bold text-[10px]">Review</a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
