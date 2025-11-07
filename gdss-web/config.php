<?php
/**
 * GDSS Configuration File
 * Konfigurasi database dan session
 */

// Check if session is already started and destroy it to apply new settings
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

// Set session configuration BEFORE starting any session
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');

// Start session with secure settings
session_start();

// Error reporting (development mode)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'gdss_db');
define('DB_USER', 'root');
define('DB_PASS', '230605110146'); // Kosongkan jika tidak ada password

// Application Configuration
define('APP_NAME', 'GDSS - Group Decision Support System');
define('APP_VERSION', '1.0.0');
define('APP_AUTHOR', 'Nur Heri Cahyana & Agus Sasmito Aribowo (2014)');

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Site URL (adjust to your environment)
define('SITE_URL', 'http://localhost/gdss-web');

// Upload Configuration
define('UPLOAD_DIR', __DIR__ . '/uploads');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Create upload directory if not exists
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}
?>