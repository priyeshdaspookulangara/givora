<?php
$page_title = "Contact Us - Givora Traders LLP";
require_once __DIR__ . '/includes/header.php';

$success_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success_msg = "Thank you for reaching out! Our support team will get back to you shortly.";
}
?>

<div class="py-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center max-w-3xl mx-auto mb-16">
        <h1 class="text-4xl font-extrabold text-white">Contact Support</h1>
        <p class="mt-4 text-lg text-goldlight">Have questions about ePINs, matrix bonuses, or packages? Get in touch with us.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Contact Information Block -->
        <div class="lg:col-span-1 bg-darkcard p-8 rounded-2xl gold-border-glow space-y-8 flex flex-col justify-between">
            <div>
                <h2 class="text-2xl font-bold text-white mb-6">Get In Touch</h2>
                <div class="space-y-6">
                    <div class="flex items-start space-x-4">
                        <div class="w-10 h-10 rounded-lg bg-gold/10 flex items-center justify-center text-gold text-lg flex-shrink-0 mt-1">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-300">Registered Office Address</h3>
                            <p class="text-sm text-gray-400 mt-1 leading-relaxed">
                                ROOM NO 36/1486 ASHWA ARCADE<br>
                                MARAR ROAD, THRISSUR 1
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start space-x-4">
                        <div class="w-10 h-10 rounded-lg bg-gold/10 flex items-center justify-center text-gold text-lg flex-shrink-0 mt-1">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-300">Email Us</h3>
                            <p class="text-sm text-gray-400 mt-1">support@givoratraders.com</p>
                        </div>
                    </div>

                    <div class="flex items-start space-x-4">
                        <div class="w-10 h-10 rounded-lg bg-gold/10 flex items-center justify-center text-gold text-lg flex-shrink-0 mt-1">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-300">Call Us</h3>
                            <p class="text-sm text-gray-400 mt-1">+91 98765 43210</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-6 border-t border-gold/10">
                <p class="text-xs text-goldlight">
                    <i class="fas fa-clock mr-1"></i> Office Hours: Mon - Sat (9:00 AM - 6:00 PM)
                </p>
            </div>
        </div>

        <!-- Contact Form Block -->
        <div class="lg:col-span-2 bg-darkcard p-8 rounded-2xl gold-border-glow">
            <?php if ($success_msg): ?>
                <div class="mb-6 p-4 rounded-xl bg-green-900/30 border border-green-500/50 text-green-300 text-sm">
                    <i class="fas fa-check-circle mr-2"></i> <?php echo htmlspecialchars($success_msg); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Full Name</label>
                    <input type="text" name="name" required class="w-full bg-darkbg border border-gold/30 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-gold">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Email Address</label>
                    <input type="email" name="email" required class="w-full bg-darkbg border border-gold/30 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-gold">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Subject</label>
                    <input type="text" name="subject" required class="w-full bg-darkbg border border-gold/30 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-gold">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Message</label>
                    <textarea name="message" rows="5" required class="w-full bg-darkbg border border-gold/30 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-gold"></textarea>
                </div>

                <button type="submit" class="w-full btn-gold py-3.5 rounded-xl font-bold text-lg shadow-lg">
                    Submit Message
                </button>
            </form>
        </div>
    </div>

    <!-- Google Map Embed Section -->
    <div class="mt-12 bg-darkcard p-4 sm:p-6 rounded-2xl gold-border-glow overflow-hidden">
        <h2 class="text-xl font-bold text-white mb-4 flex items-center">
            <i class="fas fa-map-marked-alt text-gold mr-2"></i> Our Location on Map
        </h2>
        <div class="w-full rounded-xl overflow-hidden shadow-lg border border-gold/20">
            <iframe src="https://www.google.com/maps/embed?pb=!1m17!1m12!1m3!1d3922.720986111521!2d76.20931927503831!3d10.52262808961113!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m2!1m1!2zMTDCsDMxJzIxLjUiTiA3NsKwMTInNDIuOCJF!5e0!3m2!1sen!2sin!4v1790677404246!5m2!1sen!2sin" class="w-full h-80 sm:h-96" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
