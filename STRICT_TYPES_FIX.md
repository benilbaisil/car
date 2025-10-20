# ✅ Strict Types Declaration Fix

## Issue Resolved

**Error:** `Fatal error: strict_types declaration must be the very first statement in the script`

## What Was Wrong

In PHP, `declare(strict_types=1);` **MUST** be the absolute first statement after the opening `<?php` tag. It cannot come after any other code, including `session_start()`.

### ❌ Incorrect Order:
```php
<?php
session_start();

declare(strict_types=1);  // ❌ TOO LATE - Error!
```

### ✅ Correct Order:
```php
<?php
declare(strict_types=1);  // ✅ FIRST - Correct!

session_start();
```

## Files Fixed

The following files were corrected:

1. ✅ `checkout.php`
2. ✅ `payment_verify.php`
3. ✅ `payment_success.php`
4. ✅ `payment_failed.php`
5. ✅ `admin/payments.php`

## Rule to Remember

**Always put `declare(strict_types=1);` as the FIRST statement after `<?php`**

```php
<?php
declare(strict_types=1);  // 👈 Always first!

// Now you can do everything else
session_start();
require_once 'config.php';
// ... rest of code
```

## Why This Matters

`declare(strict_types=1);` enables strict type checking in PHP, which:
- Prevents type coercion bugs
- Makes code more predictable
- Catches type errors early
- Improves code quality

But PHP requires it to be declared **before any other code executes** to ensure the strict typing rules apply to the entire file.

---

**Status:** ✅ **All files fixed and verified!**

You can now proceed with testing the payment integration.

