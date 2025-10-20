<?php
session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/Cart.php';
require_once __DIR__ . '/classes/Currency.php';

// Product Repository for fetching products
class ProductRepository {
    private PDO $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    public function getAllProducts(): array {
        $stmt = $this->pdo->prepare('SELECT id, name, brand, scale, variant, price, stock, image_url FROM products ORDER BY id');
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function searchProducts(string $keyword): array {
        $stmt = $this->pdo->prepare('SELECT id, name, brand, scale, variant, price, stock, image_url FROM products WHERE name LIKE ? OR brand LIKE ? ORDER BY id');
        $searchTerm = "%{$keyword}%";
        $stmt->execute([$searchTerm, $searchTerm]);
        return $stmt->fetchAll();
    }
}

// Navigation Class
class Navigation {
    private array $menuItems;
    private bool $isLoggedIn;
    
    public function __construct(bool $isLoggedIn) {
        $this->isLoggedIn = $isLoggedIn;
        $this->menuItems = [
            ['title' => 'Home', 'url' => 'index.php'],
            ['title' => 'Shop Collection', 'url' => 'shop.php'],
            ['title' => 'About', 'url' => 'index.php#about'],
            ['title' => 'Contact', 'url' => 'index.php#contact']
        ];
    }
    
    public function renderDesktopMenu() {
        $html = '<div class="hidden md:flex items-center space-x-8">';
        foreach ($this->menuItems as $item) {
            $html .= '<a href="' . $item['url'] . '" class="text-white hover:text-red-600 transition">' . $item['title'] . '</a>';
        }
        // Cart link with item count sourced from session cart
        $cartCount = 0;
        if (!empty($_SESSION['cart']['items']) && is_array($_SESSION['cart']['items'])) {
            foreach ($_SESSION['cart']['items'] as $q) { $cartCount += (int)$q; }
        }
        $html .= '<a href="cart.php" class="text-white hover:text-red-600 transition">Cart (' . $cartCount . ')</a>';
        
        if ($this->isLoggedIn) {
            $html .= '<a href="dashboard.php" class="text-white hover:text-red-600 transition">Dashboard</a>';
            $html .= '<a href="my_orders.php" class="text-white hover:text-red-600 transition">My Orders</a>';
            $html .= '<a href="logout.php" class="text-white hover:text-red-600 transition">Logout</a>';
        } else {
            $html .= '<a href="login.php" class="text-white hover:text-red-600 transition flex items-center space-x-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span>Login</span>
                    </a>';
        }
        $html .= '</div>';
        return $html;
    }
    
    public function renderMobileMenu() {
        $html = '<div id="mobileMenu" class="hidden md:hidden bg-black/98 border-t border-white/10">
                    <div class="px-4 py-4 space-y-3">';
        
        foreach ($this->menuItems as $item) {
            $html .= '<a href="' . $item['url'] . '" class="block text-white hover:text-red-600 transition">' . $item['title'] . '</a>';
        }
        // Mobile cart link with count
        $cartCount = 0;
        if (!empty($_SESSION['cart']['items']) && is_array($_SESSION['cart']['items'])) {
            foreach ($_SESSION['cart']['items'] as $q) { $cartCount += (int)$q; }
        }
        $html .= '<a href="cart.php" class="block text-white hover:text-red-600 transition">Cart (' . $cartCount . ')</a>';
        
        if ($this->isLoggedIn) {
            $html .= '<a href="dashboard.php" class="block text-white hover:text-red-600 transition">Dashboard</a>';
            $html .= '<a href="my_orders.php" class="block text-white hover:text-red-600 transition">My Orders</a>';
            $html .= '<a href="logout.php" class="block text-white hover:text-red-600 transition">Logout</a>';
        } else {
            $html .= '<a href="login.php" class="block text-white hover:text-red-600 transition">Login</a>';
        }
        
        $html .= '</div></div>';
        return $html;
    }
}

// Page Renderer Class
class ShopRenderer {
    private Navigation $navigation;
    private ProductRepository $productRepo;
    
    public function __construct(Navigation $navigation, ProductRepository $productRepo) {
        $this->navigation = $navigation;
        $this->productRepo = $productRepo;
    }
    
