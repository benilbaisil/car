<?php
/**
 * ImageUploadHandler Class
 * 
 * Handles image file uploads with validation and security checks
 * Supports: JPG, JPEG, PNG
 * Max size: 5MB
 * Stores images in uploads/products/ directory
 */
class ImageUploadHandler {
    // Configuration constants
    private const UPLOAD_DIR = 'uploads/products/';
    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB in bytes
    private const ALLOWED_TYPES = ['image/jpeg', 'image/jpg', 'image/png'];
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png'];
    
    private array $errors = [];
    
    /**
     * Upload an image file
     * 
     * @param array $file The $_FILES array element
     * @return string|false Returns the file path on success, false on failure
     */
    public function upload(array $file): string|false {
        // Reset errors
        $this->errors = [];
        
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            $this->errors[] = 'No file was uploaded';
            return false;
        }
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[] = $this->getUploadErrorMessage($file['error']);
            return false;
        }
        
        // Validate file size
        if ($file['size'] > self::MAX_FILE_SIZE) {
            $this->errors[] = 'File size exceeds maximum allowed size of 5MB';
            return false;
        }
        
        // Validate MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, self::ALLOWED_TYPES)) {
            $this->errors[] = 'Invalid file type. Only JPG, JPEG, and PNG are allowed';
            return false;
        }
        
        // Validate file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            $this->errors[] = 'Invalid file extension. Only .jpg, .jpeg, and .png are allowed';
            return false;
        }
        
        // Create upload directory if it doesn't exist
        if (!$this->ensureUploadDirectory()) {
            $this->errors[] = 'Failed to create upload directory';
            return false;
        }
        
        // Generate unique filename
        $filename = $this->generateUniqueFilename($extension);
        $filepath = self::UPLOAD_DIR . $filename;
        
        // Move uploaded file to destination
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            $this->errors[] = 'Failed to move uploaded file';
            return false;
        }
        
        // Set proper permissions
        chmod($filepath, 0644);
        
        return $filepath;
    }
    
    /**
     * Delete an uploaded image file
     * 
     * @param string $filepath Path to the file to delete
     * @return bool True on success, false on failure
     */
    public function delete(string $filepath): bool {
        if (empty($filepath)) {
            return false;
        }
        
        // Security check: ensure file is in uploads directory
        if (strpos($filepath, self::UPLOAD_DIR) !== 0) {
            return false;
        }
        
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        
        return false;
    }
    
    /**
     * Get upload errors
     * 
     * @return array Array of error messages
     */
    public function getErrors(): array {
        return $this->errors;
    }
    
    /**
     * Get the last error message
     * 
     * @return string|null Last error message or null if no errors
     */
    public function getLastError(): ?string {
        return empty($this->errors) ? null : end($this->errors);
    }
    
    /**
     * Ensure upload directory exists and is writable
     * 
     * @return bool True if directory exists/created, false on failure
     */
    private function ensureUploadDirectory(): bool {
        if (!is_dir(self::UPLOAD_DIR)) {
            if (!mkdir(self::UPLOAD_DIR, 0755, true)) {
                return false;
            }
        }
        
        return is_writable(self::UPLOAD_DIR);
    }
    
    /**
     * Generate a unique filename
     * 
     * @param string $extension File extension
     * @return string Unique filename
     */
    private function generateUniqueFilename(string $extension): string {
        // Use timestamp + random string for uniqueness
        $timestamp = time();
        $randomString = bin2hex(random_bytes(8));
        return "product_{$timestamp}_{$randomString}.{$extension}";
    }
    
    /**
     * Get human-readable upload error message
     * 
     * @param int $errorCode PHP upload error code
     * @return string Error message
     */
    private function getUploadErrorMessage(int $errorCode): string {
        return match($errorCode) {
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive in HTML form',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension',
            default => 'Unknown upload error'
        };
    }
    
    /**
     * Validate if a string is a valid image path
     * 
     * @param string|null $path Path to validate
     * @return bool True if valid, false otherwise
     */
    public static function isValidImagePath(?string $path): bool {
        if (empty($path)) {
            return false;
        }
        
        // Check if file exists
        if (!file_exists($path)) {
            return false;
        }
        
        // Check extension
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($extension, self::ALLOWED_EXTENSIONS);
    }
    
    /**
     * Get file size in human-readable format
     * 
     * @param string $filepath Path to file
     * @return string Formatted file size
     */
    public static function getFileSize(string $filepath): string {
        if (!file_exists($filepath)) {
            return 'Unknown';
        }
        
        $bytes = filesize($filepath);
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        
        return round($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
    }
}
?>


