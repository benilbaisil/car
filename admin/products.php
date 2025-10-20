<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

// Database configuration and Image Upload Handler
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/ImageUploadHandler.php';
require_once __DIR__ . '/../classes/Currency.php';

/**
 * Product class - represents a product entity
 */
class Product {
    private int $id;
    private string $name;
    private string $brand;
    private string $scale;
    private ?string $variant;
    private ?int $year;
    private ?string $type;
    private float $price;
    private int $stock;
    private ?string $imageUrl;
    
    public function __construct(
        int $id, string $name, string $brand, string $scale, 
        ?string $variant, ?int $year, ?string $type, 
        float $price, int $stock, ?string $imageUrl
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->brand = $brand;
        $this->scale = $scale;
        $this->variant = $variant;
        $this->year = $year;
        $this->type = $type;
        $this->price = $price;
        $this->stock = $stock;
        $this->imageUrl = $imageUrl;
    }
    
    public function getId(): int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getBrand(): string { return $this->brand; }
    public function getScale(): string { return $this->scale; }
    public function getVariant(): ?string { return $this->variant; }
    public function getYear(): ?int { return $this->year; }
    public function getType(): ?string { return $this->type; }
    public function getPrice(): float { return $this->price; }
    public function getStock(): int { return $this->stock; }
    public function getImageUrl(): ?string { return $this->imageUrl; }
}

/**
 * ProductRepository - handles database operations for products
 */
class ProductRepository {
    private PDO $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get all products
     */
    public function getAllProducts(): array {
        $stmt = $this->pdo->query('SELECT * FROM products ORDER BY id DESC');
        $products = [];
        while ($row = $stmt->fetch()) {
            $products[] = $this->createProductFromRow($row);
        }
        return $products;
    }
    
    /**
     * Get product by ID
     */
    public function getProductById(int $id): ?Product {
        $stmt = $this->pdo->prepare('SELECT * FROM products WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        
        return $row ? $this->createProductFromRow($row) : null;
    }
    
    /**
     * Create product
     */
    public function createProduct(
        string $name, string $brand, string $scale, ?string $variant,
        ?int $year, ?string $type, float $price, int $stock, ?string $imageUrl
    ): bool {
        $stmt = $this->pdo->prepare('
            INSERT INTO products (name, brand, scale, variant, year, type, price, stock, image_url)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        return $stmt->execute([$name, $brand, $scale, $variant, $year, $type, $price, $stock, $imageUrl]);
    }
    
    /**
     * Update product
     */
    public function updateProduct(
        int $id, string $name, string $brand, string $scale, ?string $variant,
        ?int $year, ?string $type, float $price, int $stock, ?string $imageUrl
    ): bool {
        $stmt = $this->pdo->prepare('
            UPDATE products 
            SET name = ?, brand = ?, scale = ?, variant = ?, year = ?, type = ?, price = ?, stock = ?, image_url = ?
            WHERE id = ?
        ');
        return $stmt->execute([$name, $brand, $scale, $variant, $year, $type, $price, $stock, $imageUrl, $id]);
    }
    
    /**
     * Delete product (also deletes associated image file)
     */
    public function deleteProduct(int $id): bool {
        // Get product to retrieve image path
        $product = $this->getProductById($id);
        
        // Delete from database
        $stmt = $this->pdo->prepare('DELETE FROM products WHERE id = ?');
        $deleted = $stmt->execute([$id]);
        
        // If database deletion successful, delete image file
        if ($deleted && $product && $product->getImageUrl()) {
            $imageHandler = new ImageUploadHandler();
            $imageHandler->delete($product->getImageUrl());
        }
        
        return $deleted;
    }
    
    /**
     * Helper to create Product object from database row
     */
    private function createProductFromRow(array $row): Product {
        return new Product(
            (int)$row['id'],
            $row['name'],
            $row['brand'],
            $row['scale'],
            $row['variant'],
            $row['year'] ? (int)$row['year'] : null,
            $row['type'],
            (float)$row['price'],
            (int)$row['stock'],
            $row['image_url']
        );
    }
}

// Initialize repository
$repo = new ProductRepository(Database::getConnection());

// Handle actions
$message = '';
$messageType = 'success';
$showAddForm = isset($_GET['action']) && $_GET['action'] === 'add';
$editProduct = null;

// Check for success message from redirect
if (isset($_SESSION['success_message'])) {
    $message = $_SESSION['success_message'];
    $messageType = 'success';
    unset($_SESSION['success_message']);
}

// Handle Delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    try {
        if ($repo->deleteProduct((int)$_GET['id'])) {
            $message = 'Product deleted successfully';
            $messageType = 'success';
        } else {
            $message = 'Failed to delete product';
            $messageType = 'error';
        }
    } catch (Exception $e) {
        $message = 'Error: Product cannot be deleted (may have related orders)';
        $messageType = 'error';
    }
}

// Handle Edit action
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editProduct = $repo->getProductById((int)$_GET['id']);
}

