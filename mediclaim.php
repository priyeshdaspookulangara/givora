<?php
$page_title = "National Insurance Mediclaim Policy";
require_once __DIR__ . '/includes/header.php';
?>

<div class="py-12 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-16">
    <!-- Hero Banner with White Rounded Logo Container -->
    <div class="bg-gradient-to-r from-blue-950/60 via-darkcard to-gold/10 border border-blue-500/40 rounded-3xl p-8 lg:p-12 gold-border-glow">
        <div class="flex flex-col lg:flex-row items-center justify-between gap-10">
            <!-- White rounded card containing National Insurance Logo -->
            <div class="w-56 h-56 bg-white rounded-3xl p-6 flex items-center justify-center shadow-2xl flex-shrink-0 border-4 border-gold/40 hover:scale-105 transition-transform duration-300">
                <img src="/assets/images/national_insurance_logo.png" alt="National Insurance Company Limited Logo" class="max-w-full max-h-full object-contain rounded-2xl">
            </div>

            <div class="space-y-4 text-center lg:text-left flex-grow">
                <div class="inline-flex items-center gap-2 px-3.5 py-1 bg-blue-500/20 text-blue-300 font-extrabold text-xs rounded-full border border-blue-500/40 uppercase tracking-wider">
                    <i class="fas fa-shield-alt"></i> Official Insurance Co-operation
                </div>
                <h1 class="text-3xl sm:text-5xl font-extrabold text-white tracking-tight leading-tight">
                    National Insurance <span class="gold-gradient-text">Medi Claim Policy</span>
                </h1>
                <p class="text-sm sm:text-base text-gray-300 leading-relaxed max-w-2xl">
                    Givora Traders LLP in official co-operation with <strong>National Insurance Company Limited (NICL)</strong> — Trusted Since 1906 — brings comprehensive Group Health Mediclaim & Cashless Hospitalization Shield to all active members across **EMI Schemes (₹10,000+)** and **3-Matrix Compensation Tiers**.
                </p>

                <div class="pt-2 flex flex-wrap justify-center lg:justify-start gap-4">
                    <a href="/register.php" class="btn-gold px-6 py-3 rounded-xl font-bold text-xs shadow-lg inline-flex items-center space-x-2">
                        <i class="fas fa-user-check"></i>
                        <span>Activate Coverage via Registration</span>
                    </a>
                    <a href="/contact.php" class="border border-blue-500/40 text-blue-300 hover:bg-blue-500/10 px-6 py-3 rounded-xl font-bold text-xs transition inline-flex items-center space-x-2">
                        <i class="fas fa-headset"></i>
                        <span>Insurance Desk Help</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Eligibility & Coverage Overview Grid -->
    <div>
        <div class="text-center max-w-3xl mx-auto mb-12">
            <h2 class="text-3xl font-extrabold text-white">Eligible Schemes & Tiers</h2>
            <p class="mt-2 text-sm text-gray-400">Complimentary Medi Claim coverage is automatically activated for members in the following schemes.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- EMI Schemes (₹10,000 & Above) Card -->
            <div class="bg-darkcard p-8 rounded-2xl gold-border-glow border-t-4 border-t-amber-400 flex flex-col justify-between space-y-6">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-400 text-2xl flex items-center justify-center mb-4">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                    <span class="text-xs uppercase font-extrabold tracking-widest text-amber-400">EMI & Welfare Schemes</span>
                    <h3 class="text-2xl font-bold text-white mt-1">₹10,000+ EMI Schemes</h3>
                    <p class="text-xs text-gray-300 mt-2 leading-relaxed">
                        Automatic activation upon joining any ₹10,000 or custom multiple scheme.
                    </p>

                    <ul class="mt-6 space-y-3 text-xs text-gray-300 border-t border-gold/10 pt-4">
                        <li class="flex items-center"><i class="fas fa-check-circle text-amber-400 mr-2.5"></i> <strong>Progressive EMI Scheme</strong> (10 Installments @ 15%)</li>
                        <li class="flex items-center"><i class="fas fa-check-circle text-amber-400 mr-2.5"></i> <strong>Vidya Vikas Support Program</strong> (Educational Welfare)</li>
                        <li class="flex items-center"><i class="fas fa-check-circle text-amber-400 mr-2.5"></i> <strong>Industrial Gas Package</strong> (6 Refill Cycles)</li>
                        <li class="flex items-center"><i class="fas fa-check-circle text-amber-400 mr-2.5"></i> <strong>Charity Support Package</strong> (Community Fund)</li>
                    </ul>
                </div>

                <div class="p-4 bg-darkbg rounded-xl border border-amber-500/20 text-xs text-amber-300 font-semibold">
                    <i class="fas fa-shield-alt mr-1.5"></i> Includes 10-Cycle Installment Return Protection during emergency hospitalization.
                </div>
            </div>

            <!-- 3-Matrix Plan Tiers Card -->
            <div class="bg-darkcard p-8 rounded-2xl gold-border-glow border-t-4 border-t-gold flex flex-col justify-between space-y-6">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-gold/10 border border-gold/30 text-gold text-2xl flex items-center justify-center mb-4">
                        <i class="fas fa-sitemap"></i>
                    </div>
                    <span class="text-xs uppercase font-extrabold tracking-widest text-gold">Matrix Compensation Plan</span>
                    <h3 class="text-2xl font-bold text-white mt-1">3-Matrix Plan Tiers</h3>
                    <p class="text-xs text-gray-300 mt-2 leading-relaxed">
                        Immediate Group Health protection for active matrix tree members.
                    </p>

                    <ul class="mt-6 space-y-3 text-xs text-gray-300 border-t border-gold/10 pt-4">
                        <li class="flex items-center"><i class="fas fa-check-circle text-gold mr-2.5"></i> <strong>Foundation Tier</strong> (₹5,000 Entry Level)</li>
                        <li class="flex items-center"><i class="fas fa-check-circle text-gold mr-2.5"></i> <strong>Leadership Tier</strong> (₹15,000 Premium Level)</li>
                        <li class="flex items-center"><i class="fas fa-check-circle text-gold mr-2.5"></i> Auto-Upgrading Sum Insured as matrix downline levels fill</li>
                        <li class="flex items-center"><i class="fas fa-check-circle text-gold mr-2.5"></i> Family Floater Extension Option</li>
                    </ul>
                </div>

                <div class="p-4 bg-darkbg rounded-xl border border-gold/20 text-xs text-gold font-semibold">
                    <i class="fas fa-crown mr-1.5"></i> Leadership Tier members receive priority cashless desk authorization.
                </div>
            </div>
        </div>
    </div>

    <!-- Key Features & Policy Highlights -->
    <div class="bg-darkcard p-8 lg:p-12 rounded-3xl gold-border-glow">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <h2 class="text-3xl font-extrabold text-white">Policy Features & Key Highlights</h2>
            <p class="text-xs text-gray-400 mt-2">Underwritten by National Insurance Company Limited (NICL) — A Govt. of India Undertaking.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Feature 1 -->
            <div class="bg-darkbg p-6 rounded-2xl border border-gold/20 space-y-3 hover:border-gold transition">
                <div class="w-12 h-12 rounded-xl bg-blue-500/10 text-blue-400 flex items-center justify-center text-xl font-bold border border-blue-500/30">
                    <i class="fas fa-hospital"></i>
                </div>
                <h3 class="text-lg font-bold text-white">Cashless Hospitalization</h3>
                <p class="text-xs text-gray-400 leading-relaxed">
                    Hassle-free cashless admission across 10,000+ NICL network hospitals nationwide without upfront out-of-pocket medical payments.
                </p>
            </div>

            <!-- Feature 2 -->
            <div class="bg-darkbg p-6 rounded-2xl border border-gold/20 space-y-3 hover:border-gold transition">
                <div class="w-12 h-12 rounded-xl bg-green-500/10 text-green-400 flex items-center justify-center text-xl font-bold border border-green-500/30">
                    <i class="fas fa-ambulance"></i>
                </div>
                <h3 class="text-lg font-bold text-white">Emergency & Surgery Cover</h3>
                <p class="text-xs text-gray-400 leading-relaxed">
                    Full coverage for emergency inpatient treatment, ICU expenses, surgical procedures, doctor fees, and nursing charges.
                </p>
            </div>

            <!-- Feature 3 -->
            <div class="bg-darkbg p-6 rounded-2xl border border-gold/20 space-y-3 hover:border-gold transition">
                <div class="w-12 h-12 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center text-xl font-bold border border-amber-500/30">
                    <i class="fas fa-hand-holding-medical"></i>
                </div>
                <h3 class="text-lg font-bold text-white">Installment Protection</h3>
                <p class="text-xs text-gray-400 leading-relaxed">
                    Guarantees that your 10-installment EMI scheme returns (15% per cycle) continue undisturbed during hospitalization periods.
                </p>
            </div>

            <!-- Feature 4 -->
            <div class="bg-darkbg p-6 rounded-2xl border border-gold/20 space-y-3 hover:border-gold transition">
                <div class="w-12 h-12 rounded-xl bg-purple-500/10 text-purple-400 flex items-center justify-center text-xl font-bold border border-purple-500/30">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h3 class="text-lg font-bold text-white">Govt. Enterprise Trust</h3>
                <p class="text-xs text-gray-400 leading-relaxed">
                    Backing from India's oldest public sector general insurer (NICL, est. 1906) ensuring 100% claim transparency and financial reliability.
                </p>
            </div>

            <!-- Feature 5 -->
            <div class="bg-darkbg p-6 rounded-2xl border border-gold/20 space-y-3 hover:border-gold transition">
                <div class="w-12 h-12 rounded-xl bg-gold/10 text-gold flex items-center justify-center text-xl font-bold border border-gold/30">
                    <i class="fas fa-users-cog"></i>
                </div>
                <h3 class="text-lg font-bold text-white">Family Floater Option</h3>
                <p class="text-xs text-gray-400 leading-relaxed">
                    Flexibility to extend cashless health coverage to immediate family members (spouse and up to 2 dependent children).
                </p>
            </div>

            <!-- Feature 6 -->
            <div class="bg-darkbg p-6 rounded-2xl border border-gold/20 space-y-3 hover:border-gold transition">
                <div class="w-12 h-12 rounded-xl bg-red-500/10 text-red-400 flex items-center justify-center text-xl font-bold border border-red-500/30">
                    <i class="fas fa-clock"></i>
                </div>
                <h3 class="text-lg font-bold text-white">24/7 Claim Assistance</h3>
                <p class="text-xs text-gray-400 leading-relaxed">
                    Dedicated Givora insurance TPA desk & 24/7 helpline to guide you through pre-authorization and claim settlement.
                </p>
            </div>
        </div>
    </div>

    <!-- Step-by-Step Activation Process -->
    <div class="bg-darkcard p-8 rounded-2xl gold-border-glow">
        <h2 class="text-2xl font-bold text-white mb-6 text-center"><i class="fas fa-list-ol text-gold mr-2"></i> How Policy Activation Works</h2>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 text-center">
            <div class="bg-darkbg p-5 rounded-xl border border-gold/20">
                <div class="w-8 h-8 rounded-full bg-gold text-darkbg font-extrabold flex items-center justify-center mx-auto mb-3 text-sm">1</div>
                <h4 class="text-sm font-bold text-white mb-1">Register Account</h4>
                <p class="text-xs text-gray-400">Join Givora Traders using an ePIN for any ₹10,000+ scheme or Matrix Plan.</p>
            </div>

            <div class="bg-darkbg p-5 rounded-xl border border-gold/20">
                <div class="w-8 h-8 rounded-full bg-gold text-darkbg font-extrabold flex items-center justify-center mx-auto mb-3 text-sm">2</div>
                <h4 class="text-sm font-bold text-white mb-1">Submit KYC Details</h4>
                <p class="text-xs text-gray-400">Complete profile details (Aadhaar/PAN) under Customer Profile for policy issuance.</p>
            </div>

            <div class="bg-darkbg p-5 rounded-xl border border-gold/20">
                <div class="w-8 h-8 rounded-full bg-gold text-darkbg font-extrabold flex items-center justify-center mx-auto mb-3 text-sm">3</div>
                <h4 class="text-sm font-bold text-white mb-1">Policy Card Issuance</h4>
                <p class="text-xs text-gray-400">Receive your Group Health Insurance Policy ID & NICL TPA Cashless Card.</p>
            </div>

            <div class="bg-darkbg p-5 rounded-xl border border-gold/20">
                <div class="w-8 h-8 rounded-full bg-gold text-darkbg font-extrabold flex items-center justify-center mx-auto mb-3 text-sm">4</div>
                <h4 class="text-sm font-bold text-white mb-1">24/7 Cashless Access</h4>
                <p class="text-xs text-gray-400">Present card at any network hospital nationwide for immediate cashless admission.</p>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="text-center pt-4">
        <a href="/register.php" class="btn-gold px-10 py-4 rounded-xl text-lg font-extrabold shadow-2xl inline-block">
            Register & Activate Medi Claim Policy Now
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
