# Admin Panel Setup Guide - Elite Diecast

## 🎯 What Has Been Created

A complete PHP OOP admin panel with the following features:

### ✅ Files Created
1. **admin/login.php** - Admin authentication with validation
2. **admin/dashboard.php** - Main dashboard with statistics
3. **admin/users.php** - User management (view, edit, delete)
4. **admin/products.php** - Product management (add, view, edit, delete)
5. **admin/orders.php** - Order management with status updates
6. **admin/logout.php** - Session cleanup
7. **admin/README.md** - Detailed documentation

### ✅ Database Updates
- Added `admins` table with default account
- Updated `orders` table status enum (pending, shipped, delivered, cancelled)
- Seeded admin account with secure password hash

## 🔑 Default Admin Credentials

```
Email: admin@gmail.com
Password: Admin@1234
```

## 📦 Installation Steps

### Step 1: Update Database
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Select the `car_showroom` database (or create it)
3. Go to SQL tab
4. Copy and paste the entire contents of `database.sql`
5. Click "Go" to execute

### Step 2: Verify Configuration
Ensure your `config.php` file contains:
```php
<?php
class Database {
    private static $host = 'localhost';
    private static $db = 'car_showroom';
    private static $user = 'root';
    private static $pass = '';
    
    public static function getConnection(): PDO {
        $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$db . ";charset=utf8mb4";
        return new PDO($dsn, self::$user, self::$pass);
    }
}
```

### Step 3: Access Admin Panel
1. Start XAMPP (Apache + MySQL)
2. Navigate to: `http://localhost/Car/admin/login.php`
3. Login with default credentials
4. You're ready to go!

## 🎨 Features Overview

### Dashboard
- **Statistics Cards:** Total users, products, orders, and revenue
- **Recent Orders:** View latest 5 orders with status
- **Low Stock Alert:** Products with stock < 10
- **Quick Actions:** Links to add products, view users, manage orders

### User Management
- **View All Users:** Table with user details and order counts
- **Edit Users:** Update name and email
- **Delete Users:** Remove users (with foreign key protection)
- **Sort by Date:** Most recent registrations first

### Product Management
- **Add Products:** Full form with all product details
  - Required: Name, Brand, Scale, Price, Stock
  - Optional: Variant, Year, Type, Image URL
- **Edit Products:** Update any product information
- **Delete Products:** Remove products (with order protection)
- **Stock Indicators:** Color-coded (red < 5, yellow < 10, green >= 10)
- **View All Products:** Complete inventory listing

### Order Management
- **View All Orders:** Customer details, status, total, date
- **Order Statistics:** Breakdown by status with revenue
- **View Order Details:** See all items in an order
- **Update Status:** Change order status (pending → shipped → delivered)
- **Status Indicators:** Color-coded status badges

## 🏗️ OOP Architecture

### Design Patterns Used
1. **Repository Pattern:** Separates data access from business logic
2. **Entity Classes:** Represent database models (Admin, User, Product, Order)
3. **Service Classes:** Handle business logic (AuthService, DashboardStats)
4. **Single Responsibility:** Each class has one clear purpose

### Class Structure Example
```php
// Entity Class
class Product {
    private int $id;
    private string $name;
    // ... getters
}

// Repository Class
class ProductRepository {
    private PDO $pdo;
    
    public function getAllProducts(): array { }
    public function createProduct(): bool { }
    public function updateProduct(): bool { }
    public function deleteProduct(): bool { }
}
```

## 🔒 Security Features

1. **Session Management:** All pages check for admin session
2. **Password Hashing:** bcrypt with cost factor 10
3. **SQL Injection Prevention:** PDO prepared statements everywhere
4. **XSS Protection:** htmlspecialchars() on all output
5. **CSRF Protection:** POST requests for destructive actions
6. **Input Validation:** Server-side and client-side validation

## 🎯 Usage Examples

