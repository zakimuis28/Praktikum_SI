<?php
/**
 * =====================================================
 * Authentication Controller
 * Handles login, register, logout functionality
 * =====================================================
 */

require_once __DIR__ . '/../config/config.php';

class AuthController {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getConnection();
    }
    
    /**
     * Login user
     * @param string $username
     * @param string $password
     * @return array
     */
    public function login($username, $password) {
        try {
            // Validate input
            if (empty($username) || empty($password)) {
                return array('success' => false, 'message' => 'Username dan password harus diisi.');
            }
            
            // Get user from database
            $stmt = $this->pdo->prepare("SELECT id, name, username, password, role FROM users WHERE username = ? LIMIT 1");
            $stmt->execute(array($username));
            $user = $stmt->fetch();
            
            if (!$user) {
                return array('success' => false, 'message' => 'Username tidak ditemukan.');
            }
            
            // Verify password
            if (!password_verify($password, $user['password'])) {
                return array('success' => false, 'message' => 'Password salah.');
            }
            
            // Set session data
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['last_activity'] = time();
            $_SESSION['login_time'] = time();
            
            return array(
                'success' => true, 
                'message' => 'Login berhasil.',
                'user' => array(
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'username' => $user['username'],
                    'role' => $user['role']
                )
            );
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return array('success' => false, 'message' => 'Terjadi kesalahan sistem.');
        }
    }
    
    /**
     * Register new user
     * @param array $userData
     * @return array
     */
    public function register($userData) {
        try {
            // Validate input
            $required = ['name', 'username', 'password', 'role'];
            foreach ($required as $field) {
                if (empty($userData[$field])) {
                    return ['success' => false, 'message' => 'Semua field harus diisi.'];
                }
            }
            
            // Validate role
            $validRoles = ['supervisor', 'teknis', 'administrasi', 'keuangan'];
            if (!in_array($userData['role'], $validRoles)) {
                return ['success' => false, 'message' => 'Role tidak valid.'];
            }
            
            // Check if username already exists
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$userData['username']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Username sudah digunakan.'];
            }
            
            // Validate password strength
            if (strlen($userData['password']) < 6) {
                return ['success' => false, 'message' => 'Password minimal 6 karakter.'];
            }
            
            // Hash password
            $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $this->pdo->prepare("INSERT INTO users (name, username, password, role) VALUES (?, ?, ?, ?)");
            $result = $stmt->execute([
                sanitizeInput($userData['name']),
                sanitizeInput($userData['username']),
                $hashedPassword,
                $userData['role']
            ]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Registrasi berhasil. Silakan login.'];
            } else {
                return ['success' => false, 'message' => 'Registrasi gagal.'];
            }
            
        } catch (Exception $e) {
            error_log("Register error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Terjadi kesalahan sistem.'];
        }
    }
    
    /**
     * Logout user
     */
    public function logout() {
        // Destroy all session data
        if (session_status() == PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
        
        // Redirect to login page
        header('Location: ' . SITE_URL . '/index.php?logout=1');
        exit();
    }
    
    /**
     * Get all users (for supervisor)
     * @return array
     */
    public function getAllUsers() {
        try {
            $stmt = $this->pdo->prepare("SELECT id, name, username, role, created_at FROM users ORDER BY created_at DESC");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get users error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Create new user (for supervisor)
     * @param array $userData
     * @return array
     */
    public function createUser($userData) {
        // Only supervisor can create users
        if (!hasRole('supervisor')) {
            return ['success' => false, 'message' => 'Akses ditolak.'];
        }
        
        return $this->register($userData);
    }
    
    /**
     * Update user
     * @param int $userId
     * @param array $userData
     * @return array
     */
    public function updateUser($userId, $userData) {
        try {
            // Only supervisor can update users
            if (!hasRole('supervisor')) {
                return ['success' => false, 'message' => 'Akses ditolak.'];
            }
            
            // Validate input
            if (empty($userData['name']) || empty($userData['username'])) {
                return ['success' => false, 'message' => 'Nama dan username harus diisi.'];
            }
            
            // Check if username is taken by another user
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1");
            $stmt->execute([$userData['username'], $userId]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Username sudah digunakan.'];
            }
            
            // Prepare update query
            $updateFields = "name = ?, username = ?, role = ?";
            $params = [
                sanitizeInput($userData['name']),
                sanitizeInput($userData['username']),
                $userData['role'],
                $userId
            ];
            
            // Include password if provided
            if (!empty($userData['password'])) {
                if (strlen($userData['password']) < 6) {
                    return ['success' => false, 'message' => 'Password minimal 6 karakter.'];
                }
                $updateFields .= ", password = ?";
                array_splice($params, -1, 0, [password_hash($userData['password'], PASSWORD_DEFAULT)]);
            }
            
            $stmt = $this->pdo->prepare("UPDATE users SET {$updateFields} WHERE id = ?");
            $result = $stmt->execute($params);
            
            if ($result) {
                return ['success' => true, 'message' => 'User berhasil diupdate.'];
            } else {
                return ['success' => false, 'message' => 'Update user gagal.'];
            }
            
        } catch (Exception $e) {
            error_log("Update user error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Terjadi kesalahan sistem.'];
        }
    }
    
    /**
     * Delete user
     * @param int $userId
     * @return array
     */
    public function deleteUser($userId) {
        try {
            // Only supervisor can delete users
            if (!hasRole('supervisor')) {
                return ['success' => false, 'message' => 'Akses ditolak.'];
            }
            
            // Cannot delete self
            if ($userId == $_SESSION['user_id']) {
                return ['success' => false, 'message' => 'Tidak dapat menghapus akun sendiri.'];
            }
            
            $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
            $result = $stmt->execute([$userId]);
            
            if ($result) {
                return ['success' => true, 'message' => 'User berhasil dihapus.'];
            } else {
                return ['success' => false, 'message' => 'Hapus user gagal.'];
            }
            
        } catch (Exception $e) {
            error_log("Delete user error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Terjadi kesalahan sistem.'];
        }
    }
    
    /**
     * Get user by ID
     * @param int $userId
     * @return array|null
     */
    public function getUserById($userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT id, name, username, role, created_at FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$userId]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get user by ID error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Change password
     * @param int $userId
     * @param string $currentPassword
     * @param string $newPassword
     * @return array
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // Get current user data
            $stmt = $this->pdo->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'message' => 'User tidak ditemukan.'];
            }
            
            // Verify current password
            if (!password_verify($currentPassword, $user['password'])) {
                return ['success' => false, 'message' => 'Password lama salah.'];
            }
            
            // Validate new password
            if (strlen($newPassword) < 6) {
                return ['success' => false, 'message' => 'Password baru minimal 6 karakter.'];
            }
            
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $result = $stmt->execute([$hashedPassword, $userId]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Password berhasil diubah.'];
            } else {
                return ['success' => false, 'message' => 'Gagal mengubah password.'];
            }
            
        } catch (Exception $e) {
            error_log("Change password error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Terjadi kesalahan sistem.'];
        }
    }
}

// Handle AJAX requests - Only process when this file is accessed directly
if (basename($_SERVER['SCRIPT_FILENAME']) === 'auth.php') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $auth = new AuthController();
        header('Content-Type: application/json');
        
        switch ($_POST['action']) {
            case 'login':
            echo json_encode($auth->login($_POST['username'] ?? '', $_POST['password'] ?? ''));
            break;
            
        case 'register':
            echo json_encode($auth->register($_POST));
            break;
            
        case 'create_user':
            echo json_encode($auth->createUser($_POST));
            break;
            
        case 'update_user':
            echo json_encode($auth->updateUser($_POST['user_id'], $_POST));
            break;
            
        case 'delete_user':
            echo json_encode($auth->deleteUser($_POST['user_id']));
            break;
            
        case 'change_password':
            echo json_encode($auth->changePassword($_POST['user_id'], $_POST['current_password'], $_POST['new_password']));
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Action tidak valid.']);
    }
    exit();
    }
}
?>