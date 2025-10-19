# Elite Diecast Admin Panel

## Overview
Complete PHP OOP-based admin panel for managing the Elite Diecast store with user management, product inventory, and order processing capabilities.

## Features

### 1. Admin Authentication
- Secure login system with session management
- Password hashing using bcrypt
- Client-side and server-side validation
- Protected admin routes

### 2. Dashboard
- Real-time statistics (users, products, orders, revenue)
- Recent orders overview
- Low stock alerts
- Quick action buttons

### 3. User Management
- View all registered users
- Edit user details (name, email)
- Delete users
- View user order count
- User activity tracking

### 4. Product Management
- Add new products with full details
- View all products with stock status
- Edit product information
- Delete products
- Stock level indicators (color-coded)
- Image URL support

### 5. Order Management
- View all orders with customer details
- Order status tracking (pending, shipped, delivered, cancelled)
- Update order status
- View detailed order items
- Order statistics by status
- Revenue tracking per status

## Default Credentials

**Email:** admin@gmail.com  
**Password:** Admin@1234

⚠️ **Important:** Change these credentials after first login!

## Installation

1. **Import Database:**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Run the `database.sql` file from the parent directory
   - This creates all tables and seeds the admin account

2. **Verify Configuration:**
   - Ensure `config.php` exists in the parent directory
   - Database connection should use XAMPP defaults (root/blank password)

3. **Access Admin Panel:**
   - Navigate to: `http://localhost/Car/admin/login.php`
   - Login with default credentials
   - Start managing your store!

## File Structure

```
admin/
├── login.php           # Admin authentication page
├── dashboard.php       # Main dashboard with statistics
├── users.php           # User management interface
├── products.php        # Product CRUD operations
├── orders.php          # Order management and status updates
├── logout.php          # Session cleanup
└── README.md           # This file
```

## OOP Architecture

### Classes Used

**Authentication:**
- `Admin` - Admin entity class
- `AdminRepository` - Database operations for admins
- `AdminAuthService` - Authentication logic

**Dashboard:**
- `DashboardStats` - Fetches dashboard statistics

**Users:**
- `User` - User entity class
- `UserRepository` - User CRUD operations

**Products:**
- `Product` - Product entity class
- `ProductRepository` - Product CRUD operations

**Orders:**
- `Order` - Order entity class
- `OrderItem` - Order item entity class
- `OrderRepository` - Order operations and statistics

## Security Features

1. **Session Management:** All pages verify admin session
2. **Password Hashing:** Bcrypt with cost factor 10
3. **SQL Injection Prevention:** PDO prepared statements
4. **XSS Protection:** htmlspecialchars() on all outputs
5. **CSRF Protection:** POST requests for destructive actions
6. **Access Control:** Redirect to login if not authenticated

## Database Schema

### admins
- id (INT, PRIMARY KEY)
- name (VARCHAR)
- email (VARCHAR, UNIQUE)
- password_hash (VARCHAR)
- created_at (TIMESTAMP)

## Usage Examples

### Adding a Product
1. Navigate to Products page
2. Click "Add New Product"
3. Fill in required fields (name, brand, scale, price, stock)
4. Optional: Add variant, year, type, image URL
5. Click "Add Product"

### Updating Order Status
1. Navigate to Orders page
2. Click "View Details" on any order
3. Select new status from dropdown
4. Click "Update Status"

### Editing User Details
1. Navigate to Users page
2. Click "Edit" next to the user
3. Update name or email
4. Click "Save Changes"

## Technologies Used

- **Backend:** PHP 8.x with OOP principles
- **Database:** MySQL with PDO
- **Frontend:** Tailwind CSS for styling
- **Security:** Password hashing, prepared statements
- **Design:** Responsive, modern UI with dark theme

## Best Practices

1. **OOP Structure:** All functionality encapsulated in classes
2. **Separation of Concerns:** Repository pattern for data access
3. **Type Safety:** Type hints for all method parameters and returns
4. **Error Handling:** Try-catch blocks for database operations
5. **Comments:** Comprehensive PHPDoc comments

## Troubleshooting

**Login Issues:**
- Verify database connection in `config.php`
- Check that admin account exists in `admins` table
- Clear browser cache and cookies

**Permission Errors:**
- Ensure XAMPP Apache and MySQL are running
- Check database user permissions

**Session Issues:**
- Verify `session_start()` is at the top of each file
- Check PHP session configuration

## Future Enhancements

- [ ] Admin role permissions (super admin, manager, etc.)
- [ ] Email notifications for orders
- [ ] Export reports (CSV, PDF)
- [ ] Product categories and filters
- [ ] Sales analytics and charts
- [ ] Activity logs for admin actions

## Support

For issues or questions:
1. Check database connection settings
2. Verify all files are in correct directories
3. Ensure XAMPP services are running
4. Review browser console for JavaScript errors

---

**Version:** 1.0  
**Last Updated:** October 2024  
**Author:** Elite Diecast Development Team


