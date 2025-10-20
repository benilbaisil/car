# 🚀 Razorpay Payment Integration - Quick Start

## ⚡ 5-Minute Setup Guide

### ✅ Prerequisites Checklist

Before starting, ensure:
- [x] XAMPP installed and running
- [x] Apache and MySQL services started
- [x] Database `car_showroom` created
- [x] User registered and can login

---

## 📋 Step-by-Step Setup

### Step 1: Update Database (2 minutes)

**Option A: Run SQL directly**
```sql
USE car_showroom;

CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `order_id` INT UNSIGNED NOT NULL,
  `razorpay_order_id` VARCHAR(100) NOT NULL UNIQUE,
  `razorpay_payment_id` VARCHAR(100) NULL,
  `razorpay_signature` VARCHAR(255) NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'INR',
  `status` ENUM('created','pending','success','failed') NOT NULL DEFAULT 'created',
  `error_reason` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payments_user` (`user_id`),
  KEY `idx_payments_order` (`order_id`),
  KEY `idx_payments_razorpay_order` (`razorpay_order_id`),
  KEY `idx_payments_status` (`status`),
  CONSTRAINT `fk_payments_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Option B: Use Command Line**
```powershell
C:\xampp\mysql\bin\mysql.exe -u root car_showroom < database.sql
```

**Option C: phpMyAdmin**
1. Open http://localhost/phpmyadmin
2. Select `car_showroom` database
3. Click "Import" tab
4. Choose `database.sql` file
5. Click "Go"

### Step 2: Verify Files (1 minute)

Check all files are in place:
```
✅ classes/RazorpayConfig.php
✅ classes/RazorpayPayment.php
✅ classes/PaymentRepository.php
✅ checkout.php
✅ payment_verify.php
✅ payment_success.php
✅ payment_failed.php
✅ admin/payments.php
✅ composer.json
✅ composer.phar
✅ vendor/ (folder)
```

### Step 3: Test the Integration (2 minutes)

#### A. User Flow Test
1. **Open homepage**
   ```
   http://localhost/Car/index.php
   ```

2. **Add product to cart**
   - Scroll to products section
   - Click "Add to Cart" on any product

3. **View cart**
   - Click "Cart" in navigation
   - Should see product in cart

4. **Go to checkout**
   - Click "Proceed to Checkout" button
   - **If not logged in:** Login first
   - Should see checkout page with order summary

5. **Proceed to payment**
   - Click "Proceed to Payment" button
   - Razorpay modal should open

6. **Complete test payment**
   - **Card Number:** `4111 1111 1111 1111`
   - **CVV:** `123`
   - **Expiry:** `12/25` (any future date)
   - **Name:** Your name
   - Click "Pay"
   - Enter **OTP:** `123456` (any 6 digits)
   - Click "Submit"

7. **Verify success**
   - Should redirect to success page
   - See order ID and payment ID
   - Cart should be empty

#### B. Admin Panel Test
1. **Login as admin**
   ```
   http://localhost/Car/login.php
   Email: admin@gmail.com
   Password: Admin@1234
   ```

2. **View payments**
   ```
   http://localhost/Car/admin/payments.php
   ```
   - Should see your test payment
   - Status should show "Success" in green
   - Amount should match

---

## 🎯 Your Razorpay Credentials

**Already configured in `classes/RazorpayConfig.php`:**

```php
Key ID:     rzp_test_R6h0atxxQ4WsUU
Key Secret: 5CyNCDCaDKmrRqPWX2K6uLGV
Mode:       Test Mode
```

**⚠️ Important:**
- These are **test credentials** - payments won't be real
- For production, get **live credentials** from Razorpay Dashboard
- Replace in `RazorpayConfig.php` when going live

---

## 🧪 Test Cards

Use these for testing:

| Card Number | Type | Result | CVV | OTP |
|-------------|------|--------|-----|-----|
| `4111 1111 1111 1111` | Visa | ✅ Success | Any 3 digits | Any 6 digits |
| `5555 5555 5555 4444` | Mastercard | ✅ Success | Any 3 digits | Any 6 digits |
| `4000 0000 0000 0002` | Visa | ❌ Fails | Any 3 digits | Any 6 digits |

**Most commonly used:**
```
Card: 4111 1111 1111 1111
CVV:  123
Exp:  12/25
OTP:  123456
```

---

## 📊 Database Verification

### Check if payment was recorded:

```sql
-- View recent payments
SELECT 
    p.id,
    u.name as user_name,
    o.id as order_id,
    p.amount,
    p.status,
    p.razorpay_payment_id,
    p.created_at
FROM payments p
LEFT JOIN users u ON p.user_id = u.id
LEFT JOIN orders o ON p.order_id = o.id
ORDER BY p.created_at DESC
LIMIT 5;
```

### Check payment statistics:

```sql
-- Count payments by status
SELECT 
    status,
    COUNT(*) as count,
    SUM(amount) as total_amount
