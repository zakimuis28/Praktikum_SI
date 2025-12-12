<?php
/**
 * =====================================================
 * BORDA Result Page - GDSS System
 * All roles can view, only Supervisor (DM1) can calculate
 * =====================================================
 */
ob_start();

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/controllers/borda_controller.php';
require_once __DIR__ . '/controllers/project_controller.php';
require_once __DIR__ . '/controllers/topsis_controller.php';

// Only Decision Makers (Supervisor, Teknis, Keuangan) can view BORDA results
// Admin cannot access this page
requireLogin();
if (hasRole('admin')) {
    header('Location: dashboard.php');
    exit();
}

// Only Supervisor can execute Borda calculation
$canExecuteBorda = hasRole('supervisor');

$bordaController = new BordaController();
$projectController = new ProjectController();
$topsisController = new TopsisController();

// Handle BORDA Calculation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'calculate_borda' && $canExecuteBorda) {
        $result = $bordaController->calculateBordaConsensus();
        if ($result['success']) {
            setFlashMessage('success', $result['message']);
        } else {
            setFlashMessage('danger', $result['message']);
        }
        header('Location: borda_result.php');
        exit();
    }
}

$projects = $projectController->getAllProjects();
$bordaResults = $bordaController->getAllBordaResults();

// Get TOPSIS Results for each DM to display in calculation table
$allTopsisResults = [];
foreach (['supervisor', 'teknis', 'keuangan'] as $field) {
    $allTopsisResults[$field] = $topsisController->getTopsisResults($field);
}
$bordaWeights = ['supervisor' => 7, 'teknis' => 4, 'keuangan' => 2];
$totalWeight = array_sum($bordaWeights);
$totalProjects = count($projects);

// Build calculation data for display
$calculationData = [];
foreach ($projects as $project) {
    $projectId = $project['id'];
    $calcRow = [
        'project' => $project,
        'dm_data' => [],
        'borda_score' => 0
    ];
    
    foreach (['supervisor', 'teknis', 'keuangan'] as $field) {
        $rank = null;
        $topsisScore = null;
        if (isset($allTopsisResults[$field])) {
            foreach ($allTopsisResults[$field] as $topsisResult) {
                if ($topsisResult['project_id'] == $projectId) {
                    $rank = $topsisResult['rank'];
                    $topsisScore = $topsisResult['topsis_score'];
                    break;
                }
            }
        }
        
        $points = $rank !== null ? ($totalProjects - $rank + 1) : 0;
        $weight = $bordaWeights[$field];
        $contribution = $points * $weight;
        
        $calcRow['dm_data'][$field] = [
            'rank' => $rank,
            'topsis_score' => $topsisScore,
            'points' => $points,
            'weight' => $weight,
            'contribution' => $contribution
        ];
        
        $calcRow['borda_score'] += $contribution;
    }
    
    $calculationData[$projectId] = $calcRow;
}

// Sort calculation data by borda score
uasort($calculationData, function($a, $b) {
    return $b['borda_score'] <=> $a['borda_score'];
});

// Assign ranks
$bordaRank = 1;
foreach ($calculationData as &$row) {
    $row['rank'] = $bordaRank++;
}
unset($row);

// Statistics
$totalProjectsCount = count($projects);
$evaluatedCount = count($bordaResults);
$highestScore = !empty($bordaResults) ? max(array_column($bordaResults, 'final_score')) : 0;
$avgScore = !empty($bordaResults) ? array_sum(array_column($bordaResults, 'final_score')) / count($bordaResults) : 0;

// Chart data
$chartLabels = array_column($bordaResults, 'project_code');
$chartScores = array_map(fn($r) => round($r['final_score'], 2), $bordaResults);

$pageTitle = 'Hasil BORDA - ' . SITE_NAME;
$flashMessage = getFlashMessage();

// Render page
renderHead($pageTitle);
renderSidebar('', 'borda_result');
renderHeader('HASIL BORDA', 'Konsensus final dari semua Decision Maker', 'trophy');
echo flashBox($flashMessage);
?>

<!-- Role Access Information -->
<?php if (!$canExecuteBorda): ?>
<div class="mb-6 bg-gradient-to-r from-blue-50 to-cyan-50 border border-blue-200 rounded-xl p-4">
    <div class="flex items-start gap-3">
        <div class="flex-shrink-0">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <div class="flex-1">
            <h5 class="text-sm font-bold text-blue-900 mb-1">Mode Viewing - Hanya Lihat</h5>
            <p class="text-sm text-blue-800">Anda dapat melihat hasil perhitungan BORDA. Hanya <strong>Supervisor (DM1)</strong> yang dapat melakukan perhitungan BORDA konsensus.</p>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Statistics Cards -->
<section class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <?= statCard('Total Proyek', $totalProjectsCount, 'projects', 'cyan') ?>
    <?= statCard('Sudah Dihitung', $evaluatedCount, 'check', 'emerald') ?>
    <?= statCard('Skor Tertinggi', round($highestScore), 'chart', 'amber') ?>
    <?= statCard('Rata-rata Skor', round($avgScore), 'results', 'purple') ?>
</section>

