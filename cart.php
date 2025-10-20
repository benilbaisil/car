<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/Cart.php';
require_once __DIR__ . '/classes/Currency.php';

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
        // Redirect to address form before checkout
        header('Location: address_form.php');
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

        <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-6 bg-red-600/20 text-red-300 border border-red-600/40 px-4 py-2 rounded">
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
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
                                <td class="px-4 py-3 text-right text-white"><?php echo Currency::format((float)$p['price']); ?></td>
                                <td class="px-4 py-3 text-center text-white"><?php echo (int)$p['quantity']; ?></td>
                                <td class="px-4 py-3 text-right text-white"><?php echo Currency::format((float)$p['subtotal']); ?></td>
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
                <div class="text-white text-xl">Total: <strong><?php echo Currency::format($total); ?></strong></div>
                <div class="flex gap-3">
                    <form method="post" action="cart.php">
                        <button name="clear_cart" value="1" class="bg-white/10 hover:bg-white/20 text-white px-4 py-2 rounded transition">Clear Cart</button>
                    </form>
                    <form method="post" action="cart.php">
                        <button name="checkout" value="1" class="bg-gradient-to-r from-red-600 to-red-500 hover:from-red-700 hover:to-red-600 text-white px-6 py-2 rounded font-semibold transition transform hover:scale-105 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            Proceed to Checkout
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>



