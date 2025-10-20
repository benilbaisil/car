# 🎯 Razorpay Payment Integration - Complete Guide

## 📋 Table of Contents
1. [Overview](#overview)
2. [System Architecture](#system-architecture)
3. [Installation](#installation)
4. [Configuration](#configuration)
5. [Payment Flow](#payment-flow)
6. [File Structure](#file-structure)
7. [Database Schema](#database-schema)
8. [Usage Guide](#usage-guide)
9. [Testing](#testing)
10. [Security Best Practices](#security-best-practices)
11. [Troubleshooting](#troubleshooting)

---

## 🎯 Overview

This is a **complete PHP OOP-based Razorpay payment integration** for the Elite Diecast e-commerce website. It supports:

✅ **Razorpay Test Mode Integration**  
✅ **Order Creation & Payment Processing**  
✅ **Payment Signature Verification**  
✅ **Success & Failure Handling**  
✅ **Database Storage of Payment Details**  
✅ **Admin Payment Management**  
✅ **Secure Configuration Management**

---

## 🏗️ System Architecture

### OOP Class Structure

```
classes/
├── RazorpayConfig.php         # Secure credential storage
├── RazorpayPayment.php        # Main payment handler
└── PaymentRepository.php      # Database operations
```

### Payment Flow Pages

```
cart.php                → User cart with "Proceed to Checkout" button
checkout.php            → Review order & create Razorpay order
payment_verify.php      → Server-side payment verification
payment_success.php     → Success page after payment
payment_failed.php      → Failure page with retry option
admin/payments.php      → Admin payment management
```

---

## 📦 Installation

### Step 1: Install Composer Dependencies

The Razorpay PHP SDK has already been installed via Composer.

**Files created:**
- `composer.json` - Dependency configuration
- `composer.phar` - Composer executable
- `vendor/` - Dependencies folder (including Razorpay SDK)

### Step 2: Update Database

Run the following SQL to create the `payments` table:

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

**Or run the updated `database.sql` file in phpMyAdmin.**

### Step 3: Verify File Structure

Ensure these files exist:
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
✅ vendor/ (folder with Razorpay SDK)
```

---

## ⚙️ Configuration

### Your Razorpay Credentials

The credentials are already configured in `classes/RazorpayConfig.php`:

```php
private const KEY_ID = 'rzp_test_R6h0atxxQ4WsUU';
private const KEY_SECRET = '5CyNCDCaDKmrRqPWX2K6uLGV';
```

**⚠️ SECURITY NOTE:**
- These are **TEST MODE** credentials (indicated by `rzp_test_` prefix)
- For **production**, replace with **LIVE credentials** from Razorpay Dashboard
- **NEVER** commit credentials to version control
- Use environment variables in production (`.env` file)

### Test Mode vs Live Mode

| Mode | Key Prefix | Use Case |
|------|-----------|----------|
| **Test** | `rzp_test_` | Development & Testing |
| **Live** | `rzp_live_` | Production |

**Current Mode:** Test Mode ✅

---

## 🔄 Payment Flow

### User Journey

```
1. User adds products to cart
   └─> cart.php displays cart items

2. User clicks "Proceed to Checkout"
   └─> Redirects to checkout.php

3. checkout.php displays order summary
   └─> User clicks "Proceed to Payment"
   └─> PHP creates order in database
   └─> PHP calls Razorpay API to create payment order
   └─> Razorpay Checkout modal opens

4. User enters payment details in Razorpay modal
   └─> Card number, CVV, OTP, etc.

5a. Payment SUCCESS:
    └─> Razorpay sends payment_id, order_id, signature
    └─> JavaScript submits to payment_verify.php
    └─> PHP verifies signature using HMAC SHA256
    └─> Updates payment status to 'success' in DB
    └─> Updates order status to 'pending'
    └─> Clears cart
    └─> Redirects to payment_success.php

5b. Payment FAILED:
    └─> User cancels or payment fails
    └─> Redirects to payment_failed.php
    └─> Payment status marked as 'failed' in DB
    └─> User can retry
```

### Technical Flow

```
┌─────────────┐
│   cart.php  │ User views cart
└──────┬──────┘
       │ Click "Proceed to Checkout"
       ▼
┌─────────────┐
│checkout.php │ Display order summary
└──────┬──────┘
       │ POST: create_order=1
       ▼
┌──────────────────────┐
│ RazorpayPayment      │ PHP Backend
│  →createOrder()      │ 1. Insert order in DB
│  →callRazorpayAPI()  │ 2. Call Razorpay API
│                      │ 3. Get razorpay_order_id
└──────┬───────────────┘
       │ Razorpay order created
       ▼
┌──────────────────────┐
│ Razorpay Checkout.js │ Frontend Modal
│  Opens payment form  │ User enters card details
└──────┬───────────────┘
       │ Payment processed by Razorpay
       ├──SUCCESS──────┐
       │               ▼
       │     ┌─────────────────────┐
       │     │ payment_verify.php  │
       │     │  →verifySignature() │ HMAC verification
       │     │  →updatePaymentDB() │ Mark as success
       │     └─────────┬───────────┘
       │               ▼
       │     ┌─────────────────────┐
       │     │payment_success.php  │ Show success
       │     └─────────────────────┘
       │
       └──FAILURE/CANCEL──┐
                          ▼
                ┌──────────────────┐
                │payment_failed.php│ Show error
                └──────────────────┘
```

---

## 📁 File Structure

### Core PHP Classes

#### 1. `classes/RazorpayConfig.php`
**Purpose:** Secure storage of Razorpay credentials

**Key Methods:**
- `getKeyId()` - Returns public key (safe for frontend)
- `getKeySecret()` - Returns private key (backend only)
- `getCurrency()` - Returns 'INR'
- `isTestMode()` - Checks if using test credentials

#### 2. `classes/RazorpayPayment.php`
**Purpose:** Main payment processing logic

**Key Methods:**
- `createOrder($amount, $orderId, $userId)` - Creates Razorpay order
- `verifyPaymentSignature($orderId, $paymentId, $signature)` - Verifies HMAC signature
- `handlePaymentSuccess()` - Processes successful payment
- `handlePaymentFailure()` - Marks payment as failed
- `callRazorpayAPI()` - Makes HTTP calls to Razorpay API

#### 3. `classes/PaymentRepository.php`
**Purpose:** Database operations for payments

**Key Methods:**
- `createPayment()` - Insert new payment record
- `updatePaymentSuccess()` - Update with payment_id & signature
- `updatePaymentFailed()` - Mark payment as failed
- `getPaymentByOrderId()` - Fetch payment by Razorpay order ID
- `getPaymentsByUser()` - Get all payments for a user
- `getAllPayments()` - Admin: get all payments

### Frontend Pages

#### 1. `checkout.php`
**Features:**
- Display order summary
- Create Razorpay order on POST
- Integrate Razorpay Checkout.js
- Handle payment success/failure callbacks

#### 2. `payment_verify.php`
**Features:**
- Server-side signature verification
- Update payment status in database
- Clear cart on success
- Redirect to success/failure page

#### 3. `payment_success.php`
**Features:**
- Display success message
- Show order ID, payment ID, amount
- Provide links to dashboard and continue shopping

#### 4. `payment_failed.php`
**Features:**
- Display error message
- Show common failure reasons
- Provide retry option

#### 5. `admin/payments.php`
**Features:**
- View all payments
- Payment statistics (total, successful, failed, revenue)
- Filter by status
- Export payment reports

---

## 🗄️ Database Schema

### `payments` Table

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT UNSIGNED | Primary key |
| `user_id` | INT UNSIGNED | FK to users table |
| `order_id` | INT UNSIGNED | FK to orders table |
| `razorpay_order_id` | VARCHAR(100) | Razorpay order ID (unique) |
| `razorpay_payment_id` | VARCHAR(100) | Razorpay payment ID (after success) |
| `razorpay_signature` | VARCHAR(255) | HMAC signature for verification |
| `amount` | DECIMAL(10,2) | Payment amount in INR |
| `currency` | VARCHAR(10) | Currency code (default: INR) |
| `status` | ENUM | 'created', 'pending', 'success', 'failed' |
| `error_reason` | TEXT | Error message if failed |
| `created_at` | TIMESTAMP | Order creation time |
| `updated_at` | TIMESTAMP | Last update time |

### Payment Status Flow

```
created   →  Order created, payment pending
   ↓
success   →  Payment verified and successful
   OR
failed    →  Payment failed or cancelled
```

---

## 📖 Usage Guide

### For Users

#### 1. Add Products to Cart
- Browse products on homepage
- Click "Add to Cart" button
- Products stored in session

#### 2. View Cart
- Click "Cart" in navigation
- See cart items with quantities and prices
- Modify quantities or remove items

#### 3. Proceed to Checkout
- Click "Proceed to Checkout" button
- Login required (redirects to login if not logged in)

#### 4. Review Order
- `checkout.php` displays order summary
- Shows customer details and items
- Click "Proceed to Payment"

#### 5. Complete Payment
- Razorpay Checkout modal opens
- Enter payment details:
  - **Test Card:** `4111 1111 1111 1111`
  - **CVV:** Any 3 digits
  - **Expiry:** Any future date
  - **OTP:** Any 6 digits
- Submit payment

#### 6. Success/Failure
- **Success:** Redirected to success page with order details
- **Failure:** Redirected to failure page with retry option

### For Admins

#### View Payments
- Login to admin panel
- Navigate to `admin/payments.php`
- View payment statistics:
  - Total payments
  - Successful payments
  - Failed payments
  - Total revenue

#### Payment Table
- See all payments with:
  - User details
  - Order number
  - Amount
  - Status (color-coded badges)
  - Razorpay order ID
  - Date and time

---

## 🧪 Testing

### Test Credentials

**Razorpay Test Mode:**
- Key ID: `rzp_test_R6h0atxxQ4WsUU`
- Key Secret: `5CyNCDCaDKmrRqPWX2K6uLGV`

**Test Cards:**

| Card Number | Type | Result |
|-------------|------|--------|
| `4111 1111 1111 1111` | Visa | Success |
| `5555 5555 5555 4444` | Mastercard | Success |
| `4000 0000 0000 0002` | Visa | Failure |

**Test OTP:** Any 6 digits (e.g., `123456`)

### Testing Checklist

#### ✅ Basic Flow
- [ ] Add product to cart
- [ ] View cart page
- [ ] Click "Proceed to Checkout"
- [ ] Login if not logged in
- [ ] See order summary on checkout page
- [ ] Click "Proceed to Payment"
- [ ] Razorpay modal opens
- [ ] Enter test card details
- [ ] Complete payment
- [ ] Redirected to success page
- [ ] See order details
- [ ] Cart is cleared

#### ✅ Payment Verification
- [ ] Payment status updated to 'success' in database
- [ ] `razorpay_payment_id` saved
- [ ] `razorpay_signature` saved
- [ ] Order status updated to 'pending'
- [ ] Payment visible in admin panel

#### ✅ Failure Scenarios
- [ ] Cancel payment in modal → Redirects to failure page
- [ ] Use failure test card → Shows error message
- [ ] Retry payment from failure page

#### ✅ Security
- [ ] Signature verification works
- [ ] Invalid signature rejected
- [ ] Key secret not exposed in frontend
- [ ] SQL injection prevented (PDO prepared statements)

---

## 🔒 Security Best Practices

### ✅ Implemented Security Measures

1. **Credential Protection**
   - Key secret stored in PHP class (not exposed to frontend)
   - Only key ID sent to JavaScript

2. **Payment Verification**
   - HMAC SHA256 signature verification
   - Signature compared using `hash_equals()` to prevent timing attacks

3. **SQL Injection Prevention**
   - All queries use PDO prepared statements
   - Input sanitization with `trim()` and type casting

4. **Session Security**
   - User authentication required for checkout
   - Session-based cart storage

5. **Error Handling**
   - Try-catch blocks for all payment operations
   - Errors logged server-side
   - Generic error messages shown to users

### 🔐 Production Recommendations

1. **Move Credentials to Environment Variables**
   ```php
   // Use .env file with vlucas/phpdotenv
   private const KEY_ID = $_ENV['RAZORPAY_KEY_ID'];
   private const KEY_SECRET = $_ENV['RAZORPAY_KEY_SECRET'];
   ```

2. **Enable HTTPS**
   - Use SSL certificate
   - Razorpay requires HTTPS for live mode

3. **Webhook Integration**
   - Set up Razorpay webhooks for payment notifications
   - Verify webhook signatures

4. **Rate Limiting**
   - Limit payment attempts per user
   - Prevent abuse

5. **Logging & Monitoring**
   - Log all payment attempts
   - Monitor failed payments
   - Set up alerts for suspicious activity

---

## 🐛 Troubleshooting

### Common Issues

#### 1. Razorpay Modal Not Opening

**Symptoms:**
- Click "Pay Now" but nothing happens
- Browser console shows JavaScript error

**Solutions:**
- ✅ Check if `checkout.js` script is loaded:
  ```html
  <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
  ```
- ✅ Open browser console (F12) and check for errors
- ✅ Verify `razorpay_order_id` is generated

#### 2. Payment Not Recorded in Database

**Symptoms:**
- Payment successful in Razorpay
- Not showing in admin panel
- Order status not updated

**Solutions:**
- ✅ Check `payment_verify.php` is being called
- ✅ Check database connection in `config.php`
- ✅ Run SQL query to check payments table:
  ```sql
  SELECT * FROM payments ORDER BY created_at DESC LIMIT 5;
  ```
- ✅ Check PHP error log:
  ```
  C:\xampp\apache\logs\error.log
  ```

#### 3. Signature Verification Failed

**Symptoms:**
- Payment successful but marked as failed
- Error: "Invalid payment signature"

**Solutions:**
- ✅ Verify key secret is correct in `RazorpayConfig.php`
- ✅ Check order ID matches between creation and verification
- ✅ Ensure signature is passed correctly from frontend

#### 4. Order Not Created

**Symptoms:**
- Click "Proceed to Payment" but nothing happens
- No Razorpay modal
- No database entry

**Solutions:**
- ✅ Check if user is logged in
- ✅ Check cart has items
- ✅ Check database connection
- ✅ Check PHP error log for exceptions

#### 5. "Unauthorized" Error from Razorpay API

**Symptoms:**
- Error: "Razorpay API error: HTTP 401"
- Order not created

**Solutions:**
- ✅ Verify Razorpay credentials are correct
- ✅ Check if key ID starts with `rzp_test_` or `rzp_live_`
- ✅ Ensure both key ID and key secret are from same account

### Debug Mode

Enable debugging in `checkout.php`:

```php
// Add at top of file
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Add before Razorpay API call
var_dump($razorpayOrder);
exit;
```

### Check Database Tables

```sql
-- Check if payments table exists
SHOW TABLES LIKE 'payments';

-- Check payments table structure
DESCRIBE payments;

-- Check recent payments
SELECT * FROM payments ORDER BY created_at DESC LIMIT 10;

-- Check payment status distribution
SELECT status, COUNT(*) as count FROM payments GROUP BY status;
```

### Check PHP Logs

```powershell
# Windows (PowerShell)
Get-Content "C:\xampp\apache\logs\error.log" -Tail 50
```

---

## 📞 Support

### Resources

- **Razorpay Documentation:** https://razorpay.com/docs/
- **Razorpay PHP SDK:** https://github.com/razorpay/razorpay-php
- **Test Mode Guide:** https://razorpay.com/docs/payment-gateway/test-card-details/

### Contact

For issues specific to this integration, check:
1. PHP error logs: `C:\xampp\apache\logs\error.log`
2. Browser console (F12 → Console)
3. Network tab (F12 → Network) for API calls

---

## ✅ Installation Verification

Run through this checklist to ensure everything is set up correctly:

### Database
- [ ] `car_showroom` database exists
- [ ] `payments` table created
- [ ] Foreign keys to `users` and `orders` tables work

### Files
- [ ] All PHP classes in `classes/` folder
- [ ] All payment pages created
- [ ] `composer.json` exists
- [ ] `vendor/` folder with Razorpay SDK

### Configuration
- [ ] Razorpay credentials in `RazorpayConfig.php`
- [ ] Database config in `config.php`
- [ ] XAMPP Apache and MySQL running

### Testing
- [ ] Can add products to cart
- [ ] "Proceed to Checkout" button works
- [ ] Razorpay modal opens
- [ ] Test payment completes successfully
- [ ] Payment shows in admin panel

---

## 🎉 Success Indicators

**You'll know it's working when:**

1. ✅ User adds products to cart
2. ✅ Click "Proceed to Checkout" → redirects to checkout.php
3. ✅ Click "Proceed to Payment" → Razorpay modal opens
4. ✅ Enter test card details → Payment processes
5. ✅ Redirected to success page with payment details
6. ✅ Cart is empty
7. ✅ Payment shows in admin panel with "success" status
8. ✅ Order status updated to "pending"

---

**🚀 Razorpay Integration Complete!**

Your Elite Diecast store now has a fully functional, secure payment system using Razorpay with PHP OOP architecture.