// Handle Add Product form submission (with image upload)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    // Validate POST data exists
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $brand = isset($_POST['brand']) ? trim($_POST['brand']) : '';
    $scale = isset($_POST['scale']) ? trim($_POST['scale']) : '';
    $variant = !empty($_POST['variant']) ? trim($_POST['variant']) : null;
    $year = !empty($_POST['year']) ? (int)$_POST['year'] : null;
    $type = !empty($_POST['type']) ? trim($_POST['type']) : null;
    $price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
    $stock = isset($_POST['stock']) ? (int)$_POST['stock'] : 0;
    
    // Handle image upload
    $imagePath = null;
    $imageHandler = new ImageUploadHandler();
    
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $imagePath = $imageHandler->upload($_FILES['product_image']);
        
        if ($imagePath === false) {
            $message = 'Image upload failed: ' . $imageHandler->getLastError();
            $messageType = 'error';
        }
    }
    
    // Only proceed if no upload errors (or no file uploaded)
    if ($messageType !== 'error') {
        // Validate required fields
        if (!empty($name) && !empty($brand) && !empty($scale) && $price > 0 && $stock >= 0) {
            try {
                $result = $repo->createProduct($name, $brand, $scale, $variant, $year, $type, $price, $stock, $imagePath);
                
                if ($result) {
                    // Success - redirect to prevent form resubmission
                    $_SESSION['success_message'] = 'Product added successfully!';
                    header('Location: products.php');
                    exit;
                } else {
                    $message = 'Failed to add product to database';
                    $messageType = 'error';
                    
                    // Clean up uploaded image if database insert failed
                    if ($imagePath) {
                        $imageHandler->delete($imagePath);
                    }
                }
            } catch (PDOException $e) {
                $message = 'Database error: ' . $e->getMessage();
                $messageType = 'error';
                
                // Clean up uploaded image if database insert failed
                if ($imagePath) {
                    $imageHandler->delete($imagePath);
                }
            }
        } else {
            $message = 'Please fill in all required fields (Name, Brand, Scale, Price, Stock)';
            $messageType = 'error';
            
            // Clean up uploaded image if validation failed
            if ($imagePath) {
                $imageHandler->delete($imagePath);
            }
        }
    }
}

// Handle Update Product form submission (with image upload)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $id = (int)$_POST['product_id'];
    $name = trim($_POST['name']);
    $brand = trim($_POST['brand']);
    $scale = trim($_POST['scale']);
    $variant = !empty($_POST['variant']) ? trim($_POST['variant']) : null;
    $year = !empty($_POST['year']) ? (int)$_POST['year'] : null;
    $type = !empty($_POST['type']) ? trim($_POST['type']) : null;
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    
    // Get existing product to preserve old image if no new upload
    $existingProduct = $repo->getProductById($id);
    $imagePath = $existingProduct ? $existingProduct->getImageUrl() : null;
    $oldImagePath = $imagePath;
    
    // Handle new image upload
    $imageHandler = new ImageUploadHandler();
    
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $newImagePath = $imageHandler->upload($_FILES['product_image']);
        
        if ($newImagePath === false) {
            $message = 'Image upload failed: ' . $imageHandler->getLastError();
            $messageType = 'error';
        } else {
            $imagePath = $newImagePath;
        }
    }
    
    // Only proceed if no upload errors
    if ($messageType !== 'error') {
        if (!empty($name) && !empty($brand) && !empty($scale) && $price > 0 && $stock >= 0) {
            if ($repo->updateProduct($id, $name, $brand, $scale, $variant, $year, $type, $price, $stock, $imagePath)) {
                $message = 'Product updated successfully';
                $messageType = 'success';
                $editProduct = null;
                
                // Delete old image if new one was uploaded
                if ($imagePath !== $oldImagePath && $oldImagePath) {
                    $imageHandler->delete($oldImagePath);
                }
            } else {
                $message = 'Failed to update product';
                $messageType = 'error';
                
                // Clean up new uploaded image if database update failed
                if ($imagePath !== $oldImagePath && $imagePath) {
                    $imageHandler->delete($imagePath);
                }
            }
        } else {
            $message = 'Please fill in all required fields';
            $messageType = 'error';
            
            // Clean up new uploaded image if validation failed
            if ($imagePath !== $oldImagePath && $imagePath) {
                $imageHandler->delete($imagePath);
            }
        }
    }
}

