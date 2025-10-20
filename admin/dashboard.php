<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

// Database configuration
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/StockManager.php';
require_once __DIR__ . '/../classes/Currency.php';

/**
 * DashboardStats - handles fetching dashboard statistics
 */
class DashboardStats {
    private PDO $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get total number of users
     */
    public function getTotalUsers(): int {
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM users');
        return (int)$stmt->fetchColumn();
    }
    
    /**
     * Get total number of products
     */
    public function getTotalProducts(): int {
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM products');
        return (int)$stmt->fetchColumn();
    }
    
    /**
     * Get total number of orders
     */
    public function getTotalOrders(): int {
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM orders');
        return (int)$stmt->fetchColumn();
    }
    
    /**
     * Get total revenue from all orders
     */
    public function getTotalRevenue(): float {
        $stmt = $this->pdo->query('SELECT COALESCE(SUM(total), 0) FROM orders WHERE status != "cancelled"');
        return (float)$stmt->fetchColumn();
    }
    
    /**
     * Get low stock products (stock <= 5)
     */
    public function getLowStockProducts(): array {
        $stmt = $this->pdo->query('SELECT name, brand, stock FROM products WHERE stock <= 5 ORDER BY stock ASC LIMIT 5');
        return $stmt->fetchAll();
    }
    
