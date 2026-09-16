<?php
$page_title = "Business Compensation Plan";
require_once __DIR__ . '/includes/header.php';
?>

<div class="py-12 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center max-w-3xl mx-auto mb-12">
        <h1 class="text-4xl font-extrabold text-white">Business Compensation Plan</h1>
        <p class="mt-4 text-lg text-goldlight">Understand our high-yield 3-Matrix Tree and Direct Referral System.</p>
    </div>

    <!-- Package Overview Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-16">
        <div class="bg-darkcard rounded-2xl p-8 gold-border-glow">
            <h2 class="text-2xl font-bold text-white mb-2">1. Foundation Tier (₹5,000)</h2>
            <p class="text-gray-400 text-sm mb-6">Entry level package designed for fast activation and matrix participation.</p>
            <ul class="space-y-3 text-sm text-gray-300">
                <li class="flex justify-between border-b border-gold/10 pb-2">
                    <span>Direct Referral Bonus:</span>
                    <span class="text-gold font-bold">10% (₹500)</span>
                </li>
                <li class="flex justify-between border-b border-gold/10 pb-2">
                    <span>User Wallet Allocation (60%):</span>
                    <span class="text-green-400 font-bold">₹300</span>
                </li>
                <li class="flex justify-between border-b border-gold/10 pb-2">
                    <span>Company Wallet Allocation (40%):</span>
                    <span class="text-amber-400 font-bold">₹200</span>
                </li>
                <li class="flex justify-between">
                    <span>Matrix Matrix Width / Depth:</span>
                    <span class="text-white font-bold">3 x 7 Matrix</span>
                </li>
            </ul>
        </div>

        <div class="bg-darkcard rounded-2xl p-8 gold-border-glow">
            <h2 class="text-2xl font-bold text-white mb-2">2. Leadership Tier (₹15,000)</h2>
            <p class="text-gray-400 text-sm mb-6">Premium level package offering maximum direct bonuses and accelerated commissions.</p>
            <ul class="space-y-3 text-sm text-gray-300">
                <li class="flex justify-between border-b border-gold/10 pb-2">
                    <span>Direct Referral Bonus:</span>
                    <span class="text-gold font-bold">10% (₹1,500)</span>
                </li>
                <li class="flex justify-between border-b border-gold/10 pb-2">
                    <span>User Wallet Allocation (60%):</span>
                    <span class="text-green-400 font-bold">₹900</span>
                </li>
                <li class="flex justify-between border-b border-gold/10 pb-2">
                    <span>Company Wallet Allocation (40%):</span>
                    <span class="text-amber-400 font-bold">₹600</span>
                </li>
                <li class="flex justify-between">
                    <span>Matrix Matrix Width / Depth:</span>
                    <span class="text-white font-bold">3 x 7 Matrix</span>
                </li>
            </ul>
        </div>
    </div>

    <!-- 3-Matrix Breakdown Table -->
    <div class="bg-darkcard rounded-2xl gold-border-glow p-8 mb-16 overflow-x-auto">
        <h2 class="text-2xl font-bold text-white mb-4">3-Matrix Commission Matrix (Levels 1 to 7)</h2>
        <p class="text-sm text-gray-400 mb-6">Every level fills horizontally up to 3 members per parent node. Commissions are automatically distributed on activation.</p>

        <table class="w-full text-left text-sm text-gray-300">
            <thead class="text-xs uppercase bg-gold/10 text-gold border-b border-gold/20">
                <tr>
                    <th class="px-4 py-3">Level</th>
                    <th class="px-4 py-3">Max Members</th>
                    <th class="px-4 py-3">Comm. %</th>
                    <th class="px-4 py-3">Foundation (₹5k) / Member</th>
                    <th class="px-4 py-3">Leadership (₹15k) / Member</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gold/10">
                <tr>
                    <td class="px-4 py-3 font-semibold text-white">Level 1</td>
                    <td class="px-4 py-3">3</td>
                    <td class="px-4 py-3 text-gold">5.0%</td>
                    <td class="px-4 py-3">₹250 (User: ₹150 / Co: ₹100)</td>
                    <td class="px-4 py-3">₹750 (User: ₹450 / Co: ₹300)</td>
                </tr>
                <tr>
                    <td class="px-4 py-3 font-semibold text-white">Level 2</td>
                    <td class="px-4 py-3">9</td>
                    <td class="px-4 py-3 text-gold">4.0%</td>
                    <td class="px-4 py-3">₹200 (User: ₹120 / Co: ₹80)</td>
                    <td class="px-4 py-3">₹600 (User: ₹360 / Co: ₹240)</td>
                </tr>
                <tr>
                    <td class="px-4 py-3 font-semibold text-white">Level 3</td>
                    <td class="px-4 py-3">27</td>
                    <td class="px-4 py-3 text-gold">3.0%</td>
                    <td class="px-4 py-3">₹150 (User: ₹90 / Co: ₹60)</td>
                    <td class="px-4 py-3">₹450 (User: ₹270 / Co: ₹180)</td>
                </tr>
                <tr>
                    <td class="px-4 py-3 font-semibold text-white">Level 4</td>
                    <td class="px-4 py-3">81</td>
                    <td class="px-4 py-3 text-gold">2.0%</td>
                    <td class="px-4 py-3">₹100 (User: ₹60 / Co: ₹40)</td>
                    <td class="px-4 py-3">₹300 (User: ₹180 / Co: ₹120)</td>
                </tr>
                <tr>
                    <td class="px-4 py-3 font-semibold text-white">Level 5</td>
                    <td class="px-4 py-3">243</td>
                    <td class="px-4 py-3 text-gold">1.5%</td>
                    <td class="px-4 py-3">₹75 (User: ₹45 / Co: ₹30)</td>
                    <td class="px-4 py-3">₹225 (User: ₹135 / Co: ₹90)</td>
                </tr>
                <tr>
                    <td class="px-4 py-3 font-semibold text-white">Level 6</td>
                    <td class="px-4 py-3">729</td>
                    <td class="px-4 py-3 text-gold">1.0%</td>
                    <td class="px-4 py-3">₹50 (User: ₹30 / Co: ₹20)</td>
                    <td class="px-4 py-3">₹150 (User: ₹90 / Co: ₹60)</td>
                </tr>
                <tr>
                    <td class="px-4 py-3 font-semibold text-white">Level 7</td>
                    <td class="px-4 py-3">2,187</td>
                    <td class="px-4 py-3 text-gold">0.5%</td>
                    <td class="px-4 py-3">₹25 (User: ₹15 / Co: ₹10)</td>
                    <td class="px-4 py-3">₹75 (User: ₹45 / Co: ₹30)</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="text-center">
        <a href="/register.php" class="btn-gold px-8 py-4 rounded-xl text-lg font-bold inline-block">
            Register & Activate Account Now
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
