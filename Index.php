<?php
session_start();
// DB config for saving contact form submissions
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/Cart.php';
require_once __DIR__ . '/classes/Currency.php';

// Base Vehicle Class
abstract class Vehicle {
    protected $id;
    protected $name;
    protected $price;
    protected $image;
    protected $type;
    
    public function __construct($id, $name, $price, $image, $type) {
        $this->id = $id;
        $this->name = $name;
        $this->price = $price;
        $this->image = $image;
        $this->type = $type;
    }
    
    public function getId() {
        return $this->id;
    }
    
    public function getName() {
        return $this->name;
    }
    
    public function getPrice() {
        return $this->price;
    }
    
    public function getFormattedPrice() {
        return Currency::format((float)$this->price);
    }
    
    public function getImage() {
        return $this->image;
    }
    
    public function getType() {
        return $this->type;
    }
    
    abstract public function getDescription();
}

// Car Class extending Vehicle
class Car extends Vehicle {
    private $model;
    private $year;
    
    public function __construct($id, $name, $model, $year, $price, $image, $type) {
        parent::__construct($id, $name, $price, $image, $type);
        $this->model = $model;
        $this->year = $year;
    }
    
    public function getModel() {
        return $this->model;
    }
    
    public function getYear() {
        return $this->year;
    }
    
    public function getDescription() {
        return "{$this->year} {$this->name} {$this->model}";
    }
}

// Showroom Management Class
class Showroom {
    private $name;
    private $vehicles = [];
    private $stats = [];
    
    public function __construct($name) {
        $this->name = $name;
        $this->stats = [
            'models_sold' => 5000,
            'satisfaction_rate' => 98,
            'years_hobby' => 15
        ];
    }
    
    public function addVehicle(Vehicle $vehicle) {
        $this->vehicles[] = $vehicle;
    }
    
    public function getAllVehicles() {
        return $this->vehicles;
    }
    
    public function getVehicleById($id) {
        foreach ($this->vehicles as $vehicle) {
            if ($vehicle->getId() == $id) {
                return $vehicle;
            }
        }
        return null;
    }
    
    public function getFeaturedVehicles($limit = 4) {
        return array_slice($this->vehicles, 0, $limit);
    }
    
    public function getName() {
        return $this->name;
    }
    
    public function getStats() {
        return $this->stats;
    }
    
    public function getTotalVehicles() {
        return count($this->vehicles);
    }
}

// Navigation Menu Class
class Navigation {
    private $menuItems = [];
    private $isLoggedIn;
    
    public function __construct($isLoggedIn = false) {
        $this->isLoggedIn = $isLoggedIn;
        $this->initializeMenu();
    }
    
    private function initializeMenu() {
        $this->menuItems = [
            ['title' => 'Home', 'url' => 'index.php'],
            ['title' => 'Services', 'url' => '#services'],
            ['title' => 'About', 'url' => '#about'],
            ['title' => 'Contact', 'url' => '#contact']
        ];
    }
    
    public function getMenuItems() {
        return $this->menuItems;
    }
    