    /**
     * Get stock statistics
     */
    public function getStockStatistics(): array {
        $stmt = $this->pdo->query('
            SELECT 
                COUNT(*) as total_products,
                SUM(stock) as total_stock,
                COUNT(CASE WHEN stock = 0 THEN 1 END) as out_of_stock,
                COUNT(CASE WHEN stock > 0 AND stock <= 5 THEN 1 END) as low_stock,
                COUNT(CASE WHEN stock > 5 THEN 1 END) as in_stock
            FROM products
        ');
        return $stmt->fetch();
    }
    
    /**
     * Get recent orders with user information
     */
    public function getRecentOrders(int $limit = 5): array {
        $stmt = $this->pdo->prepare('
            SELECT o.id, o.total, o.status, o.created_at, u.name as user_name, u.email as user_email
            FROM orders o
            JOIN users u ON o.user_id = u.id
            ORDER BY o.created_at DESC
            LIMIT ?
        ');
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}

// Fetch dashboard statistics
try {
    $stats = new DashboardStats(Database::getConnection());
    $stockManager = new StockManager(Database::getConnection());
    
    $totalUsers = $stats->getTotalUsers();
    $totalProducts = $stats->getTotalProducts();
    $totalOrders = $stats->getTotalOrders();
    $totalRevenue = $stats->getTotalRevenue();
    $lowStockProducts = $stats->getLowStockProducts();
    $recentOrders = $stats->getRecentOrders();
    $stockStats = $stats->getStockStatistics();
} catch (Exception $e) {
    $error = 'Error loading dashboard data';
}

$adminName = $_SESSION['admin']['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Elite Diecast</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-gray-900 via-gray-800 to-black min-h-screen">
    <!-- Navigation Bar -->
    <nav class="bg-black/40 backdrop-blur-md border-b border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-white">Elite Diecast Admin</h1>
                </div>
                
                <!-- Navigation Links -->
                <div class="hidden md:flex space-x-4">
                    <a href="dashboard.php" class="bg-red-600 text-white px-4 py-2 rounded-lg">Dashboard</a>
                    <a href="users.php" class="text-gray-300 hover:text-white hover:bg-white/10 px-4 py-2 rounded-lg transition">Users</a>
                    <a href="products.php" class="text-gray-300 hover:text-white hover:bg-white/10 px-4 py-2 rounded-lg transition">Products</a>
                    <a href="orders.php" class="text-gray-300 hover:text-white hover:bg-white/10 px-4 py-2 rounded-lg transition">Orders</a>
                </div>
                
                <!-- User Menu -->
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
            <h2 class="text-3xl font-bold text-white mb-2">Dashboard Overview</h2>
            <p class="text-gray-400">Welcome back! Here's what's happening with your store.</p>
        </div>
        
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Users -->
            <div class="bg-white/5 backdrop-blur-sm rounded-xl p-6 border border-white/10">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Total Users</p>
                        <p class="text-3xl font-bold text-white"><?php echo $totalUsers; ?></p>
                    </div>
                    <div class="bg-blue-600/20 p-3 rounded-lg">
                        <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <!-- Total Products -->
            <div class="bg-white/5 backdrop-blur-sm rounded-xl p-6 border border-white/10">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Total Products</p>
                        <p class="text-3xl font-bold text-white"><?php echo $totalProducts; ?></p>
                    </div>
                    <div class="bg-green-600/20 p-3 rounded-lg">
                        <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <!-- Total Orders -->
            <div class="bg-white/5 backdrop-blur-sm rounded-xl p-6 border border-white/10">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Total Orders</p>
                        <p class="text-3xl font-bold text-white"><?php echo $totalOrders; ?></p>
                    </div>
                    <div class="bg-purple-600/20 p-3 rounded-lg">
                        <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <!-- Total Revenue -->
            <div class="bg-white/5 backdrop-blur-sm rounded-xl p-6 border border-white/10">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Total Revenue</p>
                        <p class="text-3xl font-bold text-white">₹<?php echo number_format($totalRevenue, 2); ?></p>
                    </div>
                    <div class="bg-red-600/20 p-3 rounded-lg">
                        <svg class="w-8 h-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Stock Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Stock -->
            <div class="bg-white/5 backdrop-blur-sm rounded-xl p-6 border border-white/10">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Total Stock</p>
                        <p class="text-3xl font-bold text-white"><?php echo $stockStats['total_stock'] ?? 0; ?></p>
                    </div>
                    <div class="bg-blue-600/20 p-3 rounded-lg">
                        <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <!-- In Stock -->
            <div class="bg-white/5 backdrop-blur-sm rounded-xl p-6 border border-white/10">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">In Stock</p>
                        <p class="text-3xl font-bold text-green-400"><?php echo $stockStats['in_stock'] ?? 0; ?></p>
                    </div>
                    <div class="bg-green-600/20 p-3 rounded-lg">
                        <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <!-- Low Stock -->
            <div class="bg-white/5 backdrop-blur-sm rounded-xl p-6 border border-white/10">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Low Stock</p>
                        <p class="text-3xl font-bold text-yellow-400"><?php echo $stockStats['low_stock'] ?? 0; ?></p>
                    </div>
                    <div class="bg-yellow-600/20 p-3 rounded-lg">
                        <svg class="w-8 h-8 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <!-- Out of Stock -->
            <div class="bg-white/5 backdrop-blur-sm rounded-xl p-6 border border-white/10">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Out of Stock</p>
                        <p class="text-3xl font-bold text-red-400"><?php echo $stockStats['out_of_stock'] ?? 0; ?></p>
                    </div>
                    <div class="bg-red-600/20 p-3 rounded-lg">
                        <svg class="w-8 h-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Two Column Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Orders -->
            <div class="bg-white/5 backdrop-blur-sm rounded-xl p-6 border border-white/10">
                <h3 class="text-xl font-bold text-white mb-4">Recent Orders</h3>
                <div class="space-y-4">
                    <?php if (!empty($recentOrders)): ?>
                        <?php foreach ($recentOrders as $order): ?>
                            <div class="bg-white/5 rounded-lg p-4 border border-white/10">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <p class="text-white font-semibold">Order #<?php echo $order['id']; ?></p>
                                        <p class="text-gray-400 text-sm"><?php echo htmlspecialchars($order['user_name']); ?></p>
                                    </div>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold
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
                                <div class="flex justify-between items-center">
                                    <p class="text-gray-400 text-sm"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></p>
                                    <p class="text-white font-bold"><?php echo Currency::format($order['total']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-gray-400 text-center py-8">No orders yet</p>
                    <?php endif; ?>
                </div>
                <a href="orders.php" class="block text-center text-red-400 hover:text-red-300 mt-4 font-semibold">
                    View All Orders →
                </a>
            </div>
            
            <!-- Low Stock Alert -->
            <div class="bg-white/5 backdrop-blur-sm rounded-xl p-6 border border-white/10">
                <h3 class="text-xl font-bold text-white mb-4">Low Stock Alert</h3>
                <div class="space-y-4">
                    <?php if (!empty($lowStockProducts)): ?>
                        <?php foreach ($lowStockProducts as $product): ?>
                            <div class="bg-white/5 rounded-lg p-4 border border-white/10">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <p class="text-white font-semibold"><?php echo htmlspecialchars($product['name']); ?></p>
                                        <p class="text-gray-400 text-sm"><?php echo htmlspecialchars($product['brand']); ?></p>
                                    </div>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold
                                        <?php echo $product['stock'] < 5 ? 'bg-red-600/20 text-red-300' : 'bg-yellow-600/20 text-yellow-300'; ?>">
                                        <?php echo $product['stock']; ?> left
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-gray-400 text-center py-8">All products are well stocked</p>
                    <?php endif; ?>
                </div>
                <a href="products.php" class="block text-center text-red-400 hover:text-red-300 mt-4 font-semibold">
                    Manage Products →
                </a>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="mt-8 bg-white/5 backdrop-blur-sm rounded-xl p-6 border border-white/10">
            <h3 class="text-xl font-bold text-white mb-4">Quick Actions</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="products.php?action=add" class="bg-green-600/20 hover:bg-green-600/30 border border-green-600/50 text-green-300 px-6 py-4 rounded-lg transition text-center font-semibold">
                    + Add New Product
                </a>
                <a href="users.php" class="bg-blue-600/20 hover:bg-blue-600/30 border border-blue-600/50 text-blue-300 px-6 py-4 rounded-lg transition text-center font-semibold">
                    View All Users
                </a>
                <a href="orders.php" class="bg-purple-600/20 hover:bg-purple-600/30 border border-purple-600/50 text-purple-300 px-6 py-4 rounded-lg transition text-center font-semibold">
                    Manage Orders
                </a>
            </div>
        </div>
    </main>
</body>
</html>


