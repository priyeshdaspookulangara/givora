<?php
require_once __DIR__ . '/../includes/functions.php';
checkAdminLogin();

$page_title = "Financial Master Ledger";
$pdo = getDBConnection();

// Master Ledger Calculations
$stmt = $pdo->query("SELECT SUM(balance) FROM wallets");
$total_inflow = $stmt->fetchColumn() ?: 0.00;

$stmt = $pdo->query("SELECT SUM(user_wallet_60) FROM wallets");
$total_user_wallet_balance = $stmt->fetchColumn() ?: 0.00;

$stmt = $pdo->query("SELECT SUM(company_wallet_40) FROM wallets");
$total_company_wallet_balance = $stmt->fetchColumn() ?: 0.00;

$stmt = $pdo->query("SELECT SUM(p2_reserve_wallet) FROM wallets");
$total_p2_reserve_balance = $stmt->fetchColumn() ?: 0.00;

$stmt = $pdo->query("SELECT SUM(amount) FROM withdrawals WHERE status = 'Approved'");
$total_payouts_approved = $stmt->fetchColumn() ?: 0.00;

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
            <p class="text-xs text-gray-500 mt-2">All-time credited commissions across matrix</p>
        </div>

        <div class="bg-darkcard p-6 rounded-2xl gold-border-glow border-l-4 border-l-green-500">
            <span class="text-xs font-semibold text-green-400 uppercase">User Wallets Pool (60%)</span>
            <div class="text-3xl font-extrabold text-green-400 mt-2">₹<?php echo number_format($total_user_wallet_balance, 2); ?></div>
            <p class="text-xs text-green-500/80 mt-2">Currently available in member user wallets</p>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
