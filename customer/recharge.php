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
$completed_cycles = 0;
$current_cycle = 1;
$remaining_cycles = 6;
$upcoming_2_weeks = [];

if ($subscription) {
    $stmt = $pdo->prepare("SELECT * FROM recharge_schedules WHERE subscription_id = ? ORDER BY service_type ASC, term_number ASC");
    $stmt->execute([$subscription['id']]);
    $schedules = $stmt->fetchAll();

    // Calculate completed cycles, current installment, and remaining installments
    // A cycle/term is completed when all service types for that term number are Completed
    $terms_status = [];
    foreach ($schedules as $s) {
        $term_num = (int)$s['term_number'];
        if (!isset($terms_status[$term_num])) {
            $terms_status[$term_num] = ['total' => 0, 'completed' => 0];
        }
        $terms_status[$term_num]['total']++;
        if ($s['status'] === 'Completed') {
            $terms_status[$term_num]['completed']++;
        }
    }

    $completed_cycles = 0;
    foreach ($terms_status as $t_num => $t_info) {
        if ($t_info['total'] > 0 && $t_info['completed'] === $t_info['total']) {
            $completed_cycles++;
        }
    }

    $current_cycle = min(6, $completed_cycles + 1);
    $remaining_cycles = max(0, 6 - $completed_cycles);

    // Fetch upcoming mobile recharges due within 14 days (2 weeks)
    $today = date('Y-m-d H:i:s');
    $two_weeks = date('Y-m-d H:i:s', strtotime('+14 days'));

    foreach ($schedules as $s) {
        if (in_array($s['service_type'], ['Mobile_1', 'Mobile_2']) && $s['status'] === 'Scheduled' && !empty($s['due_date'])) {
            if ($s['due_date'] <= $two_weeks) {
                $upcoming_2_weeks[] = $s;
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white"><i class="fas fa-bolt text-gold mr-2"></i> My Utility Package Subscriptions</h1>
            <p class="text-xs text-gray-400 mt-1">Track 6-term Mobile Recharges (every 28 days cycle) and Gas Refill installments starting 24 hours post-registration.</p>
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
            <h2 class="text-xl font-bold text-white">No Active Utility Package</h2>
            <p class="text-xs text-gray-400 leading-relaxed">
                You are currently on the <span class="text-gold font-bold"><?php echo str_replace('_', ' ₹', $member['package_type']); ?></span> package.
                Givora Traders offers standalone utility packages for Mobile Recharges (₹1,200), Gas Refills (₹3,000), and Utility Combo (₹5,400).
            </p>
            <div class="pt-2">
                <a href="/register.php" class="btn-gold px-6 py-2.5 rounded-xl font-bold text-xs inline-block">
                    Register Utility Account
                </a>
            </div>
        </div>
    <?php else: ?>

        <!-- Installments & Cycle Summary KPI Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-darkcard p-5 rounded-2xl gold-border-glow border-l-4 border-l-gold">
                <span class="text-xs font-semibold text-gray-400 uppercase">Total Package Terms</span>
                <div class="text-2xl font-extrabold text-white mt-1">6 Terms</div>
                <p class="text-[10px] text-gold/80 mt-1">Full Package Term Span</p>
            </div>

            <div class="bg-darkcard p-5 rounded-2xl gold-border-glow border-l-4 border-l-green-500">
                <span class="text-xs font-semibold text-green-400 uppercase">Completed Installments</span>
                <div class="text-2xl font-extrabold text-green-400 mt-1"><?php echo $completed_cycles; ?> / 6</div>
                <p class="text-[10px] text-green-500/80 mt-1">Fulfilled Cycles</p>
            </div>

            <div class="bg-darkcard p-5 rounded-2xl gold-border-glow border-l-4 border-l-amber-500">
                <span class="text-xs font-semibold text-amber-400 uppercase">Current Installment</span>
                <div class="text-2xl font-extrabold text-amber-300 mt-1">Term #<?php echo $current_cycle; ?></div>
                <p class="text-[10px] text-amber-400/80 mt-1">Active Cycle in Progress</p>
            </div>

            <div class="bg-darkcard p-5 rounded-2xl gold-border-glow border-l-4 border-l-purple-500">
                <span class="text-xs font-semibold text-purple-400 uppercase">Remaining Installments</span>
                <div class="text-2xl font-extrabold text-purple-300 mt-1"><?php echo $remaining_cycles; ?> Terms</div>
                <p class="text-[10px] text-purple-400/80 mt-1">Pending Future Cycles</p>
            </div>
        </div>

        <!-- UPCOMING RECHARGES (DUE WITHIN NEXT 14 DAYS) BANNER -->
        <div class="bg-gradient-to-r from-amber-950/40 via-darkcard to-gold/20 border border-gold/40 p-6 rounded-2xl gold-border-glow mb-8">
            <div class="flex items-center justify-between border-b border-gold/20 pb-3 mb-4">
                <h3 class="text-base font-bold text-white flex items-center">
                    <i class="fas fa-clock text-amber-400 mr-2 text-lg"></i>
                    Upcoming Mobile Recharges (Due in Next 14 Days / 28-Day Cycle)
                </h3>
                <span class="text-xs bg-amber-500/20 text-amber-300 font-bold px-3 py-1 rounded-full border border-amber-500/30">
                    <?php echo count($upcoming_2_weeks); ?> Recharge(s) Due
                </span>
            </div>

            <?php if (empty($upcoming_2_weeks)): ?>
                <p class="text-xs text-gray-400 flex items-center">
                    <i class="fas fa-check-circle text-green-400 mr-2"></i>
                    No mobile recharges due in the next 14 days. Your upcoming 28-day cycle dates are listed below.
                </p>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($upcoming_2_weeks as $up):
                        $target_num = ($up['service_type'] === 'Mobile_1') ? $subscription['mobile_1'] . ' (' . $subscription['operator_1'] . ')' : $subscription['mobile_2'] . ' (' . $subscription['operator_2'] . ')';
                        $due_time = strtotime($up['due_date']);
                        $days_left = ceil(($due_time - time()) / 86400);
                    ?>
                        <div class="bg-darkbg p-4 rounded-xl border border-amber-500/30 flex items-center justify-between">
                            <div>
                                <div class="text-xs font-bold text-gold">Term #<?php echo $up['term_number']; ?> - <?php echo str_replace('_', ' ', $up['service_type']); ?></div>
                                <div class="text-sm font-mono font-bold text-white mt-0.5"><?php echo htmlspecialchars($target_num); ?></div>
                                <div class="text-[11px] text-gray-400 mt-1"><i class="fas fa-calendar-day text-amber-400 mr-1"></i> Due Date: <?php echo date('d M Y, h:i A', $due_time); ?></div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <span class="px-2.5 py-1 bg-amber-500/20 text-amber-300 font-extrabold text-xs rounded-lg border border-amber-500/40 inline-block">
                                    <?php echo ($days_left <= 0) ? 'DUE TODAY' : "In {$days_left} Days"; ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

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
                <?php if (!empty($subscription['mobile_1'])): ?>
                    <!-- Mobile 1 -->
                    <div class="bg-darkbg p-4 rounded-xl border border-gold/15 space-y-2">
                        <div class="flex items-center justify-between text-gold font-bold">
                            <span><i class="fas fa-mobile-alt mr-1"></i> Mobile Connection 1</span>
                            <span class="px-2 py-0.5 bg-gold/10 rounded text-[10px]"><?php echo htmlspecialchars($subscription['operator_1']); ?></span>
                        </div>
                        <div class="text-white font-mono text-base font-extrabold tracking-wider"><?php echo htmlspecialchars($subscription['mobile_1']); ?></div>
                        <div class="text-[11px] text-gray-400">6 Terms scheduled every 28 days</div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($subscription['mobile_2'])): ?>
                    <!-- Mobile 2 -->
                    <div class="bg-darkbg p-4 rounded-xl border border-gold/15 space-y-2">
                        <div class="flex items-center justify-between text-gold font-bold">
                            <span><i class="fas fa-mobile-alt mr-1"></i> Mobile Connection 2</span>
                            <span class="px-2 py-0.5 bg-gold/10 rounded text-[10px]"><?php echo htmlspecialchars($subscription['operator_2']); ?></span>
                        </div>
                        <div class="text-white font-mono text-base font-extrabold tracking-wider"><?php echo htmlspecialchars($subscription['mobile_2']); ?></div>
                        <div class="text-[11px] text-gray-400">6 Terms scheduled every 28 days</div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($subscription['gas_consumer_number'])): ?>
                    <!-- Indian Gas Connection -->
                    <div class="bg-darkbg p-4 rounded-xl border border-gold/15 space-y-2">
                        <div class="flex items-center justify-between text-gold font-bold">
                            <span><i class="fas fa-fire mr-1"></i> Indian Gas Connection</span>
                            <span class="px-2 py-0.5 bg-gold/10 rounded text-[10px]"><?php echo htmlspecialchars($subscription['gas_provider']); ?></span>
                        </div>
                        <div class="text-white font-mono font-bold text-sm"><?php echo htmlspecialchars($subscription['gas_consumer_number']); ?></div>
                        <div class="text-[11px] text-gray-400 truncate">Name: <?php echo htmlspecialchars($subscription['gas_customer_name']); ?></div>
                    </div>
                <?php endif; ?>
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
