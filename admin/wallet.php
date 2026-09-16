<?php
require_once __DIR__ . '/../includes/functions.php';
checkAdminLogin();

$page_title = "Withdrawal Requests Control";
$pdo = getDBConnection();

$msg = '';
$error = '';

// Handle Approve / Reject Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $withdrawal_id = intval($_POST['withdrawal_id'] ?? 0);
    $action = $_POST['action_type'] ?? '';

    if ($withdrawal_id > 0 && in_array($action, ['Approve', 'Reject'])) {
        // Fetch withdrawal record
        $stmt = $pdo->prepare("SELECT * FROM withdrawals WHERE id = ?");
        $stmt->execute([$withdrawal_id]);
        $withdrawal = $stmt->fetch();

        if (!$withdrawal) {
            $error = "Withdrawal request not found.";
        } elseif ($withdrawal['status'] !== 'Pending') {
            $error = "This withdrawal request has already been processed.";
        } else {
            try {
                $pdo->beginTransaction();

                if ($action === 'Approve') {
                    // Update withdrawal status
                    $stmt = $pdo->prepare("UPDATE withdrawals SET status = 'Approved', processed_date = NOW() WHERE id = ?");
                    $stmt->execute([$withdrawal_id]);

                    // Update corresponding transaction status
                    $stmt = $pdo->prepare("UPDATE transactions SET status = 'Approved' WHERE member_id = ? AND type = 'Withdrawal_Request' AND status = 'Pending' AND amount = ? ORDER BY created_at DESC LIMIT 1");
                    $stmt->execute([$withdrawal['member_id'], $withdrawal['amount']]);

                    $msg = "Withdrawal request #{$withdrawal_id} of ₹" . number_format($withdrawal['amount'], 2) . " APPROVED successfully.";
                } elseif ($action === 'Reject') {
                    // Update withdrawal status
                    $stmt = $pdo->prepare("UPDATE withdrawals SET status = 'Rejected', processed_date = NOW() WHERE id = ?");
                    $stmt->execute([$withdrawal_id]);

                    // Refund amount back to member's user_wallet_60
                    $stmt = $pdo->prepare("UPDATE wallets SET user_wallet_60 = user_wallet_60 + ? WHERE member_id = ?");
                    $stmt->execute([$withdrawal['amount'], $withdrawal['member_id']]);

                    // Update transaction log
                    $stmt = $pdo->prepare("INSERT INTO transactions (member_id, type, amount, wallet_type, status, description) VALUES (?, 'Admin_Adjustment', ?, 'User_Wallet', 'Credit', ?)");
                    $stmt->execute([$withdrawal['member_id'], $withdrawal['amount'], "Refund for rejected withdrawal request #{$withdrawal_id}"]);

                    $msg = "Withdrawal request #{$withdrawal_id} REJECTED and funds refunded back to member's wallet.";
                }

                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Processing failed: " . $e->getMessage();
            }
        }
    }
}

// Fetch Withdrawal Requests
$status_filter = trim($_GET['status'] ?? 'Pending');
$query = "SELECT w.*, m.name, m.email, m.phone FROM withdrawals w LEFT JOIN members m ON w.member_id = m.member_id WHERE 1=1";
$params = [];

