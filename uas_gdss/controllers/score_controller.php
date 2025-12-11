<?php
/**
 * =====================================================
 * Score Controller
 * Handles evaluation scoring by decision makers
 * =====================================================
 */

require_once __DIR__ . '/../config/config.php';

class ScoreController {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getConnection();
    }
    
    /**
     * Save evaluation scores
     * @param array $scoreData
     * @return array
     */
    public function saveScores($scoreData) {
        try {
            // Validate user role
            $userRole = $_SESSION['role'];
            if (!in_array($userRole, array('supervisor', 'teknis', 'keuangan'))) {
                return array('success' => false, 'message' => 'Hanya decision maker yang dapat memberikan penilaian.');
            }
            
            $userId = $_SESSION['user_id'];
            $projectId = intval($scoreData['project_id']);
            
            // Validate project exists
            if (!$this->projectExists($projectId)) {
                return array('success' => false, 'message' => 'Proyek tidak ditemukan.');
            }
            
            // Get criteria for user's field
            $criteria = $this->getCriteriaByField($userRole);
            if (empty($criteria)) {
                return array('success' => false, 'message' => 'Tidak ada kriteria untuk bidang ' . $userRole);
            }
            
            // Validate scores
            $scores = $scoreData['scores'] ?? array();
            foreach ($criteria as $criterion) {
                $criteriaId = $criterion['id'];
                if (!isset($scores[$criteriaId])) {
                    return array('success' => false, 'message' => 'Penilaian untuk kriteria "' . $criterion['name'] . '" harus diisi.');
                }
                
                $score = floatval($scores[$criteriaId]);
                if ($score < 1 || $score > 5) {
                    return array('success' => false, 'message' => 'Nilai harus antara 1 dan 5.');
                }
            }
            
            // Begin transaction
            $this->pdo->beginTransaction();
            
            // Delete existing scores for this project and user
            $stmt = $this->pdo->prepare("
                DELETE FROM scores 
                WHERE user_id = ? AND project_id = ?
            ");
            $stmt->execute(array($userId, $projectId));
            
            // Insert new scores
            $stmt = $this->pdo->prepare("
                INSERT INTO scores (user_id, project_id, criteria_id, value) 
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($criteria as $criterion) {
                $criteriaId = $criterion['id'];
                $score = floatval($scores[$criteriaId]);
                
                $stmt->execute(array($userId, $projectId, $criteriaId, $score));
            }
            
            // Commit transaction
            $this->pdo->commit();
            
            // Calculate TOPSIS after saving scores
            require_once __DIR__ . '/topsis_controller.php';
            $topsisController = new TopsisController();
            $topsisResult = $topsisController->calculateTopsis($userRole);
            
            // Get specific project result
            $projectTopsisScore = null;
            $projectRank = null;
            if ($topsisResult['success'] && isset($topsisResult['results'])) {
                foreach ($topsisResult['results'] as $result) {
                    if ($result['project_id'] == $projectId) {
                        $projectTopsisScore = $result['topsis_score'];
                        $projectRank = $result['rank'];
                        break;
                    }
                }
            }
            
            return array(
                'success' => true, 
                'message' => 'Penilaian berhasil disimpan dan TOPSIS dihitung.',
                'topsis_calculated' => $topsisResult['success'],
                'topsis_score' => $projectTopsisScore,
                'rank' => $projectRank,
                'total_projects' => isset($topsisResult['results']) ? count($topsisResult['results']) : 0
            );
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Save scores error: " . $e->getMessage());
            return array('success' => false, 'message' => 'Terjadi kesalahan sistem.');
        }
    }
    
    /**
     * Get user's scores for a project
     * @param int $projectId
     * @param int $userId
     * @return array
     */
    public function getUserScores($projectId, $userId = null) {
        try {
            if ($userId === null) {
                $userId = $_SESSION['user_id'];
            }
            
            $stmt = $this->pdo->prepare("
                SELECT 
                    s.criteria_id,
                    s.value,
                    c.name as criteria_name,
                    c.weight
                FROM scores s
                JOIN criteria c ON s.criteria_id = c.id
                WHERE s.user_id = ? AND s.project_id = ?
                ORDER BY c.name
            ");
            $stmt->execute(array($userId, $projectId));
            
            $scores = array();
            $results = $stmt->fetchAll();
            
            foreach ($results as $result) {
                $scores[$result['criteria_id']] = array(
                    'value' => $result['value'],
                    'criteria_name' => $result['criteria_name'],
                    'weight' => $result['weight']
                );
            }
            
            return $scores;
            
        } catch (Exception $e) {
            error_log("Get user scores error: " . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Get user's scores for a specific project (returns array with criteria_id and score)
     * @param int $userId
     * @param int $projectId
     * @return array
     */
    public function getUserProjectScores($userId, $projectId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    s.criteria_id,
                    s.value as score,
                    c.name as criteria_name,
                    c.weight,
                    c.type
                FROM scores s
                JOIN criteria c ON s.criteria_id = c.id
                WHERE s.user_id = ? AND s.project_id = ?
                ORDER BY c.name
            ");
            $stmt->execute(array($userId, $projectId));
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get user project scores error: " . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Get all scores for a project
     * @param int $projectId
     * @return array
     */
    public function getProjectScores($projectId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    s.*,
                    u.name as user_name,
                    u.role as user_role,
                    c.name as criteria_name,
                    c.field as criteria_field,
                    c.weight as criteria_weight
                FROM scores s
                JOIN users u ON s.user_id = u.id
                JOIN criteria c ON s.criteria_id = c.id
                WHERE s.project_id = ?
                ORDER BY u.role, c.name
            ");
            $stmt->execute(array($projectId));
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get project scores error: " . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Check if user has completed evaluation for a project
     * @param int $projectId
     * @param int $userId
     * @return bool
     */
    public function isEvaluationComplete($projectId, $userId = null) {
        try {
            if ($userId === null) {
                $userId = $_SESSION['user_id'];
            }
            
            // Get user role
            $stmt = $this->pdo->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute(array($userId));
            $user = $stmt->fetch();
            
            if (!$user) return false;
            
            // Count expected criteria for user's role
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM criteria WHERE field = ?");
            $stmt->execute(array($user['role']));
            $expectedCriteria = $stmt->fetch()['total'];
            
            // Count actual scores
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as total 
                FROM scores s
                JOIN criteria c ON s.criteria_id = c.id
                WHERE s.user_id = ? AND s.project_id = ? AND c.field = ?
            ");
            $stmt->execute(array($userId, $projectId, $user['role']));
            $actualScores = $stmt->fetch()['total'];
            
            return $actualScores >= $expectedCriteria;
            
        } catch (Exception $e) {
            error_log("Check evaluation complete error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get evaluation progress for all users
     * @return array
     */
    public function getEvaluationProgress() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    u.id,
                    u.name,
                    u.role,
                    COUNT(DISTINCT s.project_id) as completed_projects,
                    (SELECT COUNT(*) FROM projects) as total_projects
                FROM users u
                LEFT JOIN scores s ON u.id = s.user_id
                WHERE u.role != 'supervisor'
                GROUP BY u.id, u.name, u.role
                ORDER BY u.role, u.name
            ");
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get evaluation progress error: " . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Get user's pending projects
     * @param int $userId
     * @return array
     */
    public function getPendingProjects($userId = null) {
        try {
            if ($userId === null) {
                $userId = $_SESSION['user_id'];
            }
            
            $stmt = $this->pdo->prepare("
                SELECT p.* 
                FROM projects p
                WHERE p.id NOT IN (
                    SELECT DISTINCT project_id 
                    FROM scores 
                    WHERE user_id = ?
                )
                ORDER BY p.date DESC
            ");
            $stmt->execute(array($userId));
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get pending projects error: " . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Get completed projects for user
     * @param int $userId
     * @return array
     */
    public function getCompletedProjects($userId = null) {
        try {
            if ($userId === null) {
                $userId = $_SESSION['user_id'];
            }
            
            $stmt = $this->pdo->prepare("
                SELECT DISTINCT p.*, 
                       COUNT(s.id) as score_count,
                       MAX(s.created_at) as last_evaluated
                FROM projects p
                JOIN scores s ON p.id = s.project_id
                WHERE s.user_id = ?
                GROUP BY p.id
                ORDER BY last_evaluated DESC
            ");
            $stmt->execute(array($userId));
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get completed projects error: " . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Get user's evaluation history
     * @param int $userId
     * @return array
     */
    public function getUserEvaluationHistory($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT p.id as project_id,
                       p.project_code,
                       p.project_name,
                       p.location,
                       p.description,
                       p.date as project_date,
                       COUNT(s.id) as criteria_count,
                       AVG(s.value) as avg_score,
                       MAX(s.created_at) as evaluated_at
                FROM projects p
                JOIN scores s ON p.id = s.project_id
                WHERE s.user_id = ?
                GROUP BY p.id, p.project_code, p.project_name, p.location, p.description, p.date
                ORDER BY evaluated_at DESC
            ");
            $stmt->execute(array($userId));
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get user evaluation history error: " . $e->getMessage());
            return array();
        }
    }
    
    // Helper methods
    private function projectExists($projectId) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM projects WHERE id = ?");
        $stmt->execute(array($projectId));
        return $stmt->fetch()['total'] > 0;
    }
    
    private function getCriteriaByField($field) {
        $stmt = $this->pdo->prepare("SELECT * FROM criteria WHERE field = ? ORDER BY name");
        $stmt->execute(array($field));
        return $stmt->fetchAll();
    }
    
    /**
     * Get evaluation matrix for supervisor view
     * @return array
     */
    public function getEvaluationMatrix() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    p.id as project_id,
                    p.project_name,
                    p.project_code,
                    u.id as user_id,
                    u.name as user_name,
                    u.role as user_role,
                    COUNT(s.id) as score_count,
                    (SELECT COUNT(*) FROM criteria WHERE field = u.role) as expected_count
                FROM projects p
                CROSS JOIN users u
                LEFT JOIN scores s ON p.id = s.project_id AND u.id = s.user_id
                WHERE u.role != 'supervisor'
                GROUP BY p.id, p.project_name, p.project_code, u.id, u.name, u.role
                ORDER BY p.project_name, u.role, u.name
            ");
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get evaluation matrix error: " . $e->getMessage());
            return array();
        }
    }
}

// Handle AJAX requests - Only process when this file is accessed directly
if (basename($_SERVER['SCRIPT_FILENAME']) === 'score_controller.php') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $score = new ScoreController();
        
        // Handle save_scores with redirect (form submit)
        if ($_POST['action'] === 'save_scores') {
        $result = $score->saveScores($_POST);
        if ($result['success']) {
            // Store TOPSIS result in session for display
            if (isset($result['topsis_score']) && $result['topsis_score'] !== null) {
                $_SESSION['last_topsis_result'] = array(
                    'score' => $result['topsis_score'],
                    'rank' => $result['rank'],
                    'total' => $result['total_projects']
                );
            }
            setFlashMessage('success', $result['message']);
        } else {
            setFlashMessage('danger', $result['message']);
        }
        header('Location: ../evaluate.php');
        exit();
    }
    
    // Handle AJAX requests with JSON response
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'save':
            echo json_encode($score->saveScores($_POST));
            break;
            
        case 'get_user_scores':
            if (isset($_POST['project_id'])) {
                echo json_encode($score->getUserScores($_POST['project_id'], $_POST['user_id'] ?? null));
            } else {
                echo json_encode(array('success' => false, 'message' => 'Project ID harus diisi.'));
            }
            break;
            
        case 'get_project_scores':
            if (isset($_POST['project_id'])) {
                echo json_encode($score->getProjectScores($_POST['project_id']));
            } else {
                echo json_encode(array('success' => false, 'message' => 'Project ID harus diisi.'));
            }
            break;
            
        case 'get_progress':
            echo json_encode($score->getEvaluationProgress());
            break;
            
        case 'get_pending':
            echo json_encode($score->getPendingProjects($_POST['user_id'] ?? null));
            break;
            
        case 'get_completed':
            echo json_encode($score->getCompletedProjects($_POST['user_id'] ?? null));
            break;
            
        case 'get_matrix':
            echo json_encode($score->getEvaluationMatrix());
            break;
            
        default:
            echo json_encode(array('success' => false, 'message' => 'Action tidak valid.'));
    }
    exit();
    }
}
?>