<!-- Detail Progress per Proyek (Supervisor Only) -->
<?php if ($canExecuteBorda): 
    // Get decision makers data
    $db = getConnection();
    require_once __DIR__ . '/controllers/score_controller.php';
    $scoreController = new ScoreController();
    
    $stmt = $db->prepare("SELECT * FROM users WHERE role IN ('supervisor', 'teknis', 'keuangan') ORDER BY FIELD(role, 'supervisor', 'teknis', 'keuangan'), name");
    $stmt->execute();
    $decisionMakers = $stmt->fetchAll();
    
    // Build progress data
    $progressData = [];
    foreach ($projects as $project) {
        $progressData[$project['id']] = [
            'project' => $project,
            'evaluators' => []
        ];
        
        foreach ($decisionMakers as $dm) {
            $scores = $scoreController->getUserScores($project['id'], $dm['id']);
            $hasTopsis = false;
            
            // Check if this DM has TOPSIS result for this project
            if (isset($allTopsisResults[$dm['role']])) {
                foreach ($allTopsisResults[$dm['role']] as $topsisResult) {
                    if ($topsisResult['project_id'] == $project['id']) {
                        $hasTopsis = true;
                        break;
                    }
                }
            }
            
            $progressData[$project['id']]['evaluators'][$dm['id']] = [
                'user' => $dm,
                'has_scores' => !empty($scores),
                'has_topsis' => $hasTopsis
            ];
        }
    }
?>
<section class="mb-6">
    <div class="bg-white rounded-2xl border border-gray-200">
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
            <h5 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                <?= icon('criteria', 'w-5 h-5 text-cyan-400') ?>
                Detail Progress per Proyek
            </h5>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="border-b-2 border-gray-300">
                            <th class="text-left py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs border-r border-gray-200">Proyek</th>
                            <?php foreach ($decisionMakers as $dm): ?>
                            <th class="text-center py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs border-r border-gray-200 last:border-r-0">
                                <div>Decision M</div>
                                <span class="text-<?= ['supervisor' => 'purple', 'teknis' => 'cyan', 'keuangan' => 'amber'][$dm['role']] ?>-400 uppercase">
                                    <?= ucfirst($dm['role']) ?>
                                </span>
                            </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($progressData as $projectId => $data): ?>
                        <tr class="border-b border-gray-200 hover:bg-gray-50 transition-colors duration-200">
                            <td class="py-4 px-4 border-r border-gray-200">
                                <div>
                                    <span class="px-2 py-1 bg-cyan-500 text-white rounded text-xs font-bold">
                                        <?= htmlspecialchars($data['project']['project_code']) ?>
                                    </span>
                                    <p class="text-gray-900 font-medium mt-1"><?= htmlspecialchars($data['project']['project_name']) ?></p>
                                </div>
                            </td>
                            <?php foreach ($decisionMakers as $dm): 
                                $eval = $data['evaluators'][$dm['id']] ?? null;
                            ?>
                            <td class="py-4 px-4 text-center border-r border-gray-200 last:border-r-0">
                                <?php if ($eval && $eval['has_topsis']): ?>
                                <span class="inline-flex items-center gap-1 px-3 py-1 bg-emerald-500 text-white rounded-full text-xs font-bold">
                                    <?= icon('check', 'w-3 h-3') ?> TOPSIS
                                </span>
                                <?php elseif ($eval && $eval['has_scores']): ?>
                                <span class="inline-flex items-center gap-1 px-3 py-1 bg-amber-500 text-white rounded-full text-xs font-bold">
                                    <?= icon('clock', 'w-3 h-3') ?> Scored
                                </span>
                                <?php else: ?>
                                <span class="inline-flex items-center gap-1 px-3 py-1 bg-slate-700 text-white rounded-full text-xs font-bold">
                                    <?= icon('close', 'w-3 h-3') ?> Pending
                                </span>
                                <?php endif; ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- Legend -->
<section class="mb-6 flex flex-wrap gap-4 text-sm">
    <div class="flex items-center gap-2">
        <span class="px-3 py-1 bg-emerald-500 text-white rounded-full text-xs font-bold">TOPSIS</span>
        <span class="text-gray-900">= Evaluasi & TOPSIS selesai</span>
    </div>
    <div class="flex items-center gap-2">
        <span class="px-3 py-1 bg-amber-500 text-white rounded-full text-xs font-bold">Scored</span>
        <span class="text-gray-900">= Sudah dinilai, belum TOPSIS</span>
    </div>
    <div class="flex items-center gap-2">
        <span class="px-3 py-1 bg-slate-700 text-white rounded-full text-xs font-bold">Pending</span>
        <span class="text-gray-900">= Belum evaluasi</span>
    </div>
</section>
<?php endif; ?>

<!-- Action Button (Hitung BORDA) - HANYA UNTUK SUPERVISOR -->
<?php if ($canExecuteBorda): ?>
<section class="mb-6">
    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <div class="flex flex-col md:flex-row items-center justify-between gap-4">
            <div>
                <h5 class="text-lg font-bold text-gray-900">Perhitungan Konsensus BORDA</h5>
                <p class="text-gray-900 text-sm">Sebagai Supervisor, Anda dapat menghitung konsensus BORDA dari semua Decision Maker</p>
            </div>
            <form method="POST" class="inline">
                <input type="hidden" name="action" value="calculate_borda">
                <button type="submit" class="flex items-center gap-2 px-6 py-3 rounded-xl font-bold uppercase tracking-wide text-sm bg-gradient-to-r from-purple-500 to-purple-600 text-white hover:from-purple-400 hover:to-purple-500 transition-all duration-300 shadow-lg shadow-purple-500/25">
                    <?= icon('calculate', 'w-5 h-5') ?>
                    Hitung BORDA
                </button>
            </form>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Toggle Cards Row -->
