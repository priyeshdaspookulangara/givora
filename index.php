<?php
$page_title = "Home - Direct Selling Utility Matrix";
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="relative bg-gradient-to-b from-darkbg via-darkcard to-darkbg pt-16 pb-24 overflow-hidden border-b border-gold/20">
    <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#c5a059_1px,transparent_1px)] [background-size:16px_16px]"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center max-w-3xl mx-auto">
            <span class="inline-block px-4 py-1.5 rounded-full text-xs font-semibold bg-gold/10 text-gold border border-gold/30 mb-6 uppercase tracking-wider">
                Givora Traders LLP Official Network
            </span>
            <h1 class="text-4xl sm:text-6xl font-extrabold text-white tracking-tight leading-tight">
                Empowering Communities, Building Brighter Futures — <span class="gold-gradient-text">Together We Rise.</span>
            </h1>
            <p class="mt-6 text-lg sm:text-xl text-gray-300 leading-relaxed">
                Join India's premier utility direct-selling enterprise. Harness the power of Household Synergy Utility Distribution combined with our revolutionary 3-Matrix compensation model.
            </p>
            <div class="mt-10 flex flex-col sm:flex-row justify-center gap-4">
                <a href="/register.php" class="btn-gold px-8 py-4 rounded-xl text-lg font-bold shadow-lg flex items-center justify-center space-x-2">
                    <span>Get Started Today</span>
                    <i class="fas fa-arrow-right"></i>
                </a>
                <a href="/business_plan.php" class="border border-gold/40 text-gold hover:bg-gold/10 px-8 py-4 rounded-xl text-lg font-semibold transition flex items-center justify-center space-x-2">
                    <i class="fas fa-gem"></i>
                    <span>Explore Business Plan</span>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Quick Overview Cards -->
