<?php
require_once __DIR__ . '/../includes/functions.php';
checkMemberLogin();

$member = getLoggedInMember();
$page_title = "My Wallet & Withdrawal Requests";

$pdo = getDBConnection();
ensureWalletExists($pdo, $member['member_id']);

$error = '';
$success = '';

// Handle Withdrawal Request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount'] ?? 0);

    // Fetch current user wallet balance
    $stmt = $pdo->prepare("SELECT user_wallet_60 FROM wallets WHERE member_id = ?");
    $stmt->execute([$member['member_id']]);
    $current_user_wallet = floatval($stmt->fetchColumn() ?: 0);

    if ($amount < 500) {
        $error = "Minimum withdrawal request amount is ₹500.";
    } elseif ($amount > $current_user_wallet) {
        $error = "Insufficient User Wallet balance. Available: ₹" . number_format($current_user_wallet, 2);
    } else {
        try {
            $pdo->beginTransaction();

            // Insert into withdrawals table
            $stmt = $pdo->prepare("INSERT INTO withdrawals (member_id, amount, status) VALUES (?, ?, 'Pending')");
            $stmt->execute([$member['member_id'], $amount]);

            // Deduct from user_wallet_60
            $stmt = $pdo->prepare("UPDATE wallets SET user_wallet_60 = user_wallet_60 - ? WHERE member_id = ?");
            $stmt->execute([$amount, $member['member_id']]);

            // Log debit transaction
            $stmt = $pdo->prepare("INSERT INTO transactions (member_id, type, amount, wallet_type, status, description) VALUES (?, 'Withdrawal_Request', ?, 'User_Wallet', 'Pending', ?)");
            $stmt->execute([$member['member_id'], $amount, "Withdrawal Request submitted for approval"]);

            $pdo->commit();
            $success = "Withdrawal request of ₹" . number_format($amount, 2) . " submitted successfully!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Withdrawal request failed: " . $e->getMessage();
        }
    }
}

// Fetch Wallet Details
$stmt = $pdo->prepare("SELECT * FROM wallets WHERE member_id = ?");
$stmt->execute([$member['member_id']]);
$wallet = $stmt->fetch();

