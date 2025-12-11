<?php
/**
 * =====================================================
 * TOPSIS Controller - GDSS System
 * Technique for Order of Preference by Similarity to Ideal Solution
 * 
 * Langkah-langkah TOPSIS:
 * 1. Membangun matriks keputusan X
 * 2. Normalisasi matriks R (vector normalization)
 * 3. Pembobotan matriks V = R × W
 * 4. Menentukan solusi ideal positif (A+) dan negatif (A-)
 * 5. Menghitung jarak D+ dan D-
 * 6. Menghitung nilai preferensi Ci
 * 7. Meranking berdasarkan Ci (tertinggi = terbaik)
 * =====================================================
 */

require_once __DIR__ . '/../config/config.php';

class TopsisController {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getConnection();
    }
    
    /**
     * Hitung TOPSIS untuk bidang tertentu (supervisor/teknis/keuangan)
     * @param string $field Bidang DM
     * @return array Hasil perhitungan
     */
    public function calculateTopsis($field) {
        try {
            // Validasi field
            $validFields = ['teknis', 'supervisor', 'keuangan'];
            if (!in_array($field, $validFields)) {
                return ['success' => false, 'message' => 'Field tidak valid.'];
            }
            
            // 1. Ambil data kriteria untuk field ini
            $criteria = $this->getCriteriaByField($field);
            if (empty($criteria)) {
                return ['success' => false, 'message' => 'Tidak ada kriteria untuk field ' . $field];
            }
            
            // 2. Ambil semua proyek
            $projects = $this->getAllProjects();
            if (empty($projects)) {
                return ['success' => false, 'message' => 'Tidak ada proyek untuk dievaluasi.'];
            }
            
            // 3. Ambil user untuk field ini
            $user = $this->getUserByField($field);
            if (!$user) {
                return ['success' => false, 'message' => 'User untuk field ' . $field . ' tidak ditemukan.'];
            }
            
            // 4. Bangun matriks keputusan X
            $decisionMatrix = $this->buildDecisionMatrix($user['id'], $projects, $criteria);
            if (empty($decisionMatrix['matrix'])) {
                return ['success' => false, 'message' => 'Tidak ada data evaluasi untuk field ' . $field];
            }
            
            // 5. Normalisasi matriks R
            $normalizedMatrix = $this->normalizeMatrix($decisionMatrix['matrix'], $criteria);
            
            // 6. Pembobotan matriks V
            $weightedMatrix = $this->applyWeights($normalizedMatrix, $criteria);
            
            // 7. Tentukan solusi ideal A+ dan A-
            $idealSolutions = $this->calculateIdealSolutions($weightedMatrix, $criteria);
            
            // 8. Hitung jarak D+ dan D-
            $distances = $this->calculateDistances($weightedMatrix, $idealSolutions);
            
            // 9. Hitung nilai preferensi Ci dan ranking
            $topsisResults = $this->calculatePreferenceValues($distances, $projects);
            
            // 10. Simpan hasil ke database
            $this->saveTopsisResults($field, $topsisResults);
            
            // Log activity
            logActivity('TOPSIS_CALCULATE', "Perhitungan TOPSIS untuk field: $field");
            
            return [
                'success' => true,
                'message' => 'Perhitungan TOPSIS berhasil untuk bidang ' . strtoupper($field),
                'results' => $topsisResults,
                'details' => [
                    'decision_matrix' => $decisionMatrix,
                    'normalized_matrix' => $normalizedMatrix,
                    'weighted_matrix' => $weightedMatrix,
                    'ideal_solutions' => $idealSolutions,
                    'distances' => $distances
                ]
            ];
            
        } catch (Exception $e) {
            error_log("TOPSIS Calculate Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()];
        }
    }
    
    /**
     * Ambil kriteria berdasarkan field
     */
    private function getCriteriaByField($field) {
        $stmt = $this->pdo->prepare("SELECT * FROM criteria WHERE field = ? ORDER BY id");
        $stmt->execute([$field]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Ambil semua proyek
     */
    private function getAllProjects() {
        $stmt = $this->pdo->query("SELECT id, project_code as code, project_name as name FROM projects ORDER BY id");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Ambil user berdasarkan field/role
     */
    private function getUserByField($field) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE role = ? LIMIT 1");
        $stmt->execute([$field]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Langkah 1: Bangun matriks keputusan X
     * Baris = alternatif (proyek), Kolom = kriteria
     */
    private function buildDecisionMatrix($userId, $projects, $criteria) {
        $matrix = [];
        $criteriaIds = array_column($criteria, 'id');
        
        foreach ($projects as $project) {
            $row = [];
            $hasAllScores = true;
            
            foreach ($criteria as $criterion) {
                $stmt = $this->pdo->prepare(
                    "SELECT value FROM scores 
                     WHERE user_id = ? AND project_id = ? AND criteria_id = ?"
                );
                $stmt->execute([$userId, $project['id'], $criterion['id']]);
                $score = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($score) {
                    $row[$criterion['id']] = floatval($score['value']);
                } else {
                    $hasAllScores = false;
                    $row[$criterion['id']] = 0;
                }
            }
            
            if ($hasAllScores) {
                $matrix[$project['id']] = $row;
            }
        }
        
        return [
            'matrix' => $matrix,
            'criteria' => $criteria,
            'projects' => $projects
        ];
    }
    
    /**
     * Langkah 2: Normalisasi matriks menggunakan Vector Normalization
     * r_ij = x_ij / sqrt(sum(x_ij^2))
     */
    private function normalizeMatrix($matrix, $criteria) {
        if (empty($matrix)) return [];
        
        $normalized = [];
        
        // Hitung pembagi (sqrt of sum of squares) untuk setiap kriteria
        $divisors = [];
        foreach ($criteria as $criterion) {
            $sumSquares = 0;
            foreach ($matrix as $projectId => $row) {
                $sumSquares += pow($row[$criterion['id']], 2);
            }
            $divisors[$criterion['id']] = sqrt($sumSquares);
        }
        
        // Normalisasi setiap elemen
        foreach ($matrix as $projectId => $row) {
            $normalized[$projectId] = [];
            foreach ($criteria as $criterion) {
                $divisor = $divisors[$criterion['id']];
                if ($divisor > 0) {
                    $normalized[$projectId][$criterion['id']] = $row[$criterion['id']] / $divisor;
                } else {
                    $normalized[$projectId][$criterion['id']] = 0;
                }
            }
        }
        
        return $normalized;
    }
    
    /**
     * Langkah 3: Pembobotan matriks V = R × W
     * v_ij = w_j × r_ij
     */
    private function applyWeights($normalizedMatrix, $criteria) {
        $weighted = [];
        
        // Buat lookup untuk weight
        $weights = [];
        foreach ($criteria as $criterion) {
            $weights[$criterion['id']] = floatval($criterion['weight']);
        }
        
        foreach ($normalizedMatrix as $projectId => $row) {
            $weighted[$projectId] = [];
            foreach ($row as $criteriaId => $value) {
                $weighted[$projectId][$criteriaId] = $value * $weights[$criteriaId];
            }
        }
        
        return $weighted;
    }
    
    /**
     * Langkah 4: Tentukan solusi ideal positif (A+) dan negatif (A-)
     * A+ = max(v_ij) untuk benefit, min(v_ij) untuk cost
     * A- = min(v_ij) untuk benefit, max(v_ij) untuk cost
     */
    private function calculateIdealSolutions($weightedMatrix, $criteria) {
        $aPlus = [];  // Solusi ideal positif
        $aMinus = []; // Solusi ideal negatif
        
        // Buat lookup untuk type kriteria
        $criteriaTypes = [];
        foreach ($criteria as $criterion) {
            $criteriaTypes[$criterion['id']] = $criterion['type'];
        }
        
        foreach ($criteria as $criterion) {
            $criteriaId = $criterion['id'];
            $values = [];
            
            foreach ($weightedMatrix as $projectId => $row) {
                $values[] = $row[$criteriaId];
            }
            
            if ($criterion['type'] === 'benefit') {
                // Benefit: A+ = max, A- = min
                $aPlus[$criteriaId] = max($values);
                $aMinus[$criteriaId] = min($values);
            } else {
                // Cost: A+ = min, A- = max
                $aPlus[$criteriaId] = min($values);
                $aMinus[$criteriaId] = max($values);
            }
        }
        
        return [
            'positive' => $aPlus,
            'negative' => $aMinus
        ];
    }
    
    /**
     * Langkah 5: Hitung jarak D+ dan D-
     * D+ = sqrt(sum((v_ij - A+_j)^2))
     * D- = sqrt(sum((v_ij - A-_j)^2))
     */
    private function calculateDistances($weightedMatrix, $idealSolutions) {
        $distances = [];
        
        foreach ($weightedMatrix as $projectId => $row) {
            $dPlusSum = 0;
            $dMinusSum = 0;
            
            foreach ($row as $criteriaId => $value) {
                $dPlusSum += pow($value - $idealSolutions['positive'][$criteriaId], 2);
                $dMinusSum += pow($value - $idealSolutions['negative'][$criteriaId], 2);
            }
            
            $distances[$projectId] = [
                'positive' => sqrt($dPlusSum),
                'negative' => sqrt($dMinusSum)
            ];
        }
        
        return $distances;
    }
    
    /**
     * Langkah 6: Hitung nilai preferensi Ci
     * Ci = D- / (D+ + D-)
     * Semakin besar Ci (mendekati 1), semakin baik alternatif
     */
    private function calculatePreferenceValues($distances, $projects) {
        $results = [];
        
        // Buat lookup untuk project info
        $projectInfo = [];
        foreach ($projects as $project) {
            $projectInfo[$project['id']] = $project;
        }
        
        foreach ($distances as $projectId => $dist) {
            $dPlus = $dist['positive'];
            $dMinus = $dist['negative'];
            
            // Hindari division by zero
            if (($dPlus + $dMinus) > 0) {
                $ci = $dMinus / ($dPlus + $dMinus);
            } else {
                $ci = 0;
            }
            
            $results[] = [
                'project_id' => $projectId,
                'project_code' => $projectInfo[$projectId]['code'] ?? '',
                'project_name' => $projectInfo[$projectId]['name'] ?? '',
                'd_plus' => round($dPlus, 6),
                'd_minus' => round($dMinus, 6),
                'topsis_score' => round($ci, 6),
                'score' => round($ci, 6) // Alias untuk compatibility
            ];
        }
        
        // Sort by topsis_score descending (tertinggi = terbaik)
        usort($results, function($a, $b) {
            return $b['topsis_score'] <=> $a['topsis_score'];
        });
        
        // Assign ranking
        for ($i = 0; $i < count($results); $i++) {
            $results[$i]['rank'] = $i + 1;
        }
        
        return $results;
    }
    
    /**
     * Simpan hasil TOPSIS ke database
     */
    private function saveTopsisResults($field, $results) {
        // Validasi: pastikan ada hasil valid
        if (empty($results)) {
            throw new Exception("Tidak ada hasil TOPSIS untuk disimpan");
        }
        
        // Validasi: pastikan semua hasil memiliki nilai yang valid
        foreach ($results as $result) {
            if (!isset($result['topsis_score']) || 
                !isset($result['d_plus']) || 
                !isset($result['d_minus']) ||
                $result['topsis_score'] === null ||
                $result['d_plus'] === null ||
                $result['d_minus'] === null) {
                throw new Exception("Data TOPSIS tidak lengkap untuk project_id: " . ($result['project_id'] ?? 'unknown'));
            }
        }
        
        // Hapus hasil lama untuk field ini
        $stmt = $this->pdo->prepare("DELETE FROM topsis_results WHERE field = ?");
        $stmt->execute([$field]);
        
        // Insert hasil baru
        $stmt = $this->pdo->prepare(
            "INSERT INTO topsis_results (field, project_id, topsis_score, d_positive, d_negative, `rank`) 
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        
        foreach ($results as $result) {
            $stmt->execute([
                $field,
                $result['project_id'],
                $result['topsis_score'],
                $result['d_plus'],
                $result['d_minus'],
                $result['rank']
            ]);
        }
    }
    
    /**
     * Ambil hasil TOPSIS untuk user/field tertentu
     */
    public function getTopsisResults($field) {
        $stmt = $this->pdo->prepare(
            "SELECT tr.*, p.project_code, p.project_name 
             FROM topsis_results tr
             JOIN projects p ON tr.project_id = p.id
             WHERE tr.field = ?
             ORDER BY tr.rank ASC"
        );
        $stmt->execute([$field]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Ambil semua hasil TOPSIS (semua field)
     */
    public function getAllTopsisResults() {
        $results = [];
        $fields = ['supervisor', 'teknis', 'keuangan'];
        
        foreach ($fields as $field) {
            $results[$field] = $this->getTopsisResults($field);
        }
        
        return $results;
    }
    
    /**
     * Ambil detail perhitungan TOPSIS untuk ditampilkan
     */
    public function getTopsisCalculationDetails($field) {
        try {
            // Ambil kriteria
            $criteria = $this->getCriteriaByField($field);
            if (empty($criteria)) {
                return ['success' => false, 'message' => 'Tidak ada kriteria'];
            }
            
            // Ambil projects
            $projects = $this->getAllProjects();
            if (empty($projects)) {
                return ['success' => false, 'message' => 'Tidak ada proyek'];
            }
            
            // Ambil user
            $user = $this->getUserByField($field);
            if (!$user) {
                return ['success' => false, 'message' => 'User tidak ditemukan'];
            }
            
            // Bangun semua matriks
            $decisionMatrix = $this->buildDecisionMatrix($user['id'], $projects, $criteria);
            
            if (empty($decisionMatrix['matrix'])) {
                return ['success' => false, 'message' => 'Belum ada data evaluasi'];
            }
            
            $normalizedMatrix = $this->normalizeMatrix($decisionMatrix['matrix'], $criteria);
            $weightedMatrix = $this->applyWeights($normalizedMatrix, $criteria);
            $idealSolutions = $this->calculateIdealSolutions($weightedMatrix, $criteria);
            $distances = $this->calculateDistances($weightedMatrix, $idealSolutions);
            $topsisResults = $this->calculatePreferenceValues($distances, $projects);
            
            return [
                'success' => true,
                'criteria' => $criteria,
                'projects' => $projects,
                'decision_matrix' => $decisionMatrix['matrix'],
                'normalized_matrix' => $normalizedMatrix,
                'weighted_matrix' => $weightedMatrix,
                'ideal_solutions' => $idealSolutions,
                'distances' => $distances,
                'results' => $topsisResults
            ];
            
        } catch (Exception $e) {
            error_log("TOPSIS Details Error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Cek apakah sudah ada hasil TOPSIS untuk field tertentu
     */
    public function hasTopsisResults($field) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM topsis_results WHERE field = ?");
        $stmt->execute([$field]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Ambil statistik TOPSIS
     */
    public function getTopsisStats($field) {
        $results = $this->getTopsisResults($field);
        
        if (empty($results)) {
            return [
                'total' => 0,
                'avg_score' => 0,
                'max_score' => 0,
                'min_score' => 0
            ];
        }
        
        $scores = array_column($results, 'topsis_score');
        
        return [
            'total' => count($results),
            'avg_score' => round(array_sum($scores) / count($scores), 4),
            'max_score' => round(max($scores), 4),
            'min_score' => round(min($scores), 4)
        ];
    }
}
