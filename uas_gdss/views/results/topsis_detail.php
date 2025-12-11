<?php
/**
 * TOPSIS Detail View
 * Menampilkan detail perhitungan TOPSIS per proyek
 * Termasuk: Jarak D+, D-, Nilai Preferensi Ci
 * 
 * @package GDSS TOPSIS + BORDA
 */

require_once __DIR__ . '/../../includes/layout.php';
require_once __DIR__ . '/../../controllers/topsis_controller.php';

// Check login
requireLogin();

$userRole = $_SESSION['role'];
$pageTitle = 'Detail TOPSIS';

// Get parameters
$field = isset($_GET['field']) ? $_GET['field'] : '';
$projectId = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;

// Validate field access
$allowedFields = [];
switch ($userRole) {
    case 'admin':
        $allowedFields = ['supervisor', 'teknis', 'keuangan'];
        break;
    case 'supervisor':
        $allowedFields = ['supervisor'];
        break;
    case 'teknis':
        $allowedFields = ['teknis'];
        break;
    case 'keuangan':
        $allowedFields = ['keuangan'];
        break;
}

if (!in_array($field, $allowedFields) || $projectId <= 0) {
    header('Location: ../../topsis_results.php');
    exit;
}

$topsisController = new TopsisController();

// Get all TOPSIS results for this field
$allResults = $topsisController->getTopsisResults($field);
$details = $topsisController->getTopsisCalculationDetails($field);

// Find the specific project result
$projectResult = null;
foreach ($allResults as $result) {
    if ($result['project_id'] == $projectId) {
        $projectResult = $result;
        break;
    }
}

if (!$projectResult) {
    header('Location: ../../topsis_results.php?field=' . $field);
    exit;
}

