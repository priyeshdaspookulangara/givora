<?php
require_once __DIR__ . '/../includes/functions.php';
checkMemberLogin();

$member = getLoggedInMember();
$page_title = "Earning Reports & Statements";

$pdo = getDBConnection();

// Fetch All Member Transactions
$stmt = $pdo->prepare("SELECT * FROM transactions WHERE member_id = ? ORDER BY created_at DESC");
$stmt->execute([$member['member_id']]);
$transactions = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-white"><i class="fas fa-file-invoice-dollar text-gold mr-2"></i> Earning Reports & Transaction Audit Log</h1>
            <p class="text-xs text-gray-400 mt-1">Detailed history of direct referral bonuses, matrix level payouts, and withdrawals.</p>
        </div>
        <a href="/customer/dashboard.php" class="text-xs text-gold border border-gold/40 px-3 py-1.5 rounded-lg hover:bg-gold/10">← Dashboard</a>
    </div>

    <!-- Transaction History Master Table -->
    <div class="bg-darkcard p-6 rounded-2xl gold-border-glow">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-300">
                <thead class="bg-gold/10 text-gold uppercase border-b border-gold/20">
                    <tr>
                        <th class="p-3">Tx ID</th>
                        <th class="p-3">Date & Time</th>
                        <th class="p-3">Transaction Type</th>
                        <th class="p-3">Target Wallet</th>
                        <th class="p-3">Amount</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Description</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gold/10">
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="7" class="p-4 text-center text-gray-500">No transaction activity logged yet.</td>
                        </tr>
                    <?php else: foreach ($transactions as $tx): ?>
                        <tr class="hover:bg-gold/5 transition">
                            <td class="p-3 font-mono text-gold">#<?php echo $tx['id']; ?></td>
                            <td class="p-3 font-mono text-gray-400"><?php echo date('d M Y, H:i:s', strtotime($tx['created_at'])); ?></td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 rounded text-xs bg-gold/10 text-gold font-bold">
                                    <?php echo str_replace('_', ' ', $tx['type']); ?>
                                </span>
                            </td>
                            <td class="p-3 font-semibold text-gray-300"><?php echo htmlspecialchars($tx['wallet_type']); ?></td>
                            <td class="p-3 font-extrabold text-sm <?php echo $tx['status'] === 'Credit' ? 'text-green-400' : 'text-red-400'; ?>">
                                <?php echo $tx['status'] === 'Credit' ? '+' : '-'; ?>₹<?php echo number_format($tx['amount'], 2); ?>
                            </td>
                            <td class="p-3">
                                <?php if ($tx['status'] === 'Credit'): ?>
                                    <span class="px-2 py-0.5 rounded bg-green-500/20 text-green-400 font-bold">Credit</span>
                                <?php elseif ($tx['status'] === 'Debit'): ?>
                                    <span class="px-2 py-0.5 rounded bg-red-500/20 text-red-400 font-bold">Debit</span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 rounded bg-amber-500/20 text-amber-400 font-bold">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3 text-gray-300"><?php echo htmlspecialchars($tx['description']); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
