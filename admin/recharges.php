<?php
require_once __DIR__ . '/../includes/functions.php';
checkAdminLogin();

$page_title = "Recharge Subscriptions Management";
$pdo = getDBConnection();

$msg = '';
$error = '';

// Handle Process Schedule Action (Mark as Completed or Failed)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_schedule') {
    $schedule_id = (int)($_POST['schedule_id'] ?? 0);
    $status = trim($_POST['status'] ?? 'Completed');
    $reference_number = trim($_POST['reference_number'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (!in_array($status, ['Completed', 'Failed'])) {
        $error = "Invalid status selected.";
    } elseif ($schedule_id <= 0) {
        $error = "Invalid schedule selection.";
    } else {
        $stmt = $pdo->prepare("UPDATE recharge_schedules SET status = ?, reference_number = ?, notes = ?, completed_at = NOW() WHERE id = ?");
        if ($stmt->execute([$status, $reference_number, $notes, $schedule_id])) {
            $msg = "Recharge schedule record #{$schedule_id} updated to {$status} successfully!";
        } else {
            $error = "Failed to update recharge schedule record.";
        }
    }
}

// Fetch all subscriptions with member details and calculate completed cycle metrics
$filter_service = trim($_GET['service'] ?? '');
$filter_status = trim($_GET['status'] ?? '');

$query = "SELECT sub.*, m.name as member_name, m.phone as member_phone, m.email as member_email
          FROM recharge_subscriptions sub
          JOIN members m ON sub.member_id = m.member_id
          ORDER BY sub.id DESC";
$subscriptions = $pdo->query($query)->fetchAll();

// Calculate completed/remaining cycles for each subscription
foreach ($subscriptions as &$sub) {
    $sub_id = $sub['id'];
    $stmt_sch = $pdo->prepare("SELECT term_number, status FROM recharge_schedules WHERE subscription_id = ?");
    $stmt_sch->execute([$sub_id]);
    $sub_schs = $stmt_sch->fetchAll(PDO::FETCH_ASSOC);

    $terms = [];
    foreach ($sub_schs as $sc) {
        $tn = (int)$sc['term_number'];
        if (!isset($terms[$tn])) $terms[$tn] = ['total' => 0, 'completed' => 0];
        $terms[$tn]['total']++;
        if ($sc['status'] === 'Completed') $terms[$tn]['completed']++;
    }

    $comp_cycles = 0;
    foreach ($terms as $tn => $ti) {
        if ($ti['total'] > 0 && $ti['completed'] === $ti['total']) $comp_cycles++;
    }

    $sub['completed_cycles'] = $comp_cycles;
    $sub['current_cycle'] = min(6, $comp_cycles + 1);
    $sub['remaining_cycles'] = max(0, 6 - $comp_cycles);
}
unset($sub);

// Fetch schedules with filtering
$sched_query = "SELECT rs.*, sub.member_id, sub.mobile_1, sub.operator_1, sub.mobile_2, sub.operator_2, sub.gas_provider, sub.gas_consumer_number, sub.gas_customer_name, m.name as member_name
                FROM recharge_schedules rs
                JOIN recharge_subscriptions sub ON rs.subscription_id = sub.id
                JOIN members m ON sub.member_id = m.member_id
                WHERE 1=1";
$params = [];

if ($filter_status === 'Upcoming_14') {
    $two_weeks = date('Y-m-d H:i:s', strtotime('+14 days'));
    $sched_query .= " AND rs.service_type IN ('Mobile_1', 'Mobile_2') AND rs.status = 'Scheduled' AND rs.due_date <= ?";
    $params[] = $two_weeks;
} elseif (!empty($filter_status)) {
    $sched_query .= " AND rs.status = ?";
    $params[] = $filter_status;
}

if (!empty($filter_service)) {
    $sched_query .= " AND rs.service_type = ?";
    $params[] = $filter_service;
}

$sched_query .= " ORDER BY CASE WHEN rs.status = 'Requested' THEN 1 WHEN rs.status = 'Scheduled' THEN 2 ELSE 3 END, rs.due_date ASC, rs.id ASC LIMIT 100";

$stmt = $pdo->prepare($sched_query);
$stmt->execute($params);
$schedules = $stmt->fetchAll();

// Calculate count of upcoming recharges in next 14 days
$two_weeks_time = date('Y-m-d H:i:s', strtotime('+14 days'));
$stmt_up_cnt = $pdo->prepare("SELECT COUNT(*) FROM recharge_schedules WHERE service_type IN ('Mobile_1', 'Mobile_2') AND status = 'Scheduled' AND due_date <= ?");
$stmt_up_cnt->execute([$two_weeks_time]);
$upcoming_14_cnt = (int)$stmt_up_cnt->fetchColumn();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white"><i class="fas fa-charging-station text-gold mr-2"></i> Recharge Subscriptions & Orders</h1>
            <p class="text-xs text-gray-400 mt-1">Manage 6-term Mobile Recharges (28-day cycle) and Indian Gas Refill bookings for Utility members.</p>
        </div>
        <a href="/admin/index.php" class="text-xs text-gold border border-gold/40 px-3 py-1.5 rounded-lg hover:bg-gold/10 self-start sm:self-auto">← Admin Overview</a>
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

    <!-- Overview Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-darkcard p-5 rounded-xl border border-gold/20 flex items-center justify-between">
            <div>
                <div class="text-gray-400 text-xs font-semibold uppercase">Total Subscribers</div>
                <div class="text-2xl font-extrabold text-white mt-1"><?php echo count($subscriptions); ?></div>
            </div>
            <div class="w-10 h-10 rounded-full bg-gold/10 text-gold flex items-center justify-center text-lg">
                <i class="fas fa-users"></i>
            </div>
        </div>

        <div class="bg-darkcard p-5 rounded-xl border border-gold/20 flex items-center justify-between">
            <div>
                <div class="text-gray-400 text-xs font-semibold uppercase">Due in 14 Days (2 Weeks)</div>
                <div class="text-2xl font-extrabold text-amber-400 mt-1"><?php echo $upcoming_14_cnt; ?></div>
            </div>
            <div class="w-10 h-10 rounded-full bg-amber-500/10 text-amber-400 flex items-center justify-center text-lg">
                <i class="fas fa-clock"></i>
            </div>
        </div>

        <div class="bg-darkcard p-5 rounded-xl border border-gold/20 flex items-center justify-between">
            <div>
                <div class="text-gray-400 text-xs font-semibold uppercase">Gas Requests Pending</div>
                <div class="text-2xl font-extrabold text-blue-400 mt-1">
                    <?php
                    $pending_gas = $pdo->query("SELECT COUNT(*) FROM recharge_schedules WHERE service_type = 'Gas' AND status = 'Requested'")->fetchColumn();
                    echo $pending_gas;
                    ?>
                </div>
            </div>
            <div class="w-10 h-10 rounded-full bg-blue-500/10 text-blue-400 flex items-center justify-center text-lg">
                <i class="fas fa-gas-pump"></i>
            </div>
        </div>

        <div class="bg-darkcard p-5 rounded-xl border border-gold/20 flex items-center justify-between">
            <div>
                <div class="text-gray-400 text-xs font-semibold uppercase">Total Fulfilled Terms</div>
                <div class="text-2xl font-extrabold text-green-400 mt-1">
                    <?php
                    $completed_cnt = $pdo->query("SELECT COUNT(*) FROM recharge_schedules WHERE status = 'Completed'")->fetchColumn();
                    echo $completed_cnt;
                    ?>
                </div>
            </div>
            <div class="w-10 h-10 rounded-full bg-green-500/10 text-green-400 flex items-center justify-center text-lg">
                <i class="fas fa-check-double"></i>
            </div>
        </div>
    </div>

    <!-- Recharge Orders & Schedules Table -->
    <div class="bg-darkcard p-6 rounded-2xl gold-border-glow mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-4">
            <h2 class="text-lg font-bold text-white"><i class="fas fa-tasks text-gold mr-2"></i> Recharge Schedules & Fulfilment Control</h2>

            <!-- Filters -->
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <span class="text-gray-400">Filter:</span>
                <a href="/admin/recharges.php" class="px-3 py-1 rounded-lg <?php echo empty($filter_status) ? 'bg-gold text-darkbg font-bold' : 'bg-darkbg text-gray-300 border border-gold/20'; ?>">All</a>
                <a href="/admin/recharges.php?status=Upcoming_14" class="px-3 py-1 rounded-lg <?php echo $filter_status === 'Upcoming_14' ? 'bg-amber-500 text-darkbg font-bold' : 'bg-darkbg text-amber-300 border border-amber-500/30'; ?>">Due in 14 Days</a>
                <a href="/admin/recharges.php?status=Requested" class="px-3 py-1 rounded-lg <?php echo $filter_status === 'Requested' ? 'bg-blue-500 text-white font-bold' : 'bg-darkbg text-blue-400 border border-blue-500/20'; ?>">Gas Requested</a>
                <a href="/admin/recharges.php?status=Scheduled" class="px-3 py-1 rounded-lg <?php echo $filter_status === 'Scheduled' ? 'bg-yellow-500 text-darkbg font-bold' : 'bg-darkbg text-yellow-400 border border-yellow-500/20'; ?>">Scheduled</a>
                <a href="/admin/recharges.php?status=Completed" class="px-3 py-1 rounded-lg <?php echo $filter_status === 'Completed' ? 'bg-green-500 text-white font-bold' : 'bg-darkbg text-green-400 border border-green-500/20'; ?>">Completed</a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-300">
                <thead class="bg-gold/10 text-gold uppercase border-b border-gold/20">
                    <tr>
                        <th class="p-3">#</th>
                        <th class="p-3">Member</th>
                        <th class="p-3">Service & Term</th>
                        <th class="p-3">Target Details</th>
                        <th class="p-3">Due / Requested</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Ref / Transaction ID</th>
                        <th class="p-3 text-right">Fulfilment Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gold/10">
                    <?php if (empty($schedules)): ?>
                        <tr>
                            <td colspan="8" class="p-4 text-center text-gray-500">No recharge schedules found matching your filters.</td>
                        </tr>
                    <?php else: foreach ($schedules as $idx => $row):
                        if ($row['service_type'] === 'Mobile_1') {
                            $target = $row['mobile_1'] . ' (' . $row['operator_1'] . ')';
                        } elseif ($row['service_type'] === 'Mobile_2') {
                            $target = $row['mobile_2'] . ' (' . $row['operator_2'] . ')';
                        } else {
                            $target = $row['gas_provider'] . ' - No: ' . $row['gas_consumer_number'] . ' (' . $row['gas_customer_name'] . ')';
                        }
                    ?>
                        <tr class="hover:bg-gold/5 transition">
                            <td class="p-3 text-gray-500"><?php echo $idx + 1; ?></td>
                            <td class="p-3">
                                <div class="font-bold text-white"><?php echo htmlspecialchars($row['member_name']); ?></div>
                                <div class="font-mono text-[10px] text-gold"><?php echo htmlspecialchars($row['member_id']); ?></div>
                            </td>
                            <td class="p-3">
                                <span class="font-bold text-white"><?php echo str_replace('_', ' ', $row['service_type']); ?></span>
                                <span class="text-gold font-mono ml-1">Term #<?php echo $row['term_number']; ?></span>
                            </td>
                            <td class="p-3 font-mono font-bold text-white max-w-xs truncate"><?php echo htmlspecialchars($target); ?></td>
                            <td class="p-3 font-mono text-gray-300">
                                <?php echo $row['due_date'] ? date('d M Y, H:i', strtotime($row['due_date'])) : 'On Demand'; ?>
                            </td>
                            <td class="p-3">
                                <?php if ($row['status'] === 'Completed'): ?>
                                    <span class="px-2 py-0.5 rounded bg-green-500/20 text-green-400 font-bold">Completed</span>
                                <?php elseif ($row['status'] === 'Requested'): ?>
                                    <span class="px-2 py-0.5 rounded bg-blue-500/20 text-blue-400 font-bold animate-pulse">Gas Requested</span>
                                <?php elseif ($row['status'] === 'Failed'): ?>
                                    <span class="px-2 py-0.5 rounded bg-red-500/20 text-red-400 font-bold">Failed</span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 rounded bg-yellow-500/20 text-yellow-400 font-bold">Scheduled</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3 font-mono text-goldlight font-bold">
                                <?php echo htmlspecialchars($row['reference_number'] ?: '-'); ?>
                            </td>
                            <td class="p-3 text-right">
                                <?php if ($row['status'] !== 'Completed'): ?>
                                    <form method="POST" action="" class="flex items-center justify-end space-x-2">
                                        <input type="hidden" name="action" value="process_schedule">
                                        <input type="hidden" name="schedule_id" value="<?php echo $row['id']; ?>">
                                        <input type="text" name="reference_number" required placeholder="Ref / Txn ID" class="w-32 bg-darkbg border border-gold/30 rounded px-2 py-1 text-[11px] text-white focus:outline-none focus:border-gold font-mono">
                                        <button type="submit" name="status" value="Completed" class="bg-green-600 hover:bg-green-500 text-white font-bold px-3 py-1 rounded text-[11px] transition">
                                            Fulfill
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-gray-500 text-[11px]">Done on <?php echo date('d M, H:i', strtotime($row['completed_at'])); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Subscribers Directory Table with Installment/Cycle Tracking -->
    <div class="bg-darkcard p-6 rounded-2xl gold-border-glow">
        <h2 class="text-lg font-bold text-white mb-4"><i class="fas fa-list text-gold mr-2"></i> All Utility Subscribers & Installment Cycles</h2>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-300">
                <thead class="bg-gold/10 text-gold uppercase border-b border-gold/20">
                    <tr>
                        <th class="p-3">#</th>
                        <th class="p-3">Member ID</th>
                        <th class="p-3">Name</th>
                        <th class="p-3">Phone</th>
                        <th class="p-3">Installment Cycles</th>
                        <th class="p-3">Mobile Connections</th>
                        <th class="p-3">Gas Connection</th>
                        <th class="p-3">Start Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gold/10">
                    <?php if (empty($subscriptions)): ?>
                        <tr>
                            <td colspan="8" class="p-4 text-center text-gray-500">No recharge bundle subscriptions recorded yet.</td>
                        </tr>
                    <?php else: foreach ($subscriptions as $idx => $sub): ?>
                        <tr class="hover:bg-gold/5 transition">
                            <td class="p-3 text-gray-500"><?php echo $idx + 1; ?></td>
                            <td class="p-3 font-mono font-bold text-gold"><?php echo htmlspecialchars($sub['member_id']); ?></td>
                            <td class="p-3 font-bold text-white"><?php echo htmlspecialchars($sub['member_name']); ?></td>
                            <td class="p-3 font-mono text-gray-300"><?php echo htmlspecialchars($sub['member_phone']); ?></td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 rounded bg-green-500/20 text-green-400 font-bold text-[11px]">
                                    <?php echo $sub['completed_cycles']; ?>/6 Completed
                                </span>
                                <span class="text-[10px] text-amber-300 block mt-1 font-semibold">
                                    Active: Term #<?php echo $sub['current_cycle']; ?> (<?php echo $sub['remaining_cycles']; ?> remaining)
                                </span>
                            </td>
                            <td class="p-3 font-mono text-white">
                                <?php if ($sub['mobile_1']): ?>
                                    <div><?php echo htmlspecialchars($sub['mobile_1']); ?> <span class="text-gold">(<?php echo htmlspecialchars($sub['operator_1']); ?>)</span></div>
                                <?php endif; ?>
                                <?php if ($sub['mobile_2']): ?>
                                    <div><?php echo htmlspecialchars($sub['mobile_2']); ?> <span class="text-gold">(<?php echo htmlspecialchars($sub['operator_2']); ?>)</span></div>
                                <?php endif; ?>
                            </td>
                            <td class="p-3 text-white">
                                <?php if ($sub['gas_consumer_number']): ?>
                                    <?php echo htmlspecialchars($sub['gas_provider']); ?> - <span class="font-mono"><?php echo htmlspecialchars($sub['gas_consumer_number']); ?></span>
                                <?php else: ?>
                                    <span class="text-gray-500">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3 font-mono text-gray-400"><?php echo date('d M Y, H:i', strtotime($sub['start_date'])); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
