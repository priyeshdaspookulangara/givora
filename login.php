<?php
require_once __DIR__ . '/includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = strtoupper(trim($_POST['member_id'] ?? ''));
    $password = trim($_POST['password'] ?? '');

    if (empty($member_id) || empty($password)) {
        $error = "Please enter both Member ID and Password.";
    } else {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM members WHERE member_id = ?");
        $stmt->execute([$member_id]);
        $member = $stmt->fetch();

        if ($member) {
            $password_valid = ($password === $member['password']) ||
                              (function_exists('password_verify') && password_verify($password, $member['password']));

            if ($password_valid) {
                if (isset($member['status']) && $member['status'] === 'Inactive') {
                    $error = "Your account is currently inactive. Please contact customer support.";
                } else {
                    $_SESSION['member_id'] = $member['member_id'];
                    $_SESSION['member_name'] = $member['name'];
                    header("Location: " . getBaseUrl() . "/customer/dashboard.php");
                    exit;
                }
            } else {
                $error = "Invalid Member ID or Password. Please try again.";
            }
        } else {
            $error = "Invalid Member ID or Password. Please try again.";
        }
    }
}

$page_title = "Customer Login - Givora Traders LLP";
require_once __DIR__ . '/includes/header.php';
?>

<div class="py-16 max-w-md mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-8">
        <div class="w-16 h-16 bg-gradient-to-tr from-golddark to-goldlight rounded-full flex items-center justify-center mx-auto text-darkbg text-2xl font-bold shadow-lg mb-4">
            <i class="fas fa-user"></i>
        </div>
        <h1 class="text-3xl font-extrabold text-white">Member Login</h1>
        <p class="mt-2 text-sm text-goldlight">Access your Givora 3-matrix dashboard & wallets</p>
    </div>

    <div class="bg-darkcard p-8 rounded-2xl gold-border-glow">
        <?php if (!empty($error)): ?>
            <div class="mb-6 p-4 rounded-xl bg-red-900/30 border border-red-500/50 text-red-300 text-sm flex items-center">
                <i class="fas fa-exclamation-circle text-red-400 text-lg mr-3"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Member ID</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gold/70">
                        <i class="fas fa-id-card"></i>
                    </span>
                    <input type="text" name="member_id" required placeholder="e.g., GT100001" value="<?php echo htmlspecialchars($_POST['member_id'] ?? ''); ?>" class="w-full bg-darkbg border border-gold/30 rounded-xl pl-10 pr-4 py-3 text-white uppercase tracking-wider focus:outline-none focus:border-gold">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Password</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gold/70">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" name="password" required placeholder="Enter password" class="w-full bg-darkbg border border-gold/30 rounded-xl pl-10 pr-4 py-3 text-white focus:outline-none focus:border-gold">
                </div>
            </div>

            <button type="submit" class="w-full btn-gold py-3.5 rounded-xl font-bold text-lg shadow-lg">
                Sign In to Dashboard
            </button>
        </form>

        <div class="mt-6 text-center text-sm text-gray-400 border-t border-gold/10 pt-4">
            Don't have an account yet? <a href="/register.php" class="text-gold hover:underline font-semibold">Register here</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
