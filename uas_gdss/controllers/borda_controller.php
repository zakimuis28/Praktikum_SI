<?php
/**
 * =====================================================
 * BORDA Controller - GDSS System
 * Konsensus BORDA dari hasil TOPSIS semua Decision Maker
 * 
 * Formula BORDA:
 * 1. Ambil ranking dari setiap bidang (dari topsis_results)
 * 2. Konversi rank ke poin: points = N - rank + 1 (N = jumlah alternatif)
 * 3. Kalikan dengan bobot bidang: Supervisor=7, Teknis=4, Keuangan=2
 * 4. Jumlahkan semua kontribusi
 * 5. Ranking final berdasarkan skor BORDA tertinggi
 * =====================================================
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/topsis_controller.php';

class BordaController {
    private $pdo;
    private $topsisController;
    
    public function __construct() {
        $this->pdo = getConnection();
        $this->topsisController = new TopsisController();
    }
    
    /**
     * Hitung konsensus BORDA dari semua hasil TOPSIS
     * @return array
     */
    public function calculateBordaConsensus() {
        try {
            // Cek apakah semua field sudah ada hasil TOPSIS
            $fields = ['supervisor', 'teknis', 'keuangan'];
            $missingFields = [];
            
            foreach ($fields as $field) {
                if (!$this->topsisController->hasTopsisResults($field)) {
                    $missingFields[] = strtoupper($field);
                }
            }
            
            if (!empty($missingFields)) {
                return [
                    'success' => false,
                    'message' => 'Perhitungan TOPSIS belum lengkap untuk bidang: ' . implode(', ', $missingFields)
                ];
            }
            
            // Ambil semua hasil TOPSIS
            $topsisResults = $this->topsisController->getAllTopsisResults();
            
            // Hitung BORDA
            $bordaResults = $this->performBordaCalculation($topsisResults);
            
            // Simpan hasil
            $this->saveBordaResults($bordaResults);
            
            // Log activity
            logActivity('BORDA_CALCULATE', 'Perhitungan konsensus BORDA berhasil');
            
            return [
                'success' => true,
                'message' => 'Konsensus BORDA berhasil dihitung.',
                'results' => $bordaResults
            ];
            
        } catch (Exception $e) {
            error_log("BORDA Calculate Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()];
        }
    }
    
    /**
     * Lakukan perhitungan BORDA
     */
    private function performBordaCalculation($topsisResults) {
        $bordaResults = [];
        $projects = [];
        
        // Kumpulkan data proyek dari semua field
        foreach ($topsisResults as $field => $results) {
            foreach ($results as $result) {
                $projectId = $result['project_id'];
                if (!isset($projects[$projectId])) {
                    $projects[$projectId] = [
                        'project_id' => $projectId,
                        'project_name' => $result['project_name'],
                        'project_code' => $result['project_code'],
                        'ranks' => [],
                        'topsis_scores' => []
                    ];
                }
                $projects[$projectId]['ranks'][$field] = intval($result['rank']);
                $projects[$projectId]['topsis_scores'][$field] = floatval($result['topsis_score']);
            }
        }
        
        // Ambil bobot BORDA dari database
        $bordaWeights = $this->getBordaWeights();
        $totalProjects = count($projects);
        
        // Hitung skor BORDA untuk setiap proyek
        foreach ($projects as $projectId => $project) {
            $bordaScore = 0;
            $detailCalculation = [];
            
            foreach ($bordaWeights as $field => $weight) {
                if (isset($project['ranks'][$field])) {
                    $rank = $project['ranks'][$field];
                    
                    // Konversi rank ke poin (rank 1 mendapat poin tertinggi)
                    $points = $totalProjects - $rank + 1;
                    
                    // Kontribusi = poin × bobot bidang
                    $contribution = $points * $weight;
                    $bordaScore += $contribution;
                    
                    $detailCalculation[$field] = [
                        'rank' => $rank,
                        'points' => $points,
                        'weight' => $weight,
                        'contribution' => $contribution,
                        'topsis_score' => isset($project['topsis_scores'][$field]) 
                            ? $project['topsis_scores'][$field] : 0
                    ];
                }
            }
            
            $bordaResults[] = [
                'project_id' => $projectId,
                'project_name' => $project['project_name'],
                'project_code' => $project['project_code'],
                'borda_score' => $bordaScore,
                'detail_calculation' => $detailCalculation,
                'field_ranks' => $project['ranks']
            ];
        }
        
        // Sort by borda_score DESCENDING (skor tertinggi = rank 1)
        usort($bordaResults, function($a, $b) {
            return $b['borda_score'] <=> $a['borda_score'];
        });
        
        // Assign ranking final
        for ($i = 0; $i < count($bordaResults); $i++) {
            $bordaResults[$i]['final_rank'] = $i + 1;
        }
        
        return $bordaResults;
    }
    
    /**
     * Ambil bobot BORDA dari database
     */
    public function getBordaWeights() {
        $stmt = $this->pdo->query("SELECT part, weight FROM part_weights");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $weights = [];
        foreach ($rows as $row) {
            $weights[$row['part']] = intval($row['weight']);
        }
        
        // Default jika tidak ada di database
        if (empty($weights)) {
            $weights = [
                'supervisor' => 7,
                'teknis' => 4,
                'keuangan' => 2
            ];
        }
        
        return $weights;
    }
    
    /**
     * Simpan hasil BORDA ke database
     */
    private function saveBordaResults($results) {
        // Hapus hasil lama
        $this->pdo->exec("DELETE FROM borda_results");
        
        // Insert hasil baru
        $stmt = $this->pdo->prepare(
            "INSERT INTO borda_results (project_id, borda_score, final_rank) VALUES (?, ?, ?)"
        );
        
        foreach ($results as $result) {
            $stmt->execute([
                $result['project_id'],
                $result['borda_score'],
                $result['final_rank']
            ]);
        }
    }
    
    /**
     * Ambil semua hasil BORDA
     */
    public function getAllBordaResults() {
        $stmt = $this->pdo->query(
            "SELECT br.*, p.project_code, p.project_name, p.location, p.description
             FROM borda_results br
             JOIN projects p ON br.project_id = p.id
             ORDER BY br.final_rank ASC"
        );
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Tambahkan skor TOPSIS per field
        $topsisResults = $this->topsisController->getAllTopsisResults();
        
        foreach ($results as &$result) {
            $projectId = $result['project_id'];
            $result['supervisor_score'] = 0;
            $result['teknis_score'] = 0;
            $result['keuangan_score'] = 0;
            $result['final_score'] = $result['borda_score'];
            
            foreach (['supervisor', 'teknis', 'keuangan'] as $field) {
                if (isset($topsisResults[$field])) {
                    foreach ($topsisResults[$field] as $topsisResult) {
                        if ($topsisResult['project_id'] == $projectId) {
                            $result[$field . '_score'] = $topsisResult['topsis_score'];
                            $result[$field . '_rank'] = $topsisResult['rank'];
                            break;
                        }
                    }
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Ambil detail perhitungan BORDA untuk ditampilkan
     */
    public function getBordaCalculationDetails() {
        try {
            // Ambil semua hasil TOPSIS
            $topsisResults = $this->topsisController->getAllTopsisResults();
            
            // Cek kelengkapan
            $fields = ['supervisor', 'teknis', 'keuangan'];
            foreach ($fields as $field) {
                if (empty($topsisResults[$field])) {
                    return [
                        'success' => false,
                        'message' => 'Hasil TOPSIS untuk bidang ' . strtoupper($field) . ' belum ada'
                    ];
                }
            }
            
            // Hitung BORDA
            $bordaResults = $this->performBordaCalculation($topsisResults);
            $bordaWeights = $this->getBordaWeights();
            
            return [
                'success' => true,
                'topsis_results' => $topsisResults,
                'borda_weights' => $bordaWeights,
                'borda_results' => $bordaResults,
                'total_weight' => array_sum($bordaWeights)
            ];
            
        } catch (Exception $e) {
            error_log("BORDA Details Error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Cek apakah sudah ada hasil BORDA
     */
    public function hasBordaResults() {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM borda_results");
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Ambil statistik BORDA
     */
    public function getBordaStats() {
        $results = $this->getAllBordaResults();
        
        if (empty($results)) {
            return [
                'total' => 0,
                'avg_score' => 0,
                'max_score' => 0,
                'min_score' => 0
            ];
        }
        
        $scores = array_column($results, 'borda_score');
        
        return [
            'total' => count($results),
            'avg_score' => round(array_sum($scores) / count($scores), 2),
            'max_score' => max($scores),
            'min_score' => min($scores)
        ];
    }
    
    /**
     * Ambil proyek terbaik (rank 1)
     */
    public function getBestProject() {
        $stmt = $this->pdo->query(
            "SELECT br.*, p.project_code, p.project_name, p.description 
             FROM borda_results br
             JOIN projects p ON br.project_id = p.id
             WHERE br.final_rank = 1
             LIMIT 1"
        );
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Ambil hasil BORDA berdasarkan project
     */
    public function getBordaResultsByProject($projectId) {
        $stmt = $this->pdo->prepare(
            "SELECT br.*, p.project_code, p.project_name, p.location, p.description
             FROM borda_results br
             JOIN projects p ON br.project_id = p.id
             WHERE br.project_id = ?"
        );
        $stmt->execute([$projectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Analisis konsensus
     */
    public function getConsensusAnalysis() {
        try {
            $bordaResults = $this->getAllBordaResults();
            if (empty($bordaResults)) {
                return ['success' => false, 'message' => 'Belum ada hasil konsensus.'];
            }
            
            $topsisResults = $this->topsisController->getAllTopsisResults();
            
            $analysis = [];
            foreach ($bordaResults as $result) {
                $projectId = $result['project_id'];
                $analysis[$projectId] = [
                    'project_id' => $projectId,
                    'project_name' => $result['project_name'],
                    'project_code' => $result['project_code'],
                    'final_rank' => $result['final_rank'],
                    'borda_score' => $result['borda_score'],
                    'field_rankings' => [],
                    'rank_differences' => []
                ];
                
                foreach ($topsisResults as $field => $fieldResults) {
                    foreach ($fieldResults as $fieldResult) {
                        if ($fieldResult['project_id'] == $projectId) {
                            $fieldRank = $fieldResult['rank'];
                            $analysis[$projectId]['field_rankings'][$field] = $fieldRank;
                            $analysis[$projectId]['rank_differences'][$field] = 
                                abs($fieldRank - $result['final_rank']);
                            break;
                        }
                    }
                }
            }
            
            return [
                'success' => true,
                'analysis' => array_values($analysis),
                'borda_weights' => $this->getBordaWeights()
            ];
        } catch (Exception $e) {
            error_log("Consensus Analysis Error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
?>
