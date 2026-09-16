    </main>
<?php if ($is_sidebar_layout): ?>
            <!-- Dashboard Sidebar Footer -->
            <footer class="bg-darkcard border-t border-gold/10 px-6 py-4 text-xs text-gray-500 flex flex-col sm:flex-row justify-between items-center gap-2">
                <p>&copy; <?php echo date('Y'); ?> Givora Traders LLP. All rights reserved.</p>
                <p>3-Matrix Compensation Portal</p>
            </footer>
        </div>
    </div>
<?php else: ?>
    <!-- Public Footer -->
    <footer class="bg-darkcard border-t border-gold/20 mt-16 text-gray-400">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-golddark to-goldlight flex items-center justify-center font-bold text-darkbg text-lg shadow">
                            G
                        </div>
                        <span class="text-xl font-bold gold-gradient-text tracking-wide">GIVORA TRADERS</span>
                    </div>
                    <p class="text-sm text-gray-400 leading-relaxed">
                        Empowering Communities, Building Brighter Futures — Together We Rise. High utility direct distribution backed by structured 3-matrix compensation.
                    </p>
                </div>

                <div>
                    <h3 class="text-gold font-semibold text-lg mb-4">Quick Links</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="/index.php" class="hover:text-gold transition">Home</a></li>
                        <li><a href="/business_plan.php" class="hover:text-gold transition">Business Compensation Plan</a></li>
                        <li><a href="/about.php" class="hover:text-gold transition">About Givora Traders</a></li>
                        <li><a href="/contact.php" class="hover:text-gold transition">Contact Support</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-gold font-semibold text-lg mb-4">Portals</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="/login.php" class="hover:text-gold transition"><i class="fas fa-user-circle text-gold/70 mr-2"></i>Member Login</a></li>
                        <li><a href="/register.php" class="hover:text-gold transition"><i class="fas fa-user-plus text-gold/70 mr-2"></i>New Member Register</a></li>
                        <li><a href="/admin_login.php" class="hover:text-gold transition"><i class="fas fa-user-shield text-gold/70 mr-2"></i>Administrator Portal</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-gold font-semibold text-lg mb-4">Contact Info</h3>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><i class="fas fa-map-marker-alt text-gold mr-2"></i> Givora Traders LLP HQ, India</li>
                        <li><i class="fas fa-envelope text-gold mr-2"></i> support@givoratraders.com</li>
                        <li><i class="fas fa-phone-alt text-gold mr-2"></i> +91 98765 43210</li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gold/10 mt-8 pt-6 flex flex-col md:flex-row justify-between items-center text-xs text-gray-500">
                <p>&copy; <?php echo date('Y'); ?> Givora Traders LLP. All rights reserved.</p>
                <p class="mt-2 md:mt-0">Built with High Performance 3-Matrix Direct-Selling System.</p>
            </div>
        </div>
    </footer>
<?php endif; ?>
</body>
</html>
