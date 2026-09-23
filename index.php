<?php
$page_title = "Home - Direct Selling Utility Matrix";
require_once __DIR__ . '/includes/header.php';
?>

<!-- Swiper CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

<!-- Hero Carousel Section -->
<section class="relative bg-darkbg overflow-hidden border-b border-gold/20">
    <div class="swiper heroSwiper">
        <div class="swiper-wrapper">
            <!-- Slide 1: General Vision -->
            <div class="swiper-slide py-20 lg:py-28 bg-gradient-to-b from-darkbg via-darkcard to-darkbg relative">
                <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#c5a059_1px,transparent_1px)] [background-size:16px_16px]"></div>
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center max-w-4xl">
                    <span class="inline-block px-4 py-1.5 rounded-full text-xs font-semibold bg-gold/10 text-gold border border-gold/30 mb-6 uppercase tracking-wider">
                        Givora Traders LLP Official Enterprise
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

            <!-- Slide 2: Household Synergy Utility -->
            <div class="swiper-slide py-20 lg:py-28 bg-gradient-to-r from-darkbg via-gold/10 to-darkbg relative">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center max-w-4xl">
                    <span class="inline-block px-4 py-1.5 rounded-full text-xs font-semibold bg-gold/20 text-goldlight border border-gold/40 mb-6 uppercase tracking-wider">
                        Essential Utility Packs
                    </span>
                    <h1 class="text-4xl sm:text-6xl font-extrabold text-white tracking-tight leading-tight">
                        Household Synergy <span class="gold-gradient-text">Utility Ecosystem</span>
                    </h1>
                    <p class="mt-6 text-lg sm:text-xl text-gray-300 leading-relaxed">
                        High-quality, daily-use household products delivered straight to your doorstep across India with 100% value assurance on every package tier.
                    </p>
                    <div class="mt-10 flex flex-col sm:flex-row justify-center gap-4">
                        <a href="/about.php" class="btn-gold px-8 py-4 rounded-xl text-lg font-bold shadow-lg flex items-center justify-center space-x-2">
                            <span>Discover Products</span>
                            <i class="fas fa-box-open"></i>
                        </a>
                        <a href="/register.php?package=Foundation_5000" class="border border-gold/40 text-gold hover:bg-gold/10 px-8 py-4 rounded-xl text-lg font-semibold transition flex items-center justify-center space-x-2">
                            <span>Join ₹5,000 Tier</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Slide 3: 3-Matrix Tree & 60:40 Wallet Split -->
            <div class="swiper-slide py-20 lg:py-28 bg-gradient-to-b from-darkcard via-darkbg to-darkcard relative">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center max-w-4xl">
                    <span class="inline-block px-4 py-1.5 rounded-full text-xs font-semibold bg-gold/10 text-gold border border-gold/30 mb-6 uppercase tracking-wider">
                        3x7 Matrix Compensation
                    </span>
                    <h1 class="text-4xl sm:text-6xl font-extrabold text-white tracking-tight leading-tight">
                        Automated <span class="gold-gradient-text">60:40 Wallet Split</span> Income
                    </h1>
                    <p class="mt-6 text-lg sm:text-xl text-gray-300 leading-relaxed">
                        Instant direct referral bonuses and 7-level matrix payouts automatically routed into 60% withdrawable user wallet & 40% company reserve.
                    </p>
                    <div class="mt-10 flex flex-col sm:flex-row justify-center gap-4">
                        <a href="/register.php?package=Leadership_15000" class="btn-gold px-8 py-4 rounded-xl text-lg font-bold shadow-lg flex items-center justify-center space-x-2">
                            <span>Join Leadership ₹15,000</span>
                            <i class="fas fa-crown"></i>
                        </a>
                        <a href="/login.php" class="border border-gold/40 text-gold hover:bg-gold/10 px-8 py-4 rounded-xl text-lg font-semibold transition flex items-center justify-center space-x-2">
                            <span>Member Dashboard</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Carousel Pagination & Navigation -->
        <div class="swiper-pagination !bottom-6"></div>
        <div class="swiper-button-next !text-gold !w-12 !h-12 bg-darkcard/80 border border-gold/30 rounded-full after:!text-xl hover:bg-gold hover:!text-darkbg transition"></div>
        <div class="swiper-button-prev !text-gold !w-12 !h-12 bg-darkcard/80 border border-gold/30 rounded-full after:!text-xl hover:bg-gold hover:!text-darkbg transition"></div>
    </div>
</section>

