<?php
/**
 * =====================================================
 * Project Controller
 * Handles project CRUD operations
 * =====================================================
 */

require_once __DIR__ . '/../config/config.php';

class ProjectController {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getConnection();
    }
    
    /**
     * Get all projects
     * @return array
     */
    public function getAllProjects() {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM projects ORDER BY created_at DESC");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get projects error: " . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Get project by ID
     * @param int $projectId
     * @return array|null
     */
    public function getProjectById($projectId) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM projects WHERE id = ? LIMIT 1");
            $stmt->execute(array($projectId));
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get project by ID error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create new project
     * @param array $projectData
     * @return array
     */
    public function createProject($projectData) {
        try {
            // Only supervisor can create projects
            if (!hasRole('supervisor')) {
                return array('success' => false, 'message' => 'Akses ditolak.');
            }
            
            // Validate input
            $required = array('project_code', 'project_name', 'location', 'date');
            foreach ($required as $field) {
                if (empty($projectData[$field])) {
                    return array('success' => false, 'message' => 'Semua field wajib harus diisi.');
                }
            }
            
            // Check if project code already exists
            $stmt = $this->pdo->prepare("SELECT id FROM projects WHERE project_code = ? LIMIT 1");
            $stmt->execute(array($projectData['project_code']));
            if ($stmt->fetch()) {
                return array('success' => false, 'message' => 'Kode proyek sudah digunakan.');
            }
            
            // Validate date format
            $date = DateTime::createFromFormat('Y-m-d', $projectData['date']);
            if (!$date) {
                return array('success' => false, 'message' => 'Format tanggal tidak valid.');
            }
            
            // Insert new project
            $stmt = $this->pdo->prepare("
                INSERT INTO projects (project_code, project_name, location, date, description) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute(array(
                sanitizeInput($projectData['project_code']),
                sanitizeInput($projectData['project_name']),
                sanitizeInput($projectData['location']),
                $projectData['date'],
                sanitizeInput($projectData['description'] ?? '')
            ));
            
            if ($result) {
                return array('success' => true, 'message' => 'Proyek berhasil dibuat.');
            } else {
                return array('success' => false, 'message' => 'Gagal membuat proyek.');
            }
            
        } catch (Exception $e) {
            error_log("Create project error: " . $e->getMessage());
            return array('success' => false, 'message' => 'Terjadi kesalahan sistem.');
        }
    }
    
    /**
     * Update project
     * @param int $projectId
     * @param array $projectData
     * @return array
     */
    public function updateProject($projectId, $projectData) {
        try {
            // Only supervisor can update projects
            if (!hasRole('supervisor')) {
                return array('success' => false, 'message' => 'Akses ditolak.');
            }
            
            // Validate input
            $required = array('project_code', 'project_name', 'location', 'date');
            foreach ($required as $field) {
                if (empty($projectData[$field])) {
                    return array('success' => false, 'message' => 'Semua field wajib harus diisi.');
                }
            }
            
            // Check if project code is taken by another project
            $stmt = $this->pdo->prepare("SELECT id FROM projects WHERE project_code = ? AND id != ? LIMIT 1");
            $stmt->execute(array($projectData['project_code'], $projectId));
            if ($stmt->fetch()) {
                return array('success' => false, 'message' => 'Kode proyek sudah digunakan.');
            }
            
            // Validate date format
            $date = DateTime::createFromFormat('Y-m-d', $projectData['date']);
            if (!$date) {
                return array('success' => false, 'message' => 'Format tanggal tidak valid.');
            }
            
            // Update project
            $stmt = $this->pdo->prepare("
                UPDATE projects 
                SET project_code = ?, project_name = ?, location = ?, date = ?, description = ?
                WHERE id = ?
            ");
            
            $result = $stmt->execute(array(
                sanitizeInput($projectData['project_code']),
                sanitizeInput($projectData['project_name']),
                sanitizeInput($projectData['location']),
                $projectData['date'],
                sanitizeInput($projectData['description'] ?? ''),
                $projectId
            ));
            
            if ($result) {
                return array('success' => true, 'message' => 'Proyek berhasil diupdate.');
            } else {
                return array('success' => false, 'message' => 'Gagal mengupdate proyek.');
            }
            
        } catch (Exception $e) {
            error_log("Update project error: " . $e->getMessage());
            return array('success' => false, 'message' => 'Terjadi kesalahan sistem.');
        }
    }
    
    /**
     * Delete project
     * @param int $projectId
     * @return array
     */
    public function deleteProject($projectId) {
        try {
            // Only supervisor can delete projects
            if (!hasRole('supervisor')) {
                return array('success' => false, 'message' => 'Akses ditolak.');
            }
            
            // Check if project has evaluations
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM scores WHERE project_id = ?");
            $stmt->execute(array($projectId));
            $evaluationCount = $stmt->fetch()['total'];
            
            if ($evaluationCount > 0) {
                return array('success' => false, 'message' => 'Tidak dapat menghapus proyek yang sudah dievaluasi.');
            }
            
            // Delete project
            $stmt = $this->pdo->prepare("DELETE FROM projects WHERE id = ?");
            $result = $stmt->execute(array($projectId));
            
            if ($result) {
                return array('success' => true, 'message' => 'Proyek berhasil dihapus.');
            } else {
                return array('success' => false, 'message' => 'Gagal menghapus proyek.');
            }
            
        } catch (Exception $e) {
            error_log("Delete project error: " . $e->getMessage());
            return array('success' => false, 'message' => 'Terjadi kesalahan sistem.');
        }
    }
    
    /**
     * Get projects with evaluation progress
     * @return array
     */
    public function getProjectsWithProgress() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    p.*,
                    COUNT(DISTINCT s.user_id) as evaluators_count,
                    (SELECT COUNT(*) FROM users WHERE role != 'supervisor') as total_evaluators
                FROM projects p
                LEFT JOIN scores s ON p.id = s.project_id
                GROUP BY p.id
                ORDER BY p.created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get projects with progress error: " . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Get project evaluation details
     * @param int $projectId
     * @return array
     */
    public function getProjectEvaluationDetails($projectId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    u.name as evaluator_name,
                    u.role as evaluator_role,
                    COUNT(s.id) as scores_count,
                    (SELECT COUNT(*) FROM criteria WHERE field = u.role) as total_criteria
                FROM users u
                LEFT JOIN scores s ON u.id = s.user_id AND s.project_id = ?
                WHERE u.role != 'supervisor'
                GROUP BY u.id, u.name, u.role
                ORDER BY u.role, u.name
            ");
            $stmt->execute(array($projectId));
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get project evaluation details error: " . $e->getMessage());
            return array();
        }
    }
}

// Handle AJAX requests only when called directly (not included)
if (basename($_SERVER['SCRIPT_FILENAME']) === 'project_controller.php') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $project = new ProjectController();
        header('Content-Type: application/json');

        switch ($_POST['action']) {
            case 'create':
                echo json_encode($project->createProject($_POST));
                break;

            case 'update':
                echo json_encode($project->updateProject($_POST['project_id'], $_POST));
                break;

            case 'delete':
                echo json_encode($project->deleteProject($_POST['project_id']));
                break;

            case 'get':
                if (isset($_POST['project_id'])) {
                    echo json_encode($project->getProjectById($_POST['project_id']));
                } else {
                    echo json_encode($project->getAllProjects());
                }
                break;

            default:
                echo json_encode(array('success' => false, 'message' => 'Action tidak valid.'));
        }
        exit();
    }
}
?>