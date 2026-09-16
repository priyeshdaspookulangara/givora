<?php
$page_title = "Member Registration - Givora Traders LLP";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';

$error = '';
$success = '';
$registered_info = null;

$selected_package = $_GET['package'] ?? 'Foundation_5000';
$sponsor_param = $_GET['sponsor'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sponsor_id = trim($_POST['sponsor_id'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $epin_code = trim($_POST['epin_code'] ?? '');

    $pdo = getDBConnection();

    // Validation 1: ePIN Check
    $stmt = $pdo->prepare("SELECT * FROM epins WHERE epin_code = ?");
    $stmt->execute([$epin_code]);
    $epin = $stmt->fetch();

    if (!$epin) {
        $error = "Invalid ePIN code. Please check and try again.";
    } elseif ($epin['status'] !== 'Unused') {
        $error = "This ePIN code has already been used.";
    } else {
        $package_type = $epin['package_type']; // Get package type from ePIN record

        // Validation 2: Sponsor Check (if provided)
        if (!empty($sponsor_id)) {
            $stmt = $pdo->prepare("SELECT member_id FROM members WHERE member_id = ?");
            $stmt->execute([$sponsor_id]);
            if (!$stmt->fetch()) {
                $error = "Specified Sponsor ID does not exist.";
            }
        } else {
            $sponsor_id = 'GT100000'; // Default root sponsor
        }

        if (empty($error)) {
            // AUTOMATIC 3-MATRIX TREE AUTO-PLACEMENT (BFS Spillover starting under sponsor)
            // Placement Parent is AUTO-FILL / AUTO-DETERMINED by system matrix logic.
            $placement_info = findMatrixPlacement($pdo, $sponsor_id);

            $placement_parent = $placement_info['parent_id'];
            $matrix_position = $placement_info['position'];

            // Generate unique Member ID starting with GT + 6 digits
            $new_member_id = generateMemberId($pdo);

            try {
                $pdo->beginTransaction();

                // Insert into members
                $stmt = $pdo->prepare("INSERT INTO members (member_id, sponsor_id, placement_parent_id, matrix_position, name, email, phone, password, used_epin, package_type, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')");
                $stmt->execute([
                    $new_member_id,
                    $sponsor_id,
                    $placement_parent,
                    $matrix_position,
                    $name,
                    $email,
                    $phone,
                    $password, // plain text storage as requested
                    $epin_code,
                    $package_type
                ]);

                // Update ePIN status to Used
                $stmt = $pdo->prepare("UPDATE epins SET status = 'Used', used_by_member_id = ? WHERE epin_code = ?");
                $stmt->execute([$new_member_id, $epin_code]);

                // Create initial wallet record
                ensureWalletExists($pdo, $new_member_id);

                // Distribute direct referral & matrix commissions
                distributeCommissions($pdo, $new_member_id, $sponsor_id, $package_type);

                $pdo->commit();

                $registered_info = [
                    'member_id' => $new_member_id,
                    'password' => $password,
                    'name' => $name,
                    'package_type' => $package_type,
                    'placement_parent' => $placement_parent,
                    'matrix_position' => $matrix_position
                ];
                $success = "Registration successful! Welcome to Givora Traders LLP.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Registration failed due to a system error: " . $e->getMessage();
            }
        }
    }
}
?>

<div class="py-12 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-8">
        <h1 class="text-3xl sm:text-4xl font-extrabold text-white">Join Givora Traders LLP</h1>
        <p class="mt-2 text-goldlight text-sm sm:text-base">Enter your ePIN and details to activate your 3-matrix account instantly.</p>
    </div>

    <?php if (!empty($success) && $registered_info): ?>
        <div class="bg-darkcard p-8 rounded-2xl gold-border-glow text-center space-y-6">
            <div class="w-16 h-16 bg-green-500/20 text-green-400 rounded-full flex items-center justify-center mx-auto text-3xl border border-green-500/40">
                <i class="fas fa-check"></i>
            </div>
            <h2 class="text-2xl font-bold text-white"><?php echo $success; ?></h2>
            <div class="bg-darkbg p-6 rounded-xl text-left border border-gold/20 max-w-md mx-auto space-y-3">
                <div class="flex justify-between border-b border-gold/10 pb-2">
                    <span class="text-gray-400">Member ID:</span>
                    <span class="text-gold font-bold text-lg"><?php echo htmlspecialchars($registered_info['member_id']); ?></span>
                </div>
                <div class="flex justify-between border-b border-gold/10 pb-2">
                    <span class="text-gray-400">Password:</span>
                    <span class="text-white font-mono"><?php echo htmlspecialchars($registered_info['password']); ?></span>
                </div>
                <div class="flex justify-between border-b border-gold/10 pb-2">
                    <span class="text-gray-400">Package:</span>
                    <span class="text-white font-semibold"><?php echo str_replace('_', ' ₹', $registered_info['package_type']); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Auto Matrix Placement:</span>
                    <span class="text-white">Placed under <span class="text-gold font-bold"><?php echo htmlspecialchars($registered_info['placement_parent']); ?></span> (Position #<?php echo $registered_info['matrix_position']; ?>)</span>
                </div>
            </div>
            <div class="pt-4">
                <a href="/login.php" class="btn-gold px-8 py-3 rounded-xl font-bold inline-block">
                    Proceed to Member Login
                </a>
            </div>
        </div>
    <?php else: ?>

        <div class="bg-darkcard p-8 rounded-2xl gold-border-glow">
            <?php if (!empty($error)): ?>
                <div class="mb-6 p-4 rounded-xl bg-red-900/30 border border-red-500/50 text-red-300 text-sm flex items-center">
                    <i class="fas fa-exclamation-circle text-red-400 text-lg mr-3"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- ePIN Input -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gold mb-2">
                        <i class="fas fa-key mr-1"></i> Activation ePIN Code *
                    </label>
                    <input type="text" name="epin_code" required placeholder="e.g., GIV-A1B2C3D4E5" value="<?php echo htmlspecialchars($_POST['epin_code'] ?? ''); ?>" class="w-full bg-darkbg border border-gold/40 rounded-xl px-4 py-3.5 text-gold font-mono text-lg font-bold tracking-wider uppercase focus:outline-none focus:border-gold focus:ring-1 focus:ring-gold">
                    <p class="text-xs text-gray-400 mt-1">Required. Get an ePIN from your sponsor or company admin.</p>
                </div>

                <!-- Sponsor ID -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">
                        Sponsor ID (Referrer)
                    </label>
                    <input type="text" name="sponsor_id" placeholder="e.g., GT100000" value="<?php echo htmlspecialchars($_POST['sponsor_id'] ?? $sponsor_param); ?>" class="w-full bg-darkbg border border-gold/20 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-gold">
                    <p class="text-xs text-gray-500 mt-1">Leave blank for root company sponsor GT100000.</p>
                </div>

                <!-- Auto-Fill Matrix Placement Note -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">
                        3-Matrix Placement Parent
                    </label>
                    <div class="w-full bg-darkbg/60 border border-gold/20 rounded-xl px-4 py-3 text-gold text-xs font-semibold flex items-center space-x-2">
                        <i class="fas fa-sitemap text-gold"></i>
                        <span>Auto-Fill Spillover (Next 3 members placed automatically under parent node)</span>
                    </div>
                </div>

                <!-- Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Full Name *</label>
                    <input type="text" name="name" required placeholder="John Doe" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" class="w-full bg-darkbg border border-gold/20 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-gold">
                </div>

                <!-- Phone -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Phone Number *</label>
                    <input type="text" name="phone" required placeholder="9876543210" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" class="w-full bg-darkbg border border-gold/20 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-gold">
                    <p class="text-xs text-gray-500 mt-1">Duplicates allowed as per system policy.</p>
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Email Address *</label>
                    <input type="email" name="email" required placeholder="john@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" class="w-full bg-darkbg border border-gold/20 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-gold">
                    <p class="text-xs text-gray-500 mt-1">Duplicates allowed as per system policy.</p>
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Password *</label>
                    <input type="password" name="password" required placeholder="Create password" class="w-full bg-darkbg border border-gold/20 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-gold">
                </div>

                <!-- Submit Button -->
                <div class="md:col-span-2 mt-4">
                    <button type="submit" class="w-full btn-gold py-4 rounded-xl text-lg font-bold shadow-xl flex items-center justify-center space-x-2">
                        <i class="fas fa-user-check"></i>
                        <span>Register & Auto-Place Account</span>
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
