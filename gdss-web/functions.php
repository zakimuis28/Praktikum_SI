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
 * ANALYTIC HIERARCHY PROCESS (AHP) CALCULATION
 * ==========================================
 */

/**
 * Build symmetric pairwise comparison matrix from database results
 * @param array $comparisons Pairwise comparisons from database
 * @param array $elementIds Array of element IDs to create matrix mapping
 * @return array Symmetric matrix
 */
function buildPairwiseMatrix($comparisons, $elementIds) {
    $n = count($elementIds);
    
    // Create mapping from element ID to matrix index
    $idToIndex = array_flip($elementIds);
    
    // Initialize matrix with 1s on diagonal
    $matrix = array_fill(0, $n, array_fill(0, $n, 1.0));
    
    // Fill upper triangle and reciprocals in lower triangle
    foreach ($comparisons as $comp) {
        $elementI = $comp['element_i'];
        $elementJ = $comp['element_j'];
        $value = (float) $comp['comparison_value'];
        
        // Get matrix indices
        if (isset($idToIndex[$elementI]) && isset($idToIndex[$elementJ])) {
            $i = $idToIndex[$elementI];
            $j = $idToIndex[$elementJ];
            
            $matrix[$i][$j] = $value;
            $matrix[$j][$i] = 1.0 / $value; // Reciprocal
        }
    }
    
    return $matrix;
}

/**
 * Calculate priority vector using geometric mean method
 * @param array $matrix Pairwise comparison matrix
 * @return array Priority vector (normalized)
 */
function calculatePriorityVector($matrix) {
    $n = count($matrix);
    $priorities = [];
    
    // Calculate geometric mean for each row
    for ($i = 0; $i < $n; $i++) {
        $product = 1.0;
        for ($j = 0; $j < $n; $j++) {
            $product *= $matrix[$i][$j];
        }
        $priorities[$i] = pow($product, 1.0 / $n);
    }
    
    // Normalize to sum = 1
    $sum = array_sum($priorities);
    if ($sum > 0) {
        for ($i = 0; $i < $n; $i++) {
            $priorities[$i] /= $sum;
        }
    }
    
    return $priorities;
}

/**
 * Calculate consistency metrics (λmax, CI, CR)
 * @param array $matrix Pairwise comparison matrix
 * @param array $priorities Priority vector
 * @return array ['lambda_max', 'ci', 'cr', 'is_consistent']
 */
function calculateConsistency($matrix, $priorities) {
    $n = count($matrix);
    
    // Calculate λmax using formula: λmax = Σ(Aw)i/wi
    $lambda_max = 0.0;
    
    for ($i = 0; $i < $n; $i++) {
        $sum = 0.0;
        for ($j = 0; $j < $n; $j++) {
            $sum += $matrix[$i][$j] * $priorities[$j];
        }
        if ($priorities[$i] > 0) {
            $lambda_max += $sum / $priorities[$i];
        }
    }
    $lambda_max /= $n;
    
    // Calculate CI (Consistency Index)
    $ci = ($lambda_max - $n) / ($n - 1);
    
    // Get RI (Random Index) from database
    $ri = getRandomIndex($n);
    
    // Calculate CR (Consistency Ratio)
    $cr = $ri > 0 ? $ci / $ri : 0.0;
    
    // Check consistency (CR ≤ 0.1)
    $is_consistent = $cr <= 0.1;
    
    return [
        'lambda_max' => $lambda_max,
        'ci' => $ci,
        'cr' => $cr,
        'is_consistent' => $is_consistent
    ];
}

/**
 * Get Random Index (RI) value for given matrix size
 * @param int $n Matrix size
 * @return float RI value
 */
function getRandomIndex($n) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT random_index FROM ahp_random_index WHERE matrix_size = ?");
        $stmt->execute([$n]);
        $result = $stmt->fetch();
        
        return $result ? $result['random_index'] : 0.0;
    } catch (PDOException $e) {
        error_log("Error getting random index: " . $e->getMessage());
        
        // Fallback RI values
        $ri_values = [1 => 0.00, 2 => 0.00, 3 => 0.52, 4 => 0.89, 5 => 1.11, 6 => 1.25, 7 => 1.35, 8 => 1.40, 9 => 1.45, 10 => 1.49];
        return isset($ri_values[$n]) ? $ri_values[$n] : 1.50;
    }
}

/**
 * Save pairwise comparison to database
 * @param int $userId User ID
 * @param string $comparisonType 'criteria' or 'alternatives'
 * @param int|null $criteriaId Criteria ID (for alternatives comparison)
 * @param int|null $projectId Project ID (for alternatives comparison)
 * @param int $elementI Element i ID
 * @param int $elementJ Element j ID
 * @param float $value Comparison value
 * @return bool Success status
 */