FROM payments
GROUP BY status;
```

---

## 🔍 Quick Troubleshooting

### Issue: Razorpay Modal Not Opening

**Check 1:** Browser Console (F12 → Console)
- Look for JavaScript errors
- Should see no red errors

**Check 2:** View Page Source
- Search for `checkout.razorpay.com`
- Should find: `<script src="https://checkout.razorpay.com/v1/checkout.js"></script>`

**Check 3:** Check if order was created
```sql
SELECT * FROM orders ORDER BY created_at DESC LIMIT 1;
```

**Solution:**
- Ensure Razorpay script is loaded
- Check if `razorpay_order_id` is generated
- Look in browser Network tab (F12 → Network) for failed requests

### Issue: Payment Not Saved in Database

**Check 1:** Verify payments table exists
```sql
SHOW TABLES LIKE 'payments';
```

**Check 2:** Check PHP error log
```powershell
Get-Content "C:\xampp\apache\logs\error.log" -Tail 20
```

**Check 3:** Test database connection
```php
// Add to checkout.php temporarily
try {
    $pdo = Database::getConnection();
    echo "✅ Database connected";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage();
}
```

### Issue: "Invalid Signature" Error

**Cause:** Key secret mismatch

**Solution:**
1. Open `classes/RazorpayConfig.php`
2. Verify key secret: `5CyNCDCaDKmrRqPWX2K6uLGV`
3. Ensure no extra spaces
4. Match key ID and key secret from same Razorpay account

### Issue: Payment Successful But Order Not Updated

**Check:** Order status in database
```sql
SELECT id, user_id, status, total, created_at 
FROM orders 
ORDER BY created_at DESC 
LIMIT 5;
```

**Expected:** Status should change from 'pending' after payment

**Solution:** Check `RazorpayPayment.php` → `updateOrderStatus()` method

---

## 🎨 Customization

### Change Currency

Edit `classes/RazorpayConfig.php`:
```php
private const CURRENCY = 'USD'; // Change from INR to USD
```

### Change Company Name/Logo

Edit `classes/RazorpayConfig.php`:
```php
private const COMPANY_NAME = 'Your Company Name';
private const COMPANY_LOGO = 'https://yoursite.com/logo.png';
```

### Change Payment Button Text

Edit `checkout.php`:
```html
<button id="rzp-button">
    Pay ₹<?php echo number_format($cartTotal, 2); ?>
</button>
```

---

## 📈 Testing Checklist

Use this to verify everything works:

### User Flow
- [ ] Can add products to cart
- [ ] Cart displays correctly
- [ ] "Proceed to Checkout" button works
- [ ] Checkout page shows order summary
- [ ] "Proceed to Payment" button works
- [ ] Razorpay modal opens
- [ ] Can enter card details
- [ ] Payment processes successfully
- [ ] Redirects to success page
- [ ] Success page shows correct amount
- [ ] Cart is cleared
- [ ] Order appears in dashboard (if user dashboard has orders)

### Admin Panel
- [ ] Can access admin/payments.php
- [ ] Payment statistics show correctly
- [ ] Payment appears in table
- [ ] Status is "Success" (green badge)
- [ ] Amount is correct
- [ ] User details visible
- [ ] Order number shown
- [ ] Razorpay order ID displayed

### Database
- [ ] Payment record created in `payments` table
- [ ] `razorpay_order_id` populated
- [ ] `razorpay_payment_id` populated (after success)
- [ ] `razorpay_signature` populated (after success)
- [ ] `status` is 'success'
- [ ] Order status updated in `orders` table

---

## 🚀 Go Live Checklist

When ready for production:

### 1. Get Live Credentials
- Login to Razorpay Dashboard
- Navigate to Settings → API Keys
- Generate Live API Keys
- **Important:** These start with `rzp_live_`

### 2. Update Configuration
```php
// classes/RazorpayConfig.php
private const KEY_ID = 'rzp_live_YOUR_KEY_ID';
private const KEY_SECRET = 'rzp_live_YOUR_KEY_SECRET';
```

### 3. Enable HTTPS
- Get SSL certificate
- Configure HTTPS on server
- Razorpay requires HTTPS for live mode

### 4. Test in Live Mode
- Use real card for small amount
- Verify payment goes through
- Check money reaches your account

### 5. Set Up Webhooks
- Configure webhook URL in Razorpay Dashboard
- Handle webhook events for reliability

---

## 📞 Need Help?

### Documentation Files
- **Complete Guide:** `RAZORPAY_INTEGRATION_GUIDE.md`
- **This Quick Start:** `RAZORPAY_QUICK_START.md`

### Check Logs
```powershell
# PHP Errors
Get-Content "C:\xampp\apache\logs\error.log" -Tail 50

# MySQL Errors
Get-Content "C:\xampp\mysql\data\mysql_error.log" -Tail 50
```

### Razorpay Resources
- Dashboard: https://dashboard.razorpay.com
- Docs: https://razorpay.com/docs/
- Support: support@razorpay.com

---

## ✅ Success!

**If you can complete a test payment and see it in the admin panel, you're all set! 🎉**

Your Elite Diecast store now has a fully functional payment system powered by Razorpay.

**Next Steps:**
1. Customize the design to match your brand
2. Test with different products and amounts
3. Train your team on the admin panel
4. When ready, switch to live mode

**Happy Selling! 🚗💨**

