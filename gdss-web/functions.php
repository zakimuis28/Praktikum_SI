<?php
/**
 * GDSS Functions Library
 * Core functions for GDSS system - Sesuai Artikel Referensi
 */

require_once 'config.php';

/**
 * ==========================================
 * DATABASE CONNECTION
 * ==========================================
 */

/**
 * Get database connection
 */
function getConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            die("Koneksi database gagal. Silakan hubungi administrator.");
        }
    }
    
    return $pdo;
}

/**
 * ==========================================
 * AUTHENTICATION & SESSION MANAGEMENT
 * ==========================================
 */

/**
 * Authenticate user
 */
function authenticate($username, $password) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && $user['password'] === $password) {
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_fullname'] = $user['fullname'];
            
            return $user;
        }
        
        return false;
        
    } catch (PDOException $e) {
        error_log("Authentication error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current logged in user
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'role' => $_SESSION['user_role'],
        'fullname' => $_SESSION['user_fullname']
    ];
}

/**
 * Check if user has specific role
 */
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

/**
 * Logout user
 */
function logoutUser() {
    session_destroy();
    session_start();
}

/**
 * Require login (redirect if not logged in)
 */
function requireLogin() {
    if (!isLoggedIn()) {
        setFlashMessage('error', 'Silakan login terlebih dahulu');
        redirect('index.php');
    }
}

/**
 * ==========================================
 * PROJECT MANAGEMENT
 * ==========================================
 */

/**
 * Get all projects
 */
function getAllProjects() {
    try {
        $pdo = getConnection();
        $stmt = $pdo->query("
            SELECT * FROM projects 
            WHERE status = 'active' 
            ORDER BY id
        ");
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log("Error getting all projects: " . $e->getMessage());
        return [];
    }
}

/**
 * Get project by ID
 */
function getProjectById($id) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
        
    } catch (PDOException $e) {
        error_log("Error getting project by ID: " . $e->getMessage());
        return null;
    }
}

/**
 * Add new project
 */
function addProject($data) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO projects (code, name, location, date_offer, description)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['code'],
            $data['name'],
            $data['location'],
            $data['date_offer'],
            $data['description'] ?? ''
        ]);
        
    } catch (PDOException $e) {
        error_log("Error adding project: " . $e->getMessage());
        return false;
    }
}

/**
 * Update project
 */
function updateProject($id, $data) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("
            UPDATE projects 
            SET code = ?, name = ?, location = ?, date_offer = ?, description = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['code'],
            $data['name'],
            $data['location'],
            $data['date_offer'],
            $data['description'] ?? '',
            $id
        ]);
        
    } catch (PDOException $e) {
        error_log("Error updating project: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete project
 */
function deleteProject($id) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
        return $stmt->execute([$id]);
        
    } catch (PDOException $e) {
        error_log("Error deleting project: " . $e->getMessage());
        return false;
    }
}

/**
 * ==========================================
 * CRITERIA MANAGEMENT
 * ==========================================
 */

/**
 * Get criteria by part
 */
function getCriteriaByPart($part) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM criteria 
            WHERE part = ? 
            ORDER BY id
        ");
        $stmt->execute([$part]);
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log("Error getting criteria by part: " . $e->getMessage());
        return [];
    }
}

/**
 * ==========================================
 * EVALUATION MANAGEMENT
 * ==========================================
 */

/**
 * Save or update evaluation
 */
