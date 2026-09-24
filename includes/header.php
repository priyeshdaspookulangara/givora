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

$script_uri = $_SERVER['REQUEST_URI'] ?? '';
$is_customer_section = (strpos($script_uri, '/customer/') !== false);
$is_admin_section = (strpos($script_uri, '/admin/') !== false);
$is_sidebar_layout = $is_customer_section || $is_admin_section;

$current_page = basename($_SERVER['PHP_SELF']);
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
        .sidebar-item-active {
            background: rgba(197, 160, 89, 0.15);
            color: #f3e5ab;
            border-left: 4px solid #c5a059;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between">

<?php if ($is_sidebar_layout): ?>
    <!-- Sidebar Layout Container -->
    <div class="min-h-screen flex flex-col md:flex-row bg-darkbg w-full">
        <!-- Sidebar Backdrop for Mobile -->
        <div id="sidebar-backdrop" onclick="toggleSidebar()" class="fixed inset-0 bg-black/70 z-40 hidden md:hidden"></div>

        <!-- Left Sidebar Aside -->
        <aside id="sidebar-menu" class="fixed md:static inset-y-0 left-0 w-64 bg-darkcard border-r border-gold/20 flex flex-col justify-between z-50 transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out flex-shrink-0">
            <div>
                <!-- Brand Logo Header -->
                <div class="h-20 px-6 flex items-center border-b border-gold/20">
                    <a href="/index.php" class="flex items-center space-x-3">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-golddark to-goldlight flex items-center justify-center font-bold text-darkbg text-lg shadow-lg">
                            G
                        </div>
                        <span class="text-xl font-extrabold gold-gradient-text tracking-wide">GIVORA <span class="text-[10px] text-gold/70 block tracking-widest font-normal">TRADERS LLP</span></span>
                    </a>
                </div>

                <!-- User Profile / Admin Badge -->
                <div class="p-4 mx-3 my-4 rounded-xl bg-darkbg/80 border border-gold/20 flex items-center space-x-3">
                    <?php if ($is_customer_section && $logged_member): ?>
                        <div class="w-10 h-10 rounded-full border border-gold bg-darkcard flex items-center justify-center text-gold text-lg font-bold flex-shrink-0 overflow-hidden">
                            <?php if (!empty($logged_member['profile_image'])): ?>
                                <img src="<?php echo htmlspecialchars($logged_member['profile_image']); ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <i class="fas fa-user"></i>
                            <?php endif; ?>
                        </div>
                        <div class="overflow-hidden">
                            <div class="text-sm font-bold text-white truncate"><?php echo htmlspecialchars($logged_member['name']); ?></div>
                            <div class="text-xs text-gold font-mono truncate"><?php echo htmlspecialchars($logged_member['member_id']); ?></div>
                        </div>
                    <?php else: ?>
                        <div class="w-10 h-10 rounded-full border border-gold bg-darkcard flex items-center justify-center text-gold text-lg font-bold flex-shrink-0">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <div>
                            <div class="text-sm font-bold text-white">Admin Master</div>
                            <div class="text-xs text-gold font-mono">Control Console</div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Navigation Sidebar Links -->
                <nav class="px-3 space-y-1">
                    <?php if ($is_customer_section): ?>
                        <a href="/customer/dashboard.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-medium transition text-gray-300 hover:text-gold hover:bg-gold/5 <?php echo $current_page === 'dashboard.php' ? 'sidebar-item-active font-bold' : ''; ?>">
                            <i class="fas fa-chart-line w-5 text-gold"></i>
                            <span>Dashboard</span>
                        </a>
                        <a href="/customer/referrals.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-medium transition text-gray-300 hover:text-gold hover:bg-gold/5 <?php echo $current_page === 'referrals.php' ? 'sidebar-item-active font-bold' : ''; ?>">
                            <i class="fas fa-user-plus w-5 text-gold"></i>
                            <span>Direct Referrals</span>
                        </a>
                        <a href="/customer/matrix_income.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-medium transition text-gray-300 hover:text-gold hover:bg-gold/5 <?php echo $current_page === 'matrix_income.php' ? 'sidebar-item-active font-bold' : ''; ?>">
                            <i class="fas fa-coins w-5 text-gold"></i>
                            <span>Matrix Income</span>
                        </a>
                        <a href="/customer/profile.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-medium transition text-gray-300 hover:text-gold hover:bg-gold/5 <?php echo $current_page === 'profile.php' ? 'sidebar-item-active font-bold' : ''; ?>">
                            <i class="fas fa-user-edit w-5 text-gold"></i>
                            <span>My Profile</span>
                        </a>
                        <a href="/customer/teams.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-medium transition text-gray-300 hover:text-gold hover:bg-gold/5 <?php echo $current_page === 'teams.php' ? 'sidebar-item-active font-bold' : ''; ?>">
                            <i class="fas fa-sitemap w-5 text-gold"></i>
                            <span>My Matrix Team</span>
                        </a>
                        <a href="/customer/pins.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-medium transition text-gray-300 hover:text-gold hover:bg-gold/5 <?php echo $current_page === 'pins.php' ? 'sidebar-item-active font-bold' : ''; ?>">
                            <i class="fas fa-ticket-alt w-5 text-gold"></i>
                            <span>My ePINs</span>
                        </a>
                        <a href="/customer/recharge.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-medium transition text-gray-300 hover:text-gold hover:bg-gold/5 <?php echo $current_page === 'recharge.php' ? 'sidebar-item-active font-bold' : ''; ?>">
                            <i class="fas fa-bolt w-5 text-gold"></i>
                            <span>My Recharge Bundle</span>
                        </a>
                        <a href="/customer/wallet.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-medium transition text-gray-300 hover:text-gold hover:bg-gold/5 <?php echo $current_page === 'wallet.php' ? 'sidebar-item-active font-bold' : ''; ?>">
                            <i class="fas fa-wallet w-5 text-gold"></i>
                            <span>Wallet & Payout</span>
                        </a>
                        <a href="/customer/reports.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-medium transition text-gray-300 hover:text-gold hover:bg-gold/5 <?php echo $current_page === 'reports.php' ? 'sidebar-item-active font-bold' : ''; ?>">
                            <i class="fas fa-file-invoice-dollar w-5 text-gold"></i>
                            <span>Earning Reports</span>
                        </a>
                    <?php else: // Admin Section ?>
                        <a href="/admin/index.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-medium transition text-gray-300 hover:text-gold hover:bg-gold/5 <?php echo $current_page === 'index.php' ? 'sidebar-item-active font-bold' : ''; ?>">
                            <i class="fas fa-tachometer-alt w-5 text-gold"></i>
                            <span>Admin Overview</span>
                        </a>
                        <a href="/admin/members.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-medium transition text-gray-300 hover:text-gold hover:bg-gold/5 <?php echo $current_page === 'members.php' ? 'sidebar-item-active font-bold' : ''; ?>">
                            <i class="fas fa-users w-5 text-gold"></i>
                            <span>Members & Greetings</span>
                        </a>
                        <a href="/admin/epins.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-medium transition text-gray-300 hover:text-gold hover:bg-gold/5 <?php echo $current_page === 'epins.php' ? 'sidebar-item-active font-bold' : ''; ?>">
                            <i class="fas fa-key w-5 text-gold"></i>
                            <span>ePIN Generator</span>
                        </a>
                        <a href="/admin/recharges.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-medium transition text-gray-300 hover:text-gold hover:bg-gold/5 <?php echo $current_page === 'recharges.php' ? 'sidebar-item-active font-bold' : ''; ?>">
                            <i class="fas fa-charging-station w-5 text-gold"></i>
                            <span>Recharge Subscriptions</span>
                        </a>
                        <a href="/admin/wallet.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-medium transition text-gray-300 hover:text-gold hover:bg-gold/5 <?php echo $current_page === 'wallet.php' ? 'sidebar-item-active font-bold' : ''; ?>">
                            <i class="fas fa-money-check-alt w-5 text-gold"></i>
                            <span>Withdrawal Requests</span>
                        </a>
                        <a href="/admin/financials.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-medium transition text-gray-300 hover:text-gold hover:bg-gold/5 <?php echo $current_page === 'financials.php' ? 'sidebar-item-active font-bold' : ''; ?>">
                            <i class="fas fa-balance-scale w-5 text-gold"></i>
                            <span>Financial Master Ledger</span>
                        </a>
                        <a href="/admin/reports.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-medium transition text-gray-300 hover:text-gold hover:bg-gold/5 <?php echo $current_page === 'reports.php' ? 'sidebar-item-active font-bold' : ''; ?>">
                            <i class="fas fa-chart-pie w-5 text-gold"></i>
                            <span>System Reports</span>
                        </a>
                    <?php endif; ?>
                </nav>
            </div>

            <!-- Bottom Actions -->
            <div class="p-4 border-t border-gold/20 space-y-2">
                <a href="/index.php" class="flex items-center space-x-2 px-4 py-2 rounded-lg text-xs font-semibold text-gray-400 hover:text-gold border border-gold/20 hover:bg-gold/10 transition">
                    <i class="fas fa-globe"></i>
                    <span>Visit Public Website</span>
                </a>
                <a href="/logout.php" class="flex items-center space-x-2 px-4 py-2.5 rounded-lg text-xs font-bold text-red-400 hover:bg-red-500/10 transition">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Log Out</span>
                </a>
            </div>
        </aside>

        <!-- Main Content Area with Top Navigation Bar -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top Dashboard Header -->
            <header class="h-20 bg-darkcard border-b border-gold/20 px-4 sm:px-8 flex items-center justify-between sticky top-0 z-30">
                <div class="flex items-center space-x-4">
                    <!-- Mobile Hamburger Button -->
                    <button onclick="toggleSidebar()" class="md:hidden text-gold text-2xl p-2 focus:outline-none">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h2 class="text-lg sm:text-xl font-bold text-white truncate">
                        <?php echo isset($page_title) ? $page_title : "Portal Overview"; ?>
                    </h2>
                </div>

                <div class="flex items-center space-x-4">
                    <a href="/index.php" class="hidden sm:inline-block text-xs font-semibold text-gold border border-gold/30 px-3 py-1.5 rounded-lg hover:bg-gold/10">
                        <i class="fas fa-home mr-1"></i> Home
                    </a>
                    <a href="/logout.php" class="text-xs font-bold text-red-400 border border-red-500/30 px-3 py-1.5 rounded-lg hover:bg-red-500/10">
                        Logout
                    </a>
                </div>
            </header>

            <main class="flex-grow p-4 sm:p-8">
<?php else: ?>
    <!-- Public Navbar for Website Pages -->
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
<?php endif; ?>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar-menu');
    const backdrop = document.getElementById('sidebar-backdrop');
    if (sidebar && backdrop) {
        sidebar.classList.toggle('-translate-x-full');
        backdrop.classList.toggle('hidden');
    }
}
</script>
