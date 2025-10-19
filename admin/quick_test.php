<?php
session_start();

// Check admin login
if (!isset($_SESSION['admin'])) {
    die('<h1 style="color: red;">❌ Not logged in as admin!</h1><p><a href="login.php">Login first</a></p>');
}

require_once __DIR__ . '/../config.php';

echo '<h1>Quick Product Addition Test</h1>';
echo '<style>body { font-family: Arial; padding: 20px; } .success { color: green; } .error { color: red; }</style>';

// Test form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo '<h2>Testing Product Addition...</h2>';
    
    try {
        $pdo = Database::getConnection();
        
        $name = 'Quick Test ' . date('H:i:s');
        $brand = 'Test Brand';
        $scale = '1:24';
        $price = 29.99;
        $stock = 5;
        
        $sql = 'INSERT INTO products (name, brand, scale, price, stock) VALUES (?, ?, ?, ?, ?)';
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$name, $brand, $scale, $price, $stock]);
        
        if ($result) {
            $id = $pdo->lastInsertId();
            echo "<p class='success'>✅ SUCCESS! Product added with ID: $id</p>";
            echo "<p>Name: $name</p>";
            echo "<p><a href='products.php'>→ View in Products Page</a></p>";
        } else {
            echo "<p class='error'>❌ Failed to insert</p>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    // Show form
    echo '<form method="post">';
    echo '<button type="submit" style="padding: 10px 20px; font-size: 16px; background: green; color: white; border: none; cursor: pointer;">Test Add Product</button>';
    echo '</form>';
}
?>