function saveEvaluation($projectId, $userId, $criteriaId, $score, $notes = '') {
    try {
        $pdo = getConnection();
        
        // Validate score
        if ($score < 1 || $score > 10) {
            return false;
        }
        
        // Check if evaluation exists
        $stmt = $pdo->prepare("
            SELECT id FROM evaluations 
            WHERE project_id = ? AND user_id = ? AND criteria_id = ?
        ");
        $stmt->execute([$projectId, $userId, $criteriaId]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update existing
            $stmt = $pdo->prepare("
                UPDATE evaluations 
                SET score = ?, notes = ?, updated_at = NOW()
                WHERE id = ?
            ");
            return $stmt->execute([$score, $notes, $existing['id']]);
        } else {
            // Insert new
            $stmt = $pdo->prepare("
                INSERT INTO evaluations (project_id, user_id, criteria_id, score, notes)
                VALUES (?, ?, ?, ?, ?)
            ");
            return $stmt->execute([$projectId, $userId, $criteriaId, $score, $notes]);
        }
        
    } catch (PDOException $e) {
        error_log("Error saving evaluation: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all evaluations for a specific project and user
 */
function getUserEvaluations($projectId, $userId) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("
            SELECT e.*, c.name as criteria_name, c.part, c.type
            FROM evaluations e
            JOIN criteria c ON e.criteria_id = c.id
            WHERE e.project_id = ? AND e.user_id = ?
            ORDER BY c.part, c.id
        ");
        $stmt->execute([$projectId, $userId]);
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log("Error getting user evaluations: " . $e->getMessage());
        return [];
    }
}

/**
 * Check if user has completed evaluation for a project
 */
function hasCompletedEvaluation($projectId, $userId, $part) {
    try {
        $pdo = getConnection();
        
        // Get total criteria for this part
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM criteria WHERE part = ?");
        $stmt->execute([$part]);
        $totalCriteria = $stmt->fetchColumn();
        
        // Get completed evaluations
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM evaluations e
            JOIN criteria c ON e.criteria_id = c.id
            WHERE e.project_id = ? AND e.user_id = ? AND c.part = ?
        ");
        $stmt->execute([$projectId, $userId, $part]);
        $completedEvaluations = $stmt->fetchColumn();
        
        return $completedEvaluations >= $totalCriteria;
        
    } catch (PDOException $e) {
        error_log("Error checking evaluation completion: " . $e->getMessage());
        return false;
    }
}

/**
 * Get evaluation progress for a user
 */
function getEvaluationProgress($userId, $part) {
    try {
        $pdo = getConnection();
        
        // Get total projects
        $stmt = $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'active'");
        $totalProjects = $stmt->fetchColumn();
        
        // Get criteria count for this part
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM criteria WHERE part = ?");
        $stmt->execute([$part]);
        $criteriaCount = $stmt->fetchColumn();
        
        if ($criteriaCount == 0) {
            return [
                'total' => $totalProjects,
                'completed' => 0,
                'percentage' => 0
            ];
        }
        
        // Get completed projects for this user and part
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT e.project_id) as completed
            FROM evaluations e
            JOIN criteria c ON e.criteria_id = c.id
            WHERE e.user_id = ? AND c.part = ?
            GROUP BY e.project_id
            HAVING COUNT(DISTINCT e.criteria_id) >= ?
        ");
        $stmt->execute([$userId, $part, $criteriaCount]);
        $completedProjects = $stmt->fetchColumn() ?: 0;
        
        $percentage = $totalProjects > 0 ? round(($completedProjects / $totalProjects) * 100) : 0;
        
        return [
            'total' => $totalProjects,
            'completed' => $completedProjects,
            'percentage' => $percentage
        ];
        
    } catch (PDOException $e) {
        error_log("Error getting evaluation progress: " . $e->getMessage());
        return [
            'total' => 0,
            'completed' => 0,
            'percentage' => 0
        ];
    }
}

/**
 * ==========================================
 * WEIGHTED PRODUCT CALCULATION
 * ==========================================
 */

/**
 * Calculate Weighted Product for a specific part
 */
function calculateWeightedProduct($part) {
    try {
        $pdo = getConnection();
        
        // Get all projects
        $projects = getAllProjects();
        
        if (empty($projects)) {
            return [];
        }
        
        // Get criteria for this part
        $criteria = getCriteriaByPart($part);
        
        if (empty($criteria)) {
            return [];
        }
        
        $results = [];
        
        foreach ($projects as $project) {
            // Get all evaluations for this project and part
            $stmt = $pdo->prepare("
                SELECT e.score, c.weight, c.type, c.id
                FROM evaluations e
                JOIN criteria c ON e.criteria_id = c.id
                WHERE e.project_id = ? AND c.part = ?
            ");
            $stmt->execute([$project['id'], $part]);
            $evaluations = $stmt->fetchAll();
            
            // Skip if not all criteria evaluated
            if (count($evaluations) < count($criteria)) {
                continue;
            }
            
            // Calculate WP value
            $wpValue = 1.0;
            
            foreach ($evaluations as $eval) {
                $score = $eval['score'];
                $weight = $eval['weight'];
                $type = $eval['type'];
                
                // Normalize score (assuming max score is 10)
                $normalizedScore = $score / 10;
                
                // For cost criteria, invert the normalization
                if ($type === 'cost') {
                    // Find min score for this criteria across all projects
                    $stmt = $pdo->prepare("
                        SELECT MIN(e.score) as min_score
                        FROM evaluations e
                        WHERE e.criteria_id = ?
                    ");
                    $stmt->execute([$eval['id']]);
                    $minScore = $stmt->fetchColumn();
                    
                    if ($minScore > 0 && $score > 0) {
                        $normalizedScore = $minScore / $score;
                    }
                    
                    // Weight is negative for cost
                    $weight = -$weight;
                }
                
                // Calculate power
                $wpValue *= pow($normalizedScore, $weight);
            }
            
            $results[] = [
                'project_id' => $project['id'],
                'project_code' => $project['code'],
                'project_name' => $project['name'],
                'project_location' => $project['location'],
                'wp_value' => $wpValue
            ];
        }
        
        // Sort by WP value (descending)
        usort($results, function($a, $b) {
            return $b['wp_value'] <=> $a['wp_value'];
        });
        
        // Add ranking
        foreach ($results as $index => &$result) {
            $result['rank'] = $index + 1;
        }
        
        return $results;
        
    } catch (PDOException $e) {
        error_log("Error calculating Weighted Product: " . $e->getMessage());
        return [];
    }
}

/**
 * ==========================================
 * BORDA AGGREGATION
 * ==========================================
 */

/**
 * Calculate BORDA consensus aggregation
 */
function calculateBordaMethod() {
    try {
        $pdo = getConnection();
        
        // Get all projects
        $projects = getAllProjects();
        
        if (empty($projects)) {
            return [];
        }
        
        // Calculate WP for each part
        $teknisResults = calculateWeightedProduct('teknis');
        $administrasiResults = calculateWeightedProduct('administrasi');
        $keuanganResults = calculateWeightedProduct('keuangan');
        
        // Get part weights
        $stmt = $pdo->query("SELECT part, weight FROM part_weights");
        $partWeights = [];
        while ($row = $stmt->fetch()) {
            $partWeights[$row['part']] = $row['weight'];
        }
        
        // Default weights if not in database
        if (empty($partWeights)) {
            $partWeights = [
                'teknis' => 0.538,
                'administrasi' => 0.308,
                'keuangan' => 0.154
            ];
        }
        
        $bordaScores = [];
        $n = count($projects); // Total number of projects
        
        foreach ($projects as $project) {
            $projectId = $project['id'];
            $bordaScore = 0;
            
            // Find ranks in each part
            $teknisRank = null;
            $teknisScore = null;
            $administrasiRank = null;
            $administrasiScore = null;
            $keuanganRank = null;
            $keuanganScore = null;
            
            // Teknis
            foreach ($teknisResults as $result) {
                if ($result['project_id'] == $projectId) {
                    $teknisRank = $result['rank'];
                    $teknisScore = $result['wp_value'];
                    $bordaScore += ($n - $teknisRank + 1) * $partWeights['teknis'];
                    break;
                }
            }
            
            // Administrasi
            foreach ($administrasiResults as $result) {
                if ($result['project_id'] == $projectId) {
                    $administrasiRank = $result['rank'];
                    $administrasiScore = $result['wp_value'];
                    $bordaScore += ($n - $administrasiRank + 1) * $partWeights['administrasi'];
                    break;
                }
            }
            
            // Keuangan
            foreach ($keuanganResults as $result) {
                if ($result['project_id'] == $projectId) {
                    $keuanganRank = $result['rank'];
                    $keuanganScore = $result['wp_value'];
                    $bordaScore += ($n - $keuanganRank + 1) * $partWeights['keuangan'];
                    break;
                }
            }
            
            // Only include if at least one evaluation exists
            if ($teknisRank || $administrasiRank || $keuanganRank) {
                $bordaScores[] = [
                    'project_id' => $projectId,
                    'project_code' => $project['code'],
                    'project_name' => $project['name'],
                    'project_location' => $project['location'],
                    'teknis_rank' => $teknisRank,
                    'teknis_score' => $teknisScore,
                    'administrasi_rank' => $administrasiRank,
                    'administrasi_score' => $administrasiScore,
                    'keuangan_rank' => $keuanganRank,
                    'keuangan_score' => $keuanganScore,
                    'borda_score' => $bordaScore
                ];
            }
        }
        
        // Sort by BORDA score (descending)
        usort($bordaScores, function($a, $b) {
            return $b['borda_score'] <=> $a['borda_score'];
        });
        
        // Add final ranking
        foreach ($bordaScores as $index => &$score) {
            $score['final_rank'] = $index + 1;
        }
        
        return $bordaScores;
        
    } catch (PDOException $e) {
        error_log("Error calculating BORDA method: " . $e->getMessage());
        return [];
    }
}

/**
 * ==========================================
 * CONSENSUS FINALIZATION
 * ==========================================
 */

/**
 * Check if consensus has been finalized
 */
function isConsensusFinalized() {
    try {
        $pdo = getConnection();
        $stmt = $pdo->query("SELECT COUNT(*) FROM consensus_results WHERE is_finalized = 1");
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log("Error checking consensus finalization: " . $e->getMessage());
        return false;
    }
}

/**
 * Finalize consensus results (Admin only)
 */
function finalizeConsensus() {
    try {
        if (!hasRole('admin')) {
            return false;
        }
        
        $pdo = getConnection();
        
        // Get BORDA results
        $bordaResults = calculateBordaMethod();
        
        if (empty($bordaResults)) {
            return false;
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Clear existing finalized results
        $pdo->exec("DELETE FROM consensus_results WHERE is_finalized = 1");
        
        // Insert finalized results
        $stmt = $pdo->prepare("
            INSERT INTO consensus_results 
            (project_id, teknis_rank, teknis_score, administrasi_rank, administrasi_score, 
             keuangan_rank, keuangan_score, borda_score, final_rank, is_finalized, 
             finalized_by, finalized_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())
        ");
        
        foreach ($bordaResults as $result) {
            $stmt->execute([
                $result['project_id'],
                $result['teknis_rank'] ?? null,
                $result['teknis_score'] ?? null,
                $result['administrasi_rank'] ?? null,
                $result['administrasi_score'] ?? null,
                $result['keuangan_rank'] ?? null,
                $result['keuangan_score'] ?? null,
                $result['borda_score'],
                $result['final_rank'],
                $_SESSION['user_id']
            ]);
        }
        
        $pdo->commit();
        return true;
        
    } catch (PDOException $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        error_log("Error finalizing consensus: " . $e->getMessage());
        return false;
    }
}

/**
 * ==========================================
 * UTILITY FUNCTIONS
 * ==========================================
 */

/**
 * Get rank badge CSS class
 */
function getRankBadgeClass($rank) {
    switch ($rank) {
        case 1:
            return 'bg-warning text-dark';
        case 2:
            return 'bg-secondary';
        case 3:
            return 'bg-info';
        default:
            return 'bg-light text-dark';
    }
}

/**
 * Format score for display
 */
function formatScore($score, $decimals = 3) {
    return number_format($score, $decimals, '.', '');
}

/**
 * Format number with specific decimals
 */
function formatNumber($number, $decimals = 2) {
    return number_format($number, $decimals, '.', '');
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'd M Y') {
    $months = [
        'Jan' => 'Jan', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Apr',
        'May' => 'Mei', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Aug' => 'Agt',
        'Sep' => 'Sep', 'Oct' => 'Okt', 'Nov' => 'Nov', 'Dec' => 'Des'
    ];
    
    $formatted = date($format, strtotime($date));
    
    foreach ($months as $en => $id) {
        $formatted = str_replace($en, $id, $formatted);
    }
    
    return $formatted;
}

/**
 * Redirect to a page
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Escape HTML output
 */
function escape($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * ==========================================
 * FLASH MESSAGES
 * ==========================================
 */

/**
 * Set flash message
 */
function setFlashMessage($type, $message) {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    
    if (!isset($_SESSION['flash_messages'][$type])) {
        $_SESSION['flash_messages'][$type] = [];
    }
    
    $_SESSION['flash_messages'][$type][] = $message;
}

/**
 * Get and clear flash messages
 */
function getFlashMessages() {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * ==========================================
 * CSRF PROTECTION
 * ==========================================
 */

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * ==========================================
 * BACKWARD COMPATIBILITY
 * ==========================================
 */

/**
 * Alias for getAllProjects (for compatibility)
 */
function getProjects() {
    return getAllProjects();
}

/**
 * Login user (alias for authenticate)
 */
function loginUser($username, $password) {
    return authenticate($username, $password);
}
?>