<!-- Quick Overview Cards -->
<section class="py-20 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-16">
        <h2 class="text-3xl font-bold text-white">Matrix Compensation & Standalone Utility Packages</h2>
        <p class="mt-2 text-gray-400">Choose between high-payout matrix packages or standalone utility subscriptions.</p>
    </div>

    <!-- Matrix Packages -->
    <h3 class="text-xl font-bold text-gold border-b border-gold/20 pb-3 mb-8"><i class="fas fa-sitemap mr-2"></i> 3-Matrix Earnings Compensation Tiers</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-16">
        <!-- Foundation Tier Card -->
        <div class="bg-darkcard rounded-2xl p-8 gold-border-glow flex flex-col justify-between hover:-translate-y-2 transition-all duration-300">
            <div>
                <div class="w-12 h-12 rounded-xl bg-gold/10 flex items-center justify-center text-gold text-2xl mb-6 border border-gold/30">
                    <i class="fas fa-seedling"></i>
                </div>
                <span class="text-xs uppercase tracking-widest text-gold font-bold">Phase 1 Entry</span>
                <h3 class="text-2xl font-bold text-white mt-1">Foundation Tier</h3>
                <div class="mt-4 mb-6">
                    <span class="text-4xl font-extrabold gold-gradient-text">₹5,000</span>
                    <span class="text-gray-400 text-sm"> / one-time</span>
                </div>
                <ul class="space-y-3 text-gray-300 text-sm border-t border-gold/10 pt-6">
                    <li class="flex items-center"><i class="fas fa-check-circle text-gold mr-3"></i> 100% Direct Referral Bonus (₹500 per member)</li>
                    <li class="flex items-center"><i class="fas fa-check-circle text-gold mr-3"></i> 6-Level Phase 1 Fixed Commissions (₹150 to ₹1,000)</li>
                    <li class="flex items-center"><i class="fas fa-check-circle text-gold mr-3"></i> 60:40 User/Company Split on Matrix Earnings</li>
                    <li class="flex items-center"><i class="fas fa-check-circle text-gold mr-3"></i> Auto-Promotion to Phase 2 upon 6 levels completion</li>
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
                <span class="text-xs uppercase tracking-widest text-goldlight font-bold">High Yield Level</span>
                <h3 class="text-2xl font-bold text-white mt-1">Leadership Tier</h3>
                <div class="mt-4 mb-6">
                    <span class="text-4xl font-extrabold gold-gradient-text">₹15,000</span>
                    <span class="text-gray-400 text-sm"> / one-time</span>
                </div>
                <ul class="space-y-3 text-gray-300 text-sm border-t border-gold/10 pt-6">
                    <li class="flex items-center"><i class="fas fa-check-circle text-goldlight mr-3"></i> Premium Direct Referral Bonus (₹1,500 per member)</li>
                    <li class="flex items-center"><i class="fas fa-check-circle text-goldlight mr-3"></i> Accelerated Matrix Level Earnings</li>
                    <li class="flex items-center"><i class="fas fa-check-circle text-goldlight mr-3"></i> Full 3-Matrix Auto-Placement Support</li>
                    <li class="flex items-center"><i class="fas fa-check-circle text-goldlight mr-3"></i> Dedicated Priority Support</li>
                </ul>
            </div>
            <div class="mt-8 pt-6 border-t border-gold/10">
                <a href="/register.php?package=Leadership_15000" class="block text-center btn-gold py-3.5 rounded-xl font-bold shadow-lg">
                    Join Leadership Tier
                </a>
            </div>
        </div>
    </div>

    <!-- Standalone Utility Packages -->
    <h3 class="text-xl font-bold text-gold border-b border-gold/20 pb-3 mb-8"><i class="fas fa-bolt mr-2"></i> Standalone Utility Packages (Non-Matrix)</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Mobile Recharge Package -->
        <div class="bg-darkcard rounded-2xl p-8 gold-border-glow flex flex-col justify-between hover:-translate-y-2 transition-all duration-300">
            <div>
                <div class="w-12 h-12 rounded-xl bg-gold/10 flex items-center justify-center text-gold text-2xl mb-6 border border-gold/30">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <span class="text-xs uppercase tracking-widest text-gold font-bold">Utility Package</span>
                <h3 class="text-2xl font-bold text-white mt-1">Mobile Recharge</h3>
                <div class="mt-4 mb-6">
                    <span class="text-4xl font-extrabold gold-gradient-text">₹1,200</span>
                    <span class="text-gray-400 text-sm"> / 6 terms</span>
                </div>
                <ul class="space-y-3 text-gray-300 text-sm border-t border-gold/10 pt-6">
                    <li class="flex items-center"><i class="fas fa-check-circle text-gold mr-3"></i> 2 Mobile Numbers Included</li>
                    <li class="flex items-center"><i class="fas fa-check-circle text-gold mr-3"></i> 6 Terms every 28 days</li>
                    <li class="flex items-center"><i class="fas fa-check-circle text-gold mr-3"></i> Major Carriers (Jio, Airtel, Vi, BSNL)</li>
                    <li class="flex items-center"><i class="fas fa-check-circle text-gold mr-3"></i> Separate from Matrix Tree</li>
                </ul>
            </div>
            <div class="mt-8 pt-6 border-t border-gold/10">
                <a href="/register.php" class="block text-center border border-gold/40 text-gold hover:bg-gold/10 py-3 rounded-xl font-semibold">
                    Select Mobile Plan
                </a>
            </div>
        </div>

        <!-- Gas Connection Package -->
        <div class="bg-darkcard rounded-2xl p-8 gold-border-glow flex flex-col justify-between hover:-translate-y-2 transition-all duration-300">
            <div>
                <div class="w-12 h-12 rounded-xl bg-gold/10 flex items-center justify-center text-gold text-2xl mb-6 border border-gold/30">
                    <i class="fas fa-fire"></i>
                </div>
                <span class="text-xs uppercase tracking-widest text-gold font-bold">Utility Package</span>
                <h3 class="text-2xl font-bold text-white mt-1">Gas Refill Service</h3>
                <div class="mt-4 mb-6">
                    <span class="text-4xl font-extrabold gold-gradient-text">₹3,000</span>
                    <span class="text-gray-400 text-sm"> / 6 terms</span>
                </div>
                <ul class="space-y-3 text-gray-300 text-sm border-t border-gold/10 pt-6">
                    <li class="flex items-center"><i class="fas fa-check-circle text-gold mr-3"></i> Indian Gas Refill Connection</li>
                    <li class="flex items-center"><i class="fas fa-check-circle text-gold mr-3"></i> 6 Refill Terms on Demand</li>
                    <li class="flex items-center"><i class="fas fa-check-circle text-gold mr-3"></i> Indane, Bharat, & HP Gas Support</li>
                    <li class="flex items-center"><i class="fas fa-check-circle text-gold mr-3"></i> Separate from Matrix Tree</li>
                </ul>
            </div>
            <div class="mt-8 pt-6 border-t border-gold/10">
                <a href="/register.php" class="block text-center border border-gold/40 text-gold hover:bg-gold/10 py-3 rounded-xl font-semibold">
                    Select Gas Plan
                </a>
            </div>
        </div>

        <!-- Recharge Bundle Combo -->
        <div class="bg-darkcard rounded-2xl p-8 gold-border-glow flex flex-col justify-between hover:-translate-y-2 transition-all duration-300">
            <div>
                <div class="w-12 h-12 rounded-xl bg-gold/10 flex items-center justify-center text-gold text-2xl mb-6 border border-gold/30">
                    <i class="fas fa-bolt"></i>
                </div>
                <span class="text-xs uppercase tracking-widest text-gold font-bold">Combo Utility</span>
                <h3 class="text-2xl font-bold text-white mt-1">Recharge Combo</h3>
                <div class="mt-4 mb-6">
                    <span class="text-4xl font-extrabold gold-gradient-text">₹5,400</span>
                    <span class="text-gray-400 text-sm"> / 6 terms</span>
                </div>
                <ul class="space-y-3 text-gray-300 text-sm border-t border-gold/10 pt-6">
                    <li class="flex items-center"><i class="fas fa-check-circle text-gold mr-3"></i> 2 Mobile Numbers + 1 Gas Connection</li>
                    <li class="flex items-center"><i class="fas fa-check-circle text-gold mr-3"></i> 6 Terms Mobile + 6 Refill Terms</li>
                    <li class="flex items-center"><i class="fas fa-check-circle text-gold mr-3"></i> Combined Utility Discount Plan</li>
                    <li class="flex items-center"><i class="fas fa-check-circle text-gold mr-3"></i> Separate from Matrix Tree</li>
                </ul>
            </div>
            <div class="mt-8 pt-6 border-t border-gold/10">
                <a href="/register.php" class="block text-center border border-gold/40 text-gold hover:bg-gold/10 py-3 rounded-xl font-semibold">
                    Select Combo Plan
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

<!-- Swiper JS Script -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        var swiper = new Swiper(".heroSwiper", {
            loop: true,
            autoplay: {
                delay: 4500,
                disableOnInteraction: false,
            },
            pagination: {
                el: ".swiper-pagination",
                clickable: true,
            },
            navigation: {
                nextEl: ".swiper-button-next",
                prevEl: ".swiper-button-prev",
            },
            effect: "fade",
            fadeEffect: {
                crossFade: true
            }
        });
    });
</script>

<!-- Swiper Pagination Custom Styling -->
<style>
    .swiper-pagination-bullet {
        background: #c5a059 !important;
        opacity: 0.4;
        width: 12px;
        height: 12px;
    }
    .swiper-pagination-bullet-active {
        opacity: 1;
        width: 28px;
        border-radius: 6px;
        background: #f3e5ab !important;
    }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