    public function isUserLoggedIn() {
        return $this->isLoggedIn;
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

// ===================== CONTACT DOMAIN =====================
// Simple value object for a contact message
class ContactMessage {
    public string $name;
    public string $email;
    public string $subject;
    public string $message;

    public function __construct(string $name, string $email, string $subject, string $message) {
        $this->name = $name;
        $this->email = $email;
        $this->subject = $subject;
        $this->message = $message;
    }
}

// Repository responsible for persisting contact messages to MySQL
class ContactMessageRepository {
    private \PDO $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function save(ContactMessage $m): void {
        $stmt = $this->pdo->prepare('INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)');
        $stmt->execute([$m->name, $m->email, $m->subject, $m->message]);
    }
}

// Service that validates input and delegates to repository
class ContactService {
    private ContactMessageRepository $repo;

    public function __construct(ContactMessageRepository $repo) {
        $this->repo = $repo;
    }

    /**
     * Validate inputs and save on success. Returns array with 'ok' bool and 'errors' array.
     */
    public function submit(array $input): array {
        $name = trim((string)($input['name'] ?? ''));
        $email = trim((string)($input['email'] ?? ''));
        $subject = trim((string)($input['subject'] ?? ''));
        $message = trim((string)($input['message'] ?? ''));

        $errors = [];
        if ($name === '') $errors['name'] = 'Name is required';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Valid email is required';
        if ($subject === '') $errors['subject'] = 'Subject is required';
        if ($message === '' || strlen($message) < 10) $errors['message'] = 'Message must be at least 10 characters';

        if (!empty($errors)) {
            return ['ok' => false, 'errors' => $errors];
        }

        $this->repo->save(new ContactMessage($name, $email, $subject, $message));
        return ['ok' => true, 'errors' => []];
    }
}

// Cart class is now imported from classes/Cart.php for consistency across all pages

// Product Repository for fetching from MySQL database
class ProductRepository {
    private \PDO $pdo;
    
    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Fetch all products from database with stock information
     * Returns array of product data with id, name, brand, scale, variant, price, stock, image_url
     */
    public function getAllProducts(): array {
        $stmt = $this->pdo->prepare('SELECT id, name, brand, scale, variant, price, stock, image_url FROM products ORDER BY id');
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

// Page Renderer Class
class PageRenderer {
    private $showroom;
    private $navigation;
    private ProductRepository $productRepo;
    
    public function __construct(Showroom $showroom, Navigation $navigation, ProductRepository $productRepo) {
        $this->showroom = $showroom;
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
            <title><?php echo $this->showroom->getName(); ?> - Premium Car Showroom</title>
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
                
                /* Smooth scrolling for anchor links */
                html {
                    scroll-behavior: smooth;
                }
                
                /* Offset for fixed navigation */
                section[id] {
                    scroll-margin-top: 80px;
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
                        <span class="text-2xl font-bold text-white"><?php echo $this->showroom->getName(); ?></span>
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
        <div class="relative h-screen">
            <div class="absolute inset-0 bg-gradient-to-r from-black/80 to-black/40 z-10"></div>
            <img src="https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?w=1920&h=1080&fit=crop" alt="Hero" class="w-full h-full object-cover">
            <div class="absolute inset-0 z-20 flex items-center">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
                    <div class="max-w-3xl animate-fade-in">
                        <h1 class="text-5xl md:text-7xl font-bold text-white mb-6">Collect Your Dreams</h1>
                        <p class="text-xl md:text-2xl text-gray-300 mb-8">Explore our collection of <?php echo $this->showroom->getTotalVehicles(); ?>+ premium diecast car models</p>
                        <div class="flex flex-col sm:flex-row gap-4">
                            <a href="#our-collection" class="bg-red-600 hover:bg-red-700 text-white px-8 py-4 rounded-lg font-semibold transition transform hover:scale-105 text-center">Shop Collection</a>
                            <a href="#contact" class="bg-white/10 backdrop-blur-sm hover:bg-white/20 text-white px-8 py-4 rounded-lg font-semibold border border-white/20 transition text-center">Preorder New Releases</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render product card with stock information from database
     * Updated to show stock availability next to the Add to Cart button
     */
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
                <form method="post" action="index.php">
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
    
    /**
     * Render products section with stock information from database
     * Updated to fetch products from MySQL instead of in-memory showroom
     */
    public function renderProducts() {
        ob_start();
        // Fetch products from database with stock information
        $products = $this->productRepo->getAllProducts();
        ?>
        <section id="our-collection" class="py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-4xl md:text-5xl font-bold text-white mb-4">Our Collection</h2>
                    <p class="text-gray-400 text-lg">Premium diecast models with real-time stock availability</p>
                </div>

                <?php if (empty($products)): ?>
                    <div class="text-center py-20">
                        <div class="text-gray-400 mb-4">
                            <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0112 15c-2.34 0-4.29-1.009-5.824-2.709M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-white mb-4">No Products Available</h3>
                        <p class="text-gray-400 mb-8">Check back soon for our latest diecast models!</p>
                    </div>
                <?php else: ?>
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
    
    public function renderStats() {
        $stats = $this->showroom->getStats();
        ob_start();
        ?>
        <div class="bg-gradient-to-r from-red-600 to-red-800 py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
                    <div>
                        <div class="text-5xl font-bold text-white mb-2"><?php echo $stats['models_sold']; ?>+</div>
                        <div class="text-white/90 text-lg">Models Sold</div>
                    </div>
                    <div>
                        <div class="text-5xl font-bold text-white mb-2"><?php echo $stats['satisfaction_rate']; ?>%</div>
                        <div class="text-white/90 text-lg">Customer Satisfaction</div>
                    </div>
                    <div>
                        <div class="text-5xl font-bold text-white mb-2"><?php echo $stats['years_hobby']; ?>+</div>
                        <div class="text-white/90 text-lg">Years in Hobby</div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // Services section: three service cards with icon, title, and description
    public function renderServices() {
        ob_start();
        ?>
        <section id="services" class="py-20 bg-black/40 border-t border-white/10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-4xl font-bold text-white mb-3">Our Services</h2>
                    <p class="text-gray-400">Everything you need to grow and protect your diecast collection.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="p-6 rounded-xl bg-white/5 border border-white/10">
                        <div class="text-red-600 mb-3">
                            <!-- icon: box -->
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                        <h3 class="text-white font-semibold text-lg mb-1">Premium Curation</h3>
                        <p class="text-gray-400">Hand-picked 1:18 and 1:24 models from trusted brands—detail, finish, and value.</p>
                    </div>
                    <div class="p-6 rounded-xl bg-white/5 border border-white/10">
                        <div class="text-red-600 mb-3">
                            <!-- icon: sparkles -->
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 11l2-2m0 0l2-2m-2 2l2 2m-2-2L5 9m9 7l2-2m0 0l2-2m-2 2l2 2m-2-2l-2 2M9 17l3 3m0 0l3-3m-3 3V4"/></svg>
                        </div>
                        <h3 class="text-white font-semibold text-lg mb-1">Limited Preorders</h3>
                        <p class="text-gray-400">Secure rare releases early and get updates from announcement to delivery.</p>
                    </div>
                    <div class="p-6 rounded-xl bg-white/5 border border-white/10">
                        <div class="text-red-600 mb-3">
                            <!-- icon: shield-check -->
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h3 class="text-white font-semibold text-lg mb-1">Secure Shipping</h3>
                        <p class="text-gray-400">Protective packaging, fast dispatch, and tracking for every order.</p>
                    </div>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    // About section: brief description with mission and vision
    public function renderAbout() {
        ob_start();
        ?>
        <section id="about" class="py-20">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-4xl font-bold text-white mb-6">About Elite Diecast</h2>
                <p class="text-gray-300 mb-6">We curate premium diecast models for collectors who value craftsmanship and authenticity. Our collection focuses on detail, finish, and long-term collectability.</p>
                <ul class="space-y-3 text-gray-300">
                    <li><span class="font-semibold text-white">Mission:</span> Make collecting effortless and rewarding through trusted models and expert guidance.</li>
                    <li><span class="font-semibold text-white">Vision:</span> Build a global community where every collector can discover, learn, and share their passion.</li>
                </ul>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    // Contact section: simple contact details without form
    public function renderContact(): string {
        ob_start();
        ?>
        <section id="contact" class="py-20 bg-black/40 border-t border-white/10">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-4xl font-bold text-white mb-6">Contact Us</h2>
                <p class="text-gray-300 mb-8">Get in touch with our team for any questions or special requests.</p>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center p-6 rounded-xl bg-white/5 border border-white/10">
                        <div class="text-red-600 mb-3">
                            <svg class="w-8 h-8 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                        <h3 class="text-white font-semibold mb-2">Email</h3>
                        <p class="text-gray-400">support@elitediecast.com</p>
                    </div>
                    <div class="text-center p-6 rounded-xl bg-white/5 border border-white/10">
                        <div class="text-red-600 mb-3">
                            <svg class="w-8 h-8 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        </div>
                        <h3 class="text-white font-semibold mb-2">Phone</h3>
                        <p class="text-gray-400">+1 (555) 123-4567</p>
                    </div>
                    <div class="text-center p-6 rounded-xl bg-white/5 border border-white/10">
                        <div class="text-red-600 mb-3">
                            <svg class="w-8 h-8 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <h3 class="text-white font-semibold mb-2">Address</h3>
                        <p class="text-gray-400">123 Collector's Lane<br>Suite 18, Pasadena, CA 91101</p>
                    </div>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
    
    public function renderFooter() {
        ob_start();
        ?>
        <footer id="contact" class="bg-black border-t border-white/10 py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <div>
                        <div class="flex items-center space-x-3 mb-4">
                            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v10m4-10v10m4-10v10M3 5h18M5 5v14a2 2 0 002 2h10a2 2 0 002-2V5" />
                            </svg>
                            <span class="text-xl font-bold text-white"><?php echo $this->showroom->getName(); ?></span>
                        </div>
                        <p class="text-gray-400">Your trusted destination for premium luxury vehicles.</p>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold mb-4">Quick Links</h4>
                        <div class="space-y-2 text-gray-400">
                            <a href="#about" class="block hover:text-red-600">About Us</a>
                            <a href="#inventory" class="block hover:text-red-600">Inventory</a>
                            <a href="#" class="block hover:text-red-600">Financing</a>
                            <a href="#" class="block hover:text-red-600">Trade-In</a>
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
                                <span>info@elitemotors.com</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span>📍</span>
                                <span>123 Luxury Ave, CA</span>
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
                    <p>&copy; 2025 <?php echo $this->showroom->getName(); ?>. All rights reserved.</p>
                </div>
            </div>
        </footer>

        <script>
            function toggleMenu() {
                const menu = document.getElementById('mobileMenu');
                menu.classList.toggle('hidden');
            }
            
            // Enhanced smooth scrolling with offset for fixed navigation
            document.addEventListener('DOMContentLoaded', function() {
                // Handle anchor links with smooth scrolling
                const anchorLinks = document.querySelectorAll('a[href^="#"]');
                anchorLinks.forEach(link => {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        const targetId = this.getAttribute('href').substring(1);
                        const targetElement = document.getElementById(targetId);
                        
                        if (targetElement) {
                            const offsetTop = targetElement.offsetTop - 80; // Account for fixed navigation
                            window.scrollTo({
                                top: offsetTop,
                                behavior: 'smooth'
                            });
                        }
                    });
                });
                
                // Add scroll effect to navigation
                window.addEventListener('scroll', function() {
                    const nav = document.querySelector('nav');
                    if (window.scrollY > 100) {
                        nav.classList.add('bg-black/98');
                    } else {
                        nav.classList.remove('bg-black/98');
                    }
                });
                
                // Add intersection observer for fade-in animations
                const observerOptions = {
                    threshold: 0.1,
                    rootMargin: '0px 0px -50px 0px'
                };
                
                const observer = new IntersectionObserver(function(entries) {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('animate-fade-in');
                        }
                    });
                }, observerOptions);
                
                // Observe all product cards
                document.querySelectorAll('.bg-white\\/5').forEach(card => {
                    observer.observe(card);
                });
            });
        </script>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    public function render() {
        echo $this->renderHeader();
        echo $this->renderNavigation();
        echo $this->renderHero();
        echo $this->renderProducts();
        echo $this->renderStats();
        // New sections appended below, matching existing layout and styling
        echo $this->renderServices();
        echo $this->renderAbout();
        echo $this->renderContact();
        echo $this->renderFooter();
    }

}

// Initialize the application
$showroom = new Showroom("Elite Diecast");

// Add vehicles to showroom
$showroom->addVehicle(new Car(1, "Lamborghini", "Huracán EVO 1:18", 2024, 129, "https://images.unsplash.com/photo-1544636331-e26879cd4d9b?w=800&h=500&fit=crop", "Diecast Supercar"));
$showroom->addVehicle(new Car(2, "Ferrari", "SF90 Stradale 1:24", 2024, 59, "https://images.unsplash.com/photo-1555215695-3004980ad54e?w=800&h=500&fit=crop", "Diecast Sports"));
$showroom->addVehicle(new Car(3, "Porsche", "911 GT3 RS 1:18", 2024, 119, "https://images.unsplash.com/photo-1503376780353-7e6692767b70?w=800&h=500&fit=crop", "Diecast Sports"));
$showroom->addVehicle(new Car(4, "Nissan", "GT-R R35 1:24", 2024, 49, "https://images.unsplash.com/photo-1618843479313-40f8afb4b4d8?w=800&h=500&fit=crop", "Diecast JDM"));
$showroom->addVehicle(new Car(5, "Mercedes-AMG", "G63 1:24", 2024, 54, "https://images.unsplash.com/photo-1606664515524-ed2f786a0bd6?w=800&h=500&fit=crop", "Diecast SUV"));
$showroom->addVehicle(new Car(6, "Toyota", "Supra MK4 1:24", 2024, 45, "https://images.unsplash.com/photo-1523986371872-9d3ba2e2f642?w=800&h=500&fit=crop", "Diecast JDM"));

// Create navigation with login status
$navigation = new Navigation(isset($_SESSION['user']));

// Create product repository for database access
$productRepo = new ProductRepository(Database::getConnection());

// Create page renderer and render the page
// Handle Add to Cart POST at the entry point before rendering
// This ensures the cart session is updated and the page still renders normally.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'], $_POST['product_id'])) {
    // Basic sanitization and cart update
    $productId = (int)$_POST['product_id'];
    $cart = new Cart();
    $cart->addProduct($productId, 1);
    // Optional: PRG pattern to avoid resubmission on refresh
    header('Location: index.php');
    exit;
}

$page = new PageRenderer($showroom, $navigation, $productRepo);
$page->render();
?>