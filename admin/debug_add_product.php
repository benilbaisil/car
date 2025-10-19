<?php
/**
 * Debug Add Product Script
 * 
 * This script shows exactly what happens when you try to add a product
 * Use this to identify where the process fails
 */

session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin'])) {
    die('Please login as admin first: <a href="login.php">Login</a>');
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/ImageUploadHandler.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Add Product</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-gray-900 via-gray-800 to-black min-h-screen text-white">
    <div class="max-w-4xl mx-auto p-8">
        <h1 class="text-3xl font-bold mb-6">🔍 Debug: Add Product</h1>
        
        <?php
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
            echo "<div class='bg-blue-600/20 border border-blue-600/50 p-4 rounded-lg mb-6'>";
            echo "<h2 class='text-xl font-bold mb-4'>📊 Processing Form Submission...</h2>";
            
            // Step 1: Show POST data received
            echo "<div class='mb-4'>";
            echo "<h3 class='font-semibold mb-2'>1️⃣ POST Data Received:</h3>";
            echo "<pre class='bg-black/30 p-3 rounded text-sm overflow-x-auto'>";
            print_r($_POST);
            echo "</pre>";
            echo "</div>";
            
            // Step 2: Show FILES data
            echo "<div class='mb-4'>";
            echo "<h3 class='font-semibold mb-2'>2️⃣ FILES Data:</h3>";
            echo "<pre class='bg-black/30 p-3 rounded text-sm overflow-x-auto'>";
            print_r($_FILES);
            echo "</pre>";
            echo "</div>";
            
            // Step 3: Extract and validate data
            echo "<div class='mb-4'>";
            echo "<h3 class='font-semibold mb-2'>3️⃣ Extracting Form Data:</h3>";
            
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $brand = isset($_POST['brand']) ? trim($_POST['brand']) : '';
            $scale = isset($_POST['scale']) ? trim($_POST['scale']) : '';
            $variant = !empty($_POST['variant']) ? trim($_POST['variant']) : null;
            $year = !empty($_POST['year']) ? (int)$_POST['year'] : null;
            $type = !empty($_POST['type']) ? trim($_POST['type']) : null;
            $price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
            $stock = isset($_POST['stock']) ? (int)$_POST['stock'] : 0;
            
            echo "<ul class='space-y-1 text-sm'>";
            echo "<li>✓ Name: <strong>" . htmlspecialchars($name) . "</strong></li>";
            echo "<li>✓ Brand: <strong>" . htmlspecialchars($brand) . "</strong></li>";
            echo "<li>✓ Scale: <strong>" . htmlspecialchars($scale) . "</strong></li>";
            echo "<li>✓ Variant: <strong>" . ($variant ? htmlspecialchars($variant) : 'NULL') . "</strong></li>";
            echo "<li>✓ Year: <strong>" . ($year ?? 'NULL') . "</strong></li>";
            echo "<li>✓ Type: <strong>" . ($type ? htmlspecialchars($type) : 'NULL') . "</strong></li>";
            echo "<li>✓ Price: <strong>$" . number_format($price, 2) . "</strong></li>";
            echo "<li>✓ Stock: <strong>" . $stock . "</strong></li>";
            echo "</ul>";
            echo "</div>";
            
            // Step 4: Validation
            echo "<div class='mb-4'>";
            echo "<h3 class='font-semibold mb-2'>4️⃣ Validation Check:</h3>";
            $validationPassed = !empty($name) && !empty($brand) && !empty($scale) && $price > 0 && $stock >= 0;
            
            if ($validationPassed) {
                echo "<p class='text-green-400'>✅ Validation PASSED</p>";
            } else {
                echo "<p class='text-red-400'>❌ Validation FAILED</p>";
                echo "<ul class='text-red-300 text-sm mt-2 space-y-1'>";
                if (empty($name)) echo "<li>• Name is empty</li>";
                if (empty($brand)) echo "<li>• Brand is empty</li>";
                if (empty($scale)) echo "<li>• Scale is empty</li>";
                if ($price <= 0) echo "<li>• Price is invalid ($price)</li>";
                if ($stock < 0) echo "<li>• Stock is invalid ($stock)</li>";
                echo "</ul>";
            }
            echo "</div>";
            
            // Step 5: Image upload
            echo "<div class='mb-4'>";
            echo "<h3 class='font-semibold mb-2'>5️⃣ Image Upload:</h3>";
            $imagePath = null;
            
            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                echo "<p>Image file detected, attempting upload...</p>";
                $imageHandler = new ImageUploadHandler();
                $imagePath = $imageHandler->upload($_FILES['product_image']);
                
                if ($imagePath === false) {
                    echo "<p class='text-red-400'>❌ Upload failed: " . htmlspecialchars($imageHandler->getLastError()) . "</p>";
                } else {
                    echo "<p class='text-green-400'>✅ Upload successful: " . htmlspecialchars($imagePath) . "</p>";
                }
            } else {
                echo "<p class='text-gray-400'>ℹ️ No image uploaded</p>";
            }
            echo "</div>";
            
            // Step 6: Database insertion
            if ($validationPassed && (!isset($imageUploadFailed) || !$imageUploadFailed)) {
                echo "<div class='mb-4'>";
                echo "<h3 class='font-semibold mb-2'>6️⃣ Database Insertion:</h3>";
                
                try {
                    $pdo = Database::getConnection();
                    echo "<p class='text-green-400'>✅ Database connection established</p>";
                    
                    // Prepare SQL
                    $sql = 'INSERT INTO products (name, brand, scale, variant, year, type, price, stock, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';
                    echo "<p class='text-sm text-gray-400 mt-2'>SQL: " . htmlspecialchars($sql) . "</p>";
                    
                    $stmt = $pdo->prepare($sql);
                    echo "<p class='text-green-400'>✅ Statement prepared</p>";
                    
                    // Execute
                    $params = [$name, $brand, $scale, $variant, $year, $type, $price, $stock, $imagePath];
                    echo "<p class='text-sm text-gray-400 mt-2'>Parameters:</p>";
                    echo "<pre class='bg-black/30 p-2 rounded text-xs'>";
                    print_r($params);
                    echo "</pre>";
                    
                    $result = $stmt->execute($params);
                    
                    if ($result) {
                        $lastId = $pdo->lastInsertId();
                        $rowCount = $stmt->rowCount();
                        
                        echo "<p class='text-green-400 font-bold mt-2'>✅ INSERT SUCCESSFUL!</p>";
                        echo "<ul class='text-green-300 text-sm mt-2 space-y-1'>";
                        echo "<li>• Last Insert ID: $lastId</li>";
                        echo "<li>• Rows Affected: $rowCount</li>";
                        echo "</ul>";
                        
                        // Verify by querying
                        $verify = $pdo->prepare('SELECT * FROM products WHERE id = ?');
                        $verify->execute([$lastId]);
                        $product = $verify->fetch();
                        
                        if ($product) {
                            echo "<p class='text-green-400 mt-3'>✅ Verification: Product found in database</p>";
                            echo "<pre class='bg-black/30 p-2 rounded text-xs mt-2'>";
                            print_r($product);
                            echo "</pre>";
                        }
                        
                        echo "<div class='mt-4 p-4 bg-green-600/20 border border-green-600/50 rounded'>";
                        echo "<p class='font-bold text-green-300'>🎉 Product Added Successfully!</p>";
                        echo "<p class='text-sm mt-2'><a href='products.php' class='text-blue-400 hover:underline'>→ Go to Products Page</a></p>";
                        echo "</div>";
                    } else {
                        echo "<p class='text-red-400'>❌ Execute returned FALSE</p>";
                    }
                    
                } catch (PDOException $e) {
                    echo "<p class='text-red-400'>❌ Database Error:</p>";
                    echo "<pre class='bg-red-900/30 p-3 rounded text-sm mt-2'>";
                    echo "Error Code: " . $e->getCode() . "\n";
                    echo "Error Message: " . htmlspecialchars($e->getMessage()) . "\n";
                    echo "Error File: " . $e->getFile() . " (Line " . $e->getLine() . ")\n";
                    echo "</pre>";
                    
                    // Clean up uploaded image
                    if ($imagePath) {
                        $imageHandler->delete($imagePath);
                        echo "<p class='text-yellow-400 mt-2'>🧹 Cleaned up uploaded image</p>";
                    }
                } catch (Exception $e) {
                    echo "<p class='text-red-400'>❌ General Error:</p>";
                    echo "<pre class='bg-red-900/30 p-3 rounded text-sm mt-2'>" . htmlspecialchars($e->getMessage()) . "</pre>";
                }
                
                echo "</div>";
            } else {
                echo "<div class='mb-4 p-4 bg-red-600/20 border border-red-600/50 rounded'>";
                echo "<p class='font-bold text-red-300'>⚠️ Skipping database insertion due to validation/upload errors</p>";
                echo "</div>";
            }
            
            echo "</div>";
        }
        ?>
        
        <!-- Add Product Form -->
        <div class="bg-white/5 backdrop-blur-sm rounded-xl p-6 border border-white/10">
            <h2 class="text-2xl font-bold mb-4">Add Test Product</h2>
            
            <form method="post" action="" enctype="multipart/form-data" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-300 mb-2">Product Name *</label>
                        <input type="text" name="name" value="Debug Test Product" required
                            class="w-full px-4 py-2 bg-black/30 border border-white/20 rounded-lg text-white focus:outline-none focus:border-red-500">
                    </div>
                    <div>
                        <label class="block text-gray-300 mb-2">Brand *</label>
                        <input type="text" name="brand" value="Debug Brand" required
                            class="w-full px-4 py-2 bg-black/30 border border-white/20 rounded-lg text-white focus:outline-none focus:border-red-500">
                    </div>
                    <div>
                        <label class="block text-gray-300 mb-2">Scale *</label>
                        <input type="text" name="scale" value="1:24" required
                            class="w-full px-4 py-2 bg-black/30 border border-white/20 rounded-lg text-white focus:outline-none focus:border-red-500">
                    </div>
                    <div>
                        <label class="block text-gray-300 mb-2">Variant</label>
                        <input type="text" name="variant" value="Debug Variant"
                            class="w-full px-4 py-2 bg-black/30 border border-white/20 rounded-lg text-white focus:outline-none focus:border-red-500">
                    </div>
                    <div>
                        <label class="block text-gray-300 mb-2">Year</label>
                        <input type="number" name="year" value="2024"
                            class="w-full px-4 py-2 bg-black/30 border border-white/20 rounded-lg text-white focus:outline-none focus:border-red-500">
                    </div>
                    <div>
                        <label class="block text-gray-300 mb-2">Type</label>
                        <input type="text" name="type" value="Debug Type"
                            class="w-full px-4 py-2 bg-black/30 border border-white/20 rounded-lg text-white focus:outline-none focus:border-red-500">
                    </div>
                    <div>
                        <label class="block text-gray-300 mb-2">Price ($) *</label>
                        <input type="number" name="price" value="99.99" step="0.01" min="0" required
                            class="w-full px-4 py-2 bg-black/30 border border-white/20 rounded-lg text-white focus:outline-none focus:border-red-500">
                    </div>
                    <div>
                        <label class="block text-gray-300 mb-2">Stock *</label>
                        <input type="number" name="stock" value="10" min="0" required
                            class="w-full px-4 py-2 bg-black/30 border border-white/20 rounded-lg text-white focus:outline-none focus:border-red-500">
                    </div>
                </div>
                
                <div>
                    <label class="block text-gray-300 mb-2">Product Image (Optional)</label>
                    <input type="file" name="product_image" accept="image/jpeg,image/jpg,image/png"
                        class="w-full px-4 py-2 bg-black/30 border border-white/20 rounded-lg text-white focus:outline-none focus:border-red-500
                        file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold
                        file:bg-red-600 file:text-white hover:file:bg-red-700 file:cursor-pointer">
                    <p class="text-gray-400 text-xs mt-1">Allowed: JPG, JPEG, PNG (Max 5MB)</p>
                </div>
                
                <div class="flex space-x-4">
                    <button type="submit" name="add_product" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg transition font-semibold">
                        🧪 Test Add Product
                    </button>
                    <a href="products.php" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg transition inline-block">
                        ← Back to Products
                    </a>
                </div>
            </form>
        </div>
        
        <div class="mt-6 p-4 bg-yellow-600/20 border border-yellow-600/50 rounded-lg">
            <p class="font-semibold">💡 Instructions:</p>
            <ol class="text-sm mt-2 space-y-1 list-decimal list-inside">
                <li>Fill in the form (or use pre-filled values)</li>
                <li>Click "Test Add Product"</li>
                <li>Watch the debug output above to see what happens at each step</li>
                <li>If it succeeds here but not on products.php, the issue is in that file</li>
                <li>If it fails here, you'll see exactly where and why</li>
            </ol>
        </div>
    </div>
</body>
</html>