<section class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
    <!-- Metodologi BORDA Card -->
    <div class="bg-gradient-to-r from-purple-500 to-pink-500 backdrop-blur-sm rounded-xl border-2 border-purple-300 overflow-hidden shadow-lg hover:shadow-xl transition-all duration-300">
        <div class="flex items-center justify-between px-4 py-3 cursor-pointer hover:bg-white/10 transition-colors" onclick="openMethodologyModal()">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-white/95 rounded-lg shadow-md">
                    <?= icon('info', 'w-5 h-5 text-purple-600') ?>
                </div>
                <div>
                    <h5 class="text-sm font-bold text-white drop-shadow-md">Metodologi BORDA</h5>
                    <p class="text-xs text-purple-50">Penjelasan & rumus perhitungan</p>
                </div>
            </div>
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
            </svg>
        </div>
    </div>
    
    <!-- Perhitungan BORDA Card -->
    <div class="bg-gradient-to-r from-amber-500 to-orange-500 backdrop-blur-sm rounded-xl border-2 border-amber-300 overflow-hidden shadow-lg hover:shadow-xl transition-all duration-300">
        <div class="flex items-center justify-between px-4 py-3 cursor-pointer hover:bg-white/10 transition-colors" onclick="openCalculationModal()">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-white/95 rounded-lg shadow-md">
                    <?= icon('calculate', 'w-5 h-5 text-amber-600') ?>
                </div>
                <div>
                    <h5 class="text-sm font-bold text-white drop-shadow-md">Perhitungan BORDA</h5>
                    <p class="text-xs text-amber-50">Matriks & detail kalkulasi</p>
                </div>
            </div>
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
            </svg>
        </div>
    </div>
</section>

<!-- Metodologi BORDA Modal Popup -->
<div id="methodologyModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4" onclick="closeMethodologyModal(event)">
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm"></div>
    <div class="relative bg-white backdrop-blur-md rounded-2xl border-2 border-purple-400 shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-auto" onclick="event.stopPropagation()">
        <!-- Modal Header -->
        <div class="sticky top-0 z-10 bg-gradient-to-r from-purple-500 to-pink-500 flex items-center justify-between px-6 py-4 border-b-2 border-purple-300">
            <h5 class="text-lg font-bold text-white flex items-center gap-2 drop-shadow-md">
                <?= icon('info', 'w-5 h-5') ?>
                Metodologi BORDA
            </h5>
            <button onclick="closeMethodologyModal()" class="p-2 hover:bg-white/20 rounded-lg transition-colors">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Penjelasan Metode -->
                <div>
                    <h6 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-2">📖 Penjelasan Metode</h6>
                    <p class="text-gray-700 text-sm leading-relaxed mb-3">
                        <strong class="text-purple-600">BORDA Count</strong> adalah metode voting yang dikembangkan oleh Jean-Charles de Borda. 
                        Metode ini menggabungkan peringkat dari beberapa decision maker dengan memberikan poin berdasarkan posisi ranking.
                    </p>
                    <p class="text-gray-700 text-sm leading-relaxed">
                        Setiap DM memiliki bobot berbeda sesuai tingkat kepentingannya. Skor akhir adalah hasil agregasi dari semua penilaian DM.
                    </p>
                </div>
                
                <!-- Rumus -->
                <div>
                    <h6 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-2">📐 Rumus Perhitungan</h6>
                    <div class="bg-gray-50 rounded-xl p-4 text-sm space-y-3">
                        <div>
                            <p class="text-gray-700 text-xs mb-1">Step 1: Konversi Rank ke Poin</p>
                            <p class="text-amber-600 font-bold">Poin = N - Rank + 1</p>
                        </div>
                        <div>
                            <p class="text-gray-700 text-xs mb-1">Step 2: Hitung Kontribusi per DM</p>
                            <p class="text-cyan-600 font-bold">Kontribusi = Poin × Bobot DM</p>
                        </div>
                        <div>
                            <p class="text-gray-700 text-xs mb-1">Step 3: Total Skor BORDA</p>
                            <p class="text-emerald-600 font-bold">Skor BORDA = Σ Kontribusi</p>
                        </div>
                    </div>
                </div>
            </div>
                
                <!-- Langkah Detail -->
                <div class="mt-4 pt-4 border-t border-gray-200/50">
                    <h6 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-3">📋 Langkah-langkah Detail</h6>
                    <div class="grid md:grid-cols-3 gap-4 mb-4">
                        <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-4 border-l-4 border-purple-500 shadow-md hover:shadow-lg transition-shadow">
                            <div class="text-purple-600 font-bold mb-2 text-base">1. Ambil Ranking TOPSIS</div>
                            <p class="text-sm text-gray-700">Mengambil hasil ranking dari perhitungan TOPSIS setiap Decision Maker.</p>
                        </div>
                        <div class="bg-gradient-to-br from-pink-50 to-rose-50 rounded-xl p-4 border-l-4 border-pink-500 shadow-md hover:shadow-lg transition-shadow">
                            <div class="text-pink-600 font-bold mb-2 text-base">2. Konversi ke Poin</div>
                            <p class="text-sm text-gray-700">Mengubah ranking menjadi poin dengan rumus: Poin = N - Rank + 1.</p>
                        </div>
                        <div class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-xl p-4 border-l-4 border-amber-500 shadow-md hover:shadow-lg transition-shadow">
                            <div class="text-amber-600 font-bold mb-2 text-base">3. Kalikan Bobot DM</div>
                            <p class="text-sm text-gray-700">Mengalikan poin dengan bobot masing-masing Decision Maker.</p>
                        </div>
                        <div class="bg-gradient-to-br from-cyan-50 to-teal-50 rounded-xl p-4 border-l-4 border-cyan-500 shadow-md hover:shadow-lg transition-shadow">
                            <div class="text-cyan-600 font-bold mb-2 text-base">4. Jumlahkan Kontribusi</div>
                            <p class="text-sm text-gray-700">Menjumlahkan semua kontribusi dari setiap Decision Maker.</p>
                        </div>
                        <div class="bg-gradient-to-br from-emerald-50 to-green-50 rounded-xl p-4 border-l-4 border-emerald-500 shadow-md hover:shadow-lg transition-shadow">
                            <div class="text-emerald-600 font-bold mb-2 text-base">5. Ranking Final</div>
                            <p class="text-sm text-gray-700">Mengurutkan proyek berdasarkan total skor BORDA tertinggi.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Bobot DM -->
                <div class="mt-4 pt-4 border-t border-gray-200/50">
                    <h6 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-3">⚖️ Bobot Decision Maker</h6>
                    <div class="flex flex-wrap gap-3">
                        <?php foreach ($bordaWeights as $field => $weight): 
                            $percentage = round(($weight / $totalWeight) * 100);
                            $colors = [
                                'supervisor' => ['bg' => 'bg-purple-50', 'text' => 'text-purple-700', 'dot' => 'bg-purple-500'],
                                'teknis' => ['bg' => 'bg-cyan-50', 'text' => 'text-cyan-700', 'dot' => 'bg-cyan-500'],
                                'keuangan' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'dot' => 'bg-amber-500']
                            ];
                            $c = $colors[$field];
                        ?>
                        <div class="flex items-center gap-2 px-4 py-2 <?= $c['bg'] ?> rounded-lg">
                            <div class="w-3 h-3 <?= $c['dot'] ?> rounded-full"></div>
                            <span class="<?= $c['text'] ?> font-bold"><?= ucfirst($field) ?>: <?= $weight ?></span>
                            <span class="text-gray-600 text-xs">(<?= $percentage ?>%)</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
        </div>
    </div>
