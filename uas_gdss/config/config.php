<?php
/**
 * =====================================================
 * GDSS Configuration File
 * Database connection and system configuration
 * =====================================================
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'gdss_topsis');
define('DB_USER', 'root');
define('DB_PASS', '230605110146');
define('DB_CHARSET', 'utf8mb4');

// System configuration
define('SITE_URL', 'http://localhost/gdss/uas_gdss');
define('SITE_NAME', 'GDSS - Group Decision Support System');

// Security configuration
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds

/**
 * Database connection using PDO
 * @return PDO
 */
function getConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection failed. Please check your configuration.");
        }
    }
    
    return $pdo;
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']) && isset($_SESSION['role']);
}

/**
 * Check if user has specific role
 * @param string $role
 * @return bool
 */
function hasRole($role) {
    return isLoggedIn() && $_SESSION['role'] === $role;
}

/**
 * Require login - redirect to login page if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/index.php');
        exit();
    }
    
    // Check session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_destroy();
        header('Location: ' . SITE_URL . '/index.php?timeout=1');
        exit();
    }
    
    $_SESSION['last_activity'] = time();
}

/**
 * Require specific role - redirect if user doesn't have required role
 * @param string $required_role
 */
function requireRole($required_role) {
    requireLogin();
    
    if (!hasRole($required_role)) {
        header('Location: ' . SITE_URL . '/dashboard.php?access_denied=1');
        exit();
    }
}

/**
 * Sanitize input data
 * @param string $data
 * @return string
 */
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF token
 * @return string
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * @param string $token
 * @return bool
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Set flash message
 * @param string $type (success, error, warning, info)
 * @param string $message
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 * @return array|null
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Format date for display
 * @param string $date
 * @return string
 */
function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

/**
 * Format datetime for display
 * @param string $datetime
 * @return string
 */
function formatDateTime($datetime) {
    return date('d/m/Y H:i', strtotime($datetime));
}

/**
 * TOPSIS Field weights for BORDA calculation
 */
define('BORDA_WEIGHTS', [
    'supervisor' => 7,
    'teknis' => 4,
    'keuangan' => 2
]);

/**
 * Get BORDA weight total
 */
function getBordaWeightTotal() {
    return array_sum(BORDA_WEIGHTS);
}

/**
 * Log system activity
 * @param string $action
 * @param string $description
 */
function logActivity($action, $description = '') {
    if (isLoggedIn()) {
        try {
            $pdo = getConnection();
            $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$_SESSION['user_id'], $action, $description]);
        } catch (Exception $e) {
            error_log("Failed to log activity: " . $e->getMessage());
        }
    }
}

// Initialize error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // Set to 1 for development
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Create logs directory if it doesn't exist
$logsDir = __DIR__ . '/../logs';
if (!is_dir($logsDir)) {
    mkdir($logsDir, 0755, true);
}
?>