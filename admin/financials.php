<?php
require_once __DIR__ . '/../includes/functions.php';
checkAdminLogin();

$page_title = "Financial Master Ledger";
$pdo = getDBConnection();

// Sync all member wallets before calculating master ledger
$members_stmt = $pdo->query("SELECT member_id FROM members");
while ($m_id = $members_stmt->fetchColumn()) {
    syncMemberWallet($pdo, $m_id);
}

// Master Ledger Calculations
$stmt = $pdo->query("SELECT SUM(balance) FROM wallets");
$total_inflow = (float)($stmt->fetchColumn() ?: 0.00);

$stmt = $pdo->query("SELECT SUM(user_wallet_60) FROM wallets");
$total_user_wallet_balance = (float)($stmt->fetchColumn() ?: 0.00);

$stmt = $pdo->query("SELECT SUM(company_wallet_40) FROM wallets");
$total_company_wallet_balance = (float)($stmt->fetchColumn() ?: 0.00);

$stmt = $pdo->query("SELECT SUM(p2_reserve_wallet) FROM wallets");
$total_p2_reserve_balance = (float)($stmt->fetchColumn() ?: 0.00);

$stmt = $pdo->query("SELECT SUM(amount) FROM withdrawals WHERE status = 'Approved'");
$total_payouts_approved = (float)($stmt->fetchColumn() ?: 0.00);

// Fetch Member-by-Member Breakdown for User Wallets Pool Modal (Includes Direct Ref, Matrix Income, & Unilevel Level Income)
$breakdown_query = "
    SELECT
        m.member_id,
        m.name,
        w.user_wallet_60 as net_user_balance,
        w.balance as gross_balance,
        COALESCE(dr.dr_gross, 0.00) as dr_gross,
        COALESCE(dr.dr_gross, 0.00) * 0.90 as dr_net,
        COALESCE(dr.dr_gross, 0.00) * 0.10 as dr_tds,
        COALESCE(li.li_gross, 0.00) as li_gross,
        COALESCE(li.li_gross, 0.00) * 0.95 as li_net,
        COALESCE(li.li_gross, 0.00) * 0.05 as li_tds,
        COALESCE(mi.mi_gross_user, 0.00) as mi_gross_user,
        COALESCE(mi.mi_gross_user, 0.00) * 0.95 as mi_net_user,
        COALESCE(mi.mi_gross_user, 0.00) * 0.05 as mi_tds,
        COALESCE(wd.wd_total, 0.00) as withdrawals_total
    FROM members m
    LEFT JOIN wallets w ON m.member_id = w.member_id
    LEFT JOIN (
        SELECT member_id, SUM(amount) as dr_gross
        FROM transactions
        WHERE type = 'Direct_Referral' AND status = 'Credit'
        GROUP BY member_id
    ) dr ON m.member_id = dr.member_id
    LEFT JOIN (
        SELECT member_id, SUM(amount) as li_gross
        FROM transactions
        WHERE type = 'Level_Income' AND status = 'Credit'
        GROUP BY member_id
    ) li ON m.member_id = li.member_id
    LEFT JOIN (
        SELECT member_id, SUM(amount) as mi_gross_user
        FROM transactions
        WHERE type LIKE 'Matrix_Income%' AND wallet_type = 'User_Wallet' AND status = 'Credit'
        GROUP BY member_id
    ) mi ON m.member_id = mi.member_id
    LEFT JOIN (
        SELECT member_id, SUM(amount) as wd_total
        FROM transactions
        WHERE type = 'Withdrawal_Request' AND status IN ('Pending', 'Approved')
        GROUP BY member_id
    ) wd ON m.member_id = wd.member_id
    ORDER BY w.user_wallet_60 DESC
";

$stmt_breakdown = $pdo->query($breakdown_query);
$user_wallet_breakdown = $stmt_breakdown->fetchAll(PDO::FETCH_ASSOC);

// Totals for Modal Header Summary
$sum_dr_gross = 0;
$sum_dr_tds = 0;
$sum_li_gross = 0;
$sum_li_tds = 0;
$sum_mi_user_gross = 0;
$sum_mi_tds = 0;
$sum_withdrawals = 0;

