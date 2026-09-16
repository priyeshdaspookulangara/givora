<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$is_admin = isset($_SESSION['admin_id']);
$is_member = isset($_SESSION['member_id']);
$logged_member = null;
if ($is_member) {
    require_once __DIR__ . '/functions.php';
    $logged_member = getLoggedInMember();
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . " - Givora Traders LLP" : "Givora Traders LLP | Direct-Selling & Utility Matrix"; ?></title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        darkbg: '#0f1015',
                        darkcard: '#161822',
                        darkborder: '#2a2d3d',
                        gold: '#c5a059',
                        goldlight: '#f3e5ab',
                        golddark: '#9a7b3b',
                    }
                }
            }
        }
    </script>
    <!-- FontAwesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #0f1015;
            color: #e2e8f0;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }
        .gold-gradient-text {
            background: linear-gradient(135deg, #f3e5ab 0%, #c5a059 50%, #9a7b3b 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .gold-border-glow {
            box-shadow: 0 0 15px rgba(197, 160, 89, 0.2);
            border: 1px solid rgba(197, 160, 89, 0.3);
        }
        .btn-gold {
            background: linear-gradient(135deg, #c5a059 0%, #a6823c 100%);
            color: #0f1015;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-gold:hover {
            background: linear-gradient(135deg, #f3e5ab 0%, #c5a059 100%);
            box-shadow: 0 0 15px rgba(197, 160, 89, 0.4);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between">
    <!-- Navbar -->
    <nav class="bg-darkcard border-b border-gold/20 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <!-- Logo -->
                <div class="flex items-center space-x-3">
                    <a href="/index.php" class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-golddark to-goldlight flex items-center justify-center font-bold text-darkbg text-xl shadow-lg">
                            G
                        </div>
                        <span class="text-2xl font-extrabold gold-gradient-text tracking-wide">GIVORA <span class="text-xs text-gold/70 block tracking-widest font-normal">TRADERS LLP</span></span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="/index.php" class="text-gray-300 hover:text-gold transition font-medium">Home</a>
                    <a href="/business_plan.php" class="text-gray-300 hover:text-gold transition font-medium">Business Plan</a>
                    <a href="/about.php" class="text-gray-300 hover:text-gold transition font-medium">About Us</a>
                    <a href="/contact.php" class="text-gray-300 hover:text-gold transition font-medium">Contact</a>

                    <?php if ($is_admin): ?>
                        <a href="/admin/index.php" class="px-4 py-2 rounded-lg btn-gold flex items-center space-x-2">
                            <i class="fas fa-user-shield"></i>
                            <span>Admin Panel</span>
                        </a>
                        <a href="/logout.php" class="text-red-400 hover:text-red-300 text-sm font-semibold">Logout</a>
                    <?php elseif ($is_member): ?>
                        <a href="/customer/dashboard.php" class="px-4 py-2 rounded-lg btn-gold flex items-center space-x-2">
                            <i class="fas fa-chart-line"></i>
                            <span>Dashboard (<?php echo htmlspecialchars($_SESSION['member_id']); ?>)</span>
                        </a>
                        <a href="/logout.php" class="text-red-400 hover:text-red-300 text-sm font-semibold">Logout</a>
                    <?php else: ?>
                        <a href="/login.php" class="text-gold border border-gold/40 hover:bg-gold/10 px-4 py-2 rounded-lg transition font-medium">Customer Login</a>
                        <a href="/register.php" class="btn-gold px-5 py-2 rounded-lg font-medium shadow-md">Register</a>
                        <a href="/admin_login.php" class="text-xs text-gray-400 hover:text-gold transition"><i class="fas fa-lock"></i> Admin</a>
                    <?php endif; ?>
                </div>

                <!-- Mobile Menu Button -->
                <div class="md:hidden flex items-center">
                    <button id="mobile-menu-btn" class="text-gold focus:outline-none text-2xl">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden bg-darkcard border-b border-gold/20 px-4 pt-2 pb-6 space-y-3">
            <a href="/index.php" class="block text-gray-300 hover:text-gold py-2">Home</a>
            <a href="/business_plan.php" class="block text-gray-300 hover:text-gold py-2">Business Plan</a>
            <a href="/about.php" class="block text-gray-300 hover:text-gold py-2">About Us</a>
            <a href="/contact.php" class="block text-gray-300 hover:text-gold py-2">Contact</a>
            <?php if ($is_admin): ?>
                <a href="/admin/index.php" class="block btn-gold text-center py-2 rounded-lg my-2">Admin Panel</a>
                <a href="/logout.php" class="block text-red-400 py-2">Logout</a>
            <?php elseif ($is_member): ?>
                <a href="/customer/dashboard.php" class="block btn-gold text-center py-2 rounded-lg my-2">Dashboard (<?php echo htmlspecialchars($_SESSION['member_id']); ?>)</a>
                <a href="/logout.php" class="block text-red-400 py-2">Logout</a>
            <?php else: ?>
                <a href="/login.php" class="block text-center border border-gold/40 text-gold py-2 rounded-lg my-2">Customer Login</a>
                <a href="/register.php" class="block btn-gold text-center py-2 rounded-lg my-2">Register</a>
                <a href="/admin_login.php" class="block text-center text-xs text-gray-400 py-2">Admin Portal</a>
            <?php endif; ?>
        </div>
    </nav>
    <script>
        document.getElementById('mobile-menu-btn')?.addEventListener('click', function() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        });
    </script>
    <main class="flex-grow">