$criteria = $details['criteria'] ?? [];
$decisionMatrix = $details['decision_matrix'] ?? [];
$normalizedMatrix = $details['normalized_matrix'] ?? [];
$weightedMatrix = $details['weighted_matrix'] ?? [];
$idealPositive = $details['ideal_positive'] ?? [];
$idealNegative = $details['ideal_negative'] ?? [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - GDSS TOPSIS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @media print {
            .no-print { display: none; }
            .page-break { page-break-before: always; }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-6">
        <!-- Header -->
        <div class="mb-6 flex items-center justify-between no-print">
            <div>
                <a href="../../topsis_results.php?field=<?php echo $field; ?>" class="text-blue-600 hover:text-blue-800 mb-2 inline-block">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali ke Hasil TOPSIS
                </a>
                <h1 class="text-3xl font-bold text-gray-900">
                    <i class="fas fa-search-plus mr-2 text-indigo-600"></i>
                    Detail Perhitungan TOPSIS
                </h1>
            </div>
            <button onclick="window.print()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                <i class="fas fa-print mr-2"></i>Cetak
            </button>
        </div>

        <!-- Project Info Card -->
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-lg shadow-lg p-6 mb-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($projectResult['project_name']); ?></h2>
                    <p class="text-indigo-100">Bidang: <?php echo ucfirst($field); ?></p>
                </div>
                <div class="text-right">
                    <div class="text-5xl font-bold mb-1">
                        <?php if ($projectResult['rank'] == 1): ?>
                        <i class="fas fa-crown text-yellow-400"></i>
                        <?php endif; ?>
                        #<?php echo $projectResult['rank']; ?>
                    </div>
                    <div class="text-indigo-200">Ranking</div>
                </div>
            </div>
        </div>

        <!-- Main Metrics -->
        <div class="grid md:grid-cols-3 gap-6 mb-6">
            <!-- D+ -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-900">Jarak ke Ideal Positif</h3>
                    <span class="text-red-500"><i class="fas fa-arrow-up fa-2x"></i></span>
                </div>
                <div class="text-3xl font-bold text-red-600 font-mono">
                    <?php echo number_format($projectResult['d_positive'], 6); ?>
                </div>
                <p class="text-sm text-gray-500 mt-2">D⁺ = √Σ(vij - A⁺j)²</p>
                <p class="text-xs text-gray-400 mt-1">Semakin kecil semakin baik</p>
            </div>

            <!-- D- -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-900">Jarak ke Ideal Negatif</h3>
                    <span class="text-green-500"><i class="fas fa-arrow-down fa-2x"></i></span>
                </div>
                <div class="text-3xl font-bold text-green-600 font-mono">
                    <?php echo number_format($projectResult['d_negative'], 6); ?>
                </div>
                <p class="text-sm text-gray-500 mt-2">D⁻ = √Σ(vij - A⁻j)²</p>
                <p class="text-xs text-gray-400 mt-1">Semakin besar semakin baik</p>
            </div>

            <!-- Ci -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-900">Nilai Preferensi</h3>
                    <span class="text-blue-500"><i class="fas fa-star fa-2x"></i></span>
                </div>
                <div class="text-3xl font-bold text-blue-600 font-mono">
                    <?php echo number_format($projectResult['topsis_score'], 6); ?>
                </div>
                <p class="text-sm text-gray-500 mt-2">Ci = D⁻ / (D⁺ + D⁻)</p>
                <p class="text-xs text-gray-400 mt-1">Rentang: 0 - 1 (semakin tinggi semakin baik)</p>
            </div>
        </div>

        <!-- Calculation Breakdown -->
        <div class="bg-white rounded-lg shadow mb-6 overflow-hidden">
            <div class="bg-gray-800 text-white px-6 py-4">
                <h3 class="font-bold text-lg">
                    <i class="fas fa-calculator mr-2"></i>
                    Breakdown Perhitungan untuk Proyek Ini
                </h3>
            </div>
            <div class="p-6">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="px-4 py-2 text-left">Kriteria</th>
                            <th class="px-4 py-2 text-center">Tipe</th>
                            <th class="px-4 py-2 text-center">Bobot (w)</th>
                            <th class="px-4 py-2 text-center">Nilai (x)</th>
                            <th class="px-4 py-2 text-center">Normalisasi (r)</th>
                            <th class="px-4 py-2 text-center">Terbobot (v)</th>
                            <th class="px-4 py-2 text-center">A⁺</th>
                            <th class="px-4 py-2 text-center">A⁻</th>
                            <th class="px-4 py-2 text-center">(v - A⁺)²</th>
                            <th class="px-4 py-2 text-center">(v - A⁻)²</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $sumDiffPositive = 0;
                        $sumDiffNegative = 0;
                        foreach ($criteria as $c): 
                            $x = $decisionMatrix[$projectId][$c['id']] ?? 0;
                            $r = $normalizedMatrix[$projectId][$c['id']] ?? 0;
                            $v = $weightedMatrix[$projectId][$c['id']] ?? 0;
                            $aPlus = $idealPositive[$c['id']] ?? 0;
                            $aMinus = $idealNegative[$c['id']] ?? 0;
                            $diffPlus = pow($v - $aPlus, 2);
                            $diffMinus = pow($v - $aMinus, 2);
                            $sumDiffPositive += $diffPlus;
                            $sumDiffNegative += $diffMinus;
                        ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-2 font-medium"><?php echo htmlspecialchars($c['name']); ?></td>
                            <td class="px-4 py-2 text-center">
                                <span class="px-2 py-1 rounded text-xs <?php echo $c['type'] === 'benefit' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                                    <?php echo ucfirst($c['type']); ?>
                                </span>
                            </td>
                            <td class="px-4 py-2 text-center font-mono"><?php echo number_format($c['weight'], 3); ?></td>
                            <td class="px-4 py-2 text-center font-mono"><?php echo number_format($x, 2); ?></td>
                            <td class="px-4 py-2 text-center font-mono text-green-700"><?php echo number_format($r, 4); ?></td>
                            <td class="px-4 py-2 text-center font-mono text-purple-700"><?php echo number_format($v, 4); ?></td>
                            <td class="px-4 py-2 text-center font-mono text-green-600"><?php echo number_format($aPlus, 4); ?></td>
                            <td class="px-4 py-2 text-center font-mono text-red-600"><?php echo number_format($aMinus, 4); ?></td>
                            <td class="px-4 py-2 text-center font-mono text-red-500"><?php echo number_format($diffPlus, 6); ?></td>
                            <td class="px-4 py-2 text-center font-mono text-green-500"><?php echo number_format($diffMinus, 6); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="bg-gray-100 font-bold">
                            <td colspan="8" class="px-4 py-2 text-right">Σ (Jumlah):</td>
                            <td class="px-4 py-2 text-center font-mono text-red-600"><?php echo number_format($sumDiffPositive, 6); ?></td>
                            <td class="px-4 py-2 text-center font-mono text-green-600"><?php echo number_format($sumDiffNegative, 6); ?></td>
                        </tr>
                        <tr class="bg-gray-200 font-bold">
                            <td colspan="8" class="px-4 py-2 text-right">√Σ (Akar):</td>
                            <td class="px-4 py-2 text-center font-mono text-red-700"><?php echo number_format(sqrt($sumDiffPositive), 6); ?></td>
                            <td class="px-4 py-2 text-center font-mono text-green-700"><?php echo number_format(sqrt($sumDiffNegative), 6); ?></td>
                        </tr>
                    </tbody>
                </table>

                <!-- Final Calculation -->
                <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                    <h4 class="font-bold text-blue-800 mb-3">Perhitungan Nilai Preferensi (Ci)</h4>
                    <div class="text-center">
                        <div class="text-lg font-mono">
                            Ci = D⁻ / (D⁺ + D⁻)
                        </div>
                        <div class="text-lg font-mono mt-2">
                            Ci = <?php echo number_format($projectResult['d_negative'], 4); ?> / 
                            (<?php echo number_format($projectResult['d_positive'], 4); ?> + <?php echo number_format($projectResult['d_negative'], 4); ?>)
                        </div>
                        <div class="text-lg font-mono mt-2">
                            Ci = <?php echo number_format($projectResult['d_negative'], 4); ?> / 
                            <?php echo number_format($projectResult['d_positive'] + $projectResult['d_negative'], 4); ?>
                        </div>
                        <div class="text-2xl font-bold text-blue-700 mt-2">
                            Ci = <?php echo number_format($projectResult['topsis_score'], 6); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Comparison with Other Projects -->
        <div class="bg-white rounded-lg shadow mb-6 overflow-hidden">
            <div class="bg-gray-800 text-white px-6 py-4">
                <h3 class="font-bold text-lg">
                    <i class="fas fa-chart-bar mr-2"></i>
                    Perbandingan dengan Proyek Lain
                </h3>
            </div>
            <div class="p-6">
                <canvas id="comparisonChart" height="80"></canvas>
            </div>
        </div>

        <!-- All Rankings Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="bg-gray-800 text-white px-6 py-4">
                <h3 class="font-bold text-lg">
                    <i class="fas fa-list-ol mr-2"></i>
                    Ranking Lengkap - Bidang <?php echo ucfirst($field); ?>
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-left">Rank</th>
                            <th class="px-4 py-3 text-left">Proyek</th>
                            <th class="px-4 py-3 text-center">D⁺</th>
                            <th class="px-4 py-3 text-center">D⁻</th>
                            <th class="px-4 py-3 text-center">Ci</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allResults as $result): ?>
                        <tr class="border-b <?php echo $result['project_id'] == $projectId ? 'bg-yellow-100 font-bold' : 'hover:bg-gray-50'; ?>">
                            <td class="px-4 py-3">
                                <?php if ($result['rank'] == 1): ?>
                                <span class="inline-flex items-center justify-center w-6 h-6 bg-yellow-400 text-white rounded-full text-xs">
                                    <i class="fas fa-crown"></i>
                                </span>
                                <?php else: ?>
                                <span class="inline-flex items-center justify-center w-6 h-6 bg-gray-300 text-gray-900 rounded-full text-xs font-bold">
                                    <?php echo $result['rank']; ?>
                                </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <?php echo htmlspecialchars($result['project_name']); ?>
                                <?php if ($result['project_id'] == $projectId): ?>
                                <span class="ml-2 text-xs text-blue-600">(Proyek ini)</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-center font-mono text-red-600"><?php echo number_format($result['d_positive'], 4); ?></td>
                            <td class="px-4 py-3 text-center font-mono text-green-600"><?php echo number_format($result['d_negative'], 4); ?></td>
                            <td class="px-4 py-3 text-center font-mono text-blue-700 font-bold"><?php echo number_format($result['topsis_score'], 4); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    // Comparison Chart
    const ctx = document.getElementById('comparisonChart').getContext('2d');
    const projectNames = <?php echo json_encode(array_column($allResults, 'project_name')); ?>;
    const preferenceValues = <?php echo json_encode(array_map(function($r) { return floatval($r['topsis_score']); }, $allResults)); ?>;
    const currentProjectIndex = <?php echo array_search($projectId, array_column($allResults, 'project_id')); ?>;
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: projectNames,
            datasets: [{
                label: 'Nilai Preferensi (Ci)',
                data: preferenceValues,
                backgroundColor: preferenceValues.map((v, i) => 
                    i === currentProjectIndex ? 'rgba(99, 102, 241, 0.9)' : 'rgba(156, 163, 175, 0.6)'
                ),
                borderColor: preferenceValues.map((v, i) => 
                    i === currentProjectIndex ? 'rgb(99, 102, 241)' : 'rgb(156, 163, 175)'
                ),
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Ci: ' + context.raw.toFixed(4);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 1,
                    title: {
                        display: true,
                        text: 'Nilai Preferensi (Ci)'
                    }
                }
            }
        }
    });
    </script>
</body>
</html>

