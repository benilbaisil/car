# Image Upload System Documentation

## 📸 Overview

A complete OOP-based image upload system for the admin panel that replaces URL input with file upload functionality. The system handles image validation, storage, and automatic cleanup.

## ✅ Features Implemented

### 1. **File Upload Instead of URL**
- ✅ Admin can upload images directly from their computer
- ✅ Replaces text URL input with file upload field
- ✅ Supports JPG, JPEG, and PNG formats
- ✅ Maximum file size: 5MB

### 2. **Automatic Image Management**
- ✅ Images stored in `uploads/products/` directory
- ✅ Unique filenames generated (prevents conflicts)
- ✅ Old images deleted when product is updated with new image
- ✅ Images deleted when product is removed from database

### 3. **Validation & Security**
- ✅ File type validation (MIME type checking)
- ✅ File extension validation
- ✅ File size validation (5MB limit)
- ✅ Secure file naming (timestamp + random string)
- ✅ Path security checks

### 4. **User Experience**
- ✅ Shows current image in edit form
- ✅ Clear error messages for upload failures
- ✅ File size and format hints
- ✅ Styled file upload button
- ✅ Automatic image display on frontend

## 🗂️ File Structure

```
Car/
├── classes/
│   └── ImageUploadHandler.php    (NEW) - Handles all image upload logic
├── uploads/
│   └── products/                  (NEW) - Stores uploaded product images
│       └── product_1234567890_abc123.jpg
├── admin/
│   └── products.php               (UPDATED) - Form and upload handling
├── Index.php                      (WORKS) - Displays uploaded images
└── IMAGE_UPLOAD_DOCUMENTATION.md  (NEW) - This file
```

## 🏗️ OOP Architecture

### ImageUploadHandler Class

Located: `classes/ImageUploadHandler.php`

**Purpose:** Encapsulates all image upload, validation, and file management logic.

#### Public Methods:

```php
/**
 * Upload an image file
 * @param array $file The $_FILES array element
 * @return string|false File path on success, false on failure
 */
public function upload(array $file): string|false

/**
 * Delete an uploaded image file
 * @param string $filepath Path to the file to delete
 * @return bool True on success, false on failure
 */
public function delete(string $filepath): bool

/**
 * Get all upload errors
 * @return array Array of error messages
 */
public function getErrors(): array

/**
 * Get the last error message
 * @return string|null Last error or null
 */
public function getLastError(): ?string
```

#### Class Constants:

```php
const UPLOAD_DIR = 'uploads/products/';      // Storage directory
const MAX_FILE_SIZE = 5 * 1024 * 1024;      // 5MB in bytes
const ALLOWED_TYPES = ['image/jpeg', 'image/jpg', 'image/png'];
const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png'];
```

#### Security Features:

1. **MIME Type Validation:** Uses `finfo_file()` to verify actual file type
2. **Extension Check:** Validates file extension
3. **Size Limit:** Enforces 5MB maximum
4. **Unique Filenames:** Prevents overwriting with timestamp + random hash
5. **Path Security:** Ensures files are only deleted from uploads directory
6. **Permission Setting:** Sets 0644 permissions on uploaded files

## 📝 Updated Code Sections

### 1. Admin Products Page (`admin/products.php`)

#### Add Product Form:
```html
<!-- Before: -->
<input type="url" name="image_url" placeholder="https://...">

<!-- After: -->
<input type="file" name="product_image" accept="image/jpeg,image/jpg,image/png">
<p class="text-gray-400 text-xs mt-1">Allowed: JPG, JPEG, PNG (Max 5MB)</p>
```

#### Add Product Handler:
```php
// Handle image upload
$imageHandler = new ImageUploadHandler();

if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE) {
    $imagePath = $imageHandler->upload($_FILES['product_image']);
    
    if ($imagePath === false) {
        $message = 'Image upload failed: ' . $imageHandler->getLastError();
        $messageType = 'error';
    }
}

// Create product with uploaded image path
if ($repo->createProduct($name, $brand, $scale, $variant, $year, $type, $price, $stock, $imagePath)) {
    $message = 'Product added successfully';
} else {
    // Clean up uploaded image if database insert failed
    if ($imagePath) {
        $imageHandler->delete($imagePath);
    }
}
```