</div>

<!-- Perhitungan BORDA Modal Popup -->
<div id="calculationModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4" onclick="closeCalculationModal(event)">
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm"></div>
    <div class="relative bg-white backdrop-blur-md rounded-2xl border-2 border-amber-400 shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-auto" onclick="event.stopPropagation()">
        <!-- Modal Header -->
        <div class="sticky top-0 z-10 bg-gradient-to-r from-amber-500 to-orange-500 flex items-center justify-between px-6 py-4 border-b-2 border-amber-300">
            <h5 class="text-lg font-bold text-white flex items-center gap-2 drop-shadow-md">
                <?= icon('calculate', 'w-5 h-5') ?>
                Perhitungan BORDA
            </h5>
            <button onclick="closeCalculationModal()" class="p-2 hover:bg-white/20 rounded-lg transition-colors">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <div class="p-6">
        <!-- Tabel Bobot Decision Maker -->
        <div class="bg-gray-50 backdrop-blur-sm rounded-2xl border border-gray-200 mb-6">
            <div class="flex items-center justify-between border-b border-gray-200/50 px-6 py-4">
                <h5 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <?= icon('users', 'w-5 h-5 text-purple-400') ?>
                    Bobot Decision Maker (DM)
                </h5>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Kode</th>
                                <th class="text-left py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Decision Maker</th>
                                <th class="text-center py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Bobot (w)</th>
                                <th class="text-center py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Bobot Normalisasi (w')</th>
                                <th class="text-center py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Persentase</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $dmIdx = 1;
                            foreach ($bordaWeights as $field => $weight): 
                                $normWeight = $weight / $totalWeight;
                                $percentage = round($normWeight * 100);
                                $colors = [
                                    'supervisor' => 'text-purple-400',
                                    'teknis' => 'text-cyan-400',
                                    'keuangan' => 'text-amber-400'
                                ];
                            ?>
                            <tr class="border-b border-gray-200/50 hover:bg-gray-100 transition-colors duration-200">
                                <td class="py-3 px-4">
                                    <span class="<?= $colors[$field] ?> font-bold">DM<?= $dmIdx ?></span>
                                </td>
                                <td class="py-3 px-4 text-gray-900"><?= ucfirst($field) ?></td>
                                <td class="py-3 px-4 text-center text-gray-900 font-medium"><?= $weight ?></td>
                                <td class="py-3 px-4 text-center text-amber-400 font-bold"><?= round($normWeight, 2) ?></td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2 py-1 bg-emerald-500/20 text-emerald-400 rounded text-xs font-bold"><?= $percentage ?>%</span>
                                </td>
                            </tr>
                            <?php $dmIdx++; endforeach; ?>
                            <tr class="bg-gray-100 font-bold">
                                <td colspan="2" class="py-3 px-4 text-right text-gray-900">Total:</td>
                                <td class="py-3 px-4 text-center text-emerald-400"><?= $totalWeight ?></td>
                                <td class="py-3 px-4 text-center text-emerald-400">1</td>
                                <td class="py-3 px-4 text-center text-emerald-400">100%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tabel Ranking TOPSIS per DM -->
        <div class="bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-200 mb-6">
            <div class="flex items-center justify-between border-b border-gray-200/50 px-6 py-4">
                <h5 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <?= icon('results', 'w-5 h-5 text-cyan-400') ?>
                    Ranking TOPSIS per Decision Maker
                </h5>
                <span class="text-xs text-gray-900">Input dari hasil perhitungan TOPSIS masing-masing DM</span>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Proyek</th>
                                <th class="text-center py-3 px-4 text-purple-400 font-bold uppercase tracking-wider text-xs">Rank DM1<br><span class="text-xs text-gray-500">(Supervisor)</span></th>
                                <th class="text-center py-3 px-4 text-cyan-400 font-bold uppercase tracking-wider text-xs">Rank DM2<br><span class="text-xs text-gray-500">(Teknis)</span></th>
                                <th class="text-center py-3 px-4 text-amber-400 font-bold uppercase tracking-wider text-xs">Rank DM3<br><span class="text-xs text-gray-500">(Keuangan)</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $altIdx = 1;
                            foreach ($calculationData as $projectId => $row): 
                                $project = $row['project'];
                            ?>
                            <tr class="border-b border-gray-200/50 hover:bg-gray-100 transition-colors duration-200">
                                <td class="py-3 px-4">
                                    <span class="text-amber-400 font-bold">A<?= $altIdx ?></span>
                                    <span class="text-gray-900 ml-2"><?= htmlspecialchars($project['project_code']) ?></span>
                                </td>
                                <?php foreach (['supervisor', 'teknis', 'keuangan'] as $field): 
                                    $rank = $row['dm_data'][$field]['rank'];
                                ?>
                                <td class="py-3 px-4 text-center">
                                    <?php if ($rank !== null): ?>
                                    <span class="text-gray-900 font-medium"><?= $rank ?></span>
                                    <?php else: ?>
                                    <span class="text-gray-500 italic">-</span>
                                    <?php endif; ?>
                                </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php $altIdx++; endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tabel Konversi Rank ke Poin -->
        <div class="bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-200 mb-6">
            <div class="flex items-center justify-between border-b border-gray-200/50 px-6 py-4">
                <h5 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <?= icon('calculate', 'w-5 h-5 text-purple-400') ?>
                    Konversi Rank ke Poin BORDA
                </h5>
            </div>
            <div class="p-6">
                <div class="mb-4 p-4 bg-gray-50 rounded-xl border-l-4 border-purple-500">
                    <p class="text-gray-900 text-sm mb-1">Rumus:</p>
                    <p class="text-purple-400 font-bold text-lg">Poin = N - Rank + 1</p>
                    <p class="text-gray-500 text-xs mt-1">Dimana N = <?= $totalProjects ?> (jumlah proyek)</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Proyek</th>
                                <th class="text-center py-3 px-4 text-purple-400 font-bold uppercase tracking-wider text-xs">Poin DM1</th>
                                <th class="text-center py-3 px-4 text-cyan-400 font-bold uppercase tracking-wider text-xs">Poin DM2</th>
                                <th class="text-center py-3 px-4 text-amber-400 font-bold uppercase tracking-wider text-xs">Poin DM3</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $altIdx = 1;
                            foreach ($calculationData as $projectId => $row): 
                                $project = $row['project'];
                            ?>
                            <tr class="border-b border-gray-200/50 hover:bg-gray-100 transition-colors duration-200">
                                <td class="py-3 px-4">
                                    <span class="text-amber-400 font-bold">A<?= $altIdx ?></span>
                                    <span class="text-gray-900 ml-2"><?= htmlspecialchars($project['project_code']) ?></span>
                                </td>
                                <?php foreach (['supervisor', 'teknis', 'keuangan'] as $field): 
                                    $points = $row['dm_data'][$field]['points'];
                                    $rank = $row['dm_data'][$field]['rank'];
                                ?>
                                <td class="py-3 px-4 text-center">
                                    <?php if ($rank !== null): ?>
                                    <span class="text-gray-900 font-medium"><?= $points ?></span>
                                    <span class="text-gray-500 text-xs ml-1">(<?= $totalProjects ?>-<?= $rank ?>+1)</span>
                                    <?php else: ?>
                                    <span class="text-gray-500 italic">-</span>
                                    <?php endif; ?>
                                </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php $altIdx++; endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tabel Kontribusi per DM -->
        <div class="bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-200 mb-6">
            <div class="flex items-center justify-between border-b border-gray-200/50 px-6 py-4">
                <h5 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <?= icon('calculate', 'w-5 h-5 text-cyan-400') ?>
                    Kontribusi per Decision Maker
                </h5>
            </div>
            <div class="p-6">
                <div class="mb-4 p-4 bg-gray-50 rounded-xl border-l-4 border-cyan-500">
                    <p class="text-gray-900 text-sm mb-1">Rumus:</p>
                    <p class="text-cyan-400 font-bold text-lg">Kontribusi = Poin × Bobot DM</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Proyek</th>
                                <th class="text-center py-3 px-4 text-purple-400 font-bold uppercase tracking-wider text-xs">DM1 × 7</th>
                                <th class="text-center py-3 px-4 text-cyan-400 font-bold uppercase tracking-wider text-xs">DM2 × 4</th>
                                <th class="text-center py-3 px-4 text-amber-400 font-bold uppercase tracking-wider text-xs">DM3 × 2</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $altIdx = 1;
                            foreach ($calculationData as $projectId => $row): 
                                $project = $row['project'];
                            ?>
                            <tr class="border-b border-gray-200/50 hover:bg-gray-100 transition-colors duration-200">
                                <td class="py-3 px-4">
                                    <span class="text-amber-400 font-bold">A<?= $altIdx ?></span>
                                    <span class="text-gray-900 ml-2"><?= htmlspecialchars($project['project_code']) ?></span>
                                </td>
                                <?php foreach (['supervisor', 'teknis', 'keuangan'] as $field): 
                                    $contribution = $row['dm_data'][$field]['contribution'];
                                    $points = $row['dm_data'][$field]['points'];
                                    $weight = $row['dm_data'][$field]['weight'];
                                    $rank = $row['dm_data'][$field]['rank'];
                                ?>
                                <td class="py-3 px-4 text-center">
                                    <?php if ($rank !== null): ?>
                                    <span class="text-emerald-400 font-bold"><?= $contribution ?></span>
                                    <span class="text-gray-500 text-xs ml-1">(<?= $points ?>×<?= $weight ?>)</span>
                                    <?php else: ?>
                                    <span class="text-gray-500 italic">-</span>
                                    <?php endif; ?>
                                </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php $altIdx++; endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tabel Skor BORDA per Alternatif -->
        <div class="bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-200 mb-6">
            <div class="flex items-center justify-between border-b border-gray-200/50 px-6 py-4">
                <h5 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <?= icon('results', 'w-5 h-5 text-amber-400') ?>
                    Skor BORDA per Alternatif
                </h5>
            </div>
            <div class="p-6">
                <div class="mb-4 p-4 bg-gray-50 rounded-xl border-l-4 border-amber-500">
                    <p class="text-gray-900 text-sm mb-1">Rumus:</p>
                    <p class="text-amber-400 font-bold text-lg">Skor BORDA = Σ(Poin<sub>i</sub> × Bobot<sub>i</sub>)</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Proyek</th>
                                <th class="text-center py-3 px-4 text-purple-400 font-bold uppercase tracking-wider text-xs">K. DM1</th>
                                <th class="text-center py-3 px-4 text-cyan-400 font-bold uppercase tracking-wider text-xs">K. DM2</th>
                                <th class="text-center py-3 px-4 text-amber-400 font-bold uppercase tracking-wider text-xs">K. DM3</th>
                                <th class="text-center py-3 px-4 text-emerald-400 font-bold uppercase tracking-wider text-xs">Skor BORDA</th>
                                <th class="text-center py-3 px-4 text-cyan-400 font-bold uppercase tracking-wider text-xs">Ranking</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $altIdx = 1;
                            foreach ($calculationData as $projectId => $row): 
                                $project = $row['project'];
                                $hasData = array_sum(array_column($row['dm_data'], 'contribution')) > 0;
                                $isWinner = $row['rank'] === 1;
                                $rowClass = $isWinner ? 'bg-gradient-to-r from-yellow-50 to-amber-50 border-l-4 border-yellow-400' : '';
                            ?>
                            <tr class="border-b border-gray-200/50 hover:bg-gray-100 transition-colors duration-200 <?= $rowClass ?>">
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-2">
                                        <span class="text-amber-400 font-bold">A<?= $altIdx ?></span>
                                        <span class="text-gray-900"><?= htmlspecialchars($project['project_code']) ?></span>
                                        <?php if ($isWinner): ?>
                                        <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <?php foreach (['supervisor', 'teknis', 'keuangan'] as $field): 
                                    $contribution = $row['dm_data'][$field]['contribution'];
                                    $rank = $row['dm_data'][$field]['rank'];
                                ?>
                                <td class="py-3 px-4 text-center">
                                    <?php if ($rank !== null): ?>
                                    <span class="text-gray-900 <?= $isWinner ? 'font-bold' : '' ?>"><?= $contribution ?></span>
                                    <?php else: ?>
                                    <span class="text-gray-500 italic">-</span>
                                    <?php endif; ?>
                                </td>
                                <?php endforeach; ?>
                                <td class="py-3 px-4 text-center">
                                    <?php if ($hasData): ?>
                                    <span class="font-bold <?= $isWinner ? 'text-yellow-500 text-xl' : 'text-emerald-400' ?>"><?= $row['borda_score'] ?></span>
                                    <?php else: ?>
                                    <span class="text-gray-500 italic">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <?php if ($hasData): ?>
                                        <?php if ($row['rank'] === 1): ?>
                                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-gradient-to-r from-yellow-400 to-yellow-600 rounded-full text-white text-xs font-bold shadow-md">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                            <?= $row['rank'] ?>
                                        </span>
                                        <?php else: ?>
                                        <span class="px-3 py-1 bg-cyan-500/20 text-cyan-400 rounded-full text-xs font-bold"><?= $row['rank'] ?></span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                    <span class="text-gray-500 italic">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php $altIdx++; endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        </div>
    </div>