// Get all products
$products = $repo->getAllProducts();
$adminName = $_SESSION['admin']['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - Elite Diecast Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-gray-900 via-gray-800 to-black min-h-screen">
    <!-- Navigation Bar -->
    <nav class="bg-black/40 backdrop-blur-md border-b border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-white">Elite Diecast Admin</h1>
                </div>
                
                <div class="hidden md:flex space-x-4">
                    <a href="dashboard.php" class="text-gray-300 hover:text-white hover:bg-white/10 px-4 py-2 rounded-lg transition">Dashboard</a>
                    <a href="users.php" class="text-gray-300 hover:text-white hover:bg-white/10 px-4 py-2 rounded-lg transition">Users</a>
                    <a href="products.php" class="bg-red-600 text-white px-4 py-2 rounded-lg">Products</a>
                    <a href="orders.php" class="text-gray-300 hover:text-white hover:bg-white/10 px-4 py-2 rounded-lg transition">Orders</a>
                </div>
                
                <div class="flex items-center space-x-4">
                    <span class="text-gray-300 text-sm">Welcome, <?php echo htmlspecialchars($adminName); ?></span>
                    <a href="logout.php" class="bg-red-600/20 hover:bg-red-600 text-red-300 hover:text-white px-4 py-2 rounded-lg transition text-sm">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h2 class="text-3xl font-bold text-white mb-2">Product Management</h2>
                <p class="text-gray-400">Add, edit, and manage your product inventory</p>
            </div>
            <?php if (!$showAddForm && !$editProduct): ?>
                <a href="?action=add" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg transition font-semibold">
                    + Add New Product
                </a>
            <?php endif; ?>
        </div>
        
        <!-- Success/Error Message -->
        <?php if ($message): ?>
            <div class="mb-6 px-4 py-3 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-600/20 border border-green-600/50 text-green-300' : 'bg-red-600/20 border border-red-600/50 text-red-300'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Add Product Form -->
        <?php if ($showAddForm): ?>
            <div class="mb-6 bg-white/5 backdrop-blur-sm rounded-xl p-6 border border-white/10">
                <h3 class="text-xl font-bold text-white mb-4">Add New Product</h3>
                <!-- Form updated to support file upload with enctype="multipart/form-data" -->
                <!-- Explicit action attribute ensures proper form submission -->
                <form method="post" action="products.php" enctype="multipart/form-data" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-300 mb-2">Product Name *</label>
                            <input type="text" name="name" required
                                class="w-full px-4 py-2 bg-black/30 border border-white/20 rounded-lg text-white focus:outline-none focus:border-red-500">
                        </div>
                        <div>
                            <label class="block text-gray-300 mb-2">Brand *</label>
                            <input type="text" name="brand" required
                                class="w-full px-4 py-2 bg-black/30 border border-white/20 rounded-lg text-white focus:outline-none focus:border-red-500">
                        </div>
                        <div>
                            <label class="block text-gray-300 mb-2">Scale *</label>
                            <input type="text" name="scale" placeholder="e.g., 1:18, 1:24" required
                                class="w-full px-4 py-2 bg-black/30 border border-white/20 rounded-lg text-white focus:outline-none focus:border-red-500">
                        </div>
                        <div>
                            <label class="block text-gray-300 mb-2">Variant</label>
                            <input type="text" name="variant" placeholder="e.g., GT3 RS"
                                class="w-full px-4 py-2 bg-black/30 border border-white/20 rounded-lg text-white focus:outline-none focus:border-red-500">
                        </div>
                        <div>
                            <label class="block text-gray-300 mb-2">Year</label>
                            <input type="number" name="year" placeholder="2024"
                                class="w-full px-4 py-2 bg-black/30 border border-white/20 rounded-lg text-white focus:outline-none focus:border-red-500">
                        </div>
                        <div>
                            <label class="block text-gray-300 mb-2">Type</label>
                            <input type="text" name="type" placeholder="e.g., Diecast Sports"
                                class="w-full px-4 py-2 bg-black/30 border border-white/20 rounded-lg text-white focus:outline-none focus:border-red-500">
                        </div>
                        <div>
                            <label class="block text-gray-300 mb-2">Price (₹) *</label>
                            <input type="number" name="price" step="0.01" min="0" required
                                class="w-full px-4 py-2 bg-black/30 border border-white/20 rounded-lg text-white focus:outline-none focus:border-red-500">
                        </div>
                        <div>
                            <label class="block text-gray-300 mb-2">Stock *</label>
                            <input type="number" name="stock" min="0" required
                                class="w-full px-4 py-2 bg-black/30 border border-white/20 rounded-lg text-white focus:outline-none focus:border-red-500">
                        </div>
                    </div>
                    <!-- Image upload field (replaces URL input) -->
                    <div>
                        <label class="block text-gray-300 mb-2">Product Image</label>
                        <input type="file" name="product_image" accept="image/jpeg,image/jpg,image/png"
                            class="w-full px-4 py-2 bg-black/30 border border-white/20 rounded-lg text-white focus:outline-none focus:border-red-500
                            file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold
                            file:bg-red-600 file:text-white hover:file:bg-red-700 file:cursor-pointer">
                        <p class="text-gray-400 text-xs mt-1">Allowed: JPG, JPEG, PNG (Max 5MB)</p>
                    </div>
                    
                    <div class="flex space-x-4">
                        <button type="submit" name="add_product" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition">
                            Add Product
                        </button>
                        <a href="products.php" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg transition inline-block">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
        
        <!-- Edit Product Form -->
        <?php if ($editProduct): ?>
            <div class="mb-6 bg-white/5 backdrop-blur-sm rounded-xl p-6 border border-white/10">
                <h3 class="text-xl font-bold text-white mb-4">Edit Product</h3>
                <!-- Form updated to support file upload with enctype="multipart/form-data" -->
                <!-- Explicit action attribute ensures proper form submission -->
                <form method="post" action="products.php" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="product_id" value="<?php echo $editProduct->getId(); ?>">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-300 mb-2">Product Name *</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($editProduct->getName()); ?>" required
                                class="w-full px-4 py-2 bg-black/30 border border-white/20 rounded-lg text-white focus:outline-none focus:border-red-500">
                        </div>
                        <div>
                            <label class="block text-gray-300 mb-2">Brand *</label>
                            <input type="text" name="brand" value="<?php echo htmlspecialchars($editProduct->getBrand()); ?>" required
                                class="w-full px-4 py-2 bg-black/30 border border-white/20 rounded-lg text-white focus:outline-none focus:border-red-500">
                        </div>
                        <div>
                            <label class="block text-gray-300 mb-2">Scale *</label>
                            <input type="text" name="scale" value="<?php echo htmlspecialchars($editProduct->getScale()); ?>" required
                                class="w-full px-4 py-2 bg-black/30 border border-white/20 rounded-lg text-white focus:outline-none focus:border-red-500">
                        </div>
                        <div>
                            <label class="block text-gray-300 mb-2">Variant</label>
                            <input type="text" name="variant" value="<?php echo htmlspecialchars($editProduct->getVariant() ?? ''); ?>"
                                class="w-full px-4 py-2 bg-black/30 border border-white/20 rounded-lg text-white focus:outline-none focus:border-red-500">
                        </div>
                        <div>
                            <label class="block text-gray-300 mb-2">Year</label>
                            <input type="number" name="year" value="<?php echo $editProduct->getYear() ?? ''; ?>"
                                class="w-full px-4 py-2 bg-black/30 border border-white/20 rounded-lg text-white focus:outline-none focus:border-red-500">
                        </div>
                        <div>
                            <label class="block text-gray-300 mb-2">Type</label>
                            <input type="text" name="type" value="<?php echo htmlspecialchars($editProduct->getType() ?? ''); ?>"
                                class="w-full px-4 py-2 bg-black/30 border border-white/20 rounded-lg text-white focus:outline-none focus:border-red-500">
                        </div>
                        <div>
                            <label class="block text-gray-300 mb-2">Price (₹) *</label>
                            <input type="number" name="price" step="0.01" min="0" value="<?php echo $editProduct->getPrice(); ?>" required
                                class="w-full px-4 py-2 bg-black/30 border border-white/20 rounded-lg text-white focus:outline-none focus:border-red-500">
                        </div>
                        <div>
                            <label class="block text-gray-300 mb-2">Stock *</label>
                            <input type="number" name="stock" min="0" value="<?php echo $editProduct->getStock(); ?>" required
                                class="w-full px-4 py-2 bg-black/30 border border-white/20 rounded-lg text-white focus:outline-none focus:border-red-500">
                        </div>
                    </div>
                    <!-- Current image display and new image upload -->
                    <div>
                        <label class="block text-gray-300 mb-2">Product Image</label>
                        <?php if ($editProduct->getImageUrl() && file_exists($editProduct->getImageUrl())): ?>
                            <div class="mb-2">
                                <img src="<?php echo htmlspecialchars($editProduct->getImageUrl()); ?>" 
                                     alt="Current product image" 
                                     class="h-24 w-24 object-cover rounded border border-white/20">
                                <p class="text-gray-400 text-xs mt-1">Current image</p>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="product_image" accept="image/jpeg,image/jpg,image/png"
                            class="w-full px-4 py-2 bg-black/30 border border-white/20 rounded-lg text-white focus:outline-none focus:border-red-500
                            file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold
                            file:bg-red-600 file:text-white hover:file:bg-red-700 file:cursor-pointer">
                        <p class="text-gray-400 text-xs mt-1">Leave empty to keep current image. Allowed: JPG, JPEG, PNG (Max 5MB)</p>
                    </div>
                    
                    <div class="flex space-x-4">
                        <button type="submit" name="update_product" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition">
                            Save Changes
                        </button>
                        <a href="products.php" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg transition inline-block">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
        
        <!-- Products Table -->
        <div class="bg-white/5 backdrop-blur-sm rounded-xl overflow-hidden border border-white/10">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-white/5">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Brand</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Scale</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Price</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Stock</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        <?php if (!empty($products)): ?>
                            <?php foreach ($products as $product): ?>
                                <tr class="hover:bg-white/5 transition">
                                    <td class="px-6 py-4 text-white"><?php echo $product->getId(); ?></td>
                                    <td class="px-6 py-4 text-white"><?php echo htmlspecialchars($product->getName()); ?></td>
                                    <td class="px-6 py-4 text-gray-300"><?php echo htmlspecialchars($product->getBrand()); ?></td>
                                    <td class="px-6 py-4 text-gray-300"><?php echo htmlspecialchars($product->getScale()); ?></td>
                                    <td class="px-6 py-4 text-white font-semibold"><?php echo Currency::format($product->getPrice()); ?></td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold
                                            <?php echo $product->getStock() < 5 ? 'bg-red-600/20 text-red-300' : ($product->getStock() < 10 ? 'bg-yellow-600/20 text-yellow-300' : 'bg-green-600/20 text-green-300'); ?>">
                                            <?php echo $product->getStock(); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex space-x-2">
                                            <a href="?action=edit&id=<?php echo $product->getId(); ?>" 
                                                class="bg-blue-600/20 hover:bg-blue-600 text-blue-300 hover:text-white px-3 py-1 rounded transition text-sm">
                                                Edit
                                            </a>
                                            <a href="?action=delete&id=<?php echo $product->getId(); ?>" 
                                                onclick="return confirm('Are you sure you want to delete this product?');"
                                                class="bg-red-600/20 hover:bg-red-600 text-red-300 hover:text-white px-3 py-1 rounded transition text-sm">
                                                Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-gray-400">No products found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Statistics -->
        <div class="mt-6 bg-white/5 backdrop-blur-sm rounded-xl p-6 border border-white/10">
            <p class="text-gray-300">
                <span class="font-semibold text-white"><?php echo count($products); ?></span> total products
            </p>
        </div>
    </main>
</body>
</html>

