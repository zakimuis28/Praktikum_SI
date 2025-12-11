<?php
/**
 * BORDA Detail View
 * Menampilkan detail perhitungan BORDA Count Consensus
 * 
 * @package GDSS TOPSIS + BORDA
 */

require_once __DIR__ . '/../../includes/layout.php';
require_once __DIR__ . '/../../controllers/borda_controller.php';

// Check login
requireLogin();

$userRole = $_SESSION['role'];
$pageTitle = 'Detail Perhitungan BORDA';

// Only admin can access this page
if ($userRole !== 'admin') {
    header('Location: ../../dashboard.php');
    exit;
}

$bordaController = new BordaController();
$bordaResults = $bordaController->getAllBordaResults();
$calculationDetails = $bordaController->getBordaCalculationDetails();

// Field weights
$fieldWeights = [
    'supervisor' => 7,
    'teknis' => 4,
    'keuangan' => 2
];
$totalWeight = array_sum($fieldWeights);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - GDSS TOPSIS + BORDA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-6">
        <!-- Header -->
        <div class="mb-6 flex items-center justify-between no-print">
            <div>
                <a href="../../borda_result.php" class="text-blue-600 hover:text-blue-800 mb-2 inline-block">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali ke Hasil BORDA
                </a>
                <h1 class="text-3xl font-bold text-gray-900">
                    <i class="fas fa-vote-yea mr-2 text-green-600"></i>
                    Detail Perhitungan BORDA Count
                </h1>
                <p class="text-gray-900">Group Decision Support System - Konsensus Multi Decision Maker</p>
            </div>
            <button onclick="window.print()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                <i class="fas fa-print mr-2"></i>Cetak
            </button>
        </div>

        <!-- Method Explanation -->
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg shadow p-6 mb-6">
            <h3 class="font-bold text-lg text-green-800 mb-4">
                <i class="fas fa-info-circle mr-2"></i>
                Metode BORDA Count dengan Pembobotan
            </h3>
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <h4 class="font-semibold text-green-700 mb-2">Formula BORDA:</h4>
                    <div class="bg-white p-4 rounded-lg">
                        <p class="font-mono text-sm mb-2">Poin = N - Rank + 1</p>
                        <p class="text-sm text-gray-900">Dimana N = jumlah alternatif</p>
                        <p class="text-sm text-gray-900 mt-2">Contoh: Jika ada 5 proyek, Rank 1 = 5 poin, Rank 2 = 4 poin, dst.</p>
                    </div>
                </div>
                <div>
                    <h4 class="font-semibold text-green-700 mb-2">Bobot Decision Maker:</h4>
                    <div class="bg-white p-4 rounded-lg">
                        <div class="flex justify-between items-center mb-2">
                            <span class="flex items-center"><i class="fas fa-user-tie text-blue-600 mr-2"></i>Supervisor</span>
                            <span class="font-bold text-blue-600"><?php echo $fieldWeights['supervisor']; ?> (<?php echo round($fieldWeights['supervisor']/$totalWeight*100, 1); ?>%)</span>
                        </div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="flex items-center"><i class="fas fa-cogs text-purple-600 mr-2"></i>Teknis</span>
                            <span class="font-bold text-purple-600"><?php echo $fieldWeights['teknis']; ?> (<?php echo round($fieldWeights['teknis']/$totalWeight*100, 1); ?>%)</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="flex items-center"><i class="fas fa-coins text-yellow-600 mr-2"></i>Keuangan</span>
                            <span class="font-bold text-yellow-600"><?php echo $fieldWeights['keuangan']; ?> (<?php echo round($fieldWeights['keuangan']/$totalWeight*100, 1); ?>%)</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-4 p-4 bg-white rounded-lg">
                <h4 class="font-semibold text-green-700 mb-2">Formula Skor Total:</h4>
                <p class="font-mono text-sm">
                    Skor Total = (Poin_Supervisor × 7) + (Poin_Teknis × 4) + (Poin_Keuangan × 2)
                </p>
            </div>
        </div>

        <?php if (empty($bordaResults)): ?>
        <div class="bg-white rounded-lg shadow p-8 text-center">
            <div class="text-gray-400 mb-4">
                <i class="fas fa-vote-yea fa-4x"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Belum Ada Hasil BORDA</h3>
            <p class="text-gray-500">Pastikan semua bidang telah menyelesaikan perhitungan TOPSIS.</p>
        </div>
        <?php else: ?>

        <!-- Step 1: Rankings from TOPSIS -->
        <div class="bg-white rounded-lg shadow mb-6 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-6 py-4">
                <h3 class="font-bold text-lg">
                    <i class="fas fa-medal mr-2"></i>
                    Langkah 1: Ranking dari Setiap Bidang (Hasil TOPSIS)
                </h3>
            </div>
            <div class="p-6 overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="px-4 py-3 text-left">Proyek</th>
                            <th class="px-4 py-3 text-center" colspan="2">
                                <i class="fas fa-user-tie text-blue-600 mr-1"></i>Supervisor
                            </th>
                            <th class="px-4 py-3 text-center" colspan="2">
                                <i class="fas fa-cogs text-purple-600 mr-1"></i>Teknis
                            </th>
                            <th class="px-4 py-3 text-center" colspan="2">
                                <i class="fas fa-coins text-yellow-600 mr-1"></i>Keuangan
                            </th>
                        </tr>
                        <tr class="bg-gray-50 text-sm">
                            <th class="px-4 py-2"></th>
                            <th class="px-4 py-2 text-center">Rank</th>
                            <th class="px-4 py-2 text-center">Ci</th>
                            <th class="px-4 py-2 text-center">Rank</th>
                            <th class="px-4 py-2 text-center">Ci</th>
                            <th class="px-4 py-2 text-center">Rank</th>
                            <th class="px-4 py-2 text-center">Ci</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bordaResults as $result): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium"><?php echo htmlspecialchars($result['project_name']); ?></td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center justify-center w-8 h-8 bg-blue-100 text-blue-700 rounded-full font-bold">
                                    <?php echo $result['rank_supervisor'] ?? '-'; ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center font-mono text-blue-600">
                                <?php echo isset($result['topsis_supervisor']) ? number_format($result['topsis_supervisor'], 4) : '-'; ?>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center justify-center w-8 h-8 bg-purple-100 text-purple-700 rounded-full font-bold">
                                    <?php echo $result['rank_teknis'] ?? '-'; ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center font-mono text-purple-600">
                                <?php echo isset($result['topsis_teknis']) ? number_format($result['topsis_teknis'], 4) : '-'; ?>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center justify-center w-8 h-8 bg-yellow-100 text-yellow-700 rounded-full font-bold">
                                    <?php echo $result['rank_keuangan'] ?? '-'; ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center font-mono text-yellow-600">
                                <?php echo isset($result['topsis_keuangan']) ? number_format($result['topsis_keuangan'], 4) : '-'; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Step 2: Convert to BORDA Points -->
        <div class="bg-white rounded-lg shadow mb-6 overflow-hidden">
            <div class="bg-gradient-to-r from-green-600 to-emerald-600 text-white px-6 py-4">
                <h3 class="font-bold text-lg">
                    <i class="fas fa-exchange-alt mr-2"></i>
                    Langkah 2: Konversi Ranking ke Poin BORDA
                </h3>
                <p class="text-green-100 text-sm">Poin = N - Rank + 1 (N = <?php echo count($bordaResults); ?>)</p>
            </div>
            <div class="p-6 overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="px-4 py-3 text-left">Proyek</th>
                            <th class="px-4 py-3 text-center">
                                Poin Supervisor<br>
                                <span class="text-xs text-gray-500">(×<?php echo $fieldWeights['supervisor']; ?>)</span>
                            </th>
                            <th class="px-4 py-3 text-center">
                                Poin Teknis<br>
                                <span class="text-xs text-gray-500">(×<?php echo $fieldWeights['teknis']; ?>)</span>
                            </th>
                            <th class="px-4 py-3 text-center">
                                Poin Keuangan<br>
                                <span class="text-xs text-gray-500">(×<?php echo $fieldWeights['keuangan']; ?>)</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $n = count($bordaResults);
                        foreach ($bordaResults as $result): 
                            $pointSupervisor = isset($result['rank_supervisor']) ? $n - $result['rank_supervisor'] + 1 : 0;
                            $pointTeknis = isset($result['rank_teknis']) ? $n - $result['rank_teknis'] + 1 : 0;
                            $pointKeuangan = isset($result['rank_keuangan']) ? $n - $result['rank_keuangan'] + 1 : 0;
                        ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium"><?php echo htmlspecialchars($result['project_name']); ?></td>
                            <td class="px-4 py-3 text-center">
                                <span class="font-bold text-blue-600"><?php echo $pointSupervisor; ?></span>
                                <span class="text-gray-400 mx-1">→</span>
                                <span class="font-bold text-blue-800"><?php echo $pointSupervisor * $fieldWeights['supervisor']; ?></span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="font-bold text-purple-600"><?php echo $pointTeknis; ?></span>
                                <span class="text-gray-400 mx-1">→</span>
                                <span class="font-bold text-purple-800"><?php echo $pointTeknis * $fieldWeights['teknis']; ?></span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="font-bold text-yellow-600"><?php echo $pointKeuangan; ?></span>
                                <span class="text-gray-400 mx-1">→</span>
                                <span class="font-bold text-yellow-800"><?php echo $pointKeuangan * $fieldWeights['keuangan']; ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Step 3: Final Calculation -->
        <div class="bg-white rounded-lg shadow mb-6 overflow-hidden">
            <div class="bg-gradient-to-r from-yellow-500 to-orange-500 text-white px-6 py-4">
                <h3 class="font-bold text-lg">
                    <i class="fas fa-trophy mr-2"></i>
                    Langkah 3: Perhitungan Skor Total dan Ranking Final
                </h3>
            </div>
            <div class="p-6 overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="px-4 py-3 text-left">Proyek</th>
                            <th class="px-4 py-3 text-center">Perhitungan</th>
                            <th class="px-4 py-3 text-center">Skor Total</th>
                            <th class="px-4 py-3 text-center">Ranking Final</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bordaResults as $result): 
                            $pointSupervisor = isset($result['rank_supervisor']) ? $n - $result['rank_supervisor'] + 1 : 0;
                            $pointTeknis = isset($result['rank_teknis']) ? $n - $result['rank_teknis'] + 1 : 0;
                            $pointKeuangan = isset($result['rank_keuangan']) ? $n - $result['rank_keuangan'] + 1 : 0;
                            
                            $weightedSupervisor = $pointSupervisor * $fieldWeights['supervisor'];
                            $weightedTeknis = $pointTeknis * $fieldWeights['teknis'];
                            $weightedKeuangan = $pointKeuangan * $fieldWeights['keuangan'];
                        ?>
                        <tr class="border-b hover:bg-gray-50 <?php echo $result['final_rank'] <= 3 ? 'bg-yellow-50' : ''; ?>">
                            <td class="px-4 py-3 font-medium"><?php echo htmlspecialchars($result['project_name']); ?></td>
                            <td class="px-4 py-3 text-center font-mono text-sm">
                                (<span class="text-blue-600"><?php echo $weightedSupervisor; ?></span>) + 
                                (<span class="text-purple-600"><?php echo $weightedTeknis; ?></span>) + 
                                (<span class="text-yellow-600"><?php echo $weightedKeuangan; ?></span>)
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="text-2xl font-bold text-green-600"><?php echo number_format($result['total_score'], 2); ?></span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <?php if ($result['final_rank'] == 1): ?>
                                <span class="inline-flex items-center justify-center w-10 h-10 bg-yellow-400 text-white rounded-full text-xl">
                                    <i class="fas fa-crown"></i>
                                </span>
                                <?php elseif ($result['final_rank'] == 2): ?>
                                <span class="inline-flex items-center justify-center w-10 h-10 bg-gray-400 text-white rounded-full font-bold text-xl">2</span>
                                <?php elseif ($result['final_rank'] == 3): ?>
                                <span class="inline-flex items-center justify-center w-10 h-10 bg-orange-400 text-white rounded-full font-bold text-xl">3</span>
                                <?php else: ?>
                                <span class="inline-flex items-center justify-center w-10 h-10 bg-gray-200 text-gray-900 rounded-full font-bold text-xl"><?php echo $result['final_rank']; ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Visualization -->
        <div class="grid md:grid-cols-2 gap-6 mb-6">
            <!-- Bar Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h4 class="font-bold text-gray-900 mb-4">
                    <i class="fas fa-chart-bar mr-2 text-blue-600"></i>
                    Perbandingan Skor Total
                </h4>
                <canvas id="scoreChart" height="200"></canvas>
            </div>

            <!-- Radar Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h4 class="font-bold text-gray-900 mb-4">
                    <i class="fas fa-chart-pie mr-2 text-purple-600"></i>
                    Kontribusi Per Bidang
                </h4>
                <canvas id="radarChart" height="200"></canvas>
            </div>
        </div>

        <!-- Summary -->
        <div class="bg-gradient-to-r from-green-600 to-emerald-600 rounded-lg shadow p-6 text-white">
            <h3 class="font-bold text-xl mb-4">
                <i class="fas fa-check-circle mr-2"></i>
                Kesimpulan
            </h3>
            <div class="grid md:grid-cols-3 gap-4">
                <?php 
                $topThree = array_slice($bordaResults, 0, 3);
                foreach ($topThree as $index => $project): 
                    $medals = ['🥇', '🥈', '🥉'];
                ?>
                <div class="bg-white/20 backdrop-blur rounded-lg p-4">
                    <div class="text-3xl mb-2"><?php echo $medals[$index]; ?></div>
                    <div class="font-bold text-lg"><?php echo htmlspecialchars($project['project_name']); ?></div>
                    <div class="text-green-100">Skor: <?php echo number_format($project['total_score'], 2); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php endif; ?>
    </div>

    <?php if (!empty($bordaResults)): ?>
    <script>
    // Prepare data
    const projectNames = <?php echo json_encode(array_column($bordaResults, 'project_name')); ?>;
    const totalScores = <?php echo json_encode(array_map(function($r) { return floatval($r['total_score']); }, $bordaResults)); ?>;
    
    <?php
    $supervisorScores = [];
    $teknisScores = [];
    $keuanganScores = [];
    foreach ($bordaResults as $r) {
        $pointSupervisor = isset($r['rank_supervisor']) ? $n - $r['rank_supervisor'] + 1 : 0;
        $pointTeknis = isset($r['rank_teknis']) ? $n - $r['rank_teknis'] + 1 : 0;
        $pointKeuangan = isset($r['rank_keuangan']) ? $n - $r['rank_keuangan'] + 1 : 0;
        $supervisorScores[] = $pointSupervisor * $fieldWeights['supervisor'];
        $teknisScores[] = $pointTeknis * $fieldWeights['teknis'];
        $keuanganScores[] = $pointKeuangan * $fieldWeights['keuangan'];
    }
    ?>
    const supervisorScores = <?php echo json_encode($supervisorScores); ?>;
    const teknisScores = <?php echo json_encode($teknisScores); ?>;
    const keuanganScores = <?php echo json_encode($keuanganScores); ?>;

    // Bar Chart
    const barCtx = document.getElementById('scoreChart').getContext('2d');
    new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: projectNames,
            datasets: [{
                label: 'Skor Total',
                data: totalScores,
                backgroundColor: totalScores.map((v, i) => {
                    if (i === 0) return 'rgba(234, 179, 8, 0.8)';
                    if (i === 1) return 'rgba(156, 163, 175, 0.8)';
                    if (i === 2) return 'rgba(249, 115, 22, 0.8)';
                    return 'rgba(16, 185, 129, 0.8)';
                }),
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, title: { display: true, text: 'Skor' } }
            }
        }
    });

    // Stacked Bar Chart
    const radarCtx = document.getElementById('radarChart').getContext('2d');
    new Chart(radarCtx, {
        type: 'bar',
        data: {
            labels: projectNames,
            datasets: [
                {
                    label: 'Supervisor',
                    data: supervisorScores,
                    backgroundColor: 'rgba(59, 130, 246, 0.8)'
                },
                {
                    label: 'Teknis',
                    data: teknisScores,
                    backgroundColor: 'rgba(139, 92, 246, 0.8)'
                },
                {
                    label: 'Keuangan',
                    data: keuanganScores,
                    backgroundColor: 'rgba(234, 179, 8, 0.8)'
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            },
            scales: {
                x: { stacked: true },
                y: { stacked: true, title: { display: true, text: 'Kontribusi Skor' } }
            }
        }
    });
    </script>
    <?php endif; ?>
</body>
</html>

