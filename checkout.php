<?php
session_start();

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/RazorpayPayment.php';
require_once __DIR__ . '/classes/RazorpayConfig.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    $_SESSION['error'] = 'Please login to proceed with checkout.';
    header('Location: login.php');
    exit;
}

// Check if cart exists and has items
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    $_SESSION['error'] = 'Your cart is empty. Please add products before checkout.';
    header('Location: cart.php');
    exit;
}

$user = $_SESSION['user'];
$userId = (int)$user['id'];

// Calculate cart total and prepare order items
$pdo = Database::getConnection();
$cartTotal = 0;
$orderItems = [];

foreach ($_SESSION['cart'] as $productId => $quantity) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ? LIMIT 1');
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product && (int)$product['stock'] >= $quantity) {
        $itemTotal = (float)$product['price'] * $quantity;
        $cartTotal += $itemTotal;
        $orderItems[] = [
            'product_id' => (int)$product['id'],
            'name' => $product['name'],
            'price' => (float)$product['price'],
            'quantity' => $quantity,
            'subtotal' => $itemTotal
        ];
    }
}

if (empty($orderItems)) {
    $_SESSION['error'] = 'No valid items in cart.';
    header('Location: cart.php');
    exit;
}

// Handle order creation
$razorpayOrder = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_order'])) {
    try {
        // Create order in database
        $sql = 'INSERT INTO orders (user_id, total, status, created_at) VALUES (?, ?, ?, NOW())';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $cartTotal, 'pending']);
        $orderId = (int)$pdo->lastInsertId();
        
        // Insert order items
        $sql = 'INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)';
        $stmt = $pdo->prepare($sql);
        
        foreach ($orderItems as $item) {
            $stmt->execute([
                $orderId,
                $item['product_id'],
                $item['quantity'],
                $item['price']
            ]);
        }
        
        // Create Razorpay order
        $razorpayPayment = new RazorpayPayment();
        $razorpayOrder = $razorpayPayment->createOrder($cartTotal, $orderId, $userId);
        
        if (!$razorpayOrder) {
            $error = $razorpayPayment->getLastError();
            throw new Exception($error['message'] ?? 'Failed to create payment order');
        }
        
        // Store order ID in session for verification later
        $_SESSION['pending_order_id'] = $orderId;
        
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error creating order: ' . $e->getMessage();
        error_log("Checkout error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Elite Diecast</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?php if ($razorpayOrder): ?>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <?php endif; ?>
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
                    <a href="cart.php" class="text-gray-300 hover:text-white transition">← Back to Cart</a>
                    <a href="dashboard.php" class="text-gray-300 hover:text-white transition">Dashboard</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 py-12">
        
        <!-- Page Title -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold mb-2">Checkout</h1>
            <p class="text-gray-400">Review your order and proceed to payment</p>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-500/20 border border-red-500 text-red-200 px-4 py-3 rounded-lg mb-6">
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Order Summary -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Customer Details -->
                <div class="bg-white/5 backdrop-blur-sm rounded-xl border border-white/10 p-6">
                    <h2 class="text-xl font-bold mb-4 flex items-center">
                        <svg class="w-6 h-6 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        Customer Details
                    </h2>
                    <div class="space-y-2 text-gray-300">
                        <p><span class="text-gray-500">Name:</span> <?php echo htmlspecialchars($user['name']); ?></p>
                        <p><span class="text-gray-500">Email:</span> <?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="bg-white/5 backdrop-blur-sm rounded-xl border border-white/10 p-6">
                    <h2 class="text-xl font-bold mb-4 flex items-center">
                        <svg class="w-6 h-6 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                        Order Items (<?php echo count($orderItems); ?>)
                    </h2>
                    <div class="space-y-3">
                        <?php foreach ($orderItems as $item): ?>
                            <div class="flex justify-between items-center border-b border-white/10 pb-3">
                                <div>
                                    <p class="font-semibold"><?php echo htmlspecialchars($item['name']); ?></p>
                                    <p class="text-sm text-gray-400">
                                        ₹<?php echo number_format($item['price'], 2); ?> × <?php echo $item['quantity']; ?>
                                    </p>
                                </div>
                                <p class="font-bold text-red-600">₹<?php echo number_format($item['subtotal'], 2); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>

            <!-- Payment Section -->
            <div class="lg:col-span-1">
                <div class="bg-white/5 backdrop-blur-sm rounded-xl border border-white/10 p-6 sticky top-24">
                    <h2 class="text-xl font-bold mb-4">Order Total</h2>
                    
                    <div class="space-y-2 mb-4">
                        <div class="flex justify-between text-gray-400">
                            <span>Subtotal</span>
                            <span>₹<?php echo number_format($cartTotal, 2); ?></span>
                        </div>
                        <div class="flex justify-between text-gray-400">
                            <span>Tax (0%)</span>
                            <span>₹0.00</span>
                        </div>
                        <div class="flex justify-between text-gray-400">
                            <span>Shipping</span>
                            <span class="text-green-400">FREE</span>
                        </div>
                        <div class="border-t border-white/20 pt-2 mt-2">
                            <div class="flex justify-between text-xl font-bold">
                                <span>Total</span>
                                <span class="text-red-600">₹<?php echo number_format($cartTotal, 2); ?></span>
                            </div>
                        </div>
                    </div>

                    <?php if (!$razorpayOrder): ?>
                        <!-- Create Order Button -->
                        <form method="post" action="checkout.php">
                            <button type="submit" name="create_order" 
                                    class="w-full bg-gradient-to-r from-red-600 to-red-500 hover:from-red-700 hover:to-red-600 text-white font-bold py-3 px-6 rounded-lg transition transform hover:scale-105 flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                </svg>
                                Proceed to Payment
                            </button>
                        </form>
                    <?php else: ?>
                        <!-- Pay Now Button (Razorpay) -->
                        <button id="rzp-button" 
                                class="w-full bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-bold py-3 px-6 rounded-lg transition transform hover:scale-105 flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Pay ₹<?php echo number_format($cartTotal, 2); ?>
                        </button>
                    <?php endif; ?>

                    <div class="mt-4 flex items-center justify-center text-sm text-gray-400">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                        </svg>
                        Secure Payment by Razorpay
                    </div>
                </div>
            </div>

        </div>
    </div>

    <?php if ($razorpayOrder): ?>
    <script>
        var options = {
            "key": "<?php echo RazorpayConfig::getKeyId(); ?>",
            "amount": "<?php echo $razorpayOrder['amount_in_paise']; ?>",
            "currency": "<?php echo $razorpayOrder['currency']; ?>",
            "name": "<?php echo RazorpayConfig::getCompanyName(); ?>",
            "description": "Order #<?php echo $razorpayOrder['order_id']; ?>",
            "order_id": "<?php echo $razorpayOrder['razorpay_order_id']; ?>",
            "handler": function (response){
                // Send payment details to server for verification
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = 'payment_verify.php';
                
                var fields = {
                    'razorpay_order_id': response.razorpay_order_id,
                    'razorpay_payment_id': response.razorpay_payment_id,
                    'razorpay_signature': response.razorpay_signature
                };
                
                for(var key in fields) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = fields[key];
                    form.appendChild(input);
                }
                
                document.body.appendChild(form);
                form.submit();
            },
            "prefill": {
                "name": "<?php echo htmlspecialchars($user['name']); ?>",
                "email": "<?php echo htmlspecialchars($user['email']); ?>"
            },
            "theme": {
                "color": "#DC2626"
            },
            "modal": {
                "ondismiss": function(){
                    window.location.href = 'payment_failed.php?order_id=<?php echo $razorpayOrder['razorpay_order_id']; ?>';
                }
            }
        };
        
        var rzp = new Razorpay(options);
        
        document.getElementById('rzp-button').onclick = function(e){
            rzp.open();
            e.preventDefault();
        }
    </script>
    <?php endif; ?>

</body>
</html>

