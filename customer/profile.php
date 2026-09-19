<?php
require_once __DIR__ . '/../includes/functions.php';
checkMemberLogin();

$member = getLoggedInMember();
$page_title = "My Profile";
$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');

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
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE members SET name = ?, email = ?, phone = ?, password = ?, profile_image = ? WHERE member_id = ?");
        $stmt->execute([$name, $email, $phone, $password, $profile_image_path, $member['member_id']]);

        $msg = "Profile updated successfully!";
        $member = getLoggedInMember(); // Refresh member data
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="py-8 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-white"><i class="fas fa-user-edit text-gold mr-2"></i> Edit My Profile</h1>
        <a href="/customer/dashboard.php" class="text-xs text-gold border border-gold/40 px-3 py-1.5 rounded-lg hover:bg-gold/10">← Back to Dashboard</a>
    </div>

    <div class="bg-darkcard p-8 rounded-2xl gold-border-glow">
        <?php if (!empty($msg)): ?>
            <div class="mb-6 p-4 rounded-xl bg-green-900/30 border border-green-500/50 text-green-300 text-sm">
                <i class="fas fa-check-circle mr-2"></i> <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="mb-6 p-4 rounded-xl bg-red-900/30 border border-red-500/50 text-red-300 text-sm">
                <i class="fas fa-exclamation-circle mr-2"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data" class="space-y-6">
            <!-- Profile Picture Display & Upload -->
            <div class="flex items-center space-x-6">
                <div class="w-24 h-24 rounded-full border-2 border-gold overflow-hidden bg-darkbg flex items-center justify-center text-gold text-3xl">
                    <?php if (!empty($member['profile_image'])): ?>
                        <img src="<?php echo htmlspecialchars($member['profile_image']); ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <i class="fas fa-user"></i>
                    <?php endif; ?>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Upload Profile Image</label>
                    <input type="file" name="profile_image" accept="image/*" class="text-xs text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-gold file:text-darkbg hover:file:bg-goldlight cursor-pointer">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Member ID (Immutable)</label>
                    <input type="text" readonly value="<?php echo htmlspecialchars($member['member_id']); ?>" class="w-full bg-darkbg/50 border border-gold/10 rounded-xl px-4 py-3 text-gold font-mono font-bold cursor-not-allowed">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Package Type</label>
                    <input type="text" readonly value="<?php echo str_replace('_', ' ₹', $member['package_type']); ?>" class="w-full bg-darkbg/50 border border-gold/10 rounded-xl px-4 py-3 text-white cursor-not-allowed">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Full Name</label>
                    <input type="text" name="name" required value="<?php echo htmlspecialchars($member['name']); ?>" class="w-full bg-darkbg border border-gold/30 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-gold">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Phone Number</label>
                    <input type="text" name="phone" required value="<?php echo htmlspecialchars($member['phone']); ?>" class="w-full bg-darkbg border border-gold/30 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-gold">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Email Address</label>
                    <input type="email" name="email" required value="<?php echo htmlspecialchars($member['email']); ?>" class="w-full bg-darkbg border border-gold/30 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-gold">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Password</label>
                    <input type="text" name="password" required value="<?php echo htmlspecialchars($member['password']); ?>" class="w-full bg-darkbg border border-gold/30 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-gold">
                </div>
            </div>

            <button type="submit" class="btn-gold px-8 py-3.5 rounded-xl font-bold shadow-lg">
                Save Profile Changes
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
