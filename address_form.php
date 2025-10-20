<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/Cart.php';
require_once __DIR__ . '/classes/Currency.php';
require_once __DIR__ . '/classes/Address.php';
require_once __DIR__ . '/classes/AddressRepository.php';
require_once __DIR__ . '/classes/AddressService.php';

$user = $_SESSION['user'];
$userId = (int)$_SESSION['user']['id'];

// Initialize services
$cart = new Cart();
$addressService = new AddressService(new AddressRepository(Database::getConnection()));

// Get cart items
$cartItems = $cart->getItems();
if (empty($cartItems)) {
    header('Location: cart.php');
    exit;
}

// Fetch product details for cart items
$pdo = Database::getConnection();
$cartItemsWithDetails = [];
$total = 0;

foreach ($cartItems as $productId => $quantity) {
    $stmt = $pdo->prepare('SELECT id, name, brand, scale, variant, price, image_url FROM products WHERE id = ?');
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        $itemTotal = (float)$product['price'] * (int)$quantity;
        $total += $itemTotal;
        
        $cartItemsWithDetails[] = [
            'id' => (int)$product['id'],
            'name' => $product['name'],
            'brand' => $product['brand'],
            'scale' => $product['scale'],
            'variant' => $product['variant'] ?? '',
            'price' => (float)$product['price'],
            'quantity' => (int)$quantity,
            'image_url' => $product['image_url'],
            'subtotal' => $itemTotal
        ];
    }
}

$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_address'])) {
    $result = $addressService->createAddress($_POST, $userId);
    
    if ($result['success']) {
        // Store address ID in session for order creation
        $_SESSION['shipping_address_id'] = $result['address']->getId();
        header('Location: checkout.php');
        exit;
    } else {
        $errors = $result['errors'];
    }
}

// Get user's latest address for pre-filling
$latestAddress = $addressService->getLatestAddress($userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping Address - Elite Diecast</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fadeIn 1s ease-out;
        }
    </style>
</head>
<body class="bg-gray-900">
    <!-- Navigation -->
    <nav class="w-full bg-black/95 border-b border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="index.php" class="text-white font-bold text-xl">Elite Diecast</a>
            <div class="flex items-center gap-4">
                <a href="cart.php" class="text-white hover:text-red-600">Cart</a>
                <a href="dashboard.php" class="text-white hover:text-red-600">Dashboard</a>
                <a href="logout.php" class="text-white hover:text-red-600">Logout</a>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-white mb-4">Shipping Address</h1>
            <p class="text-gray-400">Please provide your shipping details to continue with checkout</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Address Form -->
            <div class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-xl p-8">
                <h2 class="text-xl font-semibold text-white mb-6">Address Details</h2>
                
                <?php if (!empty($errors)): ?>
                    <div class="bg-red-600/20 border border-red-600/30 rounded-lg p-4 mb-6">
                        <h3 class="text-red-300 font-semibold mb-2">Please correct the following errors:</h3>
                        <ul class="text-red-300 text-sm space-y-1">
                            <?php foreach ($errors as $error): ?>
                                <li>• <?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="address_form.php" class="space-y-6">
                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-gray-300 mb-2">Full Name *</label>
                        <input type="text" id="name" name="name" required
                               value="<?php echo htmlspecialchars($_POST['name'] ?? $latestAddress?->getName() ?? ''); ?>"
                               class="w-full px-4 py-3 bg-black/30 border border-white/20 rounded-lg text-white focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-500/20">
                    </div>

                    <!-- Street Address -->
                    <div>
                        <label for="street_address" class="block text-gray-300 mb-2">Street Address *</label>
                        <input type="text" id="street_address" name="street_address" required
                               value="<?php echo htmlspecialchars($_POST['street_address'] ?? $latestAddress?->getStreetAddress() ?? ''); ?>"
                               class="w-full px-4 py-3 bg-black/30 border border-white/20 rounded-lg text-white focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-500/20">
                    </div>

                    <!-- City and State -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="city" class="block text-gray-300 mb-2">City *</label>
                            <input type="text" id="city" name="city" required
                                   value="<?php echo htmlspecialchars($_POST['city'] ?? $latestAddress?->getCity() ?? ''); ?>"
                                   class="w-full px-4 py-3 bg-black/30 border border-white/20 rounded-lg text-white focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-500/20">
                        </div>
                        <div>
                            <label for="state" class="block text-gray-300 mb-2">State *</label>
                            <input type="text" id="state" name="state" required
                                   value="<?php echo htmlspecialchars($_POST['state'] ?? $latestAddress?->getState() ?? ''); ?>"
                                   class="w-full px-4 py-3 bg-black/30 border border-white/20 rounded-lg text-white focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-500/20">
                        </div>
                    </div>

                    <!-- Zip Code and Phone -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="zip_code" class="block text-gray-300 mb-2">Zip Code *</label>
                            <input type="text" id="zip_code" name="zip_code" required
                                   value="<?php echo htmlspecialchars($_POST['zip_code'] ?? $latestAddress?->getZipCode() ?? ''); ?>"
                                   class="w-full px-4 py-3 bg-black/30 border border-white/20 rounded-lg text-white focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-500/20">
                        </div>
                        <div>
                            <label for="phone_number" class="block text-gray-300 mb-2">Phone Number *</label>
                            <input type="tel" id="phone_number" name="phone_number" required
                                   value="<?php echo htmlspecialchars($_POST['phone_number'] ?? $latestAddress?->getPhoneNumber() ?? ''); ?>"
                                   class="w-full px-4 py-3 bg-black/30 border border-white/20 rounded-lg text-white focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-500/20">
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex gap-4">
                        <a href="cart.php" class="flex-1 bg-white/10 hover:bg-white/20 text-white px-6 py-3 rounded-lg text-center transition">
                            Back to Cart
                        </a>
                        <button type="submit" name="submit_address" 
                                class="flex-1 bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg transition transform hover:scale-105">
                            Continue to Payment
                        </button>
                    </div>
                </form>
            </div>

            <!-- Order Summary -->
            <div class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-xl p-8">
                <h2 class="text-xl font-semibold text-white mb-6">Order Summary</h2>
                
                <!-- Cart Items -->
                <div class="space-y-4 mb-6">
                    <?php foreach ($cartItemsWithDetails as $item): ?>
                        <div class="flex items-center space-x-4">
                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                                 class="w-16 h-16 object-cover rounded-lg">
                            <div class="flex-1">
                                <h3 class="text-white font-medium"><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p class="text-gray-400 text-sm"><?php echo htmlspecialchars($item['brand']); ?> • <?php echo htmlspecialchars($item['scale']); ?></p>
                                <p class="text-gray-400 text-sm">Qty: <?php echo (int)$item['quantity']; ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-white font-semibold"><?php echo Currency::format((float)$item['price']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Total -->
                <div class="border-t border-white/10 pt-4">
                    <div class="flex justify-between items-center">
                        <span class="text-xl font-semibold text-white">Total</span>
                        <span class="text-2xl font-bold text-red-600"><?php echo Currency::format($total); ?></span>
                    </div>
                </div>

                <!-- Shipping Info -->
                <div class="mt-6 p-4 bg-blue-600/20 border border-blue-600/30 rounded-lg">
                    <div class="flex items-center space-x-2">
                        <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-blue-300 text-sm">Free shipping on all orders</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
