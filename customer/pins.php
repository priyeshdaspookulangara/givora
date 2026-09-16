<?php
require_once __DIR__ . '/../includes/functions.php';
checkMemberLogin();

$member = getLoggedInMember();
$page_title = "My ePINs";

$pdo = getDBConnection();

// Fetch ePINs used by this member or assigned to this member
$stmt = $pdo->prepare("SELECT * FROM epins WHERE used_by_member_id = ? OR assigned_to = ? ORDER BY created_at DESC");
$stmt->execute([$member['member_id'], $member['member_id']]);
$epins = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-white"><i class="fas fa-ticket-alt text-gold mr-2"></i> My ePIN History</h1>
            <p class="text-xs text-gray-400 mt-1">View the history of ePINs used for your registration or assigned to your account.</p>
        </div>
        <a href="/customer/dashboard.php" class="text-xs text-gold border border-gold/40 px-3 py-1.5 rounded-lg hover:bg-gold/10">← Dashboard</a>
    </div>

    <!-- Used ePIN Highlight Card -->
    <div class="bg-darkcard p-6 rounded-2xl gold-border-glow mb-8 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div>
            <span class="text-xs text-gold font-bold uppercase tracking-wider">Registration ePIN</span>
            <div class="text-2xl font-mono font-extrabold text-white mt-1"><?php echo htmlspecialchars($member['used_epin']); ?></div>
            <p class="text-xs text-gray-400 mt-1">Package: <span class="text-goldlight font-semibold"><?php echo str_replace('_', ' ₹', $member['package_type']); ?></span></p>
        </div>
        <div class="px-4 py-2 bg-green-500/20 border border-green-500/40 text-green-400 rounded-xl font-bold text-xs flex items-center">
            <i class="fas fa-check-circle text-lg mr-2"></i> Activated & Verified
        </div>
    </div>

    <!-- ePIN Records Table -->
    <div class="bg-darkcard p-6 rounded-2xl gold-border-glow">
        <h2 class="text-lg font-bold text-white mb-4">Assigned / Generated ePINs</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-300">
                <thead class="bg-gold/10 text-gold uppercase border-b border-gold/20">
                    <tr>
                        <th class="p-3">#</th>
                        <th class="p-3">ePIN Code</th>
                        <th class="p-3">Package Tier</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Used By</th>
                        <th class="p-3">Generated / Assigned Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gold/10">
                    <?php if (empty($epins)): ?>
                        <tr>
                            <td colspan="6" class="p-4 text-center text-gray-500">No additional ePIN records found for your account.</td>
                        </tr>
                    <?php else: foreach ($epins as $idx => $pin): ?>
                        <tr>
                            <td class="p-3 text-gray-500"><?php echo $idx + 1; ?></td>
                            <td class="p-3 font-mono font-bold text-gold text-sm"><?php echo htmlspecialchars($pin['epin_code']); ?></td>
                            <td class="p-3 font-semibold text-white"><?php echo str_replace('_', ' ₹', $pin['package_type']); ?></td>
                            <td class="p-3">
                                <?php if ($pin['status'] === 'Used'): ?>
                                    <span class="px-2 py-0.5 rounded bg-red-500/20 text-red-400 font-bold">Used</span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 rounded bg-green-500/20 text-green-400 font-bold">Unused / Ready</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3 font-mono text-gray-300"><?php echo htmlspecialchars($pin['used_by_member_id'] ?: 'N/A'); ?></td>
                            <td class="p-3 text-gray-400 font-mono"><?php echo date('d M Y, H:i', strtotime($pin['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
