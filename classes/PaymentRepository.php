<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

/**
 * PaymentRepository
 * Handles all database operations for payments using OOP principles.
 */
class PaymentRepository
{
    private PDO $pdo;
    
    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }
    
    /**
     * Create a new payment record
     * 
     * @param int $userId User ID who made the payment
     * @param int $orderId Order ID associated with the payment
     * @param string $razorpayOrderId Razorpay order ID
     * @param float $amount Amount in INR
     * @param string $currency Currency code (INR)
     * @param string $status Payment status (created, pending, success, failed)
     * @return int|false The payment ID on success, false on failure
     */
    public function createPayment(
        int $userId,
        int $orderId,
        string $razorpayOrderId,
        float $amount,
        string $currency = 'INR',
        string $status = 'created'
    ): int|false {
        try {
            $sql = 'INSERT INTO payments (user_id, order_id, razorpay_order_id, amount, currency, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())';
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $userId,
                $orderId,
                $razorpayOrderId,
                $amount,
                $currency,
                $status
            ]);
            
            return $result ? (int)$this->pdo->lastInsertId() : false;
        } catch (PDOException $e) {
            error_log("PaymentRepository::createPayment() - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update payment with Razorpay payment ID and signature after successful payment
     * 
     * @param string $razorpayOrderId Razorpay order ID
     * @param string $razorpayPaymentId Razorpay payment ID
     * @param string $razorpaySignature Razorpay signature for verification
     * @param string $status Payment status
     * @return bool True on success, false on failure
     */
    public function updatePaymentSuccess(
        string $razorpayOrderId,
        string $razorpayPaymentId,
        string $razorpaySignature,
        string $status = 'success'
    ): bool {
        try {
            $sql = 'UPDATE payments 
                    SET razorpay_payment_id = ?, 
                        razorpay_signature = ?, 
                        status = ?,
                        updated_at = NOW()
                    WHERE razorpay_order_id = ?';
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                $razorpayPaymentId,
                $razorpaySignature,
                $status,
                $razorpayOrderId
            ]);
        } catch (PDOException $e) {
            error_log("PaymentRepository::updatePaymentSuccess() - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update payment status to failed
     * 
     * @param string $razorpayOrderId Razorpay order ID
     * @param string $errorReason Error reason/message
     * @return bool True on success, false on failure
     */
    public function updatePaymentFailed(string $razorpayOrderId, string $errorReason = ''): bool
    {
        try {
            $sql = 'UPDATE payments 
                    SET status = ?,
                        error_reason = ?,
                        updated_at = NOW()
                    WHERE razorpay_order_id = ?';
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute(['failed', $errorReason, $razorpayOrderId]);
        } catch (PDOException $e) {
            error_log("PaymentRepository::updatePaymentFailed() - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get payment by Razorpay order ID
     * 
     * @param string $razorpayOrderId Razorpay order ID
     * @return array|false Payment record or false if not found
     */
    public function getPaymentByOrderId(string $razorpayOrderId): array|false
    {
        try {
            $sql = 'SELECT * FROM payments WHERE razorpay_order_id = ? LIMIT 1';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$razorpayOrderId]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result !== false ? $result : false;
        } catch (PDOException $e) {
            error_log("PaymentRepository::getPaymentByOrderId() - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all payments for a user
     * 
     * @param int $userId User ID
     * @return array Array of payment records
     */
    public function getPaymentsByUser(int $userId): array
    {
        try {
            $sql = 'SELECT p.*, o.id as order_number 
                    FROM payments p
                    LEFT JOIN orders o ON p.order_id = o.id
                    WHERE p.user_id = ?
                    ORDER BY p.created_at DESC';
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("PaymentRepository::getPaymentsByUser() - " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get payment by ID
     * 
     * @param int $paymentId Payment ID
     * @return array|false Payment record or false if not found
     */
    public function getPaymentById(int $paymentId): array|false
    {
        try {
            $sql = 'SELECT * FROM payments WHERE id = ? LIMIT 1';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$paymentId]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result !== false ? $result : false;
        } catch (PDOException $e) {
            error_log("PaymentRepository::getPaymentById() - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all payments (for admin)
     * 
     * @param int $limit Number of records to fetch
     * @param int $offset Starting record offset
     * @return array Array of payment records
     */
    public function getAllPayments(int $limit = 50, int $offset = 0): array
    {
        try {
            $sql = 'SELECT p.*, u.name as user_name, u.email as user_email, o.id as order_number
                    FROM payments p
                    LEFT JOIN users u ON p.user_id = u.id
                    LEFT JOIN orders o ON p.order_id = o.id
                    ORDER BY p.created_at DESC
                    LIMIT ? OFFSET ?';
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$limit, $offset]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("PaymentRepository::getAllPayments() - " . $e->getMessage());
            return [];
        }
    }
}