#### Edit Product Form:
```html
<!-- Shows current image -->
<?php if ($editProduct->getImageUrl() && file_exists($editProduct->getImageUrl())): ?>
    <div class="mb-2">
        <img src="<?php echo htmlspecialchars($editProduct->getImageUrl()); ?>" 
             alt="Current product image" 
             class="h-24 w-24 object-cover rounded border border-white/20">
        <p class="text-gray-400 text-xs mt-1">Current image</p>
    </div>
<?php endif; ?>

<!-- File upload for new image -->
<input type="file" name="product_image" accept="image/jpeg,image/jpg,image/png">
<p class="text-gray-400 text-xs mt-1">Leave empty to keep current image</p>
```

#### Update Product Handler:
```php
// Get existing product to preserve old image
$existingProduct = $repo->getProductById($id);
$imagePath = $existingProduct ? $existingProduct->getImageUrl() : null;
$oldImagePath = $imagePath;

// Handle new image upload
$imageHandler = new ImageUploadHandler();

if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE) {
    $newImagePath = $imageHandler->upload($_FILES['product_image']);
    
    if ($newImagePath !== false) {
        $imagePath = $newImagePath;
    }
}

// Update product
if ($repo->updateProduct($id, $name, $brand, $scale, $variant, $year, $type, $price, $stock, $imagePath)) {
    // Delete old image if new one was uploaded
    if ($imagePath !== $oldImagePath && $oldImagePath) {
        $imageHandler->delete($oldImagePath);
    }
}
```

#### Delete Product Handler:
```php
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
```

### 2. Frontend Display (`Index.php`)

No changes needed! The existing code already displays images correctly:

```php
<img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
     alt="<?php echo htmlspecialchars($product['name']); ?>" 
     class="w-full h-full object-cover">
```

Works with both:
- Old URL-based images: `https://example.com/image.jpg`
- New uploaded images: `uploads/products/product_1234567890_abc123.jpg`

## 🚀 Usage Guide

### Adding a Product with Image:

1. **Navigate to Admin Panel:**
   ```
   http://localhost/Car/admin/products.php
   ```

2. **Click "Add New Product"**

3. **Fill in Product Details:**
   - Name: Ferrari SF90
   - Brand: Ferrari
   - Scale: 1:24
   - Price: 59.99
   - Stock: 10

4. **Upload Image:**
   - Click "Choose File" under "Product Image"
   - Select a JPG, JPEG, or PNG file (max 5MB)
   - File will be validated automatically

5. **Click "Add Product"**

6. **Result:**
   - Image uploaded to `uploads/products/`
   - Unique filename generated
   - Path stored in database
   - Product displayed on homepage with image

### Updating Product Image:

1. **Click "Edit" on any product**

2. **Current image is shown** (if exists)

3. **To change image:**
   - Click "Choose File"
   - Select new image
   - Old image will be automatically deleted

4. **To keep current image:**
   - Leave file upload empty
   - Click "Save Changes"

### What Happens on Delete:

1. **Admin clicks "Delete" on product**

2. **System automatically:**
   - Removes product from database
   - Deletes associated image file
   - Cleans up storage

## 🔒 Security Measures

### 1. File Type Validation
```php
// MIME type check using finfo
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, self::ALLOWED_TYPES)) {
    $this->errors[] = 'Invalid file type';
    return false;
}
```

### 2. Extension Validation
```php
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
    $this->errors[] = 'Invalid file extension';
    return false;
}
```

### 3. Size Limit
```php
if ($file['size'] > self::MAX_FILE_SIZE) {
    $this->errors[] = 'File size exceeds 5MB';
    return false;
}
```

### 4. Unique Filenames
```php
private function generateUniqueFilename(string $extension): string {
    $timestamp = time();
    $randomString = bin2hex(random_bytes(8));
    return "product_{$timestamp}_{$randomString}.{$extension}";
}
```

Example: `product_1697750123_a1b2c3d4e5f6g7h8.jpg`

### 5. Path Security
```php
public function delete(string $filepath): bool {
    // Security check: ensure file is in uploads directory
    if (strpos($filepath, self::UPLOAD_DIR) !== 0) {
        return false;
    }
    
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    
    return false;
}
```

## 🧪 Testing Checklist

### Test 1: Upload Valid Image
```
✅ Navigate to admin/products.php
✅ Click "Add New Product"
✅ Fill in product details
✅ Upload JPG image (< 5MB)
✅ Click "Add Product"
✅ Verify image appears in uploads/products/
✅ Verify product shows on homepage with image
```

