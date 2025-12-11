<?php
/**
 * API Handler untuk TOPSIS dan BORDA Operations
 * Updated untuk TOPSIS + BORDA system
 * 
 * @package GDSS TOPSIS + BORDA
 */

// Define base path relative to api folder
$basePath = dirname(__DIR__);

require_once $basePath . '/config/config.php';
require_once $basePath . '/controllers/topsis_controller.php';
require_once $basePath . '/controllers/borda_controller.php';
require_once $basePath . '/controllers/score_controller.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        // TOPSIS Operations
        case 'calculate_topsis':
            echo json_encode(handleCalculateTopsis());
            break;
            
        case 'preview_topsis':
            echo json_encode(handlePreviewTopsis());
            break;
            
        case 'get_topsis_results':
            echo json_encode(handleGetTopsisResults());
            break;
            
        case 'get_topsis_details':
            echo json_encode(handleGetTopsisDetails());
            break;
            
        // BORDA Operations
        case 'calculate_borda':
            echo json_encode(handleCalculateBorda());
            break;
            
        case 'get_borda_results':
            echo json_encode(handleGetBordaResults());
            break;
        
        case 'get_borda_details':
            echo json_encode(handleGetBordaDetails());
            break;
        
        // Score Operations
        case 'save_scores':
            echo json_encode(handleSaveScores());
            break;
        
        case 'get_scores':
            echo json_encode(handleGetScores());
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action: ' . $action]);
    }
} catch (Exception $e) {
    error_log('API Handler error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'System error occurred: ' . $e->getMessage()]);
}

/**
 * Calculate TOPSIS for user's field
 */
function handleCalculateTopsis() {
    if (!isLoggedIn()) {
        return ['success' => false, 'message' => 'Please login first'];
    }
    
    $userRole = $_SESSION['role'];
    $field = $_POST['field'] ?? $userRole;
    
    // Allowed decision-maker roles for TOPSIS calculation
    $allowedRoles = ['teknis', 'supervisor', 'keuangan', 'admin'];
    
    if (!in_array($userRole, $allowedRoles)) {
        return ['success' => false, 'message' => 'Access denied'];
    }
    
    // Admin can calculate for any field
    if ($userRole === 'admin') {
        $validFields = ['supervisor', 'teknis', 'keuangan'];
        if (!in_array($field, $validFields)) {
            return ['success' => false, 'message' => 'Invalid field specified'];
        }
    } else {
        // Non-admin can only calculate their own field
        $field = $userRole;
    }
    
    $topsisController = new TopsisController();
    $result = $topsisController->calculateTopsis($field);
    
    if ($result['success']) {
        $result['message'] = 'TOPSIS calculation completed for ' . ucfirst($field) . ' field';
        $result['redirect'] = 'topsis_results.php?field=' . $field;
    }
    
    return $result;
}

/**
 * Preview TOPSIS calculation for a project
 */
function handlePreviewTopsis() {
    if (!isLoggedIn()) {
        return ['success' => false, 'message' => 'Please login first'];
    }
    
    $userRole = $_SESSION['role'];
    $projectId = $_POST['project_id'] ?? null;
    
    if (!$projectId) {
        return ['success' => false, 'message' => 'Project ID required'];
    }
    
    $scoreController = new ScoreController();
    $scores = $scoreController->getUserScores($projectId, $_SESSION['user_id']);
    
    if (empty($scores)) {
        return ['success' => false, 'message' => 'No scores found for preview'];
    }
    
    // Build preview HTML
    $html = '<div class="alert alert-info mb-3">';
    $html .= '<strong>Preview TOPSIS untuk Project ID: ' . intval($projectId) . '</strong>';
    $html .= '</div>';
    $html .= '<table class="table table-sm table-bordered">';
    $html .= '<thead><tr><th>Kriteria</th><th>Skor</th></tr></thead><tbody>';
    
    foreach ($scores as $score) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($score['criteria_name'] ?? 'Kriteria ' . $score['criteria_id']) . '</td>';
        $html .= '<td class="text-center">' . number_format($score['score'], 2) . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table>';
    $html .= '<p class="text-muted small">Total kriteria: ' . count($scores) . '</p>';
    
    return ['success' => true, 'html' => $html, 'score_count' => count($scores)];
}

/**
 * Get TOPSIS results for a field
 */
function handleGetTopsisResults() {
    if (!isLoggedIn()) {
        return ['success' => false, 'message' => 'Please login first'];
    }
    
    $userRole = $_SESSION['role'];
    $field = $_POST['field'] ?? $userRole;
    
    // Validate field access
    if ($userRole !== 'admin' && $userRole !== $field) {
        return ['success' => false, 'message' => 'Access denied to this field'];
    }
    
    $topsisController = new TopsisController();
    $results = $topsisController->getTopsisResults($field);
    
    return ['success' => true, 'results' => $results, 'field' => $field];
}

/**
 * Get detailed TOPSIS calculation data
 */
function handleGetTopsisDetails() {
    if (!isLoggedIn()) {
        return ['success' => false, 'message' => 'Please login first'];
    }
    
    $userRole = $_SESSION['role'];
    $field = $_POST['field'] ?? $userRole;
    
    // Validate field access
    if ($userRole !== 'admin' && $userRole !== $field) {
        return ['success' => false, 'message' => 'Access denied to this field'];
    }
    
    $topsisController = new TopsisController();
    $details = $topsisController->getTopsisCalculationDetails($field);
    
    return ['success' => true, 'details' => $details, 'field' => $field];
}

/**
 * Calculate BORDA consensus (Admin only)
 */
function handleCalculateBorda() {
    if (!isLoggedIn()) {
        return ['success' => false, 'message' => 'Please login first'];
    }
    
    // Only admin can calculate BORDA
    if (!hasRole('admin')) {
        return ['success' => false, 'message' => 'Admin access required'];
    }
    
    $bordaController = new BordaController();
    $result = $bordaController->calculateBordaConsensus();
    
    if ($result['success']) {
        $result['redirect'] = 'borda_result.php';
    }
    
    return $result;
}

/**
 * Get BORDA results (Admin only)
 */
function handleGetBordaResults() {
    if (!isLoggedIn()) {
        return ['success' => false, 'message' => 'Please login first'];
    }
    
    // Only admin can view BORDA results
    if (!hasRole('admin')) {
        return ['success' => false, 'message' => 'Admin access required'];
    }
    
    $bordaController = new BordaController();
    $results = $bordaController->getAllBordaResults();
    
    return ['success' => true, 'results' => $results];
}

/**
 * Get detailed BORDA calculation data
 */
function handleGetBordaDetails() {
    if (!isLoggedIn()) {
        return ['success' => false, 'message' => 'Please login first'];
    }
    
    if (!hasRole('admin')) {
        return ['success' => false, 'message' => 'Admin access required'];
    }
    
    $bordaController = new BordaController();
    $details = $bordaController->getBordaCalculationDetails();
    
    return ['success' => true, 'details' => $details];
}

/**
 * Save scores for a project
 */
function handleSaveScores() {
    if (!isLoggedIn()) {
        return ['success' => false, 'message' => 'Please login first'];
    }
    
    $projectId = $_POST['project_id'] ?? null;
    $scoresRaw = $_POST['scores'] ?? null;
    
    if (!$projectId) {
        return ['success' => false, 'message' => 'Project ID required'];
    }
    
    if (!$scoresRaw) {
        return ['success' => false, 'message' => 'Scores data required'];
    }
    
    // Decode JSON if needed
    $scores = is_string($scoresRaw) ? json_decode($scoresRaw, true) : $scoresRaw;
    
    if (!is_array($scores) || empty($scores)) {
        return ['success' => false, 'message' => 'Invalid scores format'];
    }
    
    $scoreController = new ScoreController();
    
    // Use the saveScores method which will also calculate TOPSIS
    $result = $scoreController->saveScores([
        'project_id' => $projectId,
        'scores' => $scores
    ]);
    
    return $result;
}

/**
 * Get scores for a project
 */
function handleGetScores() {
    if (!isLoggedIn()) {
        return ['success' => false, 'message' => 'Please login first'];
    }
    
    $projectId = $_POST['project_id'] ?? null;
    
    if (!$projectId) {
        return ['success' => false, 'message' => 'Project ID required'];
    }
    
    $scoreController = new ScoreController();
    $userId = $_SESSION['user_id'];
    
    $scores = $scoreController->getUserScores($projectId, $userId);
    
    return ['success' => true, 'scores' => $scores];
}
?>