foreach ($user_wallet_breakdown as $row) {
    $sum_dr_gross += (float)$row['dr_gross'];
    $sum_dr_tds += (float)$row['dr_tds'];
    $sum_li_gross += (float)$row['li_gross'];
    $sum_li_tds += (float)$row['li_tds'];
    $sum_mi_user_gross += (float)$row['mi_gross_user'];
    $sum_mi_tds += (float)$row['mi_tds'];
    $sum_withdrawals += (float)$row['withdrawals_total'];
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white"><i class="fas fa-balance-scale text-gold mr-2"></i> Financial Master Ledger</h1>
            <p class="text-xs text-gray-400 mt-1">Centralized accounting of total inflow, wallet splits, reserves, and liquidity payouts.</p>
        </div>
        <a href="/admin/index.php" class="text-xs text-gold border border-gold/40 px-3 py-1.5 rounded-lg hover:bg-gold/10 self-start md:self-auto">← Admin Overview</a>
    </div>

    <!-- Central Accounting Grid -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
        <div class="bg-darkcard p-6 rounded-2xl gold-border-glow">
            <span class="text-xs font-semibold text-gray-400 uppercase">Total Gross Inflow</span>
            <div class="text-3xl font-extrabold gold-gradient-text mt-2">₹<?php echo number_format($total_inflow, 2); ?></div>
            <p class="text-xs text-gray-500 mt-2">All-time credited commissions across matrix & level income</p>
        </div>

        <!-- CLICKABLE USER WALLETS POOL CARD -->
        <div onclick="openUserWalletModal()" class="bg-darkcard p-6 rounded-2xl gold-border-glow border-l-4 border-l-green-500 cursor-pointer hover:scale-105 hover:border-green-400 transition transform shadow-lg group relative">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-green-400 uppercase">User Wallets Pool</span>
                <span class="text-[10px] bg-green-500/20 text-green-300 font-bold px-2 py-0.5 rounded-full border border-green-500/30 group-hover:bg-green-500 group-hover:text-darkbg transition">
                    <i class="fas fa-search-plus mr-1"></i> View Breakdown
                </span>
            </div>
            <div class="text-3xl font-extrabold text-green-400 mt-2">₹<?php echo number_format($total_user_wallet_balance, 2); ?></div>
            <p class="text-xs text-green-500/80 mt-2 flex items-center justify-between">
                <span>Currently available in member wallets</span>
                <i class="fas fa-arrow-right group-hover:translate-x-1 transition"></i>
            </p>
        </div>

        <div class="bg-darkcard p-6 rounded-2xl gold-border-glow border-l-4 border-l-amber-500">
            <span class="text-xs font-semibold text-amber-400 uppercase">Company Reserves Pool (40%)</span>
            <div class="text-3xl font-extrabold text-amber-400 mt-2">₹<?php echo number_format($total_company_wallet_balance, 2); ?></div>
            <p class="text-xs text-amber-500/80 mt-2">Retention reserve for corporate & inventory</p>
        </div>

        <div class="bg-darkcard p-6 rounded-2xl gold-border-glow border-l-4 border-l-purple-500">
            <span class="text-xs font-semibold text-purple-400 uppercase">Phase 2 Joining Reserves</span>
            <div class="text-3xl font-extrabold text-purple-300 mt-2">₹<?php echo number_format($total_p2_reserve_balance, 2); ?></div>
            <p class="text-xs text-purple-400/80 mt-2">Reserved L5 matrix income for Phase 2 entry</p>
        </div>

        <div class="bg-darkcard p-6 rounded-2xl gold-border-glow border-l-4 border-l-blue-500">
            <span class="text-xs font-semibold text-blue-400 uppercase">Total Approved Payouts</span>
            <div class="text-3xl font-extrabold text-blue-400 mt-2">₹<?php echo number_format($total_payouts_approved, 2); ?></div>
            <p class="text-xs text-blue-500/80 mt-2">Successfully disbursed member withdrawals</p>
        </div>
    </div>
</div>

<!-- USER WALLETS POOL BREAKDOWN MODAL -->
<div id="userWalletModal" class="fixed inset-0 z-50 hidden bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-darkcard border border-gold/40 rounded-2xl max-w-6xl w-full p-6 shadow-2xl relative gold-border-glow my-8">
        <!-- Close Button -->
        <button onclick="closeUserWalletModal()" class="absolute top-4 right-4 text-gray-400 hover:text-white text-xl font-bold p-1">
            <i class="fas fa-times"></i>
        </button>

        <!-- Header -->
        <div class="border-b border-gold/20 pb-4 mb-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-green-500/20 text-green-400 flex items-center justify-center text-xl border border-green-500/40">
                    <i class="fas fa-wallet"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-white">User Wallets Pool Detailed Breakdown</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Comprehensive audit statement showing Direct Referrals, Unilevel Level Income, Matrix Earnings, TDS Deductions, Withdrawals, and Net Balances.</p>
                </div>
            </div>
        </div>

        <!-- Summary Metric Badges -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6 text-xs">
            <div class="bg-darkbg p-3 rounded-xl border border-gold/20">
                <span class="text-gray-400 block">Gross Direct Ref</span>
                <span class="text-sm font-bold text-gold">₹<?php echo number_format($sum_dr_gross, 2); ?></span>
                <span class="text-[10px] text-red-400 block mt-0.5">(10% TDS: -₹<?php echo number_format($sum_dr_tds, 2); ?>)</span>
            </div>
            <div class="bg-darkbg p-3 rounded-xl border border-gold/20">
                <span class="text-gray-400 block">Gross Level Income</span>
                <span class="text-sm font-bold text-gold">₹<?php echo number_format($sum_li_gross, 2); ?></span>
                <span class="text-[10px] text-red-400 block mt-0.5">(5% TDS: -₹<?php echo number_format($sum_li_tds, 2); ?>)</span>
            </div>
            <div class="bg-darkbg p-3 rounded-xl border border-gold/20">
                <span class="text-gray-400 block">Gross Matrix (60% Share)</span>
                <span class="text-sm font-bold text-gold">₹<?php echo number_format($sum_mi_user_gross, 2); ?></span>
                <span class="text-[10px] text-red-400 block mt-0.5">(5% TDS: -₹<?php echo number_format($sum_mi_tds, 2); ?>)</span>
            </div>
            <div class="bg-darkbg p-3 rounded-xl border border-gold/20">
                <span class="text-gray-400 block">Withdrawals Debited</span>
                <span class="text-sm font-bold text-amber-400">₹<?php echo number_format($sum_withdrawals, 2); ?></span>
                <span class="text-[10px] text-gray-500 block mt-0.5">Approved & Pending</span>
            </div>
            <div class="bg-darkbg p-3 rounded-xl border border-green-500/40">
                <span class="text-gray-400 block">Net User Pool</span>
                <span class="text-sm font-bold text-green-400">₹<?php echo number_format($total_user_wallet_balance, 2); ?></span>
                <span class="text-[10px] text-green-500 block mt-0.5">Currently Liquid</span>
            </div>
        </div>

        <!-- Detailed Table -->
        <div class="overflow-x-auto max-h-[400px] overflow-y-auto border border-gold/10 rounded-xl">
            <table class="w-full text-left text-xs text-gray-300">
                <thead class="bg-gold/10 text-gold uppercase sticky top-0 bg-darkcard border-b border-gold/20">
                    <tr>
                        <th class="p-3">Member</th>
                        <th class="p-3 text-right">Direct Ref (Gross)</th>
                        <th class="p-3 text-right">Unilevel Level Income</th>
                        <th class="p-3 text-right">Matrix User Share</th>
                        <th class="p-3 text-right">TDS Deducted</th>
                        <th class="p-3 text-right">Withdrawals</th>
                        <th class="p-3 text-right">Net User Wallet</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gold/10">
                    <?php if (empty($user_wallet_breakdown)): ?>
                        <tr>
                            <td colspan="7" class="p-4 text-center text-gray-500">No member wallet entries found.</td>
                        </tr>
                    <?php else: foreach ($user_wallet_breakdown as $row):
                        $total_tds = (float)$row['dr_tds'] + (float)$row['li_tds'] + (float)$row['mi_tds'];
                    ?>
                        <tr class="hover:bg-gold/5 transition">
                            <td class="p-3">
                                <div class="font-bold text-white"><?php echo htmlspecialchars($row['name']); ?></div>
                                <div class="font-mono text-[11px] text-gold"><?php echo htmlspecialchars($row['member_id']); ?></div>
                            </td>
                            <td class="p-3 text-right font-mono text-gray-300">₹<?php echo number_format((float)$row['dr_gross'], 2); ?></td>
                            <td class="p-3 text-right font-mono text-amber-300">₹<?php echo number_format((float)$row['li_gross'], 2); ?></td>
                            <td class="p-3 text-right font-mono text-gray-300">₹<?php echo number_format((float)$row['mi_gross_user'], 2); ?></td>
                            <td class="p-3 text-right font-mono text-red-400">-₹<?php echo number_format($total_tds, 2); ?></td>
                            <td class="p-3 text-right font-mono text-amber-400">₹<?php echo number_format((float)$row['withdrawals_total'], 2); ?></td>
                            <td class="p-3 text-right font-mono font-bold text-green-400">₹<?php echo number_format((float)$row['net_user_balance'], 2); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Footer / Formula Note -->
        <div class="mt-4 pt-3 border-t border-gold/10 text-[11px] text-gray-400 flex flex-col md:flex-row justify-between items-center gap-2">
            <div>
                <i class="fas fa-info-circle text-gold mr-1"></i>
                <span class="font-semibold text-gray-300">Formula:</span> Net User Wallet = (Direct Ref Gross − 10% TDS) + (Level Income − 5% TDS) + (Matrix User Share − 5% TDS) − Withdrawals.
            </div>
            <button onclick="closeUserWalletModal()" class="bg-gold/20 text-gold border border-gold/40 px-4 py-1.5 rounded-lg hover:bg-gold hover:text-darkbg transition font-semibold">
                Close Breakdown
            </button>
        </div>
    </div>
</div>

<script>
function openUserWalletModal() {
    document.getElementById('userWalletModal').classList.remove('hidden');
}

function closeUserWalletModal() {
    document.getElementById('userWalletModal').classList.add('hidden');
}

// Close modal on escape key
document.addEventListener('keydown', function(event) {
    if (event.key === "Escape") {
        closeUserWalletModal();
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
