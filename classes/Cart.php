<?php

declare(strict_types=1);

/**
 * Cart Class
 * Unified OOP cart management with session handling.
 * This ensures consistency across all pages (Index, cart, checkout).
 */
class Cart
{
    private string $sessionKey = 'cart';
    
    public function __construct()
    {
        if (!isset($_SESSION[$this->sessionKey])) {
            $_SESSION[$this->sessionKey] = ['items' => []];
        }
    }
    
    /**
     * Add product to cart or increase quantity
     * 
     * @param int $productId Product ID
     * @param int $quantity Quantity to add (default 1)
     */
    public function addProduct(int $productId, int $quantity = 1): void
    {
        if ($quantity < 1) {
            return;
        }
        
        if (!isset($_SESSION[$this->sessionKey]['items'][$productId])) {
            $_SESSION[$this->sessionKey]['items'][$productId] = 0;
        }
        
        $_SESSION[$this->sessionKey]['items'][$productId] += $quantity;
    }
    
    /**
     * Get all cart items
     * Returns array with productId as key and quantity as value
     * 
     * @return array [productId => quantity]
     */
    public function getItems(): array
    {
        return (array)($_SESSION[$this->sessionKey]['items'] ?? []);
    }
    
    /**
     * Get total number of items in cart
     * 
     * @return int Total quantity of all items
     */
    public function getItemCount(): int
    {
        $items = $this->getItems();
        return array_sum($items);
    }
    
    /**
     * Check if cart has items
     * 
     * @return bool True if cart has items, false otherwise
     */
    public function hasItems(): bool
    {
        $items = $this->getItems();
        return !empty($items) && array_sum($items) > 0;
    }
    
    /**
     * Remove specific product from cart
     * 
     * @param int $productId Product ID to remove
     */
    public function remove(int $productId): void
    {
        if (isset($_SESSION[$this->sessionKey]['items'][$productId])) {
            unset($_SESSION[$this->sessionKey]['items'][$productId]);
        }
    }
    
    /**
     * Update product quantity in cart
     * 
     * @param int $productId Product ID
     * @param int $quantity New quantity (0 to remove)
     */
    public function updateQuantity(int $productId, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->remove($productId);
            return;
        }
        
        if (isset($_SESSION[$this->sessionKey]['items'][$productId])) {
            $_SESSION[$this->sessionKey]['items'][$productId] = $quantity;
        }
    }
    
    /**
     * Clear entire cart
     */
    public function clear(): void
    {
        $_SESSION[$this->sessionKey] = ['items' => []];
    }
    
    /**
     * Get cart data for checkout (compatible format)
     * 
     * @return array Cart items in format expected by checkout
     */
    public function getCartData(): array
    {
        return $this->getItems();
    }
}