<section class="py-20 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-16">
        <h2 class="text-3xl font-bold text-white">Choose Your Growth Tier</h2>
        <p class="mt-2 text-gray-400">Flexible entry points designed for high-yield returns and premium utility packages.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Foundation Tier Card -->
        <div class="bg-darkcard rounded-2xl p-8 gold-border-glow flex flex-col justify-between hover:-translate-y-2 transition-all duration-300">
            <div>
                <div class="w-12 h-12 rounded-xl bg-gold/10 flex items-center justify-center text-gold text-2xl mb-6 border border-gold/30">
                    <i class="fas fa-seedling"></i>
                </div>
                <span class="text-xs uppercase tracking-widest text-gold font-bold">Entry Level</span>
                <h3 class="text-2xl font-bold text-white mt-1">Foundation Tier</h3>
                <div class="mt-4 mb-6">
                    <span class="text-4xl font-extrabold gold-gradient-text">₹5,000</span>
                    <span class="text-gray-400 text-sm"> / one-time</span>
                </div>
                <ul class="space-y-3 text-gray-300 text-sm border-t border-gold/10 pt-6">
                    <li class="flex items-center"><i class="fas fa-check-circle text-gold mr-3"></i> Foundation Household Utility Pack</li>
                    <li class="flex items-center"><i class="fas fa-check-circle text-gold mr-3"></i> Direct Referral Bonus Eligibility</li>
                    <li class="flex items-center"><i class="fas fa-check-circle text-gold mr-3"></i> Matrix Level Income P1 Access</li>
                    <li class="flex items-center"><i class="fas fa-check-circle text-gold mr-3"></i> Real-time 60:40 Wallet Split</li>
                </ul>
            </div>
            <div class="mt-8 pt-6 border-t border-gold/10">
                <a href="/register.php?package=Foundation_5000" class="block text-center btn-gold py-3 rounded-xl font-bold">
                    Join Foundation Tier
                </a>
            </div>
        </div>

        <!-- Leadership Tier Card -->
        <div class="bg-gradient-to-b from-darkcard via-darkcard to-gold/10 rounded-2xl p-8 gold-border-glow flex flex-col justify-between relative transform scale-105 shadow-2xl">
            <div class="absolute -top-4 right-6 bg-gold text-darkbg text-xs font-black uppercase px-3 py-1 rounded-full shadow">
                Most Popular
            </div>
            <div>
                <div class="w-12 h-12 rounded-xl bg-gold/20 flex items-center justify-center text-goldlight text-2xl mb-6 border border-gold">
                    <i class="fas fa-crown"></i>
                </div>
                <span class="text-xs uppercase tracking-widest text-goldlight font-bold">Premium Level</span>
                <h3 class="text-2xl font-bold text-white mt-1">Leadership Tier</h3>
                <div class="mt-4 mb-6">
                    <span class="text-4xl font-extrabold gold-gradient-text">₹15,000</span>
                    <span class="text-gray-400 text-sm"> / one-time</span>
                </div>
                <ul class="space-y-3 text-gray-300 text-sm border-t border-gold/10 pt-6">
                    <li class="flex items-center"><i class="fas fa-check-circle text-goldlight mr-3"></i> Premium Leadership Utility Pack</li>
                    <li class="flex items-center"><i class="fas fa-check-circle text-goldlight mr-3"></i> Max Direct Referral Bonus (₹1,500)</li>
                    <li class="flex items-center"><i class="fas fa-check-circle text-goldlight mr-3"></i> Higher Matrix Commission Rates (P2)</li>
                    <li class="flex items-center"><i class="fas fa-check-circle text-goldlight mr-3"></i> Dedicated Priority Support</li>
                </ul>
            </div>
            <div class="mt-8 pt-6 border-t border-gold/10">
                <a href="/register.php?package=Leadership_15000" class="block text-center btn-gold py-3.5 rounded-xl font-bold shadow-lg">
                    Join Leadership Tier
                </a>
            </div>
        </div>

        <!-- Household Synergy Card -->
        <div class="bg-darkcard rounded-2xl p-8 gold-border-glow flex flex-col justify-between hover:-translate-y-2 transition-all duration-300">
            <div>
                <div class="w-12 h-12 rounded-xl bg-gold/10 flex items-center justify-center text-gold text-2xl mb-6 border border-gold/30">
                    <i class="fas fa-box-open"></i>
                </div>
                <span class="text-xs uppercase tracking-widest text-gold font-bold">Product Ecosystem</span>
                <h3 class="text-2xl font-bold text-white mt-1">Household Synergy</h3>
                <p class="mt-4 text-gray-400 text-sm leading-relaxed">
                    High quality, essential utility products distributed directly to households across India. Guaranteed value behind every registration.
                </p>
                <ul class="space-y-3 text-gray-300 text-sm border-t border-gold/10 pt-6 mt-6">
                    <li class="flex items-center"><i class="fas fa-check-circle text-gold mr-3"></i> Curated Utility Kits</li>
                    <li class="flex items-center"><i class="fas fa-check-circle text-gold mr-3"></i> Doorstep Pan-India Delivery</li>
                    <li class="flex items-center"><i class="fas fa-check-circle text-gold mr-3"></i> 100% Value Back Guarantee</li>
                </ul>
            </div>
            <div class="mt-8 pt-6 border-t border-gold/10">
                <a href="/about.php" class="block text-center border border-gold/40 text-gold hover:bg-gold/10 py-3 rounded-xl font-semibold">
                    Learn About Products
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-16 bg-darkcard/50 border-t border-gold/10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
            <div class="p-6">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gold/10 flex items-center justify-center text-gold text-2xl border border-gold/20">
                    <i class="fas fa-sitemap"></i>
                </div>
                <h4 class="text-xl font-bold text-white mb-2">3-Matrix Tree</h4>
                <p class="text-gray-400 text-sm">Automated position distribution filling 3 positions under every parent down through 7 levels.</p>
            </div>
            <div class="p-6">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gold/10 flex items-center justify-center text-gold text-2xl border border-gold/20">
                    <i class="fas fa-wallet"></i>
                </div>
                <h4 class="text-xl font-bold text-white mb-2">60 / 40 Split Wallet</h4>
                <p class="text-gray-400 text-sm">Automated allocation into User Wallet (60% withdrawable) and Company Re-investment Wallet (40%).</p>
            </div>
            <div class="p-6">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gold/10 flex items-center justify-center text-gold text-2xl border border-gold/20">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <h4 class="text-xl font-bold text-white mb-2">Secure ePIN System</h4>
                <p class="text-gray-400 text-sm">Admin generated single-use ePIN authentication ensuring total security and zero fraud.</p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
