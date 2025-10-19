# Unified Login System Documentation

## Overview

The login system has been updated to provide a **single unified login page** that handles authentication for both **Admin** and **User** accounts. This simplifies the user experience and maintains security through proper session management and password hashing.

## 🔑 How It Works

### Authentication Flow

```
User enters credentials on login.php
           ↓
    Check Admin Table First
           ↓
    ┌─────────────────────┐
    │ Is Admin?           │
    └─────────────────────┘
           ↓ YES              ↓ NO
    Create Admin Session    Check User Table
           ↓                      ↓
    Redirect to              ┌─────────────────────┐
    admin/dashboard.php      │ Is Valid User?      │
                            └─────────────────────┘
                                ↓ YES      ↓ NO
                         Create User    Show Error
                            Session     Message
                                ↓
                         Redirect to
                         dashboard.php
```

## 🔐 Credentials

### Admin Access
- **Email:** `admin@gmail.com`
- **Password:** `Admin@1234`
- **Redirects to:** `admin/dashboard.php`

### User Access
- **Email:** Any registered user email
- **Password:** User's registered password
- **Redirects to:** `dashboard.php` (user dashboard)

## 📁 File Structure

### Main Files
- **`login.php`** - Unified login page (handles both admin and user)
- **`admin/login.php`** - Redirects to main login.php
- **`admin/index.php`** - Redirects to main login.php

### Modified Components
```
login.php
├── authenticateAdmin()    // Checks admins table
├── authenticateUser()     // Checks users table
└── process()              // Main authentication logic
```

## 🏗️ OOP Architecture

### LoginPage Class

```php
class LoginPage {
    /**
     * Main entry point
     */
    public function handle(): void
    
    /**
     * Process login attempt
     * 1. Check admin credentials
     * 2. Check user credentials
     * 3. Show error if neither match
     */
    private function process(): void
    
    /**
     * Authenticate against admins table
     * Creates $_SESSION['admin'] on success
     * Redirects to admin/dashboard.php
     */
    private function authenticateAdmin(string $email, string $password): bool
    
    /**
     * Authenticate against users table
     * Creates $_SESSION['user'] on success
     * Redirects to dashboard.php
     */
    private function authenticateUser(string $email, string $password): bool
    
    /**
     * Render login form HTML
     */
    private function render(): void
}
```

## 🔒 Security Features

### 1. Password Hashing
- **Algorithm:** bcrypt (PASSWORD_DEFAULT)
- **Admin password:** Pre-hashed in database
- **User passwords:** Hashed during registration
- **Verification:** `password_verify()` for both admin and users

### 2. SQL Injection Prevention
- **PDO Prepared Statements** used for all queries
- **Parameterized queries** prevent injection attacks
```php
$stmt = $pdo->prepare('SELECT ... FROM admins WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
```

### 3. Session Management
- **Admin Session:**
  ```php
  $_SESSION['admin'] = [
      'id' => (int)$admin['id'],
      'name' => (string)$admin['name'],
      'email' => (string)$admin['email']
  ];
  ```
- **User Session:**
  ```php
  $_SESSION['user'] = [
      'id' => (int)$user['id'],
      'name' => (string)$user['name'],
      'email' => (string)$user['email']
  ];
  ```

### 4. XSS Protection
- `htmlspecialchars()` on all output
- Form validation on client and server side

### 5. Access Control
- Admin pages check for `$_SESSION['admin']`
- User pages check for `$_SESSION['user']`
- Unauthorized access redirects to login

## 🎨 UI Features

### Login Form
- **Email field** with validation
- **Password field** with min-length validation
- **Live validation** (client-side JavaScript)
- **Server-side validation** (PHP)
- **Error messages** displayed in red banner

### Admin Credentials Display
- Blue info box showing default admin credentials
- Helpful for testing and initial setup
- Can be removed in production

### Responsive Design
- **Dark theme** with glassmorphism effects
- **Mobile-friendly** layout
- **Tailwind CSS** styling

## 📊 Database Schema

