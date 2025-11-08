<?php
/**
 * Test AHP Functions
 * File test untuk memastikan semua fungsi AHP bekerja dengan baik
 */

require_once '../config.php';
require_once '../functions.php';

echo "<!DOCTYPE html><html><head><title>AHP Test</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "</head><body class='container mt-4'>";

echo "<h1>🧪 Test Fungsi AHP</h1>";
echo "<div class='row'>";

// Test 1: Database Connection
echo "<div class='col-md-6'><div class='card mb-3'>";
echo "<div class='card-header'><h5>✅ Test 1: Database Connection</h5></div>";
echo "<div class='card-body'>";
try {
    $pdo = getConnection();
    echo "✅ Database connected successfully<br>";
    
    // Check AHP tables
    $tables = ['ahp_pairwise_comparisons', 'ahp_results', 'ahp_random_index'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "✅ Table $table: $count records<br>";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage();
}
echo "</div></div></div>";

// Test 2: Random Index Data
echo "<div class='col-md-6'><div class='card mb-3'>";
echo "<div class='card-header'><h5>✅ Test 2: Random Index Data</h5></div>";
echo "<div class='card-body'>";
try {
    $pdo = getConnection();
    $stmt = $pdo->query("SELECT * FROM ahp_random_index ORDER BY n");
    $riData = $stmt->fetchAll();
    
    echo "<small>";
    foreach ($riData as $ri) {
        echo "n={$ri['n']}: RI={$ri['ri_value']}<br>";
    }
    echo "</small>";
    echo "✅ Random Index data loaded";
} catch (Exception $e) {
    echo "❌ Random Index error: " . $e->getMessage();
}
echo "</div></div></div>";

// Test 3: AHP Mathematical Functions  
echo "<div class='col-md-6'><div class='card mb-3'>";
echo "<div class='card-header'><h5>🔢 Test 3: AHP Math Functions</h5></div>";
echo "<div class='card-body'>";

// Test buildPairwiseMatrix
echo "<h6>buildPairwiseMatrix Test:</h6>";
$testComparisons = [
    ['element_i' => 1, 'element_j' => 2, 'comparison_value' => 3],
    ['element_i' => 1, 'element_j' => 3, 'comparison_value' => 5],
    ['element_i' => 2, 'element_j' => 3, 'comparison_value' => 2]
];
$elementIds = [1, 2, 3];

try {
    $matrix = buildPairwiseMatrix($testComparisons, $elementIds);
    echo "✅ Matrix built: " . count($matrix) . "x" . count($matrix[0]) . "<br>";
    
    // Test calculatePriorityVector
    $priorities = calculatePriorityVector($matrix);
    echo "✅ Priority vector: [" . implode(', ', array_map(function($p) { return number_format($p, 3); }, $priorities)) . "]<br>";
    
    // Test calculateConsistency  
    $consistency = calculateConsistency($matrix, $priorities);
    echo "✅ Consistency: CI=" . number_format($consistency['CI'], 4) . ", CR=" . number_format($consistency['CR'], 4);
    echo $consistency['is_consistent'] ? " (Consistent)" : " (Inconsistent)";
    
} catch (Exception $e) {
    echo "❌ AHP Math error: " . $e->getMessage();
}
echo "</div></div></div>";

// Test 4: User and Criteria Functions
echo "<div class='col-md-6'><div class='card mb-3'>";
echo "<div class='card-header'><h5>👤 Test 4: User & Criteria Functions</h5></div>";
echo "<div class='card-body'>";

try {
    // Test user functions  
    $pdo = getConnection();
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $userCount = $stmt->fetchColumn();
    echo "✅ Users in database: " . $userCount . " users<br>";
    
    // Test criteria functions
    $criteria = getCriteriaByPart('teknis');
    echo "✅ Teknis criteria: " . count($criteria) . " items<br>";
    
    $criteria = getCriteriaByPart('administrasi');
    echo "✅ Administrasi criteria: " . count($criteria) . " items<br>";
    
    $criteria = getCriteriaByPart('keuangan');
    echo "✅ Keuangan criteria: " . count($criteria) . " items<br>";
    
    // Test projects
    $projects = getAllProjects();
    echo "✅ Projects loaded: " . count($projects) . " projects<br>";
    
} catch (Exception $e) {
    echo "❌ Data function error: " . $e->getMessage();
}
echo "</div></div></div>";

// Test 5: AHP Pairwise Functions
echo "<div class='col-md-12'><div class='card mb-3'>";
echo "<div class='card-header'><h5>⚙️ Test 5: AHP Pairwise Functions</h5></div>";
echo "<div class='card-body'>";

try {
    // Test CSRF token
    $token = generateCSRFToken();
    echo "✅ CSRF Token generated: " . substr($token, 0, 20) . "...<br>";
    
    // Test savePairwiseComparison (if user is logged in)
    if (isLoggedIn()) {
        $user = getCurrentUser();
        echo "✅ Current user: " . $user['fullname'] . " (" . $user['role'] . ")<br>";
        
        // Test getting comparisons (should be empty initially)
        $comparisons = getPairwiseComparisons($user['id'], 'criteria');
        echo "✅ Existing criteria comparisons: " . count($comparisons) . " records<br>";
    } else {
        echo "ℹ️ No user logged in - cannot test user-specific functions<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Pairwise function error: " . $e->getMessage();
}
echo "</div></div></div>";

echo "</div>";

// Navigation
echo "<div class='mt-4'>";
echo "<a href='../dashboard.php' class='btn btn-primary me-2'>Dashboard</a>";
echo "<a href='../index.php' class='btn btn-secondary me-2'>Login</a>";
if (isLoggedIn()) {
    echo "<a href='../ahp_comparison.php' class='btn btn-success me-2'>Start AHP</a>";
}
echo "<button onclick='location.reload()' class='btn btn-outline-info'>Refresh Test</button>";
echo "</div>";

echo "</body></html>";
?>