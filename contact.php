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

    <div class="max-w-2xl mx-auto bg-darkcard p-8 rounded-2xl gold-border-glow">
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
