<?php
/**
 * Database Verification Script
 * 
 * This script checks:
 * 1. Database connection
 * 2. Required tables exist (admins, users)
 * 3. Admin account exists
 * 4. Sample data
 */

require_once __DIR__ . '/config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Check</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #1a1a1a; color: #fff; }
        .success { color: #4ade80; }
        .error { color: #f87171; }
        .warning { color: #fbbf24; }
        .section { margin: 20px 0; padding: 15px; background: #2a2a2a; border-radius: 8px; }
        h2 { color: #60a5fa; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border: 1px solid #444; }
        th { background: #333; }
        .code { background: #000; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>";

echo "<h1>🔍 Database Verification</h1>";

// Test 1: Database Connection
echo "<div class='section'>";
echo "<h2>1. Database Connection</h2>";
try {
    $pdo = Database::getConnection();
    echo "<p class='success'>✅ Successfully connected to database</p>";
} catch (PDOException $e) {
    echo "<p class='error'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p class='warning'>⚠️ Make sure:</p>";
    echo "<ul>";
    echo "<li>XAMPP MySQL is running</li>";
    echo "<li>Database 'car_showroom' exists</li>";
    echo "<li>Config.php has correct credentials</li>";
    echo "</ul>";
    echo "</div></body></html>";
    exit;
}
echo "</div>";

// Test 2: Check if admins table exists
echo "<div class='section'>";
echo "<h2>2. Admins Table</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'admins'");
    if ($stmt->rowCount() > 0) {
        echo "<p class='success'>✅ Admins table exists</p>";
        
        // Check admin records
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM admins");
        $count = $stmt->fetch()['count'];
        echo "<p>Total admins: <strong>$count</strong></p>";
        
        // Check for default admin
        $stmt = $pdo->prepare("SELECT id, name, email FROM admins WHERE email = ?");
        $stmt->execute(['admin@gmail.com']);
        $admin = $stmt->fetch();
        
        if ($admin) {
            echo "<p class='success'>✅ Default admin account found</p>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Name</th><th>Email</th></tr>";
            echo "<tr><td>{$admin['id']}</td><td>{$admin['name']}</td><td>{$admin['email']}</td></tr>";
            echo "</table>";
        } else {
            echo "<p class='error'>❌ Default admin account NOT found</p>";
            echo "<p class='warning'>Run this SQL to create admin:</p>";
            echo "<div class='code'>";
            echo "INSERT INTO admins (name, email, password_hash) VALUES<br>";
            echo "('System Admin', 'admin@gmail.com', '\$2y\$10\$Ks3g809GYRfPzOMXuefKo.N9fAgJRBj0GaleX2AswBdtGYSLudgli');";
            echo "</div>";
        }
    } else {
        echo "<p class='error'>❌ Admins table does NOT exist</p>";
        echo "<p class='warning'>⚠️ You need to run database.sql to create tables</p>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>❌ Error checking admins table: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Test 3: Check if users table exists
echo "<div class='section'>";
echo "<h2>3. Users Table</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "<p class='success'>✅ Users table exists</p>";
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $count = $stmt->fetch()['count'];
        echo "<p>Total users: <strong>$count</strong></p>";
        
        if ($count > 0) {
            $stmt = $pdo->query("SELECT id, name, email, created_at FROM users ORDER BY created_at DESC LIMIT 5");
            echo "<table>";
            echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Created</th></tr>";
            while ($user = $stmt->fetch()) {
                echo "<tr>";
                echo "<td>{$user['id']}</td>";
                echo "<td>" . htmlspecialchars($user['name']) . "</td>";
                echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                echo "<td>{$user['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p class='error'>❌ Users table does NOT exist</p>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>❌ Error checking users table: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Test 4: Check other tables
echo "<div class='section'>";
echo "<h2>4. Other Tables</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p>Found " . count($tables) . " tables:</p>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li class='success'>✅ $table</li>";
    }
    echo "</ul>";
} catch (PDOException $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Test 5: Products check
echo "<div class='section'>";
echo "<h2>5. Products</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $count = $stmt->fetch()['count'];
    echo "<p>Total products: <strong>$count</strong></p>";
    
    if ($count > 0) {
        $stmt = $pdo->query("SELECT id, name, brand, price, stock FROM products LIMIT 3");
        echo "<table>";
        echo "<tr><th>ID</th><th>Name</th><th>Brand</th><th>Price</th><th>Stock</th></tr>";
        while ($product = $stmt->fetch()) {
            echo "<tr>";
            echo "<td>{$product['id']}</td>";
            echo "<td>" . htmlspecialchars($product['name']) . "</td>";
            echo "<td>" . htmlspecialchars($product['brand']) . "</td>";
            echo "<td>\${$product['price']}</td>";
            echo "<td>{$product['stock']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Test 6: Password verification test
echo "<div class='section'>";
echo "<h2>6. Password Verification Test</h2>";
try {
    $stmt = $pdo->prepare("SELECT password_hash FROM admins WHERE email = ?");
    $stmt->execute(['admin@gmail.com']);
    $result = $stmt->fetch();
    
    if ($result) {
        $hash = $result['password_hash'];
        $testPassword = 'Admin@1234';
        
        if (password_verify($testPassword, $hash)) {
            echo "<p class='success'>✅ Password verification works correctly</p>";
            echo "<p>Hash: <code style='color: #888;'>" . htmlspecialchars(substr($hash, 0, 30)) . "...</code></p>";
        } else {
            echo "<p class='error'>❌ Password verification failed</p>";
            echo "<p class='warning'>⚠️ The password hash might be incorrect</p>";
            echo "<p>Current hash: <code style='color: #888;'>" . htmlspecialchars($hash) . "</code></p>";
        }
    } else {
        echo "<p class='warning'>⚠️ Admin account not found for password test</p>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Summary and next steps
echo "<div class='section'>";
echo "<h2>✅ Summary & Next Steps</h2>";
echo "<p><strong>If all tests pass:</strong></p>";
echo "<ul>";
echo "<li>Try logging in at: <a href='login.php' style='color: #60a5fa;'>login.php</a></li>";
echo "<li>Admin: admin@gmail.com / Admin@1234</li>";
echo "</ul>";

echo "<p><strong>If tests fail:</strong></p>";
echo "<ol>";
echo "<li>Open phpMyAdmin: <a href='http://localhost/phpmyadmin' target='_blank' style='color: #60a5fa;'>http://localhost/phpmyadmin</a></li>";
echo "<li>Select or create database: <code>car_showroom</code></li>";
echo "<li>Go to SQL tab</li>";
echo "<li>Run the complete database.sql file</li>";
echo "<li>Refresh this page</li>";
echo "</ol>";
echo "</div>";

echo "<div style='text-align: center; margin: 20px; color: #888;'>";
echo "<p>Database Check Complete | <a href='index.php' style='color: #60a5fa;'>Back to Home</a></p>";
echo "</div>";

echo "</body></html>";
?>


