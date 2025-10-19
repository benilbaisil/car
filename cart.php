<?php
session_start();
require_once __DIR__ . '/config.php';

// Cart domain class re-used on this page to interact with session
class Cart
{
    private string $sessionKey = 'cart';

    public function __construct()
    {
        if (!isset($_SESSION[$this->sessionKey])) {
            $_SESSION[$this->sessionKey] = ['items' => []];
        }
    }

    public function getItems(): array
    {
        return (array)($_SESSION[$this->sessionKey]['items'] ?? []);
    }

    public function remove(int $productId): void
    {
        if (isset($_SESSION[$this->sessionKey]['items'][$productId])) {
            unset($_SESSION[$this->sessionKey]['items'][$productId]);
        }
    }

    public function clear(): void
    {
        $_SESSION[$this->sessionKey] = ['items' => []];
    }
}

// Handle actions: remove item, clear, or checkout (demo only).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart = new Cart();
    if (isset($_POST['remove_id'])) {
        $cart->remove((int)$_POST['remove_id']);
        header('Location: cart.php');
        exit;
    }
    if (isset($_POST['clear_cart'])) {
        $cart->clear();
        header('Location: cart.php');
        exit;
    }
    if (isset($_POST['checkout'])) {
        // NOTE: For demo, just clear and show a thank you. In production, create order rows.
        $cart->clear();
        $_SESSION['checkout_success'] = true;
        header('Location: cart.php');
        exit;
    }
}

// Fetch product details for items in cart from MySQL
$pdo = Database::getConnection();
$cart = new Cart();
$items = $cart->getItems(); // [productId => quantity]

$products = [];
$total = 0.0;
if (!empty($items)) {
    $ids = array_keys($items);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT id, name, brand, scale, variant, price, image_url FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $products = $stmt->fetchAll();
    foreach ($products as &$p) {
        $pid = (int)$p['id'];
        $qty = (int)($items[$pid] ?? 0);
        $p['quantity'] = $qty;
        $p['subtotal'] = (float)$p['price'] * $qty;
        $total += $p['subtotal'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - Elite Diecast</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900">
    <!-- Header/Nav kept simple; links back to homepage -->
    <nav class="w-full bg-black/95 border-b border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="index.php" class="text-white font-bold">Elite Diecast</a>
            <div class="flex items-center gap-4">
                <a href="index.php" class="text-white hover:text-red-600">Home</a>
                <a href="dashboard.php" class="text-white hover:text-red-600">Dashboard</a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h1 class="text-3xl font-bold text-white mb-6">Your Cart</h1>

        <?php if (!empty($_SESSION['checkout_success'])): ?>
            <div class="mb-6 bg-green-600/20 text-green-300 border border-green-600/40 px-4 py-2 rounded">
                Thank you! Your order has been placed (demo checkout).
            </div>
            <?php unset($_SESSION['checkout_success']); ?>
        <?php endif; ?>

        <?php if (empty($products)): ?>
            <div class="text-gray-300">Your cart is empty. <a href="index.php#inventory" class="text-red-500 hover:text-red-400">Continue shopping</a>.</div>
        <?php else: ?>
            <!-- Cart items table rendered from DB product info and session quantities -->
            <div class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-xl overflow-hidden">
                <table class="min-w-full">
                    <thead class="bg-white/10">
                        <tr>
                            <th class="text-left text-gray-300 px-4 py-3">Product</th>
                            <th class="text-left text-gray-300 px-4 py-3">Details</th>
                            <th class="text-right text-gray-300 px-4 py-3">Price</th>
                            <th class="text-center text-gray-300 px-4 py-3">Qty</th>
                            <th class="text-right text-gray-300 px-4 py-3">Subtotal</th>
                            <th class="text-right text-gray-300 px-4 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                            <tr class="border-t border-white/10">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <img src="<?php echo htmlspecialchars($p['image_url'] ?? ''); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" class="w-16 h-16 object-cover rounded">
                                        <div class="text-white font-semibold"><?php echo htmlspecialchars($p['name']); ?></div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-300">
                                    <div>Brand: <?php echo htmlspecialchars($p['brand']); ?></div>
                                    <div>Scale: <?php echo htmlspecialchars($p['scale']); ?></div>
                                    <?php if (!empty($p['variant'])): ?><div>Variant: <?php echo htmlspecialchars($p['variant']); ?></div><?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-right text-white">$<?php echo number_format((float)$p['price'], 2); ?></td>
                                <td class="px-4 py-3 text-center text-white"><?php echo (int)$p['quantity']; ?></td>
                                <td class="px-4 py-3 text-right text-white">$<?php echo number_format((float)$p['subtotal'], 2); ?></td>
                                <td class="px-4 py-3 text-right">
                                    <form method="post" action="cart.php">
                                        <input type="hidden" name="remove_id" value="<?php echo (int)$p['id']; ?>">
                                        <button class="bg-white/10 hover:bg-red-600 text-white px-3 py-1 rounded" type="submit">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="text-white text-xl">Total: <strong>$<?php echo number_format($total, 2); ?></strong></div>
                <div class="flex gap-3">
                    <form method="post" action="cart.php">
                        <button name="clear_cart" value="1" class="bg-white/10 hover:bg-white/20 text-white px-4 py-2 rounded">Clear Cart</button>
                    </form>
                    <form method="post" action="cart.php">
                        <button name="checkout" value="1" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded font-semibold">Checkout</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>



