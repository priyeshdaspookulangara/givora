<?php
require_once __DIR__ . '/includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = "Please enter both Username and Password.";
    } else {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? AND password = ?");
        $stmt->execute([$username, $password]);
        $admin = $stmt->fetch();

        if ($admin) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            header("Location: /admin/index.php");
            exit;
        } else {
            $error = "Invalid administrator credentials.";
        }
    }
}

$page_title = "Admin Login - Givora Traders LLP";
require_once __DIR__ . '/includes/header.php';
?>

<div class="py-16 max-w-md mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-8">
        <div class="w-16 h-16 bg-gradient-to-tr from-gold to-yellow-600 rounded-full flex items-center justify-center mx-auto text-darkbg text-2xl font-bold shadow-lg mb-4">
            <i class="fas fa-user-shield"></i>
        </div>
        <h1 class="text-3xl font-extrabold text-white">Administrator Portal</h1>
        <p class="mt-2 text-sm text-goldlight">Secure Admin Access Only</p>
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
                <label class="block text-sm font-medium text-gray-300 mb-2">Admin Username</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gold/70">
                        <i class="fas fa-user-cog"></i>
                    </span>
                    <input type="text" name="username" required placeholder="admin" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" class="w-full bg-darkbg border border-gold/30 rounded-xl pl-10 pr-4 py-3 text-white focus:outline-none focus:border-gold">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Password</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gold/70">
                        <i class="fas fa-key"></i>
                    </span>
                    <input type="password" name="password" required placeholder="••••••••" class="w-full bg-darkbg border border-gold/30 rounded-xl pl-10 pr-4 py-3 text-white focus:outline-none focus:border-gold">
                </div>
            </div>

            <button type="submit" class="w-full btn-gold py-3.5 rounded-xl font-bold text-lg shadow-lg">
                Login to Admin Console
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
