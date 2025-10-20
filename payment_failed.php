<?php
declare(strict_types=1);

session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$orderId = isset($_GET['order_id']) ? htmlspecialchars($_GET['order_id']) : 'N/A';
$errorMessage = isset($_SESSION['payment_error']) ? $_SESSION['payment_error'] : 'Payment was cancelled or failed.';
unset($_SESSION['payment_error']); // Clear after reading
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed - Elite Diecast</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-gray-900 via-black to-gray-900 min-h-screen text-white">
    
    <!-- Navigation -->
    <nav class="bg-black/50 backdrop-blur-sm border-b border-white/10 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="index.php" class="text-2xl font-bold bg-gradient-to-r from-red-600 to-red-400 bg-clip-text text-transparent">
                    Elite Diecast
                </a>
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="text-gray-300 hover:text-white transition">Home</a>
                    <a href="dashboard.php" class="text-gray-300 hover:text-white transition">Dashboard</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-2xl mx-auto px-4 py-16">
        
        <!-- Failed Icon -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-24 h-24 bg-red-500/20 rounded-full border-4 border-red-500 mb-6">
                <svg class="w-12 h-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </div>
            
            <h1 class="text-4xl font-bold mb-2 text-red-500">Payment Failed</h1>
            <p class="text-xl text-gray-400">Something went wrong with your payment</p>
        </div>

        <!-- Error Details Card -->
        <div class="bg-white/5 backdrop-blur-sm rounded-xl border border-white/10 p-8 mb-6">
            <h2 class="text-2xl font-bold mb-6 flex items-center">
                <svg class="w-6 h-6 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Payment Details
            </h2>
            
            <div class="space-y-4">
                <!-- Order ID -->
                <?php if ($orderId !== 'N/A'): ?>
                <div class="flex justify-between items-center pb-3 border-b border-white/10">
                    <span class="text-gray-400">Order ID</span>
                    <span class="font-mono text-sm text-gray-300"><?php echo $orderId; ?></span>
                </div>
                <?php endif; ?>
                
                <!-- Status -->
                <div class="flex justify-between items-center pb-3 border-b border-white/10">
                    <span class="text-gray-400">Status</span>
                    <span class="bg-red-500/20 text-red-400 px-4 py-1 rounded-full text-sm font-semibold">Failed</span>
                </div>
                
                <!-- Error Message -->
                <div class="bg-red-500/10 border border-red-500/30 rounded-lg p-4">
                    <p class="text-red-300 text-sm"><?php echo htmlspecialchars($errorMessage); ?></p>
                </div>
            </div>
        </div>

        <!-- Reasons Section -->
        <div class="bg-yellow-500/10 border border-yellow-500/30 rounded-xl p-6 mb-6">
            <h3 class="text-lg font-bold mb-3 flex items-center text-yellow-400">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Common Reasons for Payment Failure
            </h3>
            <ul class="space-y-2 text-gray-300 text-sm">
                <li class="flex items-start">
                    <svg class="w-5 h-5 mr-2 text-yellow-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <span>Insufficient balance in your account</span>
                </li>
                <li class="flex items-start">
                    <svg class="w-5 h-5 mr-2 text-yellow-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <span>Incorrect card details or OTP</span>
                </li>
                <li class="flex items-start">
                    <svg class="w-5 h-5 mr-2 text-yellow-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <span>Payment gateway timeout or network issue</span>
                </li>
                <li class="flex items-start">
                    <svg class="w-5 h-5 mr-2 text-yellow-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <span>Transaction cancelled by user</span>
                </li>
                <li class="flex items-start">
                    <svg class="w-5 h-5 mr-2 text-yellow-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <span>Daily transaction limit exceeded</span>
                </li>
            </ul>
        </div>

        <!-- Action Buttons -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <a href="checkout.php" class="bg-gradient-to-r from-red-600 to-red-500 hover:from-red-700 hover:to-red-600 text-white font-bold py-3 px-6 rounded-lg transition transform hover:scale-105 text-center flex items-center justify-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Try Again
            </a>
            <a href="cart.php" class="bg-white/10 hover:bg-white/20 text-white font-bold py-3 px-6 rounded-lg transition text-center flex items-center justify-center border border-white/20">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                Back to Cart
            </a>
        </div>

        <!-- Need Help -->
        <div class="mt-8 text-center text-gray-400 text-sm">
            <p>Still facing issues? <a href="index.php#contact" class="text-red-400 hover:text-red-300">Contact our support team</a></p>
        </div>

    </div>

</body>
</html>

