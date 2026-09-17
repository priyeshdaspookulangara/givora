<?php
require_once __DIR__ . '/../includes/functions.php';
checkAdminLogin();

$page_title = "KYC Verification Requests";
$pdo = getDBConnection();

// Handle Approve / Reject Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = trim($_POST['member_id'] ?? '');
    $action = trim($_POST['action'] ?? ''); // 'approve' or 'reject'

    if (!empty($member_id) && in_array($action, ['approve', 'reject'])) {
        $new_status = ($action === 'approve') ? 'Approved' : 'Rejected';
        $stmt = $pdo->prepare("UPDATE members SET kyc_status = ? WHERE member_id = ?");
        $stmt->execute([$new_status, $member_id]);

        $_SESSION['admin_msg_success'] = "Member '{$member_id}' KYC status updated to {$new_status}.";
        header("Location: /admin/kyc.php");
        exit;
    }
}

$status_filter = trim($_GET['status'] ?? '');
$sql = "SELECT member_id, name, phone, email, address_line, place, city, pincode, state, pan_number, aadhaar_number, bank_name, bank_account_number, ifsc_code, kyc_status, created_at FROM members WHERE member_id != 'GT100000'";
$params = [];

if (!empty($status_filter)) {
    $sql .= " AND kyc_status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$kyc_members = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white"><i class="fas fa-user-check text-gold mr-2"></i> KYC Verification Management</h1>
            <p class="text-xs text-gray-400 mt-1">Review member identity, residential address, and bank account details for payout approvals.</p>
        </div>
        <a href="/admin/members.php" class="text-xs text-gold border border-gold/40 px-3 py-1.5 rounded-lg hover:bg-gold/10">← Member Directory</a>
    </div>

    <!-- Flash Messages -->
    <?php if (!empty($_SESSION['admin_msg_success'])): ?>
        <div class="mb-6 p-4 rounded-xl bg-green-900/30 border border-green-500/50 text-green-300 text-xs flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-400 text-base mr-3"></i>
                <span><?php echo htmlspecialchars($_SESSION['admin_msg_success']); unset($_SESSION['admin_msg_success']); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Filter Buttons -->
    <div class="flex items-center space-x-2 mb-6">
        <a href="/admin/kyc.php" class="px-4 py-2 rounded-xl text-xs font-bold <?php echo empty($status_filter) ? 'bg-gold text-darkbg' : 'bg-darkcard text-gray-300 border border-gold/20'; ?>">All Members</a>
        <a href="/admin/kyc.php?status=Submitted" class="px-4 py-2 rounded-xl text-xs font-bold <?php echo $status_filter === 'Submitted' ? 'bg-amber-500 text-darkbg' : 'bg-darkcard text-amber-400 border border-amber-500/30'; ?>">Pending Verification</a>
        <a href="/admin/kyc.php?status=Approved" class="px-4 py-2 rounded-xl text-xs font-bold <?php echo $status_filter === 'Approved' ? 'bg-green-500 text-darkbg' : 'bg-darkcard text-green-400 border border-green-500/30'; ?>">Approved</a>
        <a href="/admin/kyc.php?status=Rejected" class="px-4 py-2 rounded-xl text-xs font-bold <?php echo $status_filter === 'Rejected' ? 'bg-red-500 text-darkbg' : 'bg-darkcard text-red-400 border border-red-500/30'; ?>">Rejected</a>
    </div>

    <!-- KYC Table -->
    <div class="bg-darkcard p-6 rounded-2xl gold-border-glow">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-300">
                <thead class="bg-gold/10 text-gold uppercase border-b border-gold/20">
                    <tr>
                        <th class="p-3">Member ID & Name</th>
                        <th class="p-3">Address & Location</th>
                        <th class="p-3">PAN & Aadhaar</th>
                        <th class="p-3">Bank Details</th>
                        <th class="p-3">KYC Status</th>
                        <th class="p-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gold/10">
                    <?php if (empty($kyc_members)): ?>
                        <tr>
                            <td colspan="6" class="p-4 text-center text-gray-500">No member KYC records found.</td>
                        </tr>
                    <?php else: foreach ($kyc_members as $m): ?>
                        <tr class="hover:bg-gold/5 transition">
                            <td class="p-3">
                                <div class="font-bold text-white text-sm"><?php echo htmlspecialchars($m['name']); ?></div>
                                <div class="font-mono text-gold font-bold"><?php echo htmlspecialchars($m['member_id']); ?></div>
                                <div class="text-[10px] text-gray-400"><i class="fas fa-phone mr-1"></i><?php echo htmlspecialchars($m['phone']); ?></div>
                            </td>
                            <td class="p-3">
                                <div class="text-white font-medium"><?php echo htmlspecialchars($m['address_line'] ?: 'Not Provided'); ?></div>
                                <div class="text-gray-400 text-[11px]"><?php echo htmlspecialchars(($m['place'] ? $m['place'] . ', ' : '') . ($m['city'] ?: '') . ($m['pincode'] ? ' - ' . $m['pincode'] : '')); ?></div>
                                <div class="text-gray-500 text-[10px]"><?php echo htmlspecialchars($m['state'] ?: ''); ?></div>
                            </td>
                            <td class="p-3 font-mono">
                                <div><span class="text-gray-400">PAN:</span> <span class="text-amber-300 font-bold"><?php echo htmlspecialchars($m['pan_number'] ?: 'N/A'); ?></span></div>
                                <div><span class="text-gray-400">Aadhaar:</span> <span class="text-white font-bold"><?php echo htmlspecialchars($m['aadhaar_number'] ?: 'N/A'); ?></span></div>
                            </td>
                            <td class="p-3 font-mono">
                                <div class="text-white font-bold"><?php echo htmlspecialchars($m['bank_name'] ?: 'N/A'); ?></div>
                                <div><span class="text-gray-400">Acc:</span> <span class="text-gold"><?php echo htmlspecialchars($m['bank_account_number'] ?: 'N/A'); ?></span></div>
                                <div><span class="text-gray-400">IFSC:</span> <span class="text-goldlight"><?php echo htmlspecialchars($m['ifsc_code'] ?: 'N/A'); ?></span></div>
                            </td>
                            <td class="p-3">
                                <?php if ($m['kyc_status'] === 'Approved'): ?>
                                    <span class="px-2.5 py-1 rounded bg-green-500/20 text-green-400 font-bold border border-green-500/30 inline-block"><i class="fas fa-check mr-1"></i> Approved</span>
                                <?php elseif ($m['kyc_status'] === 'Submitted'): ?>
                                    <span class="px-2.5 py-1 rounded bg-amber-500/20 text-amber-300 font-bold border border-amber-500/30 inline-block"><i class="fas fa-clock mr-1"></i> Submitted</span>
                                <?php elseif ($m['kyc_status'] === 'Rejected'): ?>
                                    <span class="px-2.5 py-1 rounded bg-red-500/20 text-red-400 font-bold border border-red-500/30 inline-block"><i class="fas fa-times mr-1"></i> Rejected</span>
                                <?php else: ?>
                                    <span class="px-2.5 py-1 rounded bg-gray-500/20 text-gray-400 font-bold border border-gray-500/30 inline-block">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3 text-center">
                                <form method="POST" action="" class="inline-flex space-x-1">
                                    <input type="hidden" name="member_id" value="<?php echo $m['member_id']; ?>">
                                    <button type="submit" name="action" value="approve" class="px-2 py-1 bg-green-600 hover:bg-green-500 text-white rounded font-bold text-[10px]" title="Approve KYC">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button type="submit" name="action" value="reject" class="px-2 py-1 bg-red-600 hover:bg-red-500 text-white rounded font-bold text-[10px]" title="Reject KYC">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
