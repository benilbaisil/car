<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

// Database configuration
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Currency.php';

/**
 * Order class - represents an order entity
 */
class Order {
    private int $id;
    private int $userId;
    private string $userName;
    private string $userEmail;
    private string $status;
    private float $total;
    private string $createdAt;
    
    public function __construct(
        int $id, int $userId, string $userName, string $userEmail,
        string $status, float $total, string $createdAt
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->userName = $userName;
        $this->userEmail = $userEmail;
        $this->status = $status;
        $this->total = $total;
        $this->createdAt = $createdAt;
    }
    
    public function getId(): int { return $this->id; }
    public function getUserId(): int { return $this->userId; }
    public function getUserName(): string { return $this->userName; }
    public function getUserEmail(): string { return $this->userEmail; }
    public function getStatus(): string { return $this->status; }
    public function getTotal(): float { return $this->total; }
    public function getCreatedAt(): string { return $this->createdAt; }
}

/**
 * OrderItem class - represents an item in an order
 */
class OrderItem {
    private int $id;
    private int $orderId;
    private int $productId;
    private string $productName;
    private string $productBrand;
    private int $quantity;
    private float $unitPrice;
    
    public function __construct(
        int $id, int $orderId, int $productId, string $productName,
        string $productBrand, int $quantity, float $unitPrice
    ) {
        $this->id = $id;
        $this->orderId = $orderId;
        $this->productId = $productId;
        $this->productName = $productName;
        $this->productBrand = $productBrand;
        $this->quantity = $quantity;
        $this->unitPrice = $unitPrice;
    }
    
    public function getId(): int { return $this->id; }
    public function getOrderId(): int { return $this->orderId; }
    public function getProductId(): int { return $this->productId; }
    public function getProductName(): string { return $this->productName; }
    public function getProductBrand(): string { return $this->productBrand; }
    public function getQuantity(): int { return $this->quantity; }
    public function getUnitPrice(): float { return $this->unitPrice; }
    public function getSubtotal(): float { return $this->quantity * $this->unitPrice; }
}

/**
 * OrderRepository - handles database operations for orders
 */
