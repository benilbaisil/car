<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/Cart.php';
require_once __DIR__ . '/classes/RazorpayPayment.php';
require_once __DIR__ . '/classes/StockManager.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Check if payment details are provided
if (!isset($_POST['razorpay_order_id']) || !isset($_POST['razorpay_payment_id']) || !isset($_POST['razorpay_signature'])) {
    $_SESSION['error'] = 'Invalid payment response.';
    header('Location: payment_failed.php');
    exit;
}

$razorpayOrderId = trim($_POST['razorpay_order_id']);
$razorpayPaymentId = trim($_POST['razorpay_payment_id']);
$razorpaySignature = trim($_POST['razorpay_signature']);

try {
    // Initialize Razorpay payment handler
    $razorpayPayment = new RazorpayPayment();
    
    // Verify and handle payment success
    $verified = $razorpayPayment->handlePaymentSuccess(
        $razorpayOrderId,
        $razorpayPaymentId,
        $razorpaySignature
    );
    
    if ($verified) {
        // Get payment details
        $payment = $razorpayPayment->getPaymentByOrderId($razorpayOrderId);
        
        if ($payment) {
            // Reduce stock for the order
            $stockManager = new StockManager(Database::getConnection());
            $orderId = (int)$payment['order_id'];
            
            // Get order items for stock reduction
            $pdo = Database::getConnection();
            $sql = 'SELECT product_id, quantity FROM order_items WHERE order_id = ?';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$orderId]);
            $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Reduce stock for all items in the order
            if ($stockManager->reduceStockForOrder($orderItems)) {
                // Clear cart after successful payment using Cart class
                $cart = new Cart();
                $cart->clear();
                unset($_SESSION['pending_order_id']);
                
                // Store success message with order details
                $_SESSION['payment_success'] = [
                    'order_id' => $payment['order_id'],
                    'payment_id' => $razorpayPaymentId,
                    'amount' => $payment['amount']
                ];
                
                // Redirect to success page
                header('Location: payment_success.php');
                exit;
            } else {
                // Stock reduction failed
                $_SESSION['error'] = 'Failed to update product stock. Please contact support.';
                header('Location: payment_failed.php');
                exit;
            }
        } else {
            // Payment details not found
            $_SESSION['error'] = 'Payment details not found.';
            header('Location: payment_failed.php');
            exit;
        }
    }
    
    // If verification failed
    $error = $razorpayPayment->getLastError();
    throw new Exception($error['message'] ?? 'Payment verification failed');
    
} catch (Exception $e) {
    // Log error
    error_log("Payment verification error: " . $e->getMessage());
    
    // Mark payment as failed in database
    $razorpayPayment = new RazorpayPayment();
    $razorpayPayment->handlePaymentFailure($razorpayOrderId, $e->getMessage());
    
    // Redirect to failure page
    $_SESSION['payment_error'] = $e->getMessage();
    header('Location: payment_failed.php?order_id=' . urlencode($razorpayOrderId));
    exit;
}

