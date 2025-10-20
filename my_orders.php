<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

require_once 'config.php';
require_once 'classes/Currency.php';

$user = $_SESSION['user'];
$userId = (int)$_SESSION['user']['id'];

// Get user orders
$orders = [];
try {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("
        SELECT o.id, o.total, o.status, o.created_at,
               COUNT(oi.id) as item_count
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.user_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$userId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Handle error silently
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Elite Diecast</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900">
    <nav class="w-full bg-black/95 border-b border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="index.php" class="text-white font-bold">Elite Diecast</a>
            <div class="flex items-center gap-4">
                <a href="dashboard.php" class="text-white hover:text-red-600">Dashboard</a>
                <a href="index.php" class="text-white hover:text-red-600">Home</a>
                <a href="logout.php" class="text-white hover:text-red-600">Logout</a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h1 class="text-3xl font-bold text-white mb-6">My Orders</h1>

        <?php if (!empty($orders)): ?>
            <div class="space-y-6">
                <?php foreach ($orders as $order): ?>
                    <div class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-xl font-semibold text-white">Order #<?php echo $order['id']; ?></h3>
                                <p class="text-gray-400 text-sm">
                                    <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?>
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-bold text-white"><?php echo Currency::format($order['total']); ?></p>
                                <span class="px-3 py-1 rounded-full text-sm font-semibold 
                                    <?php 
                                    echo match($order['status']) {
                                        'pending' => 'bg-yellow-600/20 text-yellow-300',
                                        'shipped' => 'bg-blue-600/20 text-blue-300',
                                        'delivered' => 'bg-green-600/20 text-green-300',
                                        'cancelled' => 'bg-red-600/20 text-red-300',
                                        default => 'bg-gray-600/20 text-gray-300'
                                    };
                                    ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="text-gray-400 text-sm">
                            <?php echo $order['item_count']; ?> item(s) in this order
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-xl p-8 text-center">
                <h2 class="text-xl font-semibold text-white mb-4">No Orders Yet</h2>
                <p class="text-gray-400 mb-6">You haven't placed any orders yet. Start building your diecast collection!</p>
                <a href="index.php" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg transition">
                    Browse Products
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
