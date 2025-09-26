<?php
// M-Pesa API Configuration - SANDBOX ENVIRONMENT
define('CONSUMER_KEY', 'w7r1vLsxmsbxWugwP5UpeRbiz3NhpUAVOE9K6GM1j3m4IKEa');
define('CONSUMER_SECRET', 'eYXR16fIZ71Gq0JYLJFJbGz2CRDxthl6kIDeoB0fbgGXGwUeqUkaBUMSEeIPXbuo');
define('SHORTCODE', '174379'); // Use 174379 for sandbox (Lipisha Account)
define('PASSKEY', 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919'); // Sandbox passkey
define('CALLBACK_URL', ' https://3e3bd520cb3e30b797a271c06c625776.serveo.net/backend/call_back.php'); // UPDATE WITH YOUR ACTUAL DOMAIN

// Environment configuration
define('ENVIRONMENT', 'sandbox'); // 'sandbox' or 'production'

// API URLs
if (ENVIRONMENT === 'sandbox') {
    define('API_URL', 'https://sandbox.safaricom.co.ke');
    define('BUSINESS_SHORTCODE', '174379');
} else {
    define('API_URL', 'https://api.safaricom.co.ke');
    define('BUSINESS_SHORTCODE', 'your_production_shortcode');
}

// Logging
define('MPESA_LOG', '../logs/mpesa_log.txt');

// Payment amounts for packages
define('REGULAR_AMOUNT', 1); // Use 1 KSH for testing
define('PREMIUM_AMOUNT', 1); // Use 1 KSH for testing  
define('GOLD_AMOUNT', 1); // Use 1 KSH for testing

// Function to log M-Pesa transactions
function logMpesaTransaction($message, $data = []) {
    $logMessage = date('[Y-m-d H:i:s]') . " - " . $message;
    if (!empty($data)) {
        $logMessage .= " - " . json_encode($data);
    }
    $logMessage .= PHP_EOL;
    
    // Create logs directory if it doesn't exist
    if (!file_exists(dirname(MPESA_LOG))) {
        mkdir(dirname(MPESA_LOG), 0777, true);
    }
    
    file_put_contents(MPESA_LOG, $logMessage, FILE_APPEND);
}
?>