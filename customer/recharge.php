<?php
require_once __DIR__ . '/../includes/functions.php';
checkMemberLogin();

$page_title = "My Recharge Bundle";
$pdo = getDBConnection();
$member = getLoggedInMember();

$msg = '';
$error = '';

// Handle Gas Refill Request action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_gas') {
    $schedule_id = (int)($_POST['schedule_id'] ?? 0);

    // Verify schedule belongs to member's subscription
    $stmt = $pdo->prepare("SELECT rs.*, sub.member_id FROM recharge_schedules rs JOIN recharge_subscriptions sub ON rs.subscription_id = sub.id WHERE rs.id = ? AND sub.member_id = ? AND rs.service_type = 'Gas'");
    $stmt->execute([$schedule_id, $member['member_id']]);
    $schedule = $stmt->fetch();

    if (!$schedule) {
        $error = "Invalid gas schedule term selection.";
    } elseif ($schedule['status'] !== 'Scheduled') {
        $error = "This gas refill term has already been requested or completed.";
    } else {
        $stmt = $pdo->prepare("UPDATE recharge_schedules SET status = 'Requested', due_date = NOW() WHERE id = ?");
        $stmt->execute([$schedule_id]);
        $msg = "Gas Refill request for Term #{$schedule['term_number']} submitted successfully! Company admin will process it shortly.";
    }
}

