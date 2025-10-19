<?php
session_start();

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/RazorpayPayment.php';

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
            // Clear cart after successful payment
            unset($_SESSION['cart']);
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

