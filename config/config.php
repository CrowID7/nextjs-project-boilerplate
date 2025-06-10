<?php
/**
 * Application Configuration Settings
 */

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);
session_start();

// Application Constants
define('APP_NAME', 'Google Classroom Clone');
define('APP_VERSION', '1.0.0');
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'txt' => 'text/plain',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png'
]);

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'classroom_clone');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Email Configuration (if needed)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-password');
define('SMTP_FROM', 'noreply@yourapp.com');
define('SMTP_FROM_NAME', APP_NAME);

// Security Configuration
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_MAX_LENGTH', 72);
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 15 * 60); // 15 minutes
define('TOKEN_EXPIRY', 60 * 60); // 1 hour

// Pagination Configuration
define('ITEMS_PER_PAGE', 10);
define('MAX_PAGE_LINKS', 5);

// Time Zone
date_default_timezone_set('Asia/Jakarta');

// Application URLs
define('BASE_URL', 'http://localhost:8000');
define('ASSETS_URL', BASE_URL . '/assets');

// Role Configuration
define('USER_ROLES', [
    'admin' => 'Administrator',
    'dowsen' => 'Teacher',
    'mahasiswa' => 'Student'
]);

// Class Settings
define('MAX_STUDENTS_PER_CLASS', 100);
define('CLASS_CODE_LENGTH', 6);

// Assignment Settings
define('MAX_ASSIGNMENTS_PER_CLASS', 50);
define('ASSIGNMENT_FILE_TYPES', ['pdf', 'doc', 'docx', 'txt']);
define('MAX_ASSIGNMENT_FILE_SIZE', 2 * 1024 * 1024); // 2MB

/**
 * Helper Functions
 */

// Get configuration value
function getConfig($key, $default = null) {
    return defined($key) ? constant($key) : $default;
}

// Check if environment is development
function isDevelopment() {
    return true; // Change this based on your environment
}

// Get base URL with path
function url($path = '') {
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

// Get asset URL
function asset($path = '') {
    return rtrim(ASSETS_URL, '/') . '/' . ltrim($path, '/');
}

// Create upload directory if it doesn't exist
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0777, true);
}

// Set custom error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }

    $errorType = match ($errno) {
        E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE => 'Fatal Error',
        E_USER_ERROR => 'User Error',
        E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING, E_USER_WARNING => 'Warning',
        E_NOTICE, E_USER_NOTICE => 'Notice',
        E_STRICT => 'Strict Standards',
        E_DEPRECATED, E_USER_DEPRECATED => 'Deprecated',
        default => 'Unknown Error'
    };

    $message = sprintf(
        "%s: %s in %s on line %d",
        $errorType,
        $errstr,
        $errfile,
        $errline
    );

    if (isDevelopment()) {
        error_log($message);
        if ($errno == E_ERROR || $errno == E_USER_ERROR) {
            die($message);
        }
    }

    return true;
});

// Set exception handler
set_exception_handler(function($e) {
    $message = sprintf(
        "Uncaught Exception: %s in %s on line %d\nStack trace:\n%s",
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    );

    if (isDevelopment()) {
        error_log($message);
        die($message);
    } else {
        error_log($message);
        header('Location: /error.php?msg=An unexpected error occurred');
        exit();
    }
});