if (!empty($status_filter) && $status_filter !== 'All') {
    $query .= " AND w.status = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY w.request_date DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$withdrawals = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white"><i class="fas fa-university text-gold mr-2"></i> Payout Withdrawal Requests</h1>
            <p class="text-xs text-gray-400 mt-1">Review, approve, or reject member withdrawal requests with automated wallet refund handling.</p>
        </div>
        <a href="/admin/index.php" class="text-xs text-gold border border-gold/40 px-3 py-1.5 rounded-lg hover:bg-gold/10 self-start md:self-auto">← Admin Overview</a>
    </div>

    <?php if (!empty($msg)): ?>
        <div class="mb-6 p-4 rounded-xl bg-green-900/30 border border-green-500/50 text-green-300 text-sm flex items-center">
            <i class="fas fa-check-circle text-green-400 text-lg mr-3"></i>
            <span><?php echo htmlspecialchars($msg); ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="mb-6 p-4 rounded-xl bg-red-900/30 border border-red-500/50 text-red-300 text-sm flex items-center">
            <i class="fas fa-exclamation-circle text-red-400 text-lg mr-3"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <!-- Status Filter Buttons -->
    <div class="bg-darkcard p-4 rounded-2xl gold-border-glow mb-8 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center space-x-2 text-xs">
            <span class="text-gray-400 font-bold uppercase mr-2">Filter Requests:</span>
            <a href="/admin/wallet.php?status=Pending" class="px-4 py-2 rounded-xl <?php echo $status_filter === 'Pending' ? 'bg-amber-500 text-darkbg font-bold' : 'bg-darkbg text-amber-400 border border-amber-500/30'; ?>">Pending Approval</a>
            <a href="/admin/wallet.php?status=Approved" class="px-4 py-2 rounded-xl <?php echo $status_filter === 'Approved' ? 'bg-green-500 text-white font-bold' : 'bg-darkbg text-green-400 border border-green-500/30'; ?>">Approved</a>
            <a href="/admin/wallet.php?status=Rejected" class="px-4 py-2 rounded-xl <?php echo $status_filter === 'Rejected' ? 'bg-red-500 text-white font-bold' : 'bg-darkbg text-red-400 border border-red-500/30'; ?>">Rejected</a>
            <a href="/admin/wallet.php?status=All" class="px-4 py-2 rounded-xl <?php echo $status_filter === 'All' ? 'bg-gold text-darkbg font-bold' : 'bg-darkbg text-gold border border-gold/30'; ?>">All Requests</a>
        </div>
    </div>

    <!-- Requests Table -->
    <div class="bg-darkcard p-6 rounded-2xl gold-border-glow">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-300">
                <thead class="bg-gold/10 text-gold uppercase border-b border-gold/20">
                    <tr>
                        <th class="p-3">Req #</th>
                        <th class="p-3">Member Details</th>
                        <th class="p-3">Requested Amount</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Request Date</th>
                        <th class="p-3">Processed Date</th>
                        <th class="p-3 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gold/10">
                    <?php if (empty($withdrawals)): ?>
                        <tr>
                            <td colspan="7" class="p-4 text-center text-gray-500">No withdrawal requests found for selected filter status.</td>
                        </tr>
                    <?php else: foreach ($withdrawals as $w): ?>
                        <tr class="hover:bg-gold/5 transition">
                            <td class="p-3 font-mono font-bold text-gold">#<?php echo $w['id']; ?></td>
                            <td class="p-3">
                                <div class="font-bold text-white"><?php echo htmlspecialchars($w['name'] ?: 'Unknown'); ?></div>
                                <div class="text-gold font-mono text-[11px]"><?php echo htmlspecialchars($w['member_id']); ?></div>
                                <div class="text-gray-400 text-[10px]"><?php echo htmlspecialchars($w['phone']); ?></div>
                            </td>
                            <td class="p-3 font-extrabold text-sm text-green-400">₹<?php echo number_format($w['amount'], 2); ?></td>
                            <td class="p-3">
                                <?php if ($w['status'] === 'Approved'): ?>
                                    <span class="px-2.5 py-1 rounded bg-green-500/20 text-green-400 font-bold"><i class="fas fa-check-circle mr-1"></i> Approved</span>
                                <?php elseif ($w['status'] === 'Rejected'): ?>
                                    <span class="px-2.5 py-1 rounded bg-red-500/20 text-red-400 font-bold"><i class="fas fa-times-circle mr-1"></i> Rejected</span>
                                <?php else: ?>
                                    <span class="px-2.5 py-1 rounded bg-amber-500/20 text-amber-400 font-bold"><i class="fas fa-clock mr-1"></i> Pending</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3 font-mono text-gray-400"><?php echo date('d M Y, H:i', strtotime($w['request_date'])); ?></td>
                            <td class="p-3 font-mono text-gray-400"><?php echo $w['processed_date'] ? date('d M Y, H:i', strtotime($w['processed_date'])) : '-'; ?></td>
                            <td class="p-3 text-center">
                                <?php if ($w['status'] === 'Pending'): ?>
                                    <div class="flex items-center justify-center space-x-2">
                                        <form method="POST" action="" onsubmit="return confirm('Approve withdrawal request #<?php echo $w['id']; ?> for ₹<?php echo $w['amount']; ?>?');">
                                            <input type="hidden" name="withdrawal_id" value="<?php echo $w['id']; ?>">
                                            <input type="hidden" name="action_type" value="Approve">
                                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-green-600 hover:bg-green-500 text-white font-bold text-[11px] flex items-center space-x-1 shadow">
                                                <i class="fas fa-check"></i>
                                                <span>Approve</span>
                                            </button>
                                        </form>

                                        <form method="POST" action="" onsubmit="return confirm('Reject withdrawal request #<?php echo $w['id']; ?> and refund funds back to member?');">
                                            <input type="hidden" name="withdrawal_id" value="<?php echo $w['id']; ?>">
                                            <input type="hidden" name="action_type" value="Reject">
                                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-red-600 hover:bg-red-500 text-white font-bold text-[11px] flex items-center space-x-1 shadow">
                                                <i class="fas fa-times"></i>
                                                <span>Reject</span>
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span class="text-gray-500 text-[11px] italic">Completed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