### admins Table
```sql
CREATE TABLE admins (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### users Table
```sql
CREATE TABLE users (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## 🚀 Usage Examples

### Example 1: Admin Login
```
1. Navigate to: http://localhost/Car/login.php
2. Enter:
   - Email: admin@gmail.com
   - Password: Admin@1234
3. Click "Login"
4. ✅ Redirected to: admin/dashboard.php
5. Session: $_SESSION['admin'] is set
```

### Example 2: User Login
```
1. Navigate to: http://localhost/Car/login.php
2. Enter:
   - Email: user@example.com (registered user)
   - Password: UserPassword123
3. Click "Login"
4. ✅ Redirected to: dashboard.php
5. Session: $_SESSION['user'] is set
```

### Example 3: Invalid Credentials
```
1. Navigate to: http://localhost/Car/login.php
2. Enter:
   - Email: invalid@example.com
   - Password: WrongPassword
3. Click "Login"
4. ❌ Error: "Invalid email or password. Please try again or register."
5. Stays on login page
```

## 🔄 Migration from Old System

### Before (Separate Login Pages)
- `login.php` → User login only
- `admin/login.php` → Admin login only
- Two separate authentication systems
- Confusion about which page to use

### After (Unified Login)
- `login.php` → Both admin and user authentication
- `admin/login.php` → Redirects to main login
- Single authentication system
- Clear and simple user experience

## 🧪 Testing Checklist

### Admin Authentication
- [ ] Login with admin@gmail.com / Admin@1234
- [ ] Verify redirect to admin/dashboard.php
- [ ] Verify `$_SESSION['admin']` is set
- [ ] Access admin features (users, products, orders)
- [ ] Logout and verify session cleared

### User Authentication
- [ ] Register a new user account
- [ ] Login with user credentials
- [ ] Verify redirect to dashboard.php
- [ ] Verify `$_SESSION['user']` is set
- [ ] Access user features
- [ ] Logout and verify session cleared

### Error Handling
- [ ] Test with empty email/password
- [ ] Test with invalid email format
- [ ] Test with wrong password
- [ ] Test with non-existent user
- [ ] Verify error messages display correctly

### Security
- [ ] Verify passwords are hashed in database
- [ ] Verify SQL injection prevention
- [ ] Verify XSS protection on error messages
- [ ] Verify session timeout behavior
- [ ] Verify unauthorized access redirects

## 📝 Code Comments

All code includes comprehensive inline comments:

```php
/**
 * Authenticate admin credentials
 * Checks against admins table in database
 * 
 * @param string $email User-provided email
 * @param string $password User-provided password (plain text)
 * @return bool True if admin authenticated successfully
 */
private function authenticateAdmin(string $email, string $password): bool
```

## 🎯 Key Changes Summary

### 1. **Unified Authentication** ✅
- Single login page for both admin and users
- Automatic detection of account type
- Proper redirect based on role

### 2. **Session Handling** ✅
- Admin session: `$_SESSION['admin']`
- User session: `$_SESSION['user']`
- Secure session management

### 3. **Password Hashing** ✅
- bcrypt for both admin and users
- `password_verify()` for validation
- Secure password storage

### 4. **Maintained Layout** ✅
- Existing Tailwind CSS design
- Dark theme preserved
- Responsive layout maintained

### 5. **OOP Structure** ✅
- Clean class-based architecture
- Separation of concerns
- Reusable methods

## 🐛 Troubleshooting

### Issue: Admin login not working
**Solution:**
- Verify admin exists in database: `SELECT * FROM admins WHERE email = 'admin@gmail.com'`
- Check password hash matches
- Re-run database.sql if needed

### Issue: User login not working
**Solution:**
- Register a new user first
- Verify password was hashed during registration
- Check users table for account

### Issue: Redirects not working
**Solution:**
- Check that files exist:
  - `admin/dashboard.php`
  - `dashboard.php` (user dashboard)
- Verify session_start() at top of files
- Check for output before header()

### Issue: Session not persisting
**Solution:**
- Verify PHP session settings
- Check session_start() placement
- Clear browser cookies and try again

## 🔮 Future Enhancements

Potential improvements:
- [ ] Remember me functionality
- [ ] Two-factor authentication
- [ ] Password reset via email
- [ ] Account lockout after failed attempts
- [ ] Login activity logging
- [ ] Social login integration (Google, Facebook)
- [ ] Role-based permissions (multiple admin levels)

## 📞 Support

For questions or issues:
1. Check database contains admin account
2. Verify config.php database connection
3. Test with provided admin credentials
4. Check PHP error logs for debugging
5. Ensure XAMPP services are running

---

**Version:** 2.0 (Unified Login)  
**Last Updated:** October 2024  
**Compatible with:** PHP 8.x, MySQL 5.7+

## ✅ Summary

The unified login system provides:
- ✅ Single login page for admin and users
- ✅ Automatic role detection
- ✅ Secure password hashing (bcrypt)
- ✅ Proper session management
- ✅ SQL injection prevention
- ✅ XSS protection
- ✅ Clean OOP architecture
- ✅ Maintained UI/UX design
- ✅ Comprehensive comments

**Access the unified login at:** `http://localhost/Car/login.php`


