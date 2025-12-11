<?php
/**
 * =====================================================
 * Criteria Controller
 * Handles criteria CRUD operations
 * =====================================================
 */

require_once __DIR__ . '/../config/config.php';

class CriteriaController {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getConnection();
    }
    
    /**
     * Get all criteria grouped by field
     * @return array
     */
    public function getAllCriteria() {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM criteria ORDER BY field, name");
            $stmt->execute();
            $results = $stmt->fetchAll();
            
            // Group by field
            $grouped = array();
            foreach ($results as $criteria) {
                $grouped[$criteria['field']][] = $criteria;
            }
            
            return $grouped;
        } catch (Exception $e) {
            error_log("Get criteria error: " . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Get criteria by field
     * @param string $field
     * @return array
     */
    public function getCriteriaByField($field) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM criteria WHERE field = ? ORDER BY name");
            $stmt->execute(array($field));
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get criteria by field error: " . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Get criteria by ID
     * @param int $criteriaId
     * @return array|null
     */
    public function getCriteriaById($criteriaId) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM criteria WHERE id = ? LIMIT 1");
            $stmt->execute(array($criteriaId));
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get criteria by ID error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create new criteria
     * @param array $criteriaData
     * @return array
     */
    public function createCriteria($criteriaData) {
        try {
            // Only supervisor can create criteria
            if (!hasRole('supervisor')) {
                return array('success' => false, 'message' => 'Akses ditolak.');
            }
            
            // Validate input
            if (empty($criteriaData['field']) || empty($criteriaData['name'])) {
                return array('success' => false, 'message' => 'Field dan nama kriteria harus diisi.');
            }
            
            // Validate field
            $validFields = array('teknis', 'administrasi', 'keuangan');
            if (!in_array($criteriaData['field'], $validFields)) {
                return array('success' => false, 'message' => 'Field tidak valid.');
            }
            
            // Validate weight
            $weight = floatval($criteriaData['weight'] ?? 0);
            if ($weight < 0 || $weight > 1) {
                return array('success' => false, 'message' => 'Bobot harus antara 0 dan 1.');
            }
            
            // Check if criteria name already exists in the same field
            $stmt = $this->pdo->prepare("SELECT id FROM criteria WHERE field = ? AND name = ? LIMIT 1");
            $stmt->execute(array($criteriaData['field'], $criteriaData['name']));
            if ($stmt->fetch()) {
                return array('success' => false, 'message' => 'Nama kriteria sudah ada dalam bidang yang sama.');
            }
            
            // Insert new criteria
            $stmt = $this->pdo->prepare("
                INSERT INTO criteria (field, name, weight) 
                VALUES (?, ?, ?)
            ");
            
            $result = $stmt->execute(array(
                $criteriaData['field'],
                sanitizeInput($criteriaData['name']),
                $weight
            ));
            
            if ($result) {
                return array('success' => true, 'message' => 'Kriteria berhasil dibuat.');
            } else {
                return array('success' => false, 'message' => 'Gagal membuat kriteria.');
            }
            
        } catch (Exception $e) {
            error_log("Create criteria error: " . $e->getMessage());
            return array('success' => false, 'message' => 'Terjadi kesalahan sistem.');
        }
    }
    
    /**
     * Update criteria
     * @param int $criteriaId
     * @param array $criteriaData
     * @return array
     */
    public function updateCriteria($criteriaId, $criteriaData) {
        try {
            // Only supervisor can update criteria
            if (!hasRole('supervisor')) {
                return array('success' => false, 'message' => 'Akses ditolak.');
            }
            
            // Validate input
            if (empty($criteriaData['field']) || empty($criteriaData['name'])) {
                return array('success' => false, 'message' => 'Field dan nama kriteria harus diisi.');
            }
            
            // Validate field
            $validFields = array('teknis', 'administrasi', 'keuangan');
            if (!in_array($criteriaData['field'], $validFields)) {
                return array('success' => false, 'message' => 'Field tidak valid.');
            }
            
            // Validate weight
            $weight = floatval($criteriaData['weight'] ?? 0);
            if ($weight < 0 || $weight > 1) {
                return array('success' => false, 'message' => 'Bobot harus antara 0 dan 1.');
            }
            
            // Check if criteria name is taken by another criteria in the same field
            $stmt = $this->pdo->prepare("SELECT id FROM criteria WHERE field = ? AND name = ? AND id != ? LIMIT 1");
            $stmt->execute(array($criteriaData['field'], $criteriaData['name'], $criteriaId));
            if ($stmt->fetch()) {
                return array('success' => false, 'message' => 'Nama kriteria sudah ada dalam bidang yang sama.');
            }
            
            // Update criteria
            $stmt = $this->pdo->prepare("
                UPDATE criteria 
                SET field = ?, name = ?, weight = ?
                WHERE id = ?
            ");
            
            $result = $stmt->execute(array(
                $criteriaData['field'],
                sanitizeInput($criteriaData['name']),
                $weight,
                $criteriaId
            ));
            
            if ($result) {
                return array('success' => true, 'message' => 'Kriteria berhasil diupdate.');
            } else {
                return array('success' => false, 'message' => 'Gagal mengupdate kriteria.');
            }
            
        } catch (Exception $e) {
            error_log("Update criteria error: " . $e->getMessage());
            return array('success' => false, 'message' => 'Terjadi kesalahan sistem.');
        }
    }
    
    /**
     * Delete criteria
     * @param int $criteriaId
     * @return array
     */
    public function deleteCriteria($criteriaId) {
        try {
            // Only supervisor can delete criteria
            if (!hasRole('supervisor')) {
                return array('success' => false, 'message' => 'Akses ditolak.');
            }
            
            // Check if criteria has scores
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM scores WHERE criteria_id = ?");
            $stmt->execute(array($criteriaId));
            $scoreCount = $stmt->fetch()['total'];
            
            if ($scoreCount > 0) {
                return array('success' => false, 'message' => 'Tidak dapat menghapus kriteria yang sudah digunakan untuk penilaian.');
            }
            
            // Delete criteria
            $stmt = $this->pdo->prepare("DELETE FROM criteria WHERE id = ?");
            $result = $stmt->execute(array($criteriaId));
            
            if ($result) {
                return array('success' => true, 'message' => 'Kriteria berhasil dihapus.');
            } else {
                return array('success' => false, 'message' => 'Gagal menghapus kriteria.');
            }
            
        } catch (Exception $e) {
            error_log("Delete criteria error: " . $e->getMessage());
            return array('success' => false, 'message' => 'Terjadi kesalahan sistem.');
        }
    }
    
    /**
     * Update criteria weights for a field
     * @param string $field
     * @param array $weights (criteria_id => weight)
     * @return array
     */
    public function updateCriteriaWeights($field, $weights) {
        try {
            // Only supervisor can update weights
            if (!hasRole('supervisor')) {
                return array('success' => false, 'message' => 'Akses ditolak.');
            }
            
            // Validate field
            $validFields = array('teknis', 'administrasi', 'keuangan');
            if (!in_array($field, $validFields)) {
                return array('success' => false, 'message' => 'Field tidak valid.');
            }
            
            // Validate weights sum to 1
            $totalWeight = array_sum($weights);
            if (abs($totalWeight - 1.0) > 0.001) {
                return array('success' => false, 'message' => 'Total bobot harus sama dengan 1.0');
            }
            
            // Begin transaction
            $this->pdo->beginTransaction();
            
            // Update each criteria weight
            $stmt = $this->pdo->prepare("UPDATE criteria SET weight = ? WHERE id = ? AND field = ?");
            
            foreach ($weights as $criteriaId => $weight) {
                $weight = floatval($weight);
                if ($weight < 0 || $weight > 1) {
                    $this->pdo->rollBack();
                    return array('success' => false, 'message' => 'Bobot harus antara 0 dan 1.');
                }
                
                $stmt->execute(array($weight, $criteriaId, $field));
            }
            
            // Commit transaction
            $this->pdo->commit();
            
            return array('success' => true, 'message' => 'Bobot kriteria berhasil diupdate.');
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Update criteria weights error: " . $e->getMessage());
            return array('success' => false, 'message' => 'Terjadi kesalahan sistem.');
        }
    }
    
    /**
     * Get criteria weights summary
     * @return array
     */
    public function getCriteriaWeightsSummary() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    field,
                    COUNT(*) as criteria_count,
                    SUM(weight) as total_weight
                FROM criteria 
                GROUP BY field
                ORDER BY field
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get criteria weights summary error: " . $e->getMessage());
            return array();
        }
    }
}

// Handle AJAX requests only when called directly (not included)
if (basename($_SERVER['SCRIPT_FILENAME']) === 'criteria_controller.php') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $criteria = new CriteriaController();
        header('Content-Type: application/json');
        
        switch ($_POST['action']) {
            case 'create':
                echo json_encode($criteria->createCriteria($_POST));
                break;
                
            case 'update':
                echo json_encode($criteria->updateCriteria($_POST['criteria_id'], $_POST));
                break;
                
            case 'delete':
                echo json_encode($criteria->deleteCriteria($_POST['criteria_id']));
                break;
                
            case 'update_weights':
                echo json_encode($criteria->updateCriteriaWeights($_POST['field'], $_POST['weights']));
                break;
                
            case 'get':
                if (isset($_POST['field'])) {
                    echo json_encode($criteria->getCriteriaByField($_POST['field']));
                } else {
                    echo json_encode($criteria->getAllCriteria());
                }
                break;
                
            default:
                echo json_encode(array('success' => false, 'message' => 'Action tidak valid.'));
        }
        exit();
    }
}
?>