<?php
declare(strict_types=1);

session_start();

// Check admin authentication
if (!isset($_SESSION['admin'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Currency.php';
require_once __DIR__ . '/../classes/PaymentRepository.php';

$admin = $_SESSION['admin'];
$paymentRepo = new PaymentRepository();

// Get all payments
$payments = $paymentRepo->getAllPayments(100, 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Management - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-gray-900 via-black to-gray-900 min-h-screen text-white">
    
    <!-- Navigation -->
    <nav class="bg-black/50 backdrop-blur-sm border-b border-white/10 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="dashboard.php" class="text-2xl font-bold bg-gradient-to-r from-red-600 to-red-400 bg-clip-text text-transparent">
                    Admin Panel
                </a>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-300 hover:text-white transition">Dashboard</a>
                    <a href="users.php" class="text-gray-300 hover:text-white transition">Users</a>
                    <a href="products.php" class="text-gray-300 hover:text-white transition">Products</a>
                    <a href="orders.php" class="text-gray-300 hover:text-white transition">Orders</a>
                    <a href="payments.php" class="text-white font-semibold">Payments</a>
                    <a href="../logout.php" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded transition">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold mb-2">Payment Management</h1>
            <p class="text-gray-400">View and manage all Razorpay transactions</p>
        </div>

        <!-- Payment Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <?php
            $totalPayments = count($payments);
            $successfulPayments = count(array_filter($payments, fn($p) => $p['status'] === 'success'));
            $failedPayments = count(array_filter($payments, fn($p) => $p['status'] === 'failed'));
            $totalAmount = array_reduce(
                array_filter($payments, fn($p) => $p['status'] === 'success'),
                fn($carry, $p) => $carry + (float)$p['amount'],
                0
            );
            ?>
            
            <!-- Total Payments -->
            <div class="bg-white/5 backdrop-blur-sm rounded-xl border border-white/10 p-6">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-gray-400 text-sm">Total Payments</p>
                    <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
                <p class="text-3xl font-bold"><?php echo $totalPayments; ?></p>
            </div>

            <!-- Successful -->
            <div class="bg-white/5 backdrop-blur-sm rounded-xl border border-white/10 p-6">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-gray-400 text-sm">Successful</p>
                    <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-green-400"><?php echo $successfulPayments; ?></p>
            </div>

            <!-- Failed -->
            <div class="bg-white/5 backdrop-blur-sm rounded-xl border border-white/10 p-6">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-gray-400 text-sm">Failed</p>
                    <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-red-400"><?php echo $failedPayments; ?></p>
            </div>

            <!-- Total Revenue -->
            <div class="bg-white/5 backdrop-blur-sm rounded-xl border border-white/10 p-6">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-gray-400 text-sm">Total Revenue</p>
                    <svg class="w-8 h-8 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-yellow-400"><?php echo Currency::format($totalAmount); ?></p>
            </div>
        </div>

        <!-- Payments Table -->
        <div class="bg-white/5 backdrop-blur-sm rounded-xl border border-white/10 overflow-hidden">
            <div class="px-6 py-4 border-b border-white/10">
                <h2 class="text-xl font-bold">All Payments</h2>
            </div>
            
            <?php if (empty($payments)): ?>
                <div class="px-6 py-12 text-center text-gray-400">
                    <p>No payments found</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-white/10">
                            <tr>
                                <th class="text-left px-6 py-3 text-sm font-semibold text-gray-300">Payment ID</th>
                                <th class="text-left px-6 py-3 text-sm font-semibold text-gray-300">User</th>
                                <th class="text-left px-6 py-3 text-sm font-semibold text-gray-300">Order #</th>
                                <th class="text-right px-6 py-3 text-sm font-semibold text-gray-300">Amount</th>
                                <th class="text-center px-6 py-3 text-sm font-semibold text-gray-300">Status</th>
                                <th class="text-left px-6 py-3 text-sm font-semibold text-gray-300">Razorpay Order ID</th>
                                <th class="text-left px-6 py-3 text-sm font-semibold text-gray-300">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10">
                            <?php foreach ($payments as $payment): ?>
                                <tr class="hover:bg-white/5 transition">
                                    <td class="px-6 py-4">
                                        <span class="font-mono text-sm">#<?php echo $payment['id']; ?></span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm">
                                            <div class="font-semibold"><?php echo htmlspecialchars($payment['user_name'] ?? 'N/A'); ?></div>
                                            <div class="text-gray-400"><?php echo htmlspecialchars($payment['user_email'] ?? ''); ?></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="font-mono text-sm">#<?php echo $payment['order_number'] ?? $payment['order_id']; ?></span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <span class="font-bold"><?php echo Currency::format((float)$payment['amount']); ?></span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <?php
                                        $statusColors = [
                                            'success' => 'bg-green-500/20 text-green-400 border-green-500/30',
                                            'failed' => 'bg-red-500/20 text-red-400 border-red-500/30',
                                            'created' => 'bg-blue-500/20 text-blue-400 border-blue-500/30',
                                            'pending' => 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30'
                                        ];
                                        $statusClass = $statusColors[$payment['status']] ?? 'bg-gray-500/20 text-gray-400 border-gray-500/30';
                                        ?>
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold border <?php echo $statusClass; ?>">
                                            <?php echo ucfirst($payment['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="font-mono text-xs text-gray-400"><?php echo htmlspecialchars($payment['razorpay_order_id']); ?></span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm">
                                            <div><?php echo date('M d, Y', strtotime($payment['created_at'])); ?></div>
                                            <div class="text-gray-400 text-xs"><?php echo date('h:i A', strtotime($payment['created_at'])); ?></div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>

</body>
</html>

