<?php
require_once __DIR__ . '/../includes/functions.php';
checkAdminLogin();

$page_title = "System Audit Reports & Transaction Logs";
$pdo = getDBConnection();

// Fetch System Wide Transactions
$stmt = $pdo->prepare("SELECT t.*, m.name as member_name FROM transactions t LEFT JOIN members m ON t.member_id = m.member_id ORDER BY t.created_at DESC LIMIT 200");
$stmt->execute();
$all_transactions = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white"><i class="fas fa-file-alt text-gold mr-2"></i> Comprehensive Audit Trails & Payout Distribution Statements</h1>
            <p class="text-xs text-gray-400 mt-1">Full system-wide transaction history across all 3-matrix levels and wallet types.</p>
        </div>
        <a href="/admin/index.php" class="text-xs text-gold border border-gold/40 px-3 py-1.5 rounded-lg hover:bg-gold/10 self-start md:self-auto">← Admin Overview</a>
    </div>

    <!-- Master Transaction Table -->
    <div class="bg-darkcard p-6 rounded-2xl gold-border-glow">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-300">
                <thead class="bg-gold/10 text-gold uppercase border-b border-gold/20">
                    <tr>
                        <th class="p-3">Tx ID</th>
                        <th class="p-3">Member ID & Name</th>
                        <th class="p-3">Type</th>
                        <th class="p-3">Wallet Bucket</th>
                        <th class="p-3">Amount</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Description</th>
                        <th class="p-3">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gold/10">
                    <?php if (empty($all_transactions)): ?>
                        <tr>
                            <td colspan="8" class="p-4 text-center text-gray-500">No system transactions logged yet.</td>
                        </tr>
                    <?php else: foreach ($all_transactions as $tx): ?>
                        <tr class="hover:bg-gold/5 transition">
                            <td class="p-3 font-mono text-gold">#<?php echo $tx['id']; ?></td>
                            <td class="p-3 font-mono">
                                <div class="font-bold text-white"><?php echo htmlspecialchars($tx['member_id']); ?></div>
                                <div class="text-gray-400 text-[10px]"><?php echo htmlspecialchars($tx['member_name'] ?: 'System/Admin'); ?></div>
                            </td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 rounded text-[10px] bg-gold/10 text-gold font-bold">
                                    <?php echo str_replace('_', ' ', $tx['type']); ?>
                                </span>
                            </td>
                            <td class="p-3 font-semibold text-gray-300"><?php echo htmlspecialchars($tx['wallet_type']); ?></td>
                            <td class="p-3 font-extrabold text-sm <?php echo $tx['status'] === 'Credit' ? 'text-green-400' : 'text-red-400'; ?>">
                                <?php echo $tx['status'] === 'Credit' ? '+' : '-'; ?>₹<?php echo number_format($tx['amount'], 2); ?>
                            </td>
                            <td class="p-3">
                                <?php if ($tx['status'] === 'Credit' || $tx['status'] === 'Approved'): ?>
                                    <span class="px-2 py-0.5 rounded bg-green-500/20 text-green-400 font-bold"><?php echo $tx['status']; ?></span>
                                <?php elseif ($tx['status'] === 'Debit'): ?>
                                    <span class="px-2 py-0.5 rounded bg-red-500/20 text-red-400 font-bold">Debit</span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 rounded bg-amber-500/20 text-amber-400 font-bold">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3 text-gray-300"><?php echo htmlspecialchars($tx['description']); ?></td>
                            <td class="p-3 font-mono text-gray-400"><?php echo date('d M Y, H:i:s', strtotime($tx['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