</div>

<script>
function openMethodologyModal() {
    const modal = document.getElementById('methodologyModal');
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeMethodologyModal(event) {
    if (event && event.target !== event.currentTarget) return;
    const modal = document.getElementById('methodologyModal');
    modal.classList.add('hidden');
    document.body.style.overflow = '';
}

function openCalculationModal() {
    const modal = document.getElementById('calculationModal');
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeCalculationModal(event) {
    if (event && event.target !== event.currentTarget) return;
    const modal = document.getElementById('calculationModal');
    modal.classList.add('hidden');
    document.body.style.overflow = '';
}

// Close modals with ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeMethodologyModal();
        closeCalculationModal();
    }
});
</script>

<?php if (empty($bordaResults)): ?>
<!-- No Results -->
<section class="bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-200 p-12 text-center">
    <?= icon('trophy', 'w-20 h-20 text-slate-600 mx-auto mb-4') ?>
    <h4 class="text-xl font-bold text-gray-900 mb-2">Belum Ada Hasil BORDA</h4>
    <p class="text-gray-900 mb-6">Pastikan semua decision maker sudah menyelesaikan perhitungan TOPSIS terlebih dahulu</p>
    <?php if ($canExecuteBorda): ?>
    <form method="POST" class="inline">
        <input type="hidden" name="action" value="calculate_borda">
        <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-bold uppercase tracking-wide text-sm bg-gradient-to-r from-purple-500 to-purple-600 text-white hover:from-purple-400 hover:to-purple-500 transition-all duration-300">
            <?= icon('calculate', 'w-5 h-5') ?>
            Hitung BORDA Sekarang
        </button>
    </form>
    <?php else: ?>
    <p class="text-gray-900">Menunggu Supervisor menghitung BORDA...</p>
    <?php endif; ?>