class OrderRepository {
    private PDO $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get all orders with user information
     */
    public function getAllOrders(): array {
        $stmt = $this->pdo->query('
            SELECT o.id, o.user_id, o.status, o.total, o.created_at,
                   u.name as user_name, u.email as user_email
            FROM orders o
            JOIN users u ON o.user_id = u.id
            ORDER BY o.created_at DESC
        ');
        
        $orders = [];
        while ($row = $stmt->fetch()) {
            $orders[] = new Order(
                (int)$row['id'],
                (int)$row['user_id'],
                $row['user_name'],
                $row['user_email'],
                $row['status'],
                (float)$row['total'],
                $row['created_at']
            );
        }
        return $orders;
    }
    
    /**
     * Get order by ID
     */
    public function getOrderById(int $id): ?Order {
        $stmt = $this->pdo->prepare('
            SELECT o.id, o.user_id, o.status, o.total, o.created_at,
                   u.name as user_name, u.email as user_email
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.id = ?
        ');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        
        if (!$row) {
            return null;
        }
        
        return new Order(
            (int)$row['id'],
            (int)$row['user_id'],
            $row['user_name'],
            $row['user_email'],
            $row['status'],
            (float)$row['total'],
            $row['created_at']
        );
    }
    
    /**
     * Get order items for a specific order
     */
    public function getOrderItems(int $orderId): array {
        $stmt = $this->pdo->prepare('
            SELECT oi.id, oi.order_id, oi.product_id, oi.quantity, oi.unit_price,
                   p.name as product_name, p.brand as product_brand
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ');
        $stmt->execute([$orderId]);
        
        $items = [];
        while ($row = $stmt->fetch()) {
            $items[] = new OrderItem(
                (int)$row['id'],
                (int)$row['order_id'],
                (int)$row['product_id'],
                $row['product_name'],
                $row['product_brand'],
                (int)$row['quantity'],
                (float)$row['unit_price']
            );
        }
        return $items;
    }
    
    /**
     * Update order status
     */
    public function updateOrderStatus(int $id, string $status): bool {
        $validStatuses = ['pending', 'shipped', 'delivered', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            return false;
        }
        
        $stmt = $this->pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
        return $stmt->execute([$status, $id]);
    }
    
    /**
     * Get order statistics by status
     */
    public function getOrderStatsByStatus(): array {
        $stmt = $this->pdo->query('
            SELECT status, COUNT(*) as count, SUM(total) as revenue
            FROM orders
            GROUP BY status
        ');
        
        $stats = [
            'pending' => ['count' => 0, 'revenue' => 0],
            'shipped' => ['count' => 0, 'revenue' => 0],
            'delivered' => ['count' => 0, 'revenue' => 0],
            'cancelled' => ['count' => 0, 'revenue' => 0]
        ];
        
        while ($row = $stmt->fetch()) {
            $stats[$row['status']] = [
                'count' => (int)$row['count'],
                'revenue' => (float)$row['revenue']
            ];
        }
        
        return $stats;
    }
}

// Initialize repository
$repo = new OrderRepository(Database::getConnection());

// Handle actions
$message = '';
$messageType = 'success';
$viewOrderId = null;
$viewOrderItems = [];

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = (int)$_POST['order_id'];
    $newStatus = $_POST['status'];
    
    if ($repo->updateOrderStatus($orderId, $newStatus)) {
        $message = 'Order status updated successfully';
        $messageType = 'success';
    } else {
        $message = 'Failed to update order status';
        $messageType = 'error';
    }
}

// Handle view details
if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['id'])) {
    $viewOrderId = (int)$_GET['id'];
    $viewOrderItems = $repo->getOrderItems($viewOrderId);
}

// Get all orders
$orders = $repo->getAllOrders();
$orderStats = $repo->getOrderStatsByStatus();
$adminName = $_SESSION['admin']['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - Elite Diecast Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-gray-900 via-gray-800 to-black min-h-screen">
    <!-- Navigation Bar -->
    <nav class="bg-black/40 backdrop-blur-md border-b border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-white">Elite Diecast Admin</h1>
                </div>
                
                <div class="hidden md:flex space-x-4">
                    <a href="dashboard.php" class="text-gray-300 hover:text-white hover:bg-white/10 px-4 py-2 rounded-lg transition">Dashboard</a>
                    <a href="users.php" class="text-gray-300 hover:text-white hover:bg-white/10 px-4 py-2 rounded-lg transition">Users</a>
                    <a href="products.php" class="text-gray-300 hover:text-white hover:bg-white/10 px-4 py-2 rounded-lg transition">Products</a>
                    <a href="orders.php" class="bg-red-600 text-white px-4 py-2 rounded-lg">Orders</a>
                </div>
                
                <div class="flex items-center space-x-4">
                    <span class="text-gray-300 text-sm">Welcome, <?php echo htmlspecialchars($adminName); ?></span>
                    <a href="logout.php" class="bg-red-600/20 hover:bg-red-600 text-red-300 hover:text-white px-4 py-2 rounded-lg transition text-sm">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-white mb-2">Order Management</h2>
            <p class="text-gray-400">View and manage customer orders</p>
        </div>
        
        <!-- Success/Error Message -->
        <?php if ($message): ?>
            <div class="mb-6 px-4 py-3 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-600/20 border border-green-600/50 text-green-300' : 'bg-red-600/20 border border-red-600/50 text-red-300'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Order Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-yellow-600/10 backdrop-blur-sm rounded-xl p-6 border border-yellow-600/30">
                <p class="text-yellow-300 text-sm mb-1">Pending Orders</p>
                <p class="text-3xl font-bold text-white"><?php echo $orderStats['pending']['count']; ?></p>
                <p class="text-yellow-300 text-sm mt-2"><?php echo Currency::format($orderStats['pending']['revenue']); ?></p>
            </div>
            <div class="bg-blue-600/10 backdrop-blur-sm rounded-xl p-6 border border-blue-600/30">
                <p class="text-blue-300 text-sm mb-1">Shipped Orders</p>
                <p class="text-3xl font-bold text-white"><?php echo $orderStats['shipped']['count']; ?></p>
                <p class="text-blue-300 text-sm mt-2"><?php echo Currency::format($orderStats['shipped']['revenue']); ?></p>
            </div>
            <div class="bg-green-600/10 backdrop-blur-sm rounded-xl p-6 border border-green-600/30">
                <p class="text-green-300 text-sm mb-1">Delivered Orders</p>
                <p class="text-3xl font-bold text-white"><?php echo $orderStats['delivered']['count']; ?></p>
                <p class="text-green-300 text-sm mt-2"><?php echo Currency::format($orderStats['delivered']['revenue']); ?></p>
            </div>
            <div class="bg-red-600/10 backdrop-blur-sm rounded-xl p-6 border border-red-600/30">
                <p class="text-red-300 text-sm mb-1">Cancelled Orders</p>
                <p class="text-3xl font-bold text-white"><?php echo $orderStats['cancelled']['count']; ?></p>
                <p class="text-red-300 text-sm mt-2"><?php echo Currency::format($orderStats['cancelled']['revenue']); ?></p>
            </div>
        </div>
        
        <!-- View Order Details Modal -->
        <?php if ($viewOrderId && $viewOrder = $repo->getOrderById($viewOrderId)): ?>
            <div class="mb-6 bg-white/5 backdrop-blur-sm rounded-xl p-6 border border-white/10">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-xl font-bold text-white mb-2">Order #<?php echo $viewOrder->getId(); ?> Details</h3>
                        <p class="text-gray-400">Customer: <?php echo htmlspecialchars($viewOrder->getUserName()); ?> (<?php echo htmlspecialchars($viewOrder->getUserEmail()); ?>)</p>
                        <p class="text-gray-400">Date: <?php echo date('M d, Y H:i', strtotime($viewOrder->getCreatedAt())); ?></p>
                    </div>
                    <a href="orders.php" class="text-gray-400 hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </a>
                </div>
                
                <!-- Order Items -->
                <div class="bg-black/20 rounded-lg p-4 mb-4">
                    <h4 class="text-white font-semibold mb-3">Order Items</h4>
                    <div class="space-y-2">
                        <?php foreach ($viewOrderItems as $item): ?>
                            <div class="flex justify-between items-center bg-white/5 rounded p-3">
                                <div>
                                    <p class="text-white font-medium"><?php echo htmlspecialchars($item->getProductName()); ?></p>
                                    <p class="text-gray-400 text-sm"><?php echo htmlspecialchars($item->getProductBrand()); ?></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-white"><?php echo $item->getQuantity(); ?> × <?php echo Currency::format($item->getUnitPrice()); ?></p>
                                    <p class="text-gray-400 text-sm">= <?php echo Currency::format($item->getSubtotal()); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-4 pt-4 border-t border-white/10 flex justify-between">
                        <p class="text-white font-bold">Total:</p>
                        <p class="text-white font-bold text-xl"><?php echo Currency::format($viewOrder->getTotal()); ?></p>
                    </div>
                </div>
                
                <!-- Update Status Form -->
                <form method="post" class="flex items-end gap-4">
                    <input type="hidden" name="order_id" value="<?php echo $viewOrder->getId(); ?>">
                    <div class="flex-1">
                        <label class="block text-gray-300 mb-2">Update Status</label>
                        <select name="status" class="w-full px-4 py-2 bg-black/30 border border-white/20 rounded-lg text-white focus:outline-none focus:border-red-500">
                            <option value="pending" <?php echo $viewOrder->getStatus() === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="shipped" <?php echo $viewOrder->getStatus() === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="delivered" <?php echo $viewOrder->getStatus() === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $viewOrder->getStatus() === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <button type="submit" name="update_status" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition">
                        Update Status
                    </button>
                </form>
            </div>
        <?php endif; ?>
        
        <!-- Orders Table -->
        <div class="bg-white/5 backdrop-blur-sm rounded-xl overflow-hidden border border-white/10">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-white/5">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Order ID</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Total</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        <?php if (!empty($orders)): ?>
                            <?php foreach ($orders as $order): ?>
                                <tr class="hover:bg-white/5 transition">
                                    <td class="px-6 py-4 text-white font-semibold">#<?php echo $order->getId(); ?></td>
                                    <td class="px-6 py-4">
                                        <p class="text-white"><?php echo htmlspecialchars($order->getUserName()); ?></p>
                                        <p class="text-gray-400 text-sm"><?php echo htmlspecialchars($order->getUserEmail()); ?></p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold
                                            <?php 
                                            echo match($order->getStatus()) {
                                                'pending' => 'bg-yellow-600/20 text-yellow-300 border border-yellow-600/40',
                                                'shipped' => 'bg-blue-600/20 text-blue-300 border border-blue-600/40',
                                                'delivered' => 'bg-green-600/20 text-green-300 border border-green-600/40',
                                                'cancelled' => 'bg-red-600/20 text-red-300 border border-red-600/40',
                                                default => 'bg-gray-600/20 text-gray-300'
                                            };
                                            ?>">
                                            <?php echo ucfirst($order->getStatus()); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-white font-bold"><?php echo Currency::format($order->getTotal()); ?></td>
                                    <td class="px-6 py-4 text-gray-300"><?php echo date('M d, Y', strtotime($order->getCreatedAt())); ?></td>
                                    <td class="px-6 py-4">
                                        <a href="?action=view&id=<?php echo $order->getId(); ?>" 
                                            class="bg-blue-600/20 hover:bg-blue-600 text-blue-300 hover:text-white px-3 py-1 rounded transition text-sm">
                                            View Details
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-400">No orders found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Statistics Summary -->
        <div class="mt-6 bg-white/5 backdrop-blur-sm rounded-xl p-6 border border-white/10">
            <p class="text-gray-300">
                <span class="font-semibold text-white"><?php echo count($orders); ?></span> total orders
            </p>
        </div>
    </main>
</body>
</html>