// Fetch Withdrawal History
$stmt = $pdo->prepare("SELECT * FROM withdrawals WHERE member_id = ? ORDER BY request_date DESC");
$stmt->execute([$member['member_id']]);
$withdrawals = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-white"><i class="fas fa-wallet text-gold mr-2"></i> My Wallet & Financial Statements</h1>
            <p class="text-xs text-gray-400 mt-1">Real-time breakdown of User Wallet (60%) and Company Wallet (40%) allocations.</p>
        </div>
        <a href="/customer/dashboard.php" class="text-xs text-gold border border-gold/40 px-3 py-1.5 rounded-lg hover:bg-gold/10">← Dashboard</a>
    </div>

    <!-- Wallet Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Lifetime Total Balance -->
        <div class="bg-darkcard p-6 rounded-2xl gold-border-glow">
            <span class="text-xs uppercase tracking-wider text-gray-400 font-bold">Total Lifetime Inflow</span>
            <div class="text-3xl font-extrabold gold-gradient-text mt-2">₹<?php echo number_format($wallet['balance'], 2); ?></div>
            <p class="text-xs text-gray-500 mt-2">Cumulative gross income received</p>
        </div>

        <!-- User Wallet (60%) -->
        <div class="bg-darkcard p-6 rounded-2xl gold-border-glow border-l-4 border-l-green-500">
            <span class="text-xs uppercase tracking-wider text-green-400 font-bold">User Wallet (60% Withdrawable)</span>
            <div class="text-3xl font-extrabold text-green-400 mt-2">₹<?php echo number_format($wallet['user_wallet_60'], 2); ?></div>
            <p class="text-xs text-green-500/80 mt-2">Eligible for instant withdrawal request (Min ₹500)</p>
        </div>

        <!-- Company Wallet (40%) -->
        <div class="bg-darkcard p-6 rounded-2xl gold-border-glow border-l-4 border-l-amber-500">
            <span class="text-xs uppercase tracking-wider text-amber-400 font-bold">Company Wallet (40% Reserve)</span>
            <div class="text-3xl font-extrabold text-amber-400 mt-2">₹<?php echo number_format($wallet['company_wallet_40'], 2); ?></div>
            <p class="text-xs text-amber-500/80 mt-2">Company reinvestment & product liquidity fund</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Withdrawal Request Form -->
        <div class="bg-darkcard p-6 rounded-2xl gold-border-glow">
            <h2 class="text-lg font-bold text-white mb-4 border-b border-gold/10 pb-2"><i class="fas fa-hand-holding-usd text-gold mr-2"></i> Request Payout / Withdrawal</h2>

            <?php if (!empty($success)): ?>
                <div class="mb-4 p-3 rounded-xl bg-green-900/30 border border-green-500/50 text-green-300 text-xs">
                    <i class="fas fa-check-circle mr-1"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="mb-4 p-3 rounded-xl bg-red-900/30 border border-red-500/50 text-red-300 text-xs">
                    <i class="fas fa-exclamation-circle mr-1"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-300 mb-2">Available Withdrawable Balance</label>
                    <div class="text-xl font-bold text-green-400 bg-darkbg p-3 rounded-xl border border-gold/20 font-mono">
                        ₹<?php echo number_format($wallet['user_wallet_60'], 2); ?>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-300 mb-2">Withdrawal Amount (₹) *</label>
                    <input type="number" step="0.01" min="500" name="amount" required placeholder="Minimum 500" class="w-full bg-darkbg border border-gold/30 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-gold font-mono text-lg font-bold">
                    <p class="text-[11px] text-gray-400 mt-1"><i class="fas fa-info-circle text-gold"></i> Minimum threshold requirement is ₹500.</p>
                </div>

                <button type="submit" class="w-full btn-gold py-3.5 rounded-xl font-bold text-sm shadow-lg flex items-center justify-center space-x-2">
                    <i class="fas fa-paper-plane"></i>
                    <span>Submit Payout Request</span>
                </button>
            </form>
        </div>

        <!-- Withdrawal Requests History Table -->
        <div class="lg:col-span-2 bg-darkcard p-6 rounded-2xl gold-border-glow">
            <h2 class="text-lg font-bold text-white mb-4 border-b border-gold/10 pb-2"><i class="fas fa-history text-gold mr-2"></i> Payout Request History</h2>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-gray-300">
                    <thead class="bg-gold/10 text-gold uppercase border-b border-gold/20">
                        <tr>
                            <th class="p-3">Req ID</th>
                            <th class="p-3">Amount</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Request Date</th>
                            <th class="p-3">Processed Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gold/10">
                        <?php if (empty($withdrawals)): ?>
                            <tr>
                                <td colspan="5" class="p-4 text-center text-gray-500">No withdrawal requests submitted yet.</td>
                            </tr>
                        <?php else: foreach ($withdrawals as $w): ?>
                            <tr>
                                <td class="p-3 font-mono text-gold">#<?php echo $w['id']; ?></td>
                                <td class="p-3 font-bold text-white">₹<?php echo number_format($w['amount'], 2); ?></td>
                                <td class="p-3">
                                    <?php if ($w['status'] === 'Approved'): ?>
                                        <span class="px-2 py-0.5 rounded bg-green-500/20 text-green-400 font-bold"><i class="fas fa-check mr-1"></i> Approved</span>
                                    <?php elseif ($w['status'] === 'Rejected'): ?>
                                        <span class="px-2 py-0.5 rounded bg-red-500/20 text-red-400 font-bold"><i class="fas fa-times mr-1"></i> Rejected</span>
                                    <?php else: ?>
                                        <span class="px-2 py-0.5 rounded bg-amber-500/20 text-amber-400 font-bold"><i class="fas fa-clock mr-1"></i> Pending Approval</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3 font-mono text-gray-400"><?php echo date('d M Y, H:i', strtotime($w['request_date'])); ?></td>
                                <td class="p-3 font-mono text-gray-400"><?php echo $w['processed_date'] ? date('d M Y, H:i', strtotime($w['processed_date'])) : '-'; ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