// Fetch member's recharge subscription
$stmt = $pdo->prepare("SELECT * FROM recharge_subscriptions WHERE member_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$member['member_id']]);
$subscription = $stmt->fetch();

$schedules = [];
if ($subscription) {
    $stmt = $pdo->prepare("SELECT * FROM recharge_schedules WHERE subscription_id = ? ORDER BY service_type ASC, term_number ASC");
    $stmt->execute([$subscription['id']]);
    $schedules = $stmt->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white"><i class="fas fa-bolt text-gold mr-2"></i> My Recharge Bundle (₹5,400)</h1>
            <p class="text-xs text-gray-400 mt-1">6-term Mobile Recharges (every 28 days) and Gas Refill requests starting 24 hours after plan activation.</p>
        </div>
        <a href="/customer/dashboard.php" class="text-xs text-gold border border-gold/40 px-3 py-1.5 rounded-lg hover:bg-gold/10 self-start sm:self-auto">← Dashboard</a>
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

    <?php if (!$subscription): ?>
        <div class="bg-darkcard p-8 rounded-2xl gold-border-glow text-center space-y-4 max-w-2xl mx-auto">
            <div class="w-16 h-16 bg-gold/10 text-gold rounded-full flex items-center justify-center mx-auto text-3xl border border-gold/30">
                <i class="fas fa-bolt"></i>
            </div>
            <h2 class="text-xl font-bold text-white">No Active Recharge Bundle Package</h2>
            <p class="text-xs text-gray-400 leading-relaxed">
                You are currently on the <span class="text-gold font-bold"><?php echo str_replace('_', ' ₹', $member['package_type']); ?></span> package.
                The Recharge Bundle (₹5,400) is a dedicated utility package offering 6 terms of 2 mobile recharges and gas booking refill services.
            </p>
            <div class="pt-2">
                <a href="/register.php?package=Recharge_Bundle_5400" class="btn-gold px-6 py-2.5 rounded-xl font-bold text-xs inline-block">
                    Register Recharge Bundle Account
                </a>
            </div>
        </div>
    <?php else: ?>

        <!-- Subscription Overview Header Card -->
        <div class="bg-darkcard p-6 rounded-2xl gold-border-glow mb-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center border-b border-gold/10 pb-4 mb-6 gap-4">
                <div>
                    <div class="flex items-center space-x-3">
                        <span class="px-3 py-1 bg-gold/20 text-gold font-bold text-xs rounded-full uppercase border border-gold/40">Active Subscription</span>
                        <span class="text-xs text-gray-400 font-mono">ePIN: <?php echo htmlspecialchars($subscription['used_epin']); ?></span>
                    </div>
                    <h2 class="text-lg font-bold text-white mt-1">Plan Start Date: <span class="text-goldlight"><?php echo date('d M Y, h:i A', strtotime($subscription['start_date'])); ?></span></h2>
                    <p class="text-xs text-gray-400 mt-0.5">Plan starts 24 hours after registration. Recharges recur every 28 days for 6 terms.</p>
                </div>
            </div>

            <!-- Connections Details Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-xs">
                <!-- Mobile 1 -->
                <div class="bg-darkbg p-4 rounded-xl border border-gold/15 space-y-2">
                    <div class="flex items-center justify-between text-gold font-bold">
                        <span><i class="fas fa-mobile-alt mr-1"></i> Mobile Connection 1</span>
                        <span class="px-2 py-0.5 bg-gold/10 rounded text-[10px]"><?php echo htmlspecialchars($subscription['operator_1']); ?></span>
                    </div>
                    <div class="text-white font-mono text-base font-extrabold tracking-wider"><?php echo htmlspecialchars($subscription['mobile_1']); ?></div>
                    <div class="text-[11px] text-gray-400">6 Terms scheduled every 28 days</div>
                </div>

                <!-- Mobile 2 -->
                <div class="bg-darkbg p-4 rounded-xl border border-gold/15 space-y-2">
                    <div class="flex items-center justify-between text-gold font-bold">
                        <span><i class="fas fa-mobile-alt mr-1"></i> Mobile Connection 2</span>
                        <span class="px-2 py-0.5 bg-gold/10 rounded text-[10px]"><?php echo htmlspecialchars($subscription['operator_2']); ?></span>
                    </div>
                    <div class="text-white font-mono text-base font-extrabold tracking-wider"><?php echo htmlspecialchars($subscription['mobile_2']); ?></div>
                    <div class="text-[11px] text-gray-400">6 Terms scheduled every 28 days</div>
                </div>

                <!-- Indian Gas Connection -->
                <div class="bg-darkbg p-4 rounded-xl border border-gold/15 space-y-2">
                    <div class="flex items-center justify-between text-gold font-bold">
                        <span><i class="fas fa-fire mr-1"></i> Indian Gas Connection</span>
                        <span class="px-2 py-0.5 bg-gold/10 rounded text-[10px]"><?php echo htmlspecialchars($subscription['gas_provider']); ?></span>
                    </div>
                    <div class="text-white font-mono font-bold text-sm"><?php echo htmlspecialchars($subscription['gas_consumer_number']); ?></div>
                    <div class="text-[11px] text-gray-400 truncate">Name: <?php echo htmlspecialchars($subscription['gas_customer_name']); ?></div>
                </div>
            </div>
        </div>

        <!-- 6-Term Mobile Recharges Schedule -->
        <div class="bg-darkcard p-6 rounded-2xl gold-border-glow mb-8">
            <h2 class="text-lg font-bold text-white mb-4"><i class="fas fa-calendar-alt text-gold mr-2"></i> Mobile Recharge Schedule (6 Terms - 28 Days Cycle)</h2>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-gray-300">
                    <thead class="bg-gold/10 text-gold uppercase border-b border-gold/20">
                        <tr>
                            <th class="p-3">Term #</th>
                            <th class="p-3">Service</th>
                            <th class="p-3">Target Number</th>
                            <th class="p-3">Due Date</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Completed Date</th>
                            <th class="p-3">Ref/Transaction ID</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gold/10">
                        <?php
                        $mobile_schedules = array_filter($schedules, fn($s) => in_array($s['service_type'], ['Mobile_1', 'Mobile_2']));
                        foreach ($mobile_schedules as $sched):
                            $target_num = ($sched['service_type'] === 'Mobile_1') ? $subscription['mobile_1'] . ' (' . $subscription['operator_1'] . ')' : $subscription['mobile_2'] . ' (' . $subscription['operator_2'] . ')';
                        ?>
                            <tr class="hover:bg-gold/5 transition">
                                <td class="p-3 font-bold text-white">Term #<?php echo $sched['term_number']; ?></td>
                                <td class="p-3 font-semibold text-gold"><?php echo str_replace('_', ' ', $sched['service_type']); ?></td>
                                <td class="p-3 font-mono font-bold text-white"><?php echo htmlspecialchars($target_num); ?></td>
                                <td class="p-3 font-mono text-gray-300"><?php echo date('d M Y, h:i A', strtotime($sched['due_date'])); ?></td>
                                <td class="p-3">
                                    <?php if ($sched['status'] === 'Completed'): ?>
                                        <span class="px-2 py-0.5 rounded bg-green-500/20 text-green-400 font-bold">Completed</span>
                                    <?php elseif ($sched['status'] === 'Failed'): ?>
                                        <span class="px-2 py-0.5 rounded bg-red-500/20 text-red-400 font-bold">Failed</span>
                                    <?php else: ?>
                                        <span class="px-2 py-0.5 rounded bg-yellow-500/20 text-yellow-400 font-bold">Scheduled</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3 font-mono text-gray-400"><?php echo $sched['completed_at'] ? date('d M Y, H:i', strtotime($sched['completed_at'])) : '-'; ?></td>
                                <td class="p-3 font-mono text-goldlight font-bold"><?php echo htmlspecialchars($sched['reference_number'] ?: '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 6-Term Indian Gas Refills Section -->
        <div class="bg-darkcard p-6 rounded-2xl gold-border-glow">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-2">
                <div>
                    <h2 class="text-lg font-bold text-white"><i class="fas fa-fire text-gold mr-2"></i> Indian Gas Booking Refills (6 Terms)</h2>
                    <p class="text-xs text-gray-400">Request your gas refill when required. 6 total refill bookings included.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <?php
                $gas_schedules = array_filter($schedules, fn($s) => $s['service_type'] === 'Gas');
                foreach ($gas_schedules as $gas):
                ?>
                    <div class="bg-darkbg p-5 rounded-xl border border-gold/20 flex flex-col justify-between space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-bold text-white">Gas Term #<?php echo $gas['term_number']; ?></span>
                            <?php if ($gas['status'] === 'Completed'): ?>
                                <span class="px-2 py-0.5 bg-green-500/20 text-green-400 font-bold text-[10px] rounded">Completed</span>
                            <?php elseif ($gas['status'] === 'Requested'): ?>
                                <span class="px-2 py-0.5 bg-blue-500/20 text-blue-400 font-bold text-[10px] rounded">Requested</span>
                            <?php else: ?>
                                <span class="px-2 py-0.5 bg-gold/20 text-gold font-bold text-[10px] rounded">Available</span>
                            <?php endif; ?>
                        </div>

                        <div class="text-xs text-gray-400 space-y-1">
                            <div>Provider: <span class="text-white font-semibold"><?php echo htmlspecialchars($subscription['gas_provider']); ?></span></div>
                            <div>Consumer #: <span class="text-white font-mono"><?php echo htmlspecialchars($subscription['gas_consumer_number']); ?></span></div>
                            <?php if ($gas['completed_at']): ?>
                                <div class="text-green-400 font-mono">Ref #: <?php echo htmlspecialchars($gas['reference_number']); ?></div>
                            <?php endif; ?>
                        </div>

                        <div>
                            <?php if ($gas['status'] === 'Scheduled'): ?>
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="request_gas">
                                    <input type="hidden" name="schedule_id" value="<?php echo $gas['id']; ?>">
                                    <button type="submit" onclick="return confirm('Request Gas Refill for Term #<?php echo $gas['term_number']; ?>?');" class="w-full btn-gold py-2 rounded-lg font-bold text-xs flex items-center justify-center space-x-1">
                                        <i class="fas fa-gas-pump"></i>
                                        <span>Request Gas Refill</span>
                                    </button>
                                </form>
                            <?php elseif ($gas['status'] === 'Requested'): ?>
                                <button disabled class="w-full bg-blue-900/40 text-blue-300 border border-blue-500/30 py-2 rounded-lg font-bold text-xs cursor-not-allowed">
                                    Processing Request...
                                </button>
                            <?php else: ?>
                                <button disabled class="w-full bg-green-900/40 text-green-300 border border-green-500/30 py-2 rounded-lg font-bold text-xs cursor-not-allowed">
                                    ✓ Refill Completed
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
