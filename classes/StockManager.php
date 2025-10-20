<?php
declare(strict_types=1);

/**
 * StockManager Class
 * Handles all stock-related operations for the Diecast Car Store
 * Follows OOP principles with proper encapsulation and error handling
 */
class StockManager
{
    private PDO $pdo;
    
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    
    /**
     * Reduce stock for a single product
     * @param int $productId Product ID
     * @param int $quantity Quantity to reduce
     * @return bool Success status
     */
    public function reduceStock(int $productId, int $quantity): bool
    {
        try {
            $this->pdo->beginTransaction();
            
            // Check current stock
            $stmt = $this->pdo->prepare("SELECT stock FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            $currentStock = $stmt->fetchColumn();
            
            if ($currentStock === false) {
                throw new Exception("Product not found");
            }
            
            if ($currentStock < $quantity) {
                throw new Exception("Insufficient stock. Available: {$currentStock}, Required: {$quantity}");
            }
            
            // Update stock
            $stmt = $this->pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $result = $stmt->execute([$quantity, $productId]);
            
            if (!$result) {
                throw new Exception("Failed to update stock");
            }
            
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Stock reduction failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Reduce stock for multiple products in an order
     * @param array $orderItems Array of items with product_id and quantity
     * @return bool Success status
     */
    public function reduceStockForOrder(array $orderItems): bool
    {
        try {
            $this->pdo->beginTransaction();
            
            // Validate all items have sufficient stock
            foreach ($orderItems as $item) {
                $productId = (int)$item['product_id'];
                $quantity = (int)$item['quantity'];
                
                $stmt = $this->pdo->prepare("SELECT stock FROM products WHERE id = ?");
                $stmt->execute([$productId]);
                $currentStock = $stmt->fetchColumn();
                
                if ($currentStock === false) {
                    throw new Exception("Product ID {$productId} not found");
                }
                
                if ($currentStock < $quantity) {
                    throw new Exception("Insufficient stock for product ID {$productId}. Available: {$currentStock}, Required: {$quantity}");
                }
            }
            
            // Reduce stock for all items
            foreach ($orderItems as $item) {
                $productId = (int)$item['product_id'];
                $quantity = (int)$item['quantity'];
                
                $stmt = $this->pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $result = $stmt->execute([$quantity, $productId]);
                
                if (!$result) {
                    throw new Exception("Failed to update stock for product ID {$productId}");
                }
            }
            
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Order stock reduction failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get current stock for a product
     * @param int $productId Product ID
     * @return int Current stock level
     */
    public function getCurrentStock(int $productId): int
    {
        try {
            $stmt = $this->pdo->prepare("SELECT stock FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            $stock = $stmt->fetchColumn();
            
            return $stock !== false ? (int)$stock : 0;
            
        } catch (Exception $e) {
            error_log("Failed to get stock for product {$productId}: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get stock status for a product
     * @param int $productId Product ID
     * @return array Stock information
     */
    public function getStockStatus(int $productId): array
    {
        $stock = $this->getCurrentStock($productId);
        
        return [
            'stock' => $stock,
            'is_available' => $stock > 0,
            'status' => $stock > 10 ? 'in_stock' : ($stock > 0 ? 'low_stock' : 'out_of_stock'),
            'status_text' => $stock > 10 ? 'In Stock' : ($stock > 0 ? 'Low Stock' : 'Out of Stock'),
            'status_color' => $stock > 10 ? 'text-green-400' : ($stock > 0 ? 'text-yellow-400' : 'text-red-400')
        ];
    }
    
    /**
     * Get low stock products (stock <= 5)
     * @return array Products with low stock
     */
    public function getLowStockProducts(): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, name, brand, stock 
                FROM products 
                WHERE stock <= 5 
                ORDER BY stock ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Failed to get low stock products: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Restore stock (for order cancellation)
     * @param int $productId Product ID
     * @param int $quantity Quantity to restore
     * @return bool Success status
     */
    public function restoreStock(int $productId, int $quantity): bool
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            return $stmt->execute([$quantity, $productId]);
            
        } catch (Exception $e) {
            error_log("Stock restoration failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get stock statistics for admin dashboard
     * @return array Stock statistics
     */
    public function getStockStatistics(): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as total_products,
                    SUM(stock) as total_stock,
                    COUNT(CASE WHEN stock = 0 THEN 1 END) as out_of_stock,
                    COUNT(CASE WHEN stock > 0 AND stock <= 5 THEN 1 END) as low_stock,
                    COUNT(CASE WHEN stock > 5 THEN 1 END) as in_stock
                FROM products
            ");
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Failed to get stock statistics: " . $e->getMessage());
            return [
                'total_products' => 0,
                'total_stock' => 0,
                'out_of_stock' => 0,
                'low_stock' => 0,
                'in_stock' => 0
            ];
        }
    }
}
?>