</section>

<?php else: ?>

<!-- Ranking BORDA -->
<section class="mb-8 bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-200">
    <div class="flex items-center justify-between border-b border-gray-200/50 px-6 py-4">
        <h5 class="text-lg font-bold text-gray-900 flex items-center gap-2">
            <?= icon('trophy', 'w-5 h-5 text-yellow-400') ?>
            Ranking BORDA
        </h5>
    </div>
    <div class="p-6">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Rank</th>
                        <th class="text-left py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Kode</th>
                        <th class="text-left py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Nama Proyek</th>
                        <th class="text-left py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Lokasi</th>
                        <th class="text-center py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Skor BORDA</th>
                        <th class="text-center py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bordaResults as $index => $result): 
                        $rank = $result['final_rank'] ?? ($index + 1);
                        $rankColors = [1 => 'text-yellow-400', 2 => 'text-gray-900', 3 => 'text-amber-600'];
                        $rankColor = $rankColors[$rank] ?? 'text-gray-900';
                    ?>
                    <tr class="border-b border-gray-200/50 hover:bg-gray-100 transition-colors duration-200">
                        <td class="py-4 px-4">
                            <?php if ($rank === 1): ?>
                                <div class="flex items-center justify-center w-12 h-12 rounded-full bg-gradient-to-br from-yellow-400 to-yellow-600 shadow-lg">
                                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                </div>
                            <?php elseif ($rank === 2): ?>
                                <div class="flex items-center justify-center w-12 h-12 rounded-full bg-gradient-to-br from-gray-300 to-gray-500 shadow-md">
                                    <span class="text-white font-bold text-lg"><?= $rank ?></span>
                                </div>
                            <?php elseif ($rank === 3): ?>
                                <div class="flex items-center justify-center w-12 h-12 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 shadow-md">
                                    <span class="text-white font-bold text-lg"><?= $rank ?></span>
                                </div>
                            <?php else: ?>
                                <div class="flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 border-2 border-gray-300">
                                    <span class="text-gray-600 font-bold text-lg"><?= $rank ?></span>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="py-4 px-4">
                            <span class="font-mono text-sm text-gray-900 font-medium"><?= htmlspecialchars($result['project_code']) ?></span>
                        </td>
                        <td class="py-4 px-4">
                            <div class="font-bold text-gray-900"><?= htmlspecialchars($result['project_name']) ?></div>
                        </td>
                        <td class="py-4 px-4 text-gray-900"><?= htmlspecialchars($result['location'] ?? '-') ?></td>
                        <td class="py-4 px-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <span class="font-bold text-lg text-emerald-500"><?= round($result['final_score']) ?></span>
                            </div>
                        </td>
                        <td class="py-4 px-4 text-center">
                            <?php if ($rank === 1): ?>
                            <span class="inline-flex items-center gap-1.5 px-4 py-2 bg-gradient-to-r from-yellow-400 to-yellow-600 rounded-full text-white text-xs font-bold shadow-md">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                TERBAIK
                            </span>
                            <?php elseif ($rank === 2): ?>
                            <span class="inline-flex items-center gap-1.5 px-4 py-2 bg-gradient-to-r from-gray-300 to-gray-500 rounded-full text-white text-xs font-bold shadow-md">
                                TOP 2
                            </span>
                            <?php elseif ($rank === 3): ?>
                            <span class="inline-flex items-center gap-1.5 px-4 py-2 bg-gradient-to-r from-orange-400 to-orange-600 rounded-full text-white text-xs font-bold shadow-md">
                                TOP 3
                            </span>
                            <?php else: ?>
                            <span class="px-3 py-1.5 bg-gray-100 text-gray-500 rounded-full text-xs font-medium">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- Chart Section -->
