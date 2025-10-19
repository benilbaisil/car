<?php

declare(strict_types=1);

require_once __DIR__ . '/RazorpayConfig.php';
require_once __DIR__ . '/PaymentRepository.php';

/**
 * RazorpayPayment
 * Main OOP class for handling Razorpay payment operations.
 * 
 * This class provides methods to:
 * - Create payment orders
 * - Verify payment signatures
 * - Handle payment success/failure
 * - Integrate with database
 */
class RazorpayPayment
{
    private string $keyId;
    private string $keySecret;
    private PaymentRepository $repository;
    private array $lastError = [];
    
    public function __construct()
    {
        $this->keyId = RazorpayConfig::getKeyId();
        $this->keySecret = RazorpayConfig::getKeySecret();
        $this->repository = new PaymentRepository();
    }
    
    /**
     * Create a Razorpay order
     * 
     * @param float $amount Amount in INR (will be converted to paise)
     * @param int $orderId Database order ID
     * @param int $userId User ID
     * @param string $receiptPrefix Prefix for receipt number
     * @return array|false Order details on success, false on failure
     */
    public function createOrder(
        float $amount,
        int $orderId,
        int $userId,
        string $receiptPrefix = 'ORDER'
    ): array|false {
        try {
            // Amount must be in paise (multiply by 100)
            $amountInPaise = (int)($amount * 100);
            
            // Generate unique receipt
            $receipt = $receiptPrefix . '_' . $orderId . '_' . time();
            
            // Prepare order data
            $orderData = [
                'receipt' => $receipt,
                'amount' => $amountInPaise,
                'currency' => RazorpayConfig::getCurrency(),
                'notes' => [
                    'order_id' => $orderId,
                    'user_id' => $userId
                ]
            ];
            
            // Create order via Razorpay API
            $razorpayOrder = $this->callRazorpayAPI('orders', $orderData);
            
            if (!$razorpayOrder || !isset($razorpayOrder['id'])) {
                $this->lastError = ['message' => 'Failed to create Razorpay order'];
                return false;
            }
            
            // Save payment record in database
            $paymentId = $this->repository->createPayment(
                $userId,
                $orderId,
                $razorpayOrder['id'],
                $amount,
                RazorpayConfig::getCurrency(),
                'created'
            );
            
            if (!$paymentId) {
                $this->lastError = ['message' => 'Failed to save payment in database'];
                return false;
            }
            
            return [
                'razorpay_order_id' => $razorpayOrder['id'],
                'amount' => $amount,
                'amount_in_paise' => $amountInPaise,
                'currency' => RazorpayConfig::getCurrency(),
                'key_id' => $this->keyId,
                'payment_id' => $paymentId,
                'order_id' => $orderId
            ];
            
        } catch (Exception $e) {
            $this->lastError = ['message' => $e->getMessage()];
            error_log("RazorpayPayment::createOrder() - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verify Razorpay payment signature
     * 
     * @param string $razorpayOrderId Razorpay order ID
     * @param string $razorpayPaymentId Razorpay payment ID
     * @param string $razorpaySignature Razorpay signature
     * @return bool True if signature is valid, false otherwise
     */
    public function verifyPaymentSignature(
        string $razorpayOrderId,
        string $razorpayPaymentId,
        string $razorpaySignature
    ): bool {
        try {
            // Generate expected signature
            $expectedSignature = hash_hmac(
                'sha256',
                $razorpayOrderId . '|' . $razorpayPaymentId,
                $this->keySecret
            );
            
            // Compare signatures
            return hash_equals($expectedSignature, $razorpaySignature);
            
        } catch (Exception $e) {
            $this->lastError = ['message' => $e->getMessage()];
            error_log("RazorpayPayment::verifyPaymentSignature() - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Handle successful payment
     * 
     * @param string $razorpayOrderId Razorpay order ID
     * @param string $razorpayPaymentId Razorpay payment ID
     * @param string $razorpaySignature Razorpay signature
     * @return bool True on success, false on failure
     */
    public function handlePaymentSuccess(
        string $razorpayOrderId,
        string $razorpayPaymentId,
        string $razorpaySignature
    ): bool {
        try {
            // First verify the signature
            if (!$this->verifyPaymentSignature($razorpayOrderId, $razorpayPaymentId, $razorpaySignature)) {
                $this->lastError = ['message' => 'Invalid payment signature'];
                return false;
            }
            
            // Update payment record in database
            $result = $this->repository->updatePaymentSuccess(
                $razorpayOrderId,
                $razorpayPaymentId,
                $razorpaySignature,
                'success'
            );
            
            if (!$result) {
                $this->lastError = ['message' => 'Failed to update payment in database'];
                return false;
            }
            
            // Get payment details to update order status
            $payment = $this->repository->getPaymentByOrderId($razorpayOrderId);
            if ($payment && isset($payment['order_id'])) {
                // Update order status to 'pending' (awaiting shipment)
                $this->updateOrderStatus((int)$payment['order_id'], 'pending');
            }
            
            return true;
            
        } catch (Exception $e) {
            $this->lastError = ['message' => $e->getMessage()];
            error_log("RazorpayPayment::handlePaymentSuccess() - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Handle failed payment
     * 
     * @param string $razorpayOrderId Razorpay order ID
     * @param string $errorReason Error reason/message
     * @return bool True on success, false on failure
     */
    public function handlePaymentFailure(string $razorpayOrderId, string $errorReason = 'Payment failed'): bool
    {
        try {
            return $this->repository->updatePaymentFailed($razorpayOrderId, $errorReason);
        } catch (Exception $e) {
            $this->lastError = ['message' => $e->getMessage()];
            error_log("RazorpayPayment::handlePaymentFailure() - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get payment details by Razorpay order ID
     * 
     * @param string $razorpayOrderId Razorpay order ID
     * @return array|false Payment details or false
     */
    public function getPaymentByOrderId(string $razorpayOrderId): array|false
    {
        return $this->repository->getPaymentByOrderId($razorpayOrderId);
    }
    
    /**
     * Get all payments for a user
     * 
     * @param int $userId User ID
     * @return array Array of payment records
     */
    public function getUserPayments(int $userId): array
    {
        return $this->repository->getPaymentsByUser($userId);
    }
    
    /**
     * Get last error
     * 
     * @return array Error details
     */
    public function getLastError(): array
    {
        return $this->lastError;
    }
    
    /**
     * Call Razorpay API
     * 
     * @param string $endpoint API endpoint (e.g., 'orders')
     * @param array $data Request data
     * @return array|false Response data or false on failure
     */
    private function callRazorpayAPI(string $endpoint, array $data): array|false
    {
        try {
            $url = 'https://api.razorpay.com/v1/' . $endpoint;
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_USERPWD, $this->keyId . ':' . $this->keySecret);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                $this->lastError = ['message' => 'Razorpay API error: HTTP ' . $httpCode, 'response' => $response];
                return false;
            }
            
            $responseData = json_decode($response, true);
            return is_array($responseData) ? $responseData : false;
            
        } catch (Exception $e) {
            $this->lastError = ['message' => $e->getMessage()];
            error_log("RazorpayPayment::callRazorpayAPI() - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update order status in database
     * 
     * @param int $orderId Order ID
     * @param string $status New status
     * @return bool True on success, false on failure
     */
    private function updateOrderStatus(int $orderId, string $status): bool
    {
        try {
            require_once __DIR__ . '/../config.php';
            $pdo = Database::getConnection();
            
            $sql = 'UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?';
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([$status, $orderId]);
            
        } catch (Exception $e) {
            error_log("RazorpayPayment::updateOrderStatus() - " . $e->getMessage());
            return false;
        }
    }
}

