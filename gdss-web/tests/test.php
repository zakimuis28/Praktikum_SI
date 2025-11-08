<?php
/**
 * GDSS Test Connection & Setup
 * File untuk testing koneksi database dan setup aplikasi
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔧 GDSS System Check</h2>";

// Test 1: PHP Version
echo "<h3>1. PHP Version Check</h3>";
echo "PHP Version: " . phpversion() . "<br>";
if (version_compare(phpversion(), '8.0', '>=')) {
    echo "✅ PHP Version OK<br>";
} else {
    echo "❌ PHP Version harus 8.0+<br>";
}
echo "<br>";

// Test 2: Required Extensions
echo "<h3>2. PHP Extensions Check</h3>";
$required_extensions = ['pdo', 'pdo_mysql', 'session'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ Extension $ext loaded<br>";
    } else {
        echo "❌ Extension $ext NOT loaded<br>";
    }
}
echo "<br>";

// Test 3: Database Configuration
echo "<h3>3. Database Configuration</h3>";
require_once 'config.php';

echo "DB Host: " . DB_HOST . "<br>";
echo "DB Name: " . DB_NAME . "<br>";
echo "DB User: " . DB_USER . "<br>";
echo "DB Pass: " . (DB_PASS ? str_repeat('*', strlen(DB_PASS)) : 'EMPTY') . "<br>";
echo "<br>";

// Test 4: Database Connection
echo "<h3>4. Database Connection Test</h3>";
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✅ Database connection successful<br>";
    
    // Test 5: Check if tables exist
    echo "<h3>5. Database Tables Check</h3>";
    $tables = ['users', 'projects', 'criteria', 'evaluations', 'part_weights'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "✅ Table '$table' exists with $count records<br>";
        } catch (PDOException $e) {
            echo "❌ Table '$table' does not exist or error: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<br>";
    
    // Test 6: Check users data
    echo "<h3>6. Users Data Check</h3>";
    try {
        $stmt = $pdo->query("SELECT username, fullname, role FROM users");
        $users = $stmt->fetchAll();
        
        if (!empty($users)) {
            echo "✅ Found " . count($users) . " users:<br>";
            foreach ($users as $user) {
                echo "- Username: <strong>{$user['username']}</strong> | Role: {$user['role']} | Name: {$user['fullname']}<br>";
            }
        } else {
            echo "❌ No users found in database<br>";
        }
    } catch (PDOException $e) {
        echo "❌ Error checking users: " . $e->getMessage() . "<br>";
    }
    
    echo "<br>";
    
    // Test 7: Login Function Test
    echo "<h3>7. Login Function Test</h3>";
    require_once 'functions.php';
    
    // Test dengan user admin
    $testUser = loginUser('admin', 'admin123');
    if ($testUser) {
        echo "✅ Login function works - User 'admin' can login<br>";
        echo "User data: " . json_encode($testUser) . "<br>";
        
        // Logout setelah test
        logoutUser();
    } else {
        echo "❌ Login function failed for user 'admin'<br>";
        
        // Cek password di database
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute(['admin']);
        $adminUser = $stmt->fetch();
        
        if ($adminUser) {
            echo "🔍 Admin user found in database:<br>";
            echo "- Username: {$adminUser['username']}<br>";
            echo "- Password in DB: {$adminUser['password']}<br>";
            echo "- Expected password: admin123<br>";
            
            if ($adminUser['password'] === 'admin123') {
                echo "✅ Password matches<br>";
            } else {
                echo "❌ Password does not match<br>";
            }
        } else {
            echo "❌ Admin user not found in database<br>";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    echo "<br>";
    echo "<h4>Possible Solutions:</h4>";
    echo "1. Make sure MySQL/MariaDB is running<br>";
    echo "2. Check database credentials in config.php<br>";
    echo "3. Make sure database 'gdss_db' exists<br>";
    echo "4. Run the install_gdss.sql script first<br>";
}

echo "<br>";
echo "<h3>8. File Permissions Check</h3>";
$files_to_check = ['config.php', 'functions.php', 'index.php'];
foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        if (is_readable($file)) {
            echo "✅ File '$file' is readable<br>";
        } else {
            echo "❌ File '$file' is not readable<br>";
        }
    } else {
        echo "❌ File '$file' does not exist<br>";
    }
}

echo "<br>";
echo "<h3>🔧 Quick Fix Commands</h3>";
echo "<pre>";
echo "1. Create database:
   CREATE DATABASE gdss_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

2. Import SQL script:
   mysql -u root -p gdss_db < install_gdss.sql

3. Check if MySQL is running:
   net start mysql (Windows)
   sudo service mysql start (Linux)

4. Test login directly:
   Username: admin
   Password: admin123
</pre>";

echo "<br>";
echo "<p><a href='index.php'>← Back to Login Page</a></p>";
?>