### Example 1: Adding a New Product
```php
// Navigate to Products page
// Click "Add New Product"
// Fill in:
Name: "Bugatti Chiron"
Brand: "Bugatti"
Scale: "1:18"
Variant: "Sport"
Year: 2024
Type: "Diecast Hypercar"
Price: 149.00
Stock: 5
Image URL: https://example.com/bugatti.jpg
// Click "Add Product"
```

### Example 2: Managing an Order
```php
// Navigate to Orders page
// Click "View Details" on Order #5
// Change status from "pending" to "shipped"
// Click "Update Status"
// Customer receives updated order status
```

### Example 3: Editing a User
```php
// Navigate to Users page
// Click "Edit" next to user
// Update name: "John Smith" → "John Doe"
// Update email: "john@old.com" → "john@new.com"
// Click "Save Changes"
```

## 📊 Database Tables

### admins
```sql
CREATE TABLE admins (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Relationships
- `orders.user_id` → `users.id` (CASCADE on delete)
- `order_items.order_id` → `orders.id` (CASCADE on delete)
- `order_items.product_id` → `products.id` (RESTRICT on delete)

## 🎨 UI Features

### Design Elements
- **Dark Theme:** Modern gradient background
- **Glassmorphism:** Backdrop blur effects
- **Responsive:** Works on desktop, tablet, mobile
- **Color Coding:** Visual indicators for status/stock
- **Icons:** SVG icons for better UX
- **Hover Effects:** Interactive button states

### Navigation
- Persistent top navbar with admin name
- Active page highlighting
- Quick logout button
- Responsive mobile menu (can be enhanced)

## 🚀 Testing Checklist

- [ ] Login with default credentials
- [ ] View dashboard statistics
- [ ] Add a new product
- [ ] Edit an existing product
- [ ] View all users
- [ ] Edit a user's details
- [ ] View all orders
- [ ] Update an order status
- [ ] View order details
- [ ] Check low stock alerts
- [ ] Test logout functionality

## 🐛 Troubleshooting

### Issue: Cannot login
**Solution:** 
- Verify admin exists in database: `SELECT * FROM admins WHERE email = 'admin@gmail.com'`
- Re-run database.sql if needed
- Clear browser cache/cookies

### Issue: Database connection error
**Solution:**
- Check XAMPP MySQL is running
- Verify database name is `car_showroom`
- Check config.php credentials

### Issue: Session errors
**Solution:**
- Ensure `session_start()` is at top of each file
- Check PHP error logs in XAMPP

### Issue: Products not showing
**Solution:**
- Verify products exist: `SELECT * FROM products`
- Check ProductRepository connection
- Look for PHP errors in browser console

## 📝 Code Comments

All code includes comprehensive comments:
- Class-level PHPDoc blocks
- Method documentation with @param and @return
- Inline comments for complex logic
- Security notes where applicable

## 🔄 Future Enhancements

Potential improvements:
1. Multi-admin support with role-based permissions
2. Email notifications for order updates
3. Sales analytics with charts
4. Product image upload (vs URL only)
5. Bulk operations (delete multiple, export CSV)
6. Advanced search and filtering
7. Activity logs for audit trail
8. Two-factor authentication
9. Password reset functionality
10. API endpoints for mobile app

## 📞 Support

If you encounter issues:
1. Check all files are in `C:\xampp\htdocs\Car\admin\`
2. Verify database tables exist
3. Ensure XAMPP services are running
4. Check PHP error logs: `C:\xampp\php\logs\php_error_log`
5. Review browser console for JavaScript errors

## ✅ Completion Status

All requested features implemented:
- ✅ Admin Login with default credentials
- ✅ Session handling and validation
- ✅ User Management (view, edit, delete)
- ✅ Product Management (add, view, edit, delete)
- ✅ Order Management (view, status updates, details)
- ✅ MySQL integration
- ✅ Proper OOP structure
- ✅ Secure password handling
- ✅ HTML/CSS frontend with Tailwind
- ✅ Comprehensive comments

---

**Ready to use!** Navigate to `http://localhost/Car/admin/login.php` and start managing your store.

**Version:** 1.0  
**Created:** October 2024


