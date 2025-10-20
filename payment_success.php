<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/classes/Currency.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Check if payment success data exists
if (!isset($_SESSION['payment_success'])) {
    header('Location: index.php');
    exit;
}

$paymentData = $_SESSION['payment_success'];
unset($_SESSION['payment_success']); // Clear after reading
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - Elite Diecast</title>
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
        
        <!-- Success Animation Container -->
        <div class="text-center mb-8">
            <!-- Success Checkmark -->
            <div class="inline-flex items-center justify-center w-24 h-24 bg-green-500/20 rounded-full border-4 border-green-500 mb-6">
                <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            
            <h1 class="text-4xl font-bold mb-2 text-green-500">Payment Successful!</h1>
            <p class="text-xl text-gray-400">Thank you for your purchase</p>
        </div>

        <!-- Payment Details Card -->
        <div class="bg-white/5 backdrop-blur-sm rounded-xl border border-white/10 p-8 mb-6">
            <h2 class="text-2xl font-bold mb-6 flex items-center">
                <svg class="w-6 h-6 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Order Confirmed
            </h2>
            
            <div class="space-y-4">
                <!-- Order ID -->
                <div class="flex justify-between items-center pb-3 border-b border-white/10">
                    <span class="text-gray-400">Order ID</span>
                    <span class="font-mono font-bold text-green-400">#<?php echo htmlspecialchars((string)$paymentData['order_id']); ?></span>
                </div>
                
                <!-- Payment ID -->
                <div class="flex justify-between items-center pb-3 border-b border-white/10">
                    <span class="text-gray-400">Payment ID</span>
                    <span class="font-mono text-sm text-gray-300"><?php echo htmlspecialchars((string)$paymentData['payment_id']); ?></span>
                </div>
                
                <!-- Amount Paid -->
                <div class="flex justify-between items-center pb-3 border-b border-white/10">
                    <span class="text-gray-400">Amount Paid</span>
                    <span class="text-2xl font-bold text-green-400"><?php echo Currency::format((float)$paymentData['amount']); ?></span>
                </div>
                
                <!-- Status -->
                <div class="flex justify-between items-center pb-3">
                    <span class="text-gray-400">Status</span>
                    <span class="bg-green-500/20 text-green-400 px-4 py-1 rounded-full text-sm font-semibold">Paid</span>
                </div>
            </div>
        </div>

        <!-- What's Next Section -->
        <div class="bg-blue-500/10 border border-blue-500/30 rounded-xl p-6 mb-6">
            <h3 class="text-lg font-bold mb-3 flex items-center text-blue-400">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                What's Next?
            </h3>
            <ul class="space-y-2 text-gray-300">
                <li class="flex items-start">
                    <svg class="w-5 h-5 mr-2 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span>You'll receive an order confirmation email shortly</span>
                </li>
                <li class="flex items-start">
                    <svg class="w-5 h-5 mr-2 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span>Your order will be processed within 24 hours</span>
                </li>
                <li class="flex items-start">
                    <svg class="w-5 h-5 mr-2 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span>Track your order from your dashboard</span>
                </li>
            </ul>
        </div>

        <!-- Action Buttons -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <a href="dashboard.php" class="bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 text-white font-bold py-3 px-6 rounded-lg transition transform hover:scale-105 text-center flex items-center justify-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                View My Orders
            </a>
            <a href="index.php" class="bg-white/10 hover:bg-white/20 text-white font-bold py-3 px-6 rounded-lg transition text-center flex items-center justify-center border border-white/20">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                Continue Shopping
            </a>
        </div>

        <!-- Need Help -->
        <div class="mt-8 text-center text-gray-400 text-sm">
            <p>Need help? <a href="index.php#contact" class="text-red-400 hover:text-red-300">Contact our support team</a></p>
        </div>

    </div>

</body>
</html>