function savePairwiseComparison($userId, $comparisonType, $criteriaId, $projectId, $elementI, $elementJ, $value) {
    try {
        $pdo = getConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO ahp_pairwise_comparisons 
            (user_id, comparison_type, criteria_id, project_id, element_i, element_j, comparison_value)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            comparison_value = VALUES(comparison_value),
            updated_at = CURRENT_TIMESTAMP
        ");
        
        return $stmt->execute([$userId, $comparisonType, $criteriaId, $projectId, $elementI, $elementJ, $value]);
        
    } catch (PDOException $e) {
        error_log("Error saving pairwise comparison: " . $e->getMessage());
        return false;
    }
}

/**
 * Get pairwise comparisons for criteria or alternatives
 * @param int $userId User ID
 * @param string $comparisonType 'criteria' or 'alternatives'
 * @param int|null $criteriaId Criteria ID (for alternatives)
 * @param int|null $projectId Project ID (for alternatives)
 * @return array Comparisons
 */
function getPairwiseComparisons($userId, $comparisonType, $criteriaId = null, $projectId = null) {
    try {
        $pdo = getConnection();
        
        $sql = "
            SELECT element_i, element_j, comparison_value
            FROM ahp_pairwise_comparisons
            WHERE user_id = ? AND comparison_type = ?
        ";
        $params = [$userId, $comparisonType];
        
        if ($criteriaId !== null) {
            $sql .= " AND criteria_id = ?";
            $params[] = $criteriaId;
        } else {
            $sql .= " AND criteria_id IS NULL";
        }
        
        if ($projectId !== null) {
            $sql .= " AND project_id = ?";
            $params[] = $projectId;
        } else {
            $sql .= " AND project_id IS NULL";
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log("Error getting pairwise comparisons: " . $e->getMessage());
        return [];
    }
}

/**
 * Calculate AHP results for criteria or alternatives
 * @param int $userId User ID
 * @param string $calculationType 'criteria_weights', 'alternatives_scores', or 'global_scores'
 * @param array $elements Array of elements (criteria or projects)
 * @param int|null $criteriaId Criteria ID (for alternatives)
 * @param int|null $projectId Project ID (for alternatives)
 * @return array|false AHP calculation results
 */
function calculateAHPResults($userId, $calculationType, $elements, $criteriaId = null, $projectId = null) {
    try {
        $n = count($elements);
        if ($n < 2) {
            return false;
        }
        
        // Get pairwise comparisons
        $comparisonType = ($calculationType === 'criteria_weights') ? 'criteria' : 'alternatives';
        $comparisons = getPairwiseComparisons($userId, $comparisonType, $criteriaId, $projectId);
        
        // Build comparison matrix
        $compArray = [];
        foreach ($comparisons as $comp) {
            $compArray[] = [
                'i' => $comp['element_i'],
                'j' => $comp['element_j'],
                'value' => $comp['comparison_value']
            ];
        }
        
        $matrix = buildPairwiseMatrix($compArray, $n);
        
        // Calculate priority vector
        $priorities = calculatePriorityVector($matrix);
        
        // Calculate consistency
        $consistency = calculateConsistency($matrix, $priorities);
        
        // Save results to database
        saveAHPResults($userId, $calculationType, $criteriaId, $projectId, $matrix, $priorities, $consistency);
        
        return [
            'matrix' => $matrix,
            'priorities' => $priorities,
            'consistency' => $consistency,
            'elements' => $elements
        ];
        
    } catch (Exception $e) {
        error_log("Error calculating AHP results: " . $e->getMessage());
        return false;
    }
}

/**
 * Save AHP calculation results to database
 * @param int $userId User ID
 * @param string $calculationType Calculation type
 * @param int|null $criteriaId Criteria ID
 * @param int|null $projectId Project ID
 * @param array $matrix Pairwise comparison matrix
 * @param array $priorities Priority vector
 * @param array $consistency Consistency metrics
 * @return bool Success status
 */
function saveAHPResults($userId, $calculationType, $criteriaId, $projectId, $matrix, $priorities, $consistency) {
    try {
        $pdo = getConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO ahp_results 
            (user_id, project_id, criteria_id, calculation_type, matrix_data, priority_vector, 
             lambda_max, consistency_index, consistency_ratio, is_consistent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            matrix_data = VALUES(matrix_data),
            priority_vector = VALUES(priority_vector),
            lambda_max = VALUES(lambda_max),
            consistency_index = VALUES(consistency_index),
            consistency_ratio = VALUES(consistency_ratio),
            is_consistent = VALUES(is_consistent),
            updated_at = CURRENT_TIMESTAMP
        ");
        
        return $stmt->execute([
            $userId,
            $projectId,
            $criteriaId,
            $calculationType,
            json_encode($matrix),
            json_encode($priorities),
            $consistency['lambda_max'],
            $consistency['ci'],
            $consistency['cr'],
            $consistency['is_consistent'] ? 1 : 0
        ]);
        
    } catch (PDOException $e) {
        error_log("Error saving AHP results: " . $e->getMessage());
        return false;
    }
}

/**
 * Calculate global AHP scores for all projects (Decision Maker level) - Old version
 * @param int $userId User ID
 * @param string $part User part (teknis, administrasi, keuangan)
 * @return array AHP results with global scores and rankings
 */
function calculateAHPGlobalScoresOld($userId, $part) {
    try {
        $pdo = getConnection();
        
        // Get criteria for this part
        $criteria = getCriteriaByPart($part);
        if (empty($criteria)) {
            return [];
        }
        
        // Get all projects
        $projects = getAllProjects();
        if (empty($projects)) {
            return [];
        }
        
        // Get criteria weights from AHP calculation
        $stmt = $pdo->prepare("
            SELECT priority_vector, is_consistent 
            FROM ahp_results 
            WHERE user_id = ? AND calculation_type = 'criteria_weights' 
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([$userId]);
        $criteriaResult = $stmt->fetch();
        
        if (!$criteriaResult || !$criteriaResult['is_consistent']) {
            return [];
        }
        
        $criteriaWeights = json_decode($criteriaResult['priority_vector'], true);
        
        // Calculate global scores for each project
        $globalScores = [];
        
        foreach ($projects as $project) {
            $projectScore = 0.0;
            $hasAllAlternativeScores = true;
            
            // For each criterion, get alternative scores
            foreach ($criteria as $index => $criterion) {
                $stmt = $pdo->prepare("
                    SELECT priority_vector, is_consistent 
                    FROM ahp_results 
                    WHERE user_id = ? AND calculation_type = 'alternatives_scores' 
                    AND criteria_id = ? 
                    ORDER BY created_at DESC LIMIT 1
                ");
                $stmt->execute([$userId, $criterion['id']]);
                $altResult = $stmt->fetch();
                
                if (!$altResult || !$altResult['is_consistent']) {
                    $hasAllAlternativeScores = false;
                    break;
                }
                
                $alternativeScores = json_decode($altResult['priority_vector'], true);
                
                // Find project score in alternatives
                $projectIndex = array_search($project['id'], array_column($projects, 'id'));
                if ($projectIndex !== false && isset($alternativeScores[$projectIndex])) {
                    $projectScore += $criteriaWeights[$index] * $alternativeScores[$projectIndex];
                }
            }
            
            if ($hasAllAlternativeScores) {
                $globalScores[] = [
                    'project_id' => $project['id'],
                    'project_code' => $project['code'],
                    'project_name' => $project['name'],
                    'project_location' => $project['location'],
                    'global_score' => $projectScore
                ];
            }
        }
        
        // Sort by global score (descending)
        usort($globalScores, function($a, $b) {
            return $b['global_score'] <=> $a['global_score'];
        });
        
        // Add ranking
        foreach ($globalScores as $index => &$result) {
            $result['rank'] = $index + 1;
        }
        
        // Save global results
        if (!empty($globalScores)) {
            $stmt = $pdo->prepare("
                INSERT INTO ahp_results 
                (user_id, calculation_type, global_scores, ranking, lambda_max, consistency_index, consistency_ratio, is_consistent)
                VALUES (?, 'global_scores', ?, ?, 0, 0, 0, 1)
                ON DUPLICATE KEY UPDATE
                global_scores = VALUES(global_scores),
                ranking = VALUES(ranking),
                updated_at = CURRENT_TIMESTAMP
            ");
            
            $globalScoresJson = json_encode(array_column($globalScores, 'global_score'));
            $rankingJson = json_encode(array_column($globalScores, 'rank'));
            $stmt->execute([$userId, $globalScoresJson, $rankingJson]);
        }
        
        return $globalScores;
        
    } catch (Exception $e) {
        error_log("Error calculating AHP global scores: " . $e->getMessage());
        return [];
    }
}

/**
 * ==========================================
 * WEIGHTED PRODUCT CALCULATION (LEGACY - Keep for compatibility)
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
 * Get user ID by role
 * @param string $role User role
 * @return int|null User ID
 */
function getUserIdByRole($role) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE role = ? LIMIT 1");
        $stmt->execute([$role]);
        $result = $stmt->fetch();
        return $result ? $result['id'] : null;
    } catch (PDOException $e) {
        error_log("Error getting user ID by role: " . $e->getMessage());
        return null;
    }
}

/**
 * Calculate BORDA consensus aggregation using AHP rankings
 */
function calculateBordaMethod() {
    try {
        $pdo = getConnection();
        
        // Get all projects
        $projects = getAllProjects();
        
        if (empty($projects)) {
            return [];
        }
        
        // Get AHP rankings for each part
        $teknisUserId = getUserIdByRole('teknis');
        $administrasiUserId = getUserIdByRole('administrasi');
        $keuanganUserId = getUserIdByRole('keuangan');
        
        $teknisResults = $teknisUserId ? calculateAHPGlobalScoresOld($teknisUserId, 'teknis') : [];
        $administrasiResults = $administrasiUserId ? calculateAHPGlobalScoresOld($administrasiUserId, 'administrasi') : [];
        $keuanganResults = $keuanganUserId ? calculateAHPGlobalScoresOld($keuanganUserId, 'keuangan') : [];
        
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
                    $teknisScore = $result['global_score'];
                    $bordaScore += ($n - $teknisRank + 1) * $partWeights['teknis'];
                    break;
                }
            }
            
            // Administrasi
            foreach ($administrasiResults as $result) {
                if ($result['project_id'] == $projectId) {
                    $administrasiRank = $result['rank'];
                    $administrasiScore = $result['global_score'];
                    $bordaScore += ($n - $administrasiRank + 1) * $partWeights['administrasi'];
                    break;
                }
            }
            
            // Keuangan
            foreach ($keuanganResults as $result) {
                if ($result['project_id'] == $projectId) {
                    $keuanganRank = $result['rank'];
                    $keuanganScore = $result['global_score'];
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
 * Finalize consensus results (Supervisor only)
 */
function finalizeConsensus() {
    try {
        if (!hasRole('supervisor')) {
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

/**
 * Save AHP result for a user and project
 * @param int $userId User ID
 * @param int $projectId Project ID  
 * @param float $globalScore Global AHP score
 * @return bool Success status
 */
function saveAHPResult($userId, $projectId, $globalScore) {
    try {
        $pdo = getConnection();
        
        // Delete existing result
        $stmt = $pdo->prepare("DELETE FROM ahp_results WHERE user_id = ? AND project_id = ?");
        $stmt->execute([$userId, $projectId]);
        
        // Insert new result
        $stmt = $pdo->prepare("
            INSERT INTO ahp_results (user_id, project_id, global_score, calculation_type, created_at) 
            VALUES (?, ?, ?, 'global_scores', NOW())
        ");
        return $stmt->execute([$userId, $projectId, $globalScore]);
        
    } catch (PDOException $e) {
        error_log("Error saving AHP result: " . $e->getMessage());
        return false;
    }
}

/**
 * Calculate AHP Global Scores (simplified version)
 * @param array $criteriaPriorities Array of criteria priorities [criteria_id => priority]
 * @param array $alternativePriorities Array of alternative priorities [criteria_id => [project_id => priority]]  
 * @return array Global scores [project_id => score]
 */
function calculateAHPGlobalScores($criteriaPriorities, $alternativePriorities) {
    $globalScores = [];
    
    // Get all project IDs from alternative priorities
    $projectIds = [];
    foreach ($alternativePriorities as $criteriaId => $alternatives) {
        $projectIds = array_merge($projectIds, array_keys($alternatives));
    }
    $projectIds = array_unique($projectIds);
    
    // Calculate global score for each project
    foreach ($projectIds as $projectId) {
        $globalScore = 0.0;
        
        // Sum weighted scores across all criteria
        foreach ($criteriaPriorities as $criteriaId => $criteriaWeight) {
            if (isset($alternativePriorities[$criteriaId][$projectId])) {
                $alternativeScore = $alternativePriorities[$criteriaId][$projectId];
                $globalScore += $criteriaWeight * $alternativeScore;
            }
        }
        
        $globalScores[$projectId] = $globalScore;
    }
    
    return $globalScores;
}

/**
 * Get AHP results for a specific user and criteria/project
 * @param int $userId User ID
 * @param string $calculationType Type of calculation
 * @param int|null $criteriaId Criteria ID (optional)
 * @param int|null $projectId Project ID (optional)
 * @return array|false AHP results or false if not found
 */
function getAHPResults($userId, $calculationType, $criteriaId = null, $projectId = null) {
    try {
        $pdo = getConnection();
        
        $sql = "
            SELECT * FROM ahp_results 
            WHERE user_id = ? AND calculation_type = ?
        ";
        $params = [$userId, $calculationType];
        
        if ($criteriaId !== null) {
            $sql .= " AND criteria_id = ?";
            $params[] = $criteriaId;
        }
        
        if ($projectId !== null) {
            $sql .= " AND project_id = ?";
            $params[] = $projectId;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch();
        
    } catch (PDOException $e) {
        error_log("Error getting AHP results: " . $e->getMessage());
        return false;
    }
}
?>