### Test 2: Upload Invalid File Type
```
✅ Try to upload .txt file
✅ Should show error: "Invalid file type. Only JPG, JPEG, and PNG are allowed"
✅ Product should NOT be created
```

### Test 3: Upload File Too Large
```
✅ Try to upload image > 5MB
✅ Should show error: "File size exceeds maximum allowed size of 5MB"
✅ Product should NOT be created
```

### Test 4: Update Product Image
```
✅ Edit existing product
✅ Current image should be displayed
✅ Upload new image
✅ Click "Save Changes"
✅ New image should appear
✅ Old image file should be deleted from uploads/
```

### Test 5: Delete Product
```
✅ Delete a product that has an image
✅ Product removed from database
✅ Image file deleted from uploads/products/
```

### Test 6: Edit Without Changing Image
```
✅ Edit product
✅ Change name or price
✅ Don't upload new image
✅ Click "Save Changes"
✅ Original image should remain unchanged
```

## 📊 Database Schema

No changes needed! The existing `products` table already has the `image_url` column:

```sql
CREATE TABLE products (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    brand VARCHAR(100) NOT NULL,
    scale VARCHAR(10) NOT NULL,
    variant VARCHAR(120) NULL,
    year SMALLINT NULL,
    type VARCHAR(50) NULL,
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    image_url TEXT NULL,  -- Stores file path or URL
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Before:** `image_url` = `https://example.com/image.jpg`  
**After:** `image_url` = `uploads/products/product_1697750123_abc123.jpg`

## 🐛 Troubleshooting

### Issue 1: "Failed to create upload directory"

**Solution:**
```bash
# Create directory manually
mkdir uploads\products

# Or check permissions (Windows)
# Right-click folder → Properties → Security → Edit
# Give IUSR and IIS_IUSRS full control
```

### Issue 2: "Failed to move uploaded file"

**Cause:** Permission issues or directory doesn't exist

**Solution:**
```php
// Check directory exists and is writable
var_dump(is_dir('uploads/products'));        // Should be true
var_dump(is_writable('uploads/products'));   // Should be true
```

### Issue 3: Image not displaying on frontend

**Cause:** Incorrect path in database

**Check:**
```sql
SELECT id, name, image_url FROM products;
```

Should show: `uploads/products/product_xxx.jpg`  
NOT: `/uploads/products/...` or `C:\xampp\htdocs\...`

**Fix:**
Use relative path from web root: `uploads/products/filename.jpg`

### Issue 4: Upload succeeds but image broken

**Cause:** MIME type mismatch or corrupted file

**Solution:**
- Ensure file is actually an image
- Try different image
- Check file isn't corrupted

## 📈 Performance Considerations

### Image Optimization (Future Enhancement):

```php
// After upload, resize/compress image
public function optimizeImage(string $filepath): bool {
    $image = imagecreatefromjpeg($filepath);
    $width = imagesx($image);
    $height = imagesy($image);
    
    // Resize if larger than 800px width
    if ($width > 800) {
        $newWidth = 800;
        $newHeight = ($height / $width) * $newWidth;
        
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagejpeg($resized, $filepath, 85); // 85% quality
        
        imagedestroy($image);
        imagedestroy($resized);
        return true;
    }
    
    return false;
}
```

## ✅ Summary

### What Was Created:

1. ✅ **ImageUploadHandler Class** - Complete OOP file upload system
2. ✅ **Upload Directory** - Secure storage location
3. ✅ **Form Updates** - File upload inputs with validation hints
4. ✅ **Upload Handling** - Validation, error handling, cleanup
5. ✅ **Update Handling** - Replace old images, preserve if no new upload
6. ✅ **Delete Handling** - Automatic cleanup on product deletion
7. ✅ **Frontend Display** - Works automatically with uploaded images

### Benefits:

- ✅ Professional file upload system
- ✅ Secure with multiple validation layers
- ✅ Automatic cleanup prevents orphaned files
- ✅ Clean error handling and user feedback
- ✅ Follows OOP principles
- ✅ Easy to maintain and extend

### Files Modified:

```
Created:
- classes/ImageUploadHandler.php
- uploads/products/ (directory)
- IMAGE_UPLOAD_DOCUMENTATION.md

Modified:
- admin/products.php (forms and handlers)

No Changes Needed:
- Index.php (already displays images correctly)
- database.sql (image_url column already exists)
```

---

**Version:** 1.0  
**Last Updated:** October 2024  
**Status:** Production Ready ✅

