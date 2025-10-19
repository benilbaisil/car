# Troubleshooting Guide - "Unexpected error. Please try again."

## 🔍 Quick Diagnosis

If you're seeing "Unexpected error. Please try again." on the login page, follow these steps:

### Step 1: Run Database Check
Navigate to: **`http://localhost/Car/check_database.php`**

This will automatically test:
- ✅ Database connection
- ✅ Tables exist (admins, users, products)
- ✅ Admin account exists
- ✅ Password hash verification

### Step 2: Common Issues & Solutions

#### Issue 1: Database Not Found
**Error:** `Database error: SQLSTATE[HY000] [1049] Unknown database 'car_showroom'`

**Solution:**
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Click "New" to create a database
3. Name it: `car_showroom`
4. Click "Create"
5. Select the database
6. Go to "SQL" tab
7. Copy and paste the entire contents of `database.sql`
8. Click "Go"

#### Issue 2: Admins Table Missing
**Error:** `Database error: Table 'car_showroom.admins' doesn't exist`

**Solution:**
Run the `database.sql` file in phpMyAdmin:
```sql
-- Make sure this table exists
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert admin account
INSERT INTO `admins` (`name`, `email`, `password_hash`) VALUES
('System Admin', 'admin@gmail.com', '$2y$10$Ks3g809GYRfPzOMXuefKo.N9fAgJRBj0GaleX2AswBdtGYSLudgli');
```

#### Issue 3: Admin Account Missing
**Error:** Login works but no admin account found

**Solution:**
Run this SQL in phpMyAdmin:
```sql
INSERT INTO `admins` (`name`, `email`, `password_hash`) VALUES
('System Admin', 'admin@gmail.com', '$2y$10$Ks3g809GYRfPzOMXuefKo.N9fAgJRBj0GaleX2AswBdtGYSLudgli');
```

#### Issue 4: Wrong Password Hash
**Error:** Can't login even with correct credentials

**Solution:**
1. Generate a new hash:
```bash
C:\xampp\php\php.exe -r "echo password_hash('Admin@1234', PASSWORD_DEFAULT);"
```

2. Update in database:
```sql
UPDATE admins 
SET password_hash = 'YOUR_NEW_HASH_HERE' 
WHERE email = 'admin@gmail.com';
```

#### Issue 5: MySQL Not Running
**Error:** `Database error: SQLSTATE[HY000] [2002] No connection could be made`

**Solution:**
1. Open XAMPP Control Panel
2. Make sure MySQL is running (green)
3. If not, click "Start" next to MySQL
4. Wait for it to turn green
5. Try logging in again

#### Issue 6: PDO Extension Missing
**Error:** `Fatal error: Class 'PDO' not found`

**Solution:**
1. Open `C:\xampp\php\php.ini`
2. Find line: `;extension=pdo_mysql`
3. Remove the semicolon: `extension=pdo_mysql`
4. Save file
5. Restart Apache in XAMPP

### Step 3: Verify Configuration

Check `config.php` has correct settings:
```php
<?php
class Database {
    private static ?\PDO $pdo = null;
    
    public static function getConnection(): \PDO {
        if (self::$pdo === null) {
            $host = '127.0.0.1';      // ✅ Should be 127.0.0.1 or localhost
            $db = 'car_showroom';      // ✅ Must match your database name
            $user = 'root';            // ✅ Default XAMPP user
            $pass = '';                // ✅ Default XAMPP has no password
            
            $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ];
            self::$pdo = new \PDO($dsn, $user, $pass, $options);
        }
        return self::$pdo;
    }
}
?>
```

### Step 4: Check Error Messages

I've updated `login.php` to show detailed error messages. Now when you try to login, you'll see:
- **"Database error: [specific message]"** - Database issue
- **"Unexpected error: [specific message]"** - Other issues
- **"Invalid email or password"** - Wrong credentials

This helps identify the exact problem!

## 🧪 Testing Checklist

After fixing issues, test:

### Test 1: Database Connection
```
✅ Navigate to: http://localhost/Car/check_database.php
✅ Should show all green checkmarks
```

### Test 2: Admin Login
```
✅ Navigate to: http://localhost/Car/login.php
✅ Enter: admin@gmail.com / Admin@1234
✅ Should redirect to: admin/dashboard.php
```

### Test 3: User Registration & Login
```
✅ Navigate to: http://localhost/Car/register.php
✅ Register a new user
✅ Login with user credentials
✅ Should redirect to: dashboard.php
```

## 🔧 Manual Database Setup

If automatic setup fails, run these commands in phpMyAdmin SQL tab:

```sql
-- 1. Create database
CREATE DATABASE IF NOT EXISTS `car_showroom` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `car_showroom`;

-- 2. Create admins table
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Insert admin account
INSERT INTO `admins` (`name`, `email`, `password_hash`) VALUES
('System Admin', 'admin@gmail.com', '$2y$10$Ks3g809GYRfPzOMXuefKo.N9fAgJRBj0GaleX2AswBdtGYSLudgli');

-- 4. Create users table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Verify
SELECT * FROM admins;  -- Should show admin account
SELECT * FROM users;   -- Should show empty or your registered users
```

## 📞 Still Having Issues?

If you're still encountering errors:

1. **Check XAMPP Status**
   - Apache: Running (Green)
   - MySQL: Running (Green)

2. **Check PHP Logs**
   - Location: `C:\xampp\apache\logs\error.log`
   - Look for recent errors

3. **Clear Browser Cache**
   - Press Ctrl + Shift + Delete
   - Clear cache and cookies
   - Try again

4. **Test Database Manually**
   - Open phpMyAdmin: `http://localhost/phpmyadmin`
   - Select `car_showroom` database
   - Run: `SELECT * FROM admins WHERE email = 'admin@gmail.com';`
   - Should return 1 row

5. **Verify Files Exist**
   ```
   ✅ C:\xampp\htdocs\Car\config.php
   ✅ C:\xampp\htdocs\Car\login.php
   ✅ C:\xampp\htdocs\Car\database.sql
   ✅ C:\xampp\htdocs\Car\admin\dashboard.php
   ✅ C:\xampp\htdocs\Car\dashboard.php
   ```

## 🎯 Quick Fix Commands

### Reset Everything (Nuclear Option)
```sql
-- In phpMyAdmin SQL tab:
DROP DATABASE IF EXISTS car_showroom;
CREATE DATABASE car_showroom CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE car_showroom;

-- Then paste the entire database.sql content
```

### Just Reset Admin Password
```sql
UPDATE admins 
SET password_hash = '$2y$10$Ks3g809GYRfPzOMXuefKo.N9fAgJRBj0GaleX2AswBdtGYSLudgli' 
WHERE email = 'admin@gmail.com';
```

### Check Current Admin
```sql
SELECT id, name, email, 
       SUBSTRING(password_hash, 1, 20) as hash_preview 
FROM admins 
WHERE email = 'admin@gmail.com';
```

## ✅ Success Indicators

You'll know everything is working when:
- ✅ `check_database.php` shows all green checkmarks
- ✅ Login with admin credentials redirects to admin panel
- ✅ Login with user credentials redirects to user dashboard
- ✅ No error messages appear

## 📚 Related Files

- **Check Database:** `http://localhost/Car/check_database.php`
- **Login Page:** `http://localhost/Car/login.php`
- **Register Page:** `http://localhost/Car/register.php`
- **Admin Panel:** `http://localhost/Car/admin/dashboard.php`
- **User Dashboard:** `http://localhost/Car/dashboard.php`

---

**Last Updated:** October 2024  
**Compatible with:** XAMPP 8.x, PHP 8.x, MySQL 5.7+


