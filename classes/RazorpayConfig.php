<?php

declare(strict_types=1);

/**
 * RazorpayConfig
 * Secure configuration class for Razorpay credentials.
 * 
 * SECURITY BEST PRACTICES:
 * - In production, move credentials to environment variables or .env file
 * - Never commit credentials to version control
 * - Use different keys for test and live modes
 */
class RazorpayConfig
{
    // Test Mode Credentials (for development)
    private const KEY_ID = 'rzp_test_R6h0atxxQ4WsUU';
    private const KEY_SECRET = '5CyNCDCaDKmrRqPWX2K6uLGV';
    
    // Currency and other settings
    private const CURRENCY = 'INR';
    private const COMPANY_NAME = 'Elite Diecast';
    private const COMPANY_LOGO = 'https://your-logo-url.com/logo.png';
    
    /**
     * Get Razorpay Key ID (public key - safe to expose on frontend)
     */
    public static function getKeyId(): string
    {
        return self::KEY_ID;
    }
    
    /**
     * Get Razorpay Key Secret (private key - NEVER expose on frontend)
     */
    public static function getKeySecret(): string
    {
        return self::KEY_SECRET;
    }
    
    /**
     * Get currency code
     */
    public static function getCurrency(): string
    {
        return self::CURRENCY;
    }
    
    /**
     * Get company name for payment page
     */
    public static function getCompanyName(): string
    {
        return self::COMPANY_NAME;
    }
    
    /**
     * Get company logo URL for payment page
     */
    public static function getCompanyLogo(): string
    {
        return self::COMPANY_LOGO;
    }
    
    /**
     * Check if we're in test mode
     */
    public static function isTestMode(): bool
    {
        return strpos(self::KEY_ID, 'test') !== false;
    }
}