<section class="mb-8">
    <div class="bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-200 p-6">
        <h5 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
            <?= icon('chart', 'w-5 h-5 text-cyan-400') ?>
            Grafik Ranking BORDA
        </h5>
        <canvas id="bordaChart" height="150"></canvas>
    </div>
</section>

<!-- Kesimpulan & Rekomendasi -->
<section class="mb-8">
    <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl border border-emerald-400 overflow-hidden shadow-xl">
        <div class="px-6 py-4 bg-white/10 border-b border-white/20">
            <h5 class="text-lg font-bold text-white flex items-center gap-2">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                Kesimpulan & Rekomendasi Keputusan
            </h5>
        </div>
        <div class="p-6">
            <?php 
            // Get winner data from calculationData
            $winnerData = reset($calculationData);
            $winnerProject = $winnerData['project'];
            $winnerScore = $winnerData['borda_score'];
            
            // Get all scores for comparison
            $allScores = array_column($calculationData, 'borda_score');
            $highestScore = max($allScores);
            $secondHighestScore = count($allScores) > 1 ? $allScores[1] : 0;
            $scoreDifference = $highestScore - $secondHighestScore;
            ?>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Proyek Terbaik -->
                <div class="bg-white/95 backdrop-blur-sm rounded-xl p-6 border-2 border-yellow-400 shadow-lg">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-3 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-full">
                            <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider font-bold">Proyek Terbaik</p>
                            <h3 class="text-xl font-black text-gray-900"><?= htmlspecialchars($winnerProject['project_name']) ?></h3>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between py-2 px-3 bg-gray-50 rounded-lg">
                            <span class="text-sm text-gray-600">Kode Proyek:</span>
                            <span class="font-bold text-amber-600"><?= htmlspecialchars($winnerProject['project_code']) ?></span>
                        </div>
                        <div class="flex items-center justify-between py-2 px-3 bg-gray-50 rounded-lg">
                            <span class="text-sm text-gray-600">Total Poin BORDA:</span>
                            <span class="text-2xl font-black text-emerald-600"><?= $winnerScore ?></span>
                        </div>
                        <div class="flex items-center justify-between py-2 px-3 bg-gray-50 rounded-lg">
                            <span class="text-sm text-gray-600">Selisih dengan Rank 2:</span>
                            <span class="font-bold text-purple-600">+<?= $scoreDifference ?> poin</span>
                        </div>
                    </div>
                </div>

                <!-- Rekomendasi -->
                <div class="bg-white/95 backdrop-blur-sm rounded-xl p-6 border border-gray-200">
                    <div class="flex items-start gap-3 mb-4">
                        <div class="p-2 bg-emerald-100 rounded-lg">
                            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-2">Rekomendasi Keputusan</h4>
                            <p class="text-gray-700 text-sm leading-relaxed mb-3">
                                Berdasarkan konsensus dari <?= count($bordaWeights) ?> Decision Maker menggunakan metode <strong class="text-purple-600">BORDA Count</strong>, 
                                proyek <strong class="text-amber-600"><?= htmlspecialchars($winnerProject['project_code']) ?></strong> 
                                memperoleh skor tertinggi <strong class="text-emerald-600"><?= $winnerScore ?> poin</strong>.
                            </p>
                            <div class="p-3 bg-emerald-50 border-l-4 border-emerald-500 rounded">
                                <p class="text-sm font-bold text-emerald-800 mb-1">✓ Keputusan Final:</p>
                                <p class="text-sm text-emerald-700">
                                    <strong><?= htmlspecialchars($winnerProject['project_name']) ?></strong> direkomendasikan 
                                    sebagai proyek prioritas utama untuk dilaksanakan berdasarkan penilaian agregat dari seluruh Decision Maker.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detail Kontribusi DM -->
            <div class="mt-6 bg-white/95 backdrop-blur-sm rounded-xl p-6 border border-gray-200">
                <h5 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4">Kontribusi Decision Maker untuk Proyek Terbaik</h5>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <?php 
                    $dmColors = [
                        'supervisor' => ['bg' => 'bg-purple-50', 'text' => 'text-purple-600', 'border' => 'border-purple-200'],
                        'teknis' => ['bg' => 'bg-cyan-50', 'text' => 'text-cyan-600', 'border' => 'border-cyan-200'],
                        'keuangan' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-600', 'border' => 'border-amber-200']
                    ];
                    $dmNames = ['supervisor' => 'Supervisor', 'teknis' => 'Teknis', 'keuangan' => 'Keuangan'];
                    foreach (['supervisor', 'teknis', 'keuangan'] as $idx => $field): 
                        $dmData = $winnerData['dm_data'][$field];
                        $colors = $dmColors[$field];
                    ?>
                    <div class="<?= $colors['bg'] ?> border <?= $colors['border'] ?> rounded-lg p-4">
                        <p class="text-xs <?= $colors['text'] ?> uppercase tracking-wider font-bold mb-2">DM<?= ($idx + 1) ?> - <?= $dmNames[$field] ?></p>
                        <div class="flex items-baseline gap-2">
                            <span class="text-2xl font-black <?= $colors['text'] ?>"><?= $dmData['contribution'] ?></span>
                            <span class="text-xs text-gray-500">poin</span>
                        </div>
                        <p class="text-xs text-gray-600 mt-1">Rank: <?= $dmData['rank'] ?? '-' ?> | Bobot: <?= $dmData['weight'] ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('bordaChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [{
                label: 'Skor BORDA',
                data: <?= json_encode($chartScores) ?>,
                backgroundColor: [
                    'rgba(245, 158, 11, 0.8)',
                    'rgba(16, 185, 129, 0.8)',
                    'rgba(6, 182, 212, 0.8)',
                    'rgba(139, 92, 246, 0.8)',
                    'rgba(236, 72, 153, 0.8)',
                    'rgba(239, 68, 68, 0.8)'
                ],
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: Math.max(...<?= json_encode($chartScores) ?>) + 10,
                    ticks: { 
                        color: '#94a3b8',
                        stepSize: 20
                    },
                    grid: { color: 'rgba(148, 163, 184, 0.1)' }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#94a3b8' }
                }
            }
        }
    });
});
</script>
<?php endif; ?>

<?php renderFooter(); ?>






