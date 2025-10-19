<?php
/**
 * Product Addition Diagnostic Tool
 * 
 * This script tests the product addition functionality
 * and shows detailed error messages to help debug issues
 */

session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin'])) {
    die('Please login as admin first: <a href="login.php">Login</a>');
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/ImageUploadHandler.php';

echo "<h1>Product Addition Diagnostic</h1>";
echo "<style>
    body { font-family: Arial; margin: 20px; background: #1a1a1a; color: #fff; }
    .success { color: #4ade80; }
    .error { color: #f87171; }
    .section { margin: 20px 0; padding: 15px; background: #2a2a2a; border-radius: 8px; }
    pre { background: #000; padding: 10px; border-radius: 4px; overflow-x: auto; }
</style>";

// Test 1: Database Connection
echo "<div class='section'>";
echo "<h2>1. Testing Database Connection</h2>";
try {
    $pdo = Database::getConnection();
    echo "<p class='success'>✅ Database connected successfully</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}
echo "</div>";

// Test 2: Check products table
echo "<div class='section'>";
echo "<h2>2. Testing Products Table</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $count = $stmt->fetch()['count'];
    echo "<p class='success'>✅ Products table exists</p>";
    echo "<p>Current product count: <strong>$count</strong></p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Products table error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Test 3: Test ProductRepository class
echo "<div class='section'>";
echo "<h2>3. Testing ProductRepository Class</h2>";

class ProductRepository {
    private PDO $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    public function createProduct(
        string $name, string $brand, string $scale, ?string $variant,
        ?int $year, ?string $type, float $price, int $stock, ?string $imageUrl
    ): bool {
        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO products (name, brand, scale, variant, year, type, price, stock, image_url)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $result = $stmt->execute([$name, $brand, $scale, $variant, $year, $type, $price, $stock, $imageUrl]);
            
            echo "<p class='success'>✅ SQL executed successfully</p>";
            echo "<p>Rows affected: " . $stmt->rowCount() . "</p>";
            echo "<p>Last insert ID: " . $this->pdo->lastInsertId() . "</p>";
            
            return $result;
        } catch (PDOException $e) {
            echo "<p class='error'>❌ SQL Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            return false;
        }
    }
    
    public function getAllProducts(): array {
        $stmt = $this->pdo->query('SELECT id, name, brand, price, stock FROM products ORDER BY id DESC LIMIT 5');
        return $stmt->fetchAll();
    }
}

$repo = new ProductRepository($pdo);
echo "<p class='success'>✅ ProductRepository instantiated</p>";
echo "</div>";

// Test 4: Try to insert a test product
echo "<div class='section'>";
echo "<h2>4. Testing Product Insertion</h2>";

$testName = 'TEST PRODUCT ' . date('Y-m-d H:i:s');
$testBrand = 'Test Brand';
$testScale = '1:24';
$testPrice = 99.99;
$testStock = 5;

echo "<p>Attempting to insert:</p>";
echo "<pre>";
echo "Name: $testName\n";
echo "Brand: $testBrand\n";
echo "Scale: $testScale\n";
echo "Price: $testPrice\n";
echo "Stock: $testStock\n";
echo "</pre>";

try {
    $result = $repo->createProduct(
        $testName,
        $testBrand,
        $testScale,
        'Test Variant',
        2024,
        'Test Type',
        $testPrice,
        $testStock,
        null
    );
    
    if ($result) {
        echo "<p class='success'>✅ Test product inserted successfully!</p>";
    } else {
        echo "<p class='error'>❌ Insert returned false</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Exception: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Test 5: Display recent products
echo "<div class='section'>";
echo "<h2>5. Recent Products (Last 5)</h2>";
try {
    $products = $repo->getAllProducts();
    
    if (!empty($products)) {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%; color: #fff;'>";
        echo "<tr style='background: #333;'>";
        echo "<th>ID</th><th>Name</th><th>Brand</th><th>Price</th><th>Stock</th>";
        echo "</tr>";
        
        foreach ($products as $product) {
            echo "<tr style='background: #2a2a2a;'>";
            echo "<td>{$product['id']}</td>";
            echo "<td>" . htmlspecialchars($product['name']) . "</td>";
            echo "<td>" . htmlspecialchars($product['brand']) . "</td>";
            echo "<td>\${$product['price']}</td>";
            echo "<td>{$product['stock']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check if test product is in list
        $testFound = false;
        foreach ($products as $product) {
            if (strpos($product['name'], 'TEST PRODUCT') !== false) {
                $testFound = true;
                break;
            }
        }
        
        if ($testFound) {
            echo "<p class='success'>✅ Test product appears in list!</p>";
        }
    } else {
        echo "<p class='error'>No products found in database</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error fetching products: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Test 6: Check PHP upload settings
echo "<div class='section'>";
echo "<h2>6. PHP Upload Settings</h2>";
echo "<pre>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "\n";
echo "file_uploads: " . (ini_get('file_uploads') ? 'On' : 'Off') . "\n";
echo "</pre>";
echo "</div>";

// Test 7: Check uploads directory
echo "<div class='section'>";
echo "<h2>7. Uploads Directory</h2>";
$uploadsDir = __DIR__ . '/../uploads/products/';
if (is_dir($uploadsDir)) {
    echo "<p class='success'>✅ Directory exists: $uploadsDir</p>";
    if (is_writable($uploadsDir)) {
        echo "<p class='success'>✅ Directory is writable</p>";
    } else {
        echo "<p class='error'>❌ Directory is NOT writable</p>";
    }
    
    $files = scandir($uploadsDir);
    $fileCount = count($files) - 2; // Exclude . and ..
    echo "<p>Files in directory: <strong>$fileCount</strong></p>";
} else {
    echo "<p class='error'>❌ Directory does not exist: $uploadsDir</p>";
}
echo "</div>";

// Cleanup suggestion
echo "<div class='section'>";
echo "<h2>🧹 Cleanup</h2>";
echo "<p>To remove test products, run this SQL:</p>";
echo "<pre>DELETE FROM products WHERE name LIKE 'TEST PRODUCT%';</pre>";
echo "<p><a href='products.php' style='color: #60a5fa;'>Go to Products Page</a></p>";
echo "</div>";

echo "<div class='section' style='background: #1a4d2e; border: 2px solid #4ade80;'>";
echo "<h2>✅ Diagnosis Complete</h2>";
echo "<p>If all tests passed above, the product addition functionality is working correctly.</p>";
echo "<p>If the admin form still doesn't work, the issue might be:</p>";
echo "<ul>";
echo "<li>Form not submitting (check browser console for JavaScript errors)</li>";
echo "<li>Missing required fields</li>";
echo "<li>Page redirecting before showing errors</li>";
echo "<li>Session or CSRF token issues</li>";
echo "</ul>";
echo "</div>";
?>


