<?php
require_once __DIR__ . '/../includes/functions.php';
checkMemberLogin();

$member = getLoggedInMember();
$page_title = "My Profile & KYC Details";
$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Extended Address & Location Fields
    $address_line = trim($_POST['address_line'] ?? '');
    $place = trim($_POST['place'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    $state = trim($_POST['state'] ?? '');

    // KYC & Banking Fields
    $pan_number = strtoupper(trim($_POST['pan_number'] ?? ''));
    $aadhaar_number = trim($_POST['aadhaar_number'] ?? '');
    $bank_name = trim($_POST['bank_name'] ?? '');
    $bank_account_number = trim($_POST['bank_account_number'] ?? '');
    $ifsc_code = strtoupper(trim($_POST['ifsc_code'] ?? ''));

    $profile_image_path = $member['profile_image'];

    // Handle Profile Image Upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['profile_image']['tmp_name'];
        $fileName = $_FILES['profile_image']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $uploadFileDir = __DIR__ . '/../uploads/profiles/';
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0777, true);
            }
            $newFileName = $member['member_id'] . '_' . time() . '.' . $fileExtension;
            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $profile_image_path = '/uploads/profiles/' . $newFileName;
            } else {
                $error = "Error moving uploaded profile image.";
            }
        } else {
            $error = "Invalid image extension. Allowed: jpg, jpeg, png, webp.";
        }
    }

    if (empty($error)) {
        // Automatically determine KYC status based on details completion
        $kyc_status = $member['kyc_status'] ?? 'Pending';
        if (!empty($address_line) && !empty($city) && !empty($pincode) && !empty($pan_number) && !empty($aadhaar_number) && !empty($bank_name) && !empty($bank_account_number) && !empty($ifsc_code)) {
            if ($kyc_status === 'Pending' || $kyc_status === 'Rejected') {
                $kyc_status = 'Submitted';
            }
        }

        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE members SET name = ?, email = ?, phone = ?, password = ?, profile_image = ?, address_line = ?, place = ?, city = ?, pincode = ?, state = ?, pan_number = ?, aadhaar_number = ?, bank_name = ?, bank_account_number = ?, ifsc_code = ?, kyc_status = ? WHERE member_id = ?");
        $stmt->execute([
            $name, $email, $phone, $password, $profile_image_path,
            $address_line, $place, $city, $pincode, $state,
            $pan_number, $aadhaar_number, $bank_name, $bank_account_number, $ifsc_code,
            $kyc_status, $member['member_id']
        ]);

        $msg = "Profile & KYC details saved successfully!";
        $member = getLoggedInMember(); // Refresh member data
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="py-8 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-white"><i class="fas fa-id-card text-gold mr-2"></i> My Profile & Verification (KYC)</h1>
            <p class="text-xs text-gray-400 mt-1">Complete your address and bank verification details to unlock payout withdrawals.</p>
        </div>
        <a href="/customer/dashboard.php" class="text-xs text-gold border border-gold/40 px-3 py-1.5 rounded-lg hover:bg-gold/10">← Back to Dashboard</a>
    </div>

    <!-- KYC Status Banner -->
    <div class="mb-8 p-6 rounded-2xl bg-darkcard gold-border-glow border border-gold/30 flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex items-center space-x-4">
            <div class="w-12 h-12 rounded-full flex items-center justify-center text-xl font-bold
                <?php
                if (($member['kyc_status'] ?? 'Pending') === 'Approved') echo 'bg-green-500/20 text-green-400 border border-green-500/40';
                elseif (($member['kyc_status'] ?? 'Pending') === 'Submitted') echo 'bg-amber-500/20 text-amber-300 border border-amber-500/40';
                elseif (($member['kyc_status'] ?? 'Pending') === 'Rejected') echo 'bg-red-500/20 text-red-400 border border-red-500/40';
                else echo 'bg-gray-500/20 text-gray-400 border border-gray-500/40';
                ?>">
                <i class="fas <?php
                if (($member['kyc_status'] ?? 'Pending') === 'Approved') echo 'fa-check-circle';
                elseif (($member['kyc_status'] ?? 'Pending') === 'Submitted') echo 'fa-clock';
                elseif (($member['kyc_status'] ?? 'Pending') === 'Rejected') echo 'fa-times-circle';
                else echo 'fa-exclamation-triangle';
                ?>"></i>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wider text-gray-400 font-bold">Verification Status</div>
                <div class="text-lg font-bold text-white flex items-center gap-2">
                    KYC Status:
                    <span class="font-bold <?php
                    if (($member['kyc_status'] ?? 'Pending') === 'Approved') echo 'text-green-400';
                    elseif (($member['kyc_status'] ?? 'Pending') === 'Submitted') echo 'text-amber-300';
                    elseif (($member['kyc_status'] ?? 'Pending') === 'Rejected') echo 'text-red-400';
                    else echo 'text-gray-400';
                    ?>"><?php echo strtoupper($member['kyc_status'] ?? 'Pending'); ?></span>
                </div>
                <p class="text-xs text-gray-400 mt-0.5">
                    <?php
                    if (($member['kyc_status'] ?? 'Pending') === 'Approved') echo "Your KYC and bank details are verified. Payout withdrawals are fully unlocked!";
                    elseif (($member['kyc_status'] ?? 'Pending') === 'Submitted') echo "Your details have been submitted and are pending admin verification.";
                    elseif (($member['kyc_status'] ?? 'Pending') === 'Rejected') echo "Your previous KYC submission was rejected. Please re-check and resubmit valid details.";
                    else echo "Please complete all address, PAN, Aadhaar, and bank account details below to request cash withdrawals.";
                    ?>
                </p>
            </div>
        </div>
    </div>

    <div class="bg-darkcard p-8 rounded-2xl gold-border-glow">
        <?php if (!empty($msg)): ?>
            <div class="mb-6 p-4 rounded-xl bg-green-900/30 border border-green-500/50 text-green-300 text-sm flex items-center">
                <i class="fas fa-check-circle text-green-400 mr-3 text-lg"></i>
                <span><?php echo htmlspecialchars($msg); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="mb-6 p-4 rounded-xl bg-red-900/30 border border-red-500/50 text-red-300 text-sm flex items-center">
                <i class="fas fa-exclamation-circle text-red-400 mr-3 text-lg"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data" class="space-y-8">
            <!-- Basic Information & Avatar -->
            <div>
                <h3 class="text-sm font-bold text-gold uppercase tracking-wider mb-4 border-b border-gold/10 pb-2 flex items-center">
                    <i class="fas fa-user-circle mr-2"></i> 1. Basic Account Information
                </h3>

                <div class="flex items-center space-x-6 mb-6">
                    <div class="w-20 h-20 rounded-full border-2 border-gold overflow-hidden bg-darkbg flex items-center justify-center text-gold text-2xl flex-shrink-0">
                        <?php if (!empty($member['profile_image'])): ?>
                            <img src="<?php echo htmlspecialchars($member['profile_image']); ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <i class="fas fa-user"></i>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1.5">Profile Avatar Image</label>
                        <input type="file" name="profile_image" accept="image/*" class="text-xs text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-gold file:text-darkbg hover:file:bg-goldlight cursor-pointer">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1.5">Member ID</label>
                        <input type="text" readonly value="<?php echo htmlspecialchars($member['member_id']); ?>" class="w-full bg-darkbg/50 border border-gold/10 rounded-xl px-4 py-2.5 text-gold font-mono font-bold cursor-not-allowed">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1.5">Package Tier</label>
                        <input type="text" readonly value="<?php echo str_replace('_', ' ₹', $member['package_type']); ?>" class="w-full bg-darkbg/50 border border-gold/10 rounded-xl px-4 py-2.5 text-white cursor-not-allowed">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1.5">Full Name *</label>
                        <input type="text" name="name" required value="<?php echo htmlspecialchars($member['name']); ?>" class="w-full bg-darkbg border border-gold/30 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-gold">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1.5">Phone Number *</label>
                        <input type="text" name="phone" required value="<?php echo htmlspecialchars($member['phone']); ?>" class="w-full bg-darkbg border border-gold/30 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-gold">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1.5">Email Address *</label>
                        <input type="email" name="email" required value="<?php echo htmlspecialchars($member['email']); ?>" class="w-full bg-darkbg border border-gold/30 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-gold">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1.5">Password *</label>
                        <input type="text" name="password" required value="<?php echo htmlspecialchars($member['password']); ?>" class="w-full bg-darkbg border border-gold/30 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-gold">
                    </div>
                </div>
            </div>

            <!-- Additional Address & Location Details -->
            <div>
                <h3 class="text-sm font-bold text-gold uppercase tracking-wider mb-4 border-b border-gold/10 pb-2 flex items-center">
                    <i class="fas fa-map-marker-alt mr-2"></i> 2. Residential Address Details
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-300 mb-1.5">Address Line *</label>
                        <input type="text" name="address_line" placeholder="House No, Street, Landmark..." value="<?php echo htmlspecialchars($member['address_line'] ?? ''); ?>" class="w-full bg-darkbg border border-gold/30 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-gold">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1.5">Place / Village *</label>
                        <input type="text" name="place" placeholder="e.g., Aluva" value="<?php echo htmlspecialchars($member['place'] ?? ''); ?>" class="w-full bg-darkbg border border-gold/30 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-gold">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1.5">City / District *</label>
                        <input type="text" name="city" placeholder="e.g., Ernakulam" value="<?php echo htmlspecialchars($member['city'] ?? ''); ?>" class="w-full bg-darkbg border border-gold/30 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-gold">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1.5">Pincode *</label>
                        <input type="text" name="pincode" placeholder="e.g., 683101" value="<?php echo htmlspecialchars($member['pincode'] ?? ''); ?>" class="w-full bg-darkbg border border-gold/30 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-gold">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1.5">State *</label>
                        <input type="text" name="state" placeholder="e.g., Kerala" value="<?php echo htmlspecialchars($member['state'] ?? 'Kerala'); ?>" class="w-full bg-darkbg border border-gold/30 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-gold">
                    </div>
                </div>
            </div>

            <!-- KYC Verification & Bank Details -->
            <div>
                <h3 class="text-sm font-bold text-gold uppercase tracking-wider mb-4 border-b border-gold/10 pb-2 flex items-center">
                    <i class="fas fa-university mr-2"></i> 3. Bank Account & Identity (KYC)
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1.5">PAN Card Number *</label>
                        <input type="text" name="pan_number" placeholder="ABCDE1234F" value="<?php echo htmlspecialchars($member['pan_number'] ?? ''); ?>" class="w-full bg-darkbg border border-gold/30 rounded-xl px-4 py-2.5 text-xs text-white font-mono uppercase focus:outline-none focus:border-gold">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1.5">Aadhaar Card Number *</label>
                        <input type="text" name="aadhaar_number" placeholder="1234 5678 9012" value="<?php echo htmlspecialchars($member['aadhaar_number'] ?? ''); ?>" class="w-full bg-darkbg border border-gold/30 rounded-xl px-4 py-2.5 text-xs text-white font-mono focus:outline-none focus:border-gold">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1.5">Bank Name *</label>
                        <input type="text" name="bank_name" placeholder="e.g., State Bank of India" value="<?php echo htmlspecialchars($member['bank_name'] ?? ''); ?>" class="w-full bg-darkbg border border-gold/30 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-gold">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1.5">Bank Account Number *</label>
                        <input type="text" name="bank_account_number" placeholder="e.g., 20123456789" value="<?php echo htmlspecialchars($member['bank_account_number'] ?? ''); ?>" class="w-full bg-darkbg border border-gold/30 rounded-xl px-4 py-2.5 text-xs text-white font-mono focus:outline-none focus:border-gold">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1.5">Bank IFSC Code *</label>
                        <input type="text" name="ifsc_code" placeholder="e.g., SBIN0001234" value="<?php echo htmlspecialchars($member['ifsc_code'] ?? ''); ?>" class="w-full bg-darkbg border border-gold/30 rounded-xl px-4 py-2.5 text-xs text-white font-mono uppercase focus:outline-none focus:border-gold">
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-gold/10 flex justify-end">
                <button type="submit" class="btn-gold px-8 py-3.5 rounded-xl font-bold shadow-lg flex items-center space-x-2">
                    <i class="fas fa-save"></i>
                    <span>Save Profile & Submit KYC Details</span>
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
