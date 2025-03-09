<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'pagoupix');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application configuration
define('DEBUG_MODE', true);

// Asaas API configuration
define('ASAAS_API_KEY', 'YOUR_ASAAS_API_KEY');
define('ASAAS_API_URL', 'https://sandbox.asaas.com/api/v3'); // Change to production URL when ready

// Email configuration
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your_email@example.com');
define('SMTP_PASS', 'your_email_password');
define('SMTP_FROM', 'your_email@example.com');
define('SMTP_FROM_NAME', 'PagouPix');

// Application configuration
define('APP_NAME', 'PagouPix');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);