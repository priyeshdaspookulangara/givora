<?php
require_once __DIR__ . '/../includes/functions.php';
checkMemberLogin();

$member = getLoggedInMember();
$page_title = "Matrix Level Income Breakdown";

$pdo = getDBConnection();

// Fetch all Matrix Level Income transactions
$stmt = $pdo->prepare("SELECT * FROM transactions WHERE member_id = ? AND type LIKE 'Matrix_Income%' AND status = 'Credit' ORDER BY created_at DESC");
$stmt->execute([$member['member_id']]);
$matrix_txs = $stmt->fetchAll();

// Calculate Gross Matrix Income
$gross_matrix_income = 0.00;
$user_wallet_share = 0.00;

foreach ($matrix_txs as $tx) {
    if ($tx['wallet_type'] === 'User_Wallet') {
        $user_wallet_share += (float)$tx['amount'];
    }
}

// Gross matrix total is User Wallet 60% share / 0.60
$gross_matrix_total = ($user_wallet_share > 0) ? ($user_wallet_share / 0.60) : 0.00;
$tds_deduction = $user_wallet_share * 0.05; // 5% TDS
$net_user_wallet_credit = $user_wallet_share - $tds_deduction;

require_once __DIR__ . '/../includes/header.php';
?>

<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-white"><i class="fas fa-sitemap text-gold mr-2"></i> Matrix Level Income Breakdown</h1>
            <p class="text-xs text-gray-400 mt-1">Detailed statements of auto-spillover matrix level commissions earned across Phase 1 & Phase 2.</p>
        </div>
        <a href="/customer/dashboard.php" class="text-xs text-gold border border-gold/40 px-3 py-1.5 rounded-lg hover:bg-gold/10">← Dashboard</a>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Gross Matrix Income -->
        <div class="bg-darkcard p-6 rounded-2xl gold-border-glow border-l-4 border-l-purple-500">
            <span class="text-xs uppercase tracking-wider text-purple-400 font-bold">Total Gross Matrix Income</span>
            <div class="text-3xl font-extrabold text-purple-300 mt-2">₹<?php echo number_format($gross_matrix_total, 2); ?></div>
            <p class="text-xs text-gray-400 mt-2">Combined Phase 1 & Phase 2 level earnings</p>
        </div>

        <!-- 60% User Wallet Share -->
        <div class="bg-darkcard p-6 rounded-2xl gold-border-glow border-l-4 border-l-blue-500">
            <span class="text-xs uppercase tracking-wider text-blue-400 font-bold">60% User Wallet Allocation</span>
            <div class="text-3xl font-extrabold text-blue-400 mt-2">₹<?php echo number_format($user_wallet_share, 2); ?></div>
            <p class="text-xs text-gray-400 mt-2">Withdrawable share before 5% TDS deduction</p>
        </div>

        <!-- Net User Wallet Credit -->
        <div class="bg-darkcard p-6 rounded-2xl gold-border-glow border-l-4 border-l-green-500">
            <span class="text-xs uppercase tracking-wider text-green-400 font-bold">Net Credited (After 5% TDS)</span>
            <div class="text-3xl font-extrabold text-green-400 mt-2">₹<?php echo number_format($net_user_wallet_credit, 2); ?></div>
            <p class="text-xs text-green-500/80 mt-2">Net withdrawable amount added to User Wallet</p>
        </div>
    </div>

    <!-- Matrix Transactions Table -->
    <div class="bg-darkcard p-6 rounded-2xl gold-border-glow">
        <h2 class="text-lg font-bold text-white mb-4 border-b border-gold/10 pb-2"><i class="fas fa-list-alt text-gold mr-2"></i> Matrix Level Commission Logs</h2>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-300">
                <thead class="bg-gold/10 text-gold uppercase border-b border-gold/20">
                    <tr>
                        <th class="p-3">Tx ID</th>
                        <th class="p-3">Date & Time</th>
                        <th class="p-3">Income Type</th>
                        <th class="p-3">Bucket</th>
                        <th class="p-3">Amount</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Description</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gold/10">
                    <?php if (empty($matrix_txs)): ?>
                        <tr>
                            <td colspan="7" class="p-4 text-center text-gray-500">No matrix level commission transactions recorded yet.</td>
                        </tr>
                    <?php else: foreach ($matrix_txs as $tx): ?>
                        <tr class="hover:bg-gold/5 transition">
                            <td class="p-3 font-mono text-gold">#<?php echo $tx['id']; ?></td>
                            <td class="p-3 font-mono text-gray-400"><?php echo date('d M Y, H:i', strtotime($tx['created_at'])); ?></td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 rounded text-[10px] bg-purple-500/20 text-purple-300 font-bold">
                                    <?php echo str_replace('_', ' ', $tx['type']); ?>
                                </span>
                            </td>
                            <td class="p-3 font-semibold text-gray-300"><?php echo htmlspecialchars($tx['wallet_type']); ?></td>
                            <td class="p-3 font-bold text-green-400">+₹<?php echo number_format($tx['amount'], 2); ?></td>
                            <td class="p-3"><span class="px-2 py-0.5 rounded bg-green-500/20 text-green-400 font-bold">Credit</span></td>
                            <td class="p-3 text-gray-300"><?php echo htmlspecialchars($tx['description']); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