    public function renderHeader() {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Shop Collection - Elite Diecast</title>
            <script src="https://cdn.tailwindcss.com"></script>
            <style>
                @keyframes fadeIn {
                    from { opacity: 0; transform: translateY(20px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                .animate-fade-in {
                    animation: fadeIn 1s ease-out;
                }
                @keyframes slideIn {
                    from { transform: translateX(-100%); }
                    to { transform: translateX(0); }
                }
                .animate-slide-in {
                    animation: slideIn 0.5s ease-out;
                }
            </style>
        </head>
        <body class="bg-gray-900">
        <?php
        return ob_get_clean();
    }
    
    public function renderNavigation() {
        ob_start();
        ?>
        <nav class="fixed w-full bg-black/95 backdrop-blur-sm z-50 border-b border-white/10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-20">
                    <div class="flex items-center space-x-3">
                        <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v10m4-10v10m4-10v10M3 5h18M5 5v14a2 2 0 002 2h10a2 2 0 002-2V5" />
                        </svg>
                        <span class="text-2xl font-bold text-white">Elite Diecast</span>
                    </div>
                    
                    <?php echo $this->navigation->renderDesktopMenu(); ?>

                    <button class="md:hidden text-white" onclick="toggleMenu()">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
            <?php echo $this->navigation->renderMobileMenu(); ?>
        </nav>
        <?php
        return ob_get_clean();
    }
    
    public function renderHero() {
        ob_start();
        ?>
        <div class="relative h-96">
            <div class="absolute inset-0 bg-gradient-to-r from-black/80 to-black/40 z-10"></div>
            <img src="https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?w=1920&h=600&fit=crop" alt="Shop Collection" class="w-full h-full object-cover">
            <div class="absolute inset-0 z-20 flex items-center">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
                    <div class="max-w-3xl animate-fade-in">
                        <h1 class="text-4xl md:text-6xl font-bold text-white mb-6">Shop Collection</h1>
                        <p class="text-xl md:text-2xl text-gray-300 mb-8">Discover our premium diecast car models</p>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function renderSearchBar() {
        ob_start();
        ?>
        <section class="py-12 bg-black/40 border-b border-white/10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="max-w-2xl mx-auto">
                    <form method="GET" action="shop.php" class="relative">
                        <div class="flex">
                            <input type="text" name="search" placeholder="Search for models, brands, or keywords..." 
                                   value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                                   class="flex-1 px-6 py-4 bg-white/10 backdrop-blur-sm border border-white/20 rounded-l-lg text-white placeholder-gray-400 focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-500/20">
                            <button type="submit" class="px-8 py-4 bg-red-600 hover:bg-red-700 text-white rounded-r-lg font-semibold transition transform hover:scale-105">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
    
    public function renderProductCard(array $product) {
        ob_start();
        $stock = (int)$product['stock'];
        $isInStock = $stock > 0;
        ?>
        <div class="bg-white/5 backdrop-blur-sm rounded-xl overflow-hidden border border-white/10 hover:border-red-600/50 transition transform hover:scale-105 cursor-pointer">
            <div class="relative h-48 overflow-hidden">
                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="w-full h-full object-cover">
                <div class="absolute top-4 right-4 bg-red-600 text-white px-3 py-1 rounded-full text-sm font-semibold">
                    <?php echo htmlspecialchars($product['scale']); ?>
                </div>
            </div>
            <div class="p-6">
                <h3 class="text-xl font-bold text-white mb-2"><?php echo htmlspecialchars($product['name']); ?></h3>
                <p class="text-sm text-gray-400 mb-1"><?php echo htmlspecialchars($product['brand']); ?> • <?php echo htmlspecialchars($product['scale']); ?></p>
                <?php if (!empty($product['variant'])): ?>
                    <p class="text-xs text-gray-500 mb-2"><?php echo htmlspecialchars($product['variant']); ?></p>
                <?php endif; ?>
                <p class="text-2xl text-red-600 font-bold mb-4"><?php echo Currency::format((float)$product['price']); ?></p>

                <!-- Stock information displayed above the Add to Cart button -->
                <div class="mb-3">
                    <?php if ($isInStock): ?>
                        <span class="text-green-400 text-sm">✓ In Stock (<?php echo $stock; ?> available)</span>
                    <?php else: ?>
                        <span class="text-red-400 text-sm">✗ Out of Stock</span>
                    <?php endif; ?>
                </div>

                <!-- Add to Cart form with stock check -->
                <form method="post" action="shop.php">
                    <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">
                    <button type="submit" name="add_to_cart" value="1"
                            class="w-full py-2 rounded-lg transition <?php echo $isInStock ? 'bg-white/10 hover:bg-red-600 text-white' : 'bg-gray-600 text-gray-400 cursor-not-allowed'; ?>"
                            <?php echo $isInStock ? '' : 'disabled'; ?>>
                        <?php echo $isInStock ? 'Add to Cart' : 'Out of Stock'; ?>
                    </button>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function renderProducts($products) {
        ob_start();
        ?>
        <section class="py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <?php if (empty($products)): ?>
                    <div class="text-center py-20">
                        <div class="text-gray-400 mb-4">
                            <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0112 15c-2.34 0-4.29-1.009-5.824-2.709M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-white mb-4">No Products Found</h3>
                        <p class="text-gray-400 mb-8">Try adjusting your search terms or browse all products.</p>
                        <a href="shop.php" class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                            View All Products
                        </a>
                    </div>
                <?php else: ?>
                    <div class="mb-8">
                        <h2 class="text-3xl font-bold text-white mb-4">
                            <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                                Search Results for "<?php echo htmlspecialchars($_GET['search']); ?>"
                            <?php else: ?>
                                Our Collection
                            <?php endif; ?>
                        </h2>
                        <p class="text-gray-400"><?php echo count($products); ?> product(s) found</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                        <?php foreach ($products as $product): ?>
                            <?php echo $this->renderProductCard($product); ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
    
    public function renderFooter() {
        ob_start();
        ?>
        <footer class="bg-black border-t border-white/10 py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <div>
                        <div class="flex items-center space-x-3 mb-4">
                            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v10m4-10v10m4-10v10M3 5h18M5 5v14a2 2 0 002 2h10a2 2 0 002-2V5" />
                            </svg>
                            <span class="text-xl font-bold text-white">Elite Diecast</span>
                        </div>
                        <p class="text-gray-400">Your trusted destination for premium diecast models.</p>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold mb-4">Quick Links</h4>
                        <div class="space-y-2 text-gray-400">
                            <a href="index.php#about" class="block hover:text-red-600">About Us</a>
                            <a href="shop.php" class="block hover:text-red-600">Shop Collection</a>
                            <a href="cart.php" class="block hover:text-red-600">Cart</a>
                            <?php if (isset($_SESSION['user'])): ?>
                                <a href="dashboard.php" class="block hover:text-red-600">Dashboard</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold mb-4">Contact</h4>
                        <div class="space-y-2 text-gray-400">
                            <div class="flex items-center space-x-2">
                                <span>📞</span>
                                <span>+1 (555) 123-4567</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span>✉️</span>
                                <span>support@elitediecast.com</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span>📍</span>
                                <span>123 Collector's Lane, CA</span>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold mb-4">Follow Us</h4>
                        <div class="flex space-x-4">
                            <a href="#" class="text-gray-400 hover:text-red-600 transition">Facebook</a>
                            <a href="#" class="text-gray-400 hover:text-red-600 transition">Instagram</a>
                            <a href="#" class="text-gray-400 hover:text-red-600 transition">Twitter</a>
                        </div>
                    </div>
                </div>
                <div class="border-t border-white/10 mt-8 pt-8 text-center text-gray-400">
                    <p>&copy; 2025 Elite Diecast. All rights reserved.</p>
                </div>
            </div>
        </footer>

        <script>
            function toggleMenu() {
                const menu = document.getElementById('mobileMenu');
                menu.classList.toggle('hidden');
            }
        </script>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    public function render($products) {
        echo $this->renderHeader();
        echo $this->renderNavigation();
        echo $this->renderHero();
        echo $this->renderSearchBar();
        echo $this->renderProducts($products);
        echo $this->renderFooter();
    }
}

// Handle Add to Cart POST at the entry point before rendering
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'], $_POST['product_id'])) {
    // Basic sanitization and cart update
    $productId = (int)$_POST['product_id'];
    $cart = new Cart();
    $cart->addProduct($productId, 1);
    // Optional: PRG pattern to avoid resubmission on refresh
    header('Location: shop.php' . (isset($_GET['search']) ? '?search=' . urlencode($_GET['search']) : ''));
    exit;
}

// Create navigation with login status
$navigation = new Navigation(isset($_SESSION['user']));

// Create product repository for database access
$productRepo = new ProductRepository(Database::getConnection());

// Get products based on search
$products = [];
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $products = $productRepo->searchProducts(trim($_GET['search']));
} else {
    $products = $productRepo->getAllProducts();
}

// Create shop renderer and render the page
$shop = new ShopRenderer($navigation, $productRepo);
$shop->render($products);
?>
