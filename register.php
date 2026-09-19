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

    // Recharge Bundle fields
    $mobile_1 = trim($_POST['mobile_1'] ?? '');
    $operator_1 = trim($_POST['operator_1'] ?? '');
    $mobile_2 = trim($_POST['mobile_2'] ?? '');
    $operator_2 = trim($_POST['operator_2'] ?? '');
    $gas_provider = trim($_POST['gas_provider'] ?? '');
    $gas_consumer_number = trim($_POST['gas_consumer_number'] ?? '');
    $gas_customer_name = trim($_POST['gas_customer_name'] ?? '');

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

        if ($package_type === 'Recharge_Bundle_5400') {
            // Validate Recharge Bundle fields
            if (empty($mobile_1) || empty($operator_1) || empty($mobile_2) || empty($operator_2) || empty($gas_provider) || empty($gas_consumer_number) || empty($gas_customer_name)) {
                $error = "Please fill in all Recharge Bundle connection details (2 Mobile numbers with carriers and Indian Gas connection details).";
            }
        } else {
            // Validation 2: Sponsor Check (if provided) for Matrix Packages
            if (!empty($sponsor_id)) {
                $stmt = $pdo->prepare("SELECT member_id FROM members WHERE member_id = ?");
                $stmt->execute([$sponsor_id]);
                if (!$stmt->fetch()) {
                    $error = "Specified Sponsor ID does not exist.";
                }
            } else {
                $sponsor_id = 'GT100000'; // Default root sponsor
            }
        }

        if (empty($error)) {
            // Generate unique Member ID starting with GT + 6 digits
            $new_member_id = generateMemberId($pdo);

            try {
                $pdo->beginTransaction();

                if ($package_type === 'Recharge_Bundle_5400') {
                    // Recharge Bundle is completely separate from Matrix Plan: placement_parent_id & matrix_position remain NULL
                    $stmt = $pdo->prepare("INSERT INTO members (member_id, sponsor_id, placement_parent_id, matrix_position, name, email, phone, password, used_epin, package_type, status) VALUES (?, ?, NULL, NULL, ?, ?, ?, ?, ?, 'Recharge_Bundle_5400', 'Active')");
                    $stmt->execute([
                        $new_member_id,
                        !empty($sponsor_id) ? $sponsor_id : 'GT100000',
                        $name,
                        $email,
                        $phone,
                        $password,
                        $epin_code
                    ]);

                    // Update ePIN status to Used
                    $stmt = $pdo->prepare("UPDATE epins SET status = 'Used', used_by_member_id = ? WHERE epin_code = ?");
                    $stmt->execute([$new_member_id, $epin_code]);

                    // Create initial wallet record
                    ensureWalletExists($pdo, $new_member_id);

                    // Create Recharge Bundle Subscription and 6-term schedules
                    createRechargeSubscription($pdo, $new_member_id, $epin_code, $mobile_1, $operator_1, $mobile_2, $operator_2, $gas_provider, $gas_consumer_number, $gas_customer_name);

                    $pdo->commit();

                    $registered_info = [
                        'member_id' => $new_member_id,
                        'password' => $password,
                        'name' => $name,
                        'package_type' => $package_type,
                        'is_recharge' => true,
                        'mobile_1' => $mobile_1,
                        'mobile_2' => $mobile_2,
                        'gas_provider' => $gas_provider
                    ];
                    $success = "Registration successful! Your 6-term Recharge Bundle plan will start in 24 hours.";
                } else {
                    // AUTOMATIC 3-MATRIX TREE AUTO-PLACEMENT
                    $placement_info = findMatrixPlacement($pdo, $sponsor_id);
                    $placement_parent = $placement_info['parent_id'];
                    $matrix_position = $placement_info['position'];

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
                        $password,
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
                        'is_recharge' => false,
                        'placement_parent' => $placement_parent,
                        'matrix_position' => $matrix_position
                    ];
                    $success = "Registration successful! Welcome to Givora Traders LLP.";
                }
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
        <p class="mt-2 text-goldlight text-sm sm:text-base">Enter your ePIN and details to activate your account instantly.</p>
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
                <?php if (!empty($registered_info['is_recharge'])): ?>
                    <div class="p-3 bg-gold/10 border border-gold/30 rounded-lg text-xs space-y-1">
                        <div class="text-gold font-bold"><i class="fas fa-bolt mr-1"></i> Recharge Bundle Active</div>
                        <div class="text-gray-300">Plan starts in 24 hours with 6 terms of mobile & gas recharges.</div>
                    </div>
                <?php else: ?>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Auto Matrix Placement:</span>
                        <span class="text-white">Placed under <span class="text-gold font-bold"><?php echo htmlspecialchars($registered_info['placement_parent']); ?></span> (Position #<?php echo $registered_info['matrix_position']; ?>)</span>
                    </div>
                <?php endif; ?>
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

            <form method="POST" action="" class="grid grid-cols-1 md:grid-cols-2 gap-6" id="registrationForm">
                <!-- ePIN Input -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gold mb-2">
                        <i class="fas fa-key mr-1"></i> Activation ePIN Code *
                    </label>
                    <div class="relative">
                        <input type="text" id="epin_code" name="epin_code" required placeholder="e.g., GIV-A1B2C3D4E5" value="<?php echo htmlspecialchars($_POST['epin_code'] ?? ''); ?>" class="w-full bg-darkbg border border-gold/40 rounded-xl px-4 py-3.5 text-gold font-mono text-lg font-bold tracking-wider uppercase focus:outline-none focus:border-gold focus:ring-1 focus:ring-gold">
                        <span id="epin_status" class="absolute right-4 top-3.5 text-xs font-bold hidden"></span>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Required. Get an ePIN from your sponsor or company admin.</p>
                </div>

                <!-- Sponsor ID -->
                <div id="sponsor_section">
                    <label class="block text-sm font-medium text-gray-300 mb-2">
                        Sponsor ID (Referrer)
                    </label>
                    <input type="text" name="sponsor_id" placeholder="e.g., GT100000" value="<?php echo htmlspecialchars($_POST['sponsor_id'] ?? $sponsor_param); ?>" class="w-full bg-darkbg border border-gold/20 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-gold">
                    <p class="text-xs text-gray-500 mt-1">Leave blank for root company sponsor GT100000.</p>
                </div>

                <!-- Matrix Placement Note -->
                <div id="matrix_section">
                    <label class="block text-sm font-medium text-gray-300 mb-2">
                        3-Matrix Placement Parent
                    </label>
                    <div class="w-full bg-darkbg/60 border border-gold/20 rounded-xl px-4 py-3 text-gold text-xs font-semibold flex items-center space-x-2">
                        <i class="fas fa-sitemap text-gold"></i>
                        <span>Auto-Fill Spillover (3-matrix placement)</span>
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

                <!-- RECHARGE BUNDLE DETAILS SECTION (Dynamically displayed when ePIN is Recharge Bundle) -->
                <div id="recharge_bundle_fields" class="hidden md:col-span-2 bg-darkbg/80 border border-gold/30 p-6 rounded-2xl space-y-4">
                    <div class="flex items-center space-x-2 border-b border-gold/20 pb-3">
                        <i class="fas fa-bolt text-gold text-xl"></i>
                        <div>
                            <h3 class="text-sm font-bold text-white uppercase tracking-wider">Recharge Bundle Details (₹5,400)</h3>
                            <p class="text-xs text-gray-400">Collect 2 Mobile connections + 1 Indian Gas connection for 6 terms starting in 24 hours.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Mobile 1 -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-300 mb-1">Mobile 1 Number *</label>
                            <input type="text" name="mobile_1" id="mobile_1" placeholder="First Mobile Number" value="<?php echo htmlspecialchars($_POST['mobile_1'] ?? ''); ?>" class="w-full bg-darkcard border border-gold/20 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-gold">
                        </div>

                        <!-- Operator 1 -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-300 mb-1">Mobile 1 Carrier / Operator *</label>
                            <select name="operator_1" id="operator_1" class="w-full bg-darkcard border border-gold/20 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-gold">
                                <option value="Jio">Jio</option>
                                <option value="Airtel">Airtel</option>
                                <option value="Vi">Vi (Vodafone Idea)</option>
                                <option value="BSNL">BSNL</option>
                            </select>
                        </div>

                        <!-- Mobile 2 -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-300 mb-1">Mobile 2 Number *</label>
                            <input type="text" name="mobile_2" id="mobile_2" placeholder="Second Mobile Number" value="<?php echo htmlspecialchars($_POST['mobile_2'] ?? ''); ?>" class="w-full bg-darkcard border border-gold/20 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-gold">
                        </div>

                        <!-- Operator 2 -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-300 mb-1">Mobile 2 Carrier / Operator *</label>
                            <select name="operator_2" id="operator_2" class="w-full bg-darkcard border border-gold/20 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-gold">
                                <option value="Jio">Jio</option>
                                <option value="Airtel">Airtel</option>
                                <option value="Vi">Vi (Vodafone Idea)</option>
                                <option value="BSNL">BSNL</option>
                            </select>
                        </div>

                        <!-- Gas Company -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-300 mb-1">Indian Gas Provider *</label>
                            <select name="gas_provider" id="gas_provider" class="w-full bg-darkcard border border-gold/20 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-gold">
                                <option value="Indane Gas">Indane Gas (Indian Oil)</option>
                                <option value="Bharat Gas">Bharat Gas (BPCL)</option>
                                <option value="HP Gas">HP Gas (HPCL)</option>
                            </select>
                        </div>

                        <!-- Gas Consumer Number -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-300 mb-1">Gas Consumer Number / LPG ID *</label>
                            <input type="text" name="gas_consumer_number" id="gas_consumer_number" placeholder="Consumer No / 17-digit LPG ID" value="<?php echo htmlspecialchars($_POST['gas_consumer_number'] ?? ''); ?>" class="w-full bg-darkcard border border-gold/20 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-gold">
                        </div>

                        <!-- Gas Customer Name -->
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-gray-300 mb-1">Gas Connection Customer / Registered Name *</label>
                            <input type="text" name="gas_customer_name" id="gas_customer_name" placeholder="Name registered on Gas Passbook/Bill" value="<?php echo htmlspecialchars($_POST['gas_customer_name'] ?? ''); ?>" class="w-full bg-darkcard border border-gold/20 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-gold">
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="md:col-span-2 mt-4">
                    <button type="submit" class="w-full btn-gold py-4 rounded-xl text-lg font-bold shadow-xl flex items-center justify-center space-x-2">
                        <i class="fas fa-user-check"></i>
                        <span>Register Account</span>
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const epinInput = document.getElementById('epin_code');
    const statusSpan = document.getElementById('epin_status');
    const rechargeFields = document.getElementById('recharge_bundle_fields');
    const matrixSection = document.getElementById('matrix_section');

    function checkEpin() {
        const code = epinInput.value.trim();
        if (code.length < 5) {
            rechargeFields.classList.add('hidden');
            matrixSection.classList.remove('hidden');
            statusSpan.classList.add('hidden');
            return;
        }

        fetch('/check_epin.php?epin=' + encodeURIComponent(code))
            .then(res => res.json())
            .then(data => {
                if (data.valid) {
                    statusSpan.textContent = '✓ ' + data.package_type.replace('_', ' ₹');
                    statusSpan.className = 'absolute right-4 top-3.5 text-xs font-bold text-green-400';
                    statusSpan.classList.remove('hidden');

                    if (data.is_recharge_bundle) {
                        rechargeFields.classList.remove('hidden');
                        matrixSection.classList.add('hidden');
                        // Make recharge fields required
                        document.getElementById('mobile_1').required = true;
                        document.getElementById('mobile_2').required = true;
                        document.getElementById('gas_consumer_number').required = true;
                        document.getElementById('gas_customer_name').required = true;
                    } else {
                        rechargeFields.classList.add('hidden');
                        matrixSection.classList.remove('hidden');
                        document.getElementById('mobile_1').required = false;
                        document.getElementById('mobile_2').required = false;
                        document.getElementById('gas_consumer_number').required = false;
                        document.getElementById('gas_customer_name').required = false;
                    }
                } else {
                    statusSpan.textContent = '✕ ' + data.message;
                    statusSpan.className = 'absolute right-4 top-3.5 text-xs font-bold text-red-400';
                    statusSpan.classList.remove('hidden');
                    rechargeFields.classList.add('hidden');
                    matrixSection.classList.remove('hidden');
                }
            })
            .catch(() => {
                statusSpan.classList.add('hidden');
            });
    }

    epinInput.addEventListener('input', checkEpin);
    epinInput.addEventListener('blur', checkEpin);
    if (epinInput.value) checkEpin();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
