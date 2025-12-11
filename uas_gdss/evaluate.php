<?php
/**
 * =====================================================
 * Evaluate Page - GDSS System
 * Multi-Role Project Evaluation with TOPSIS
 * =====================================================
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/controllers/project_controller.php';
require_once __DIR__ . '/controllers/criteria_controller.php';
require_once __DIR__ . '/controllers/score_controller.php';
require_once __DIR__ . '/controllers/topsis_controller.php';

// Require Decision Maker roles only
$allowedRoles = ['supervisor', 'teknis', 'keuangan'];
if (!in_array($_SESSION['role'], $allowedRoles)) {
    setFlashMessage('error', 'Access denied. Only Decision Makers can access this page.');
    header('Location: dashboard.php');
    exit();
}

$projectController = new ProjectController();
$criteriaController = new CriteriaController();
$scoreController = new ScoreController();
$topsisController = new TopsisController();

$userRole = $_SESSION['role'];
$userId = $_SESSION['user_id'];

$projects = $projectController->getAllProjects();
$criteria = $criteriaController->getCriteriaByField($userRole);

$selectedProjectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : null;
$selectedProject = null;
$existingScores = [];

if ($selectedProjectId) {
    $selectedProject = $projectController->getProjectById($selectedProjectId);
    $existingScores = $scoreController->getUserScores($selectedProjectId, $userId);
}

$roleColors = ['supervisor' => 'purple', 'teknis' => 'cyan', 'keuangan' => 'amber'];
$roleColor = $roleColors[$userRole] ?? 'cyan';

$pageTitle = 'Evaluasi ' . ucfirst($userRole) . ' - ' . SITE_NAME;
$flashMessage = getFlashMessage();

// Check if there's TOPSIS result to display
$topsisResult = null;
if (isset($_SESSION['last_topsis_result'])) {
    $topsisResult = $_SESSION['last_topsis_result'];
    unset($_SESSION['last_topsis_result']);
}

// Render page
renderHead($pageTitle);
renderSidebar('', 'evaluate');
renderHeader('EVALUASI ' . strtoupper($userRole), 'Berikan penilaian untuk setiap proyek', 'criteria');
echo flashBox($flashMessage);

// Show TOPSIS Result Modal if available
if ($topsisResult): ?>
<div id="topsisResultModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="closeTopsisModal()"></div>
    <div class="relative bg-white border border-gray-200 rounded-2xl p-6 max-w-md w-full animate-scale-in">
        <div class="text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Hasil Perhitungan TOPSIS</h3>
            <p class="text-gray-900 text-sm mb-6">Bidang: <?= strtoupper($userRole) ?></p>
            
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                    <p class="text-gray-900 text-xs uppercase tracking-wider mb-1">Skor TOPSIS</p>
                    <p class="text-2xl font-bold text-cyan-400"><?= number_format($topsisResult['score'], 4) ?></p>
                </div>
                <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                    <p class="text-gray-900 text-xs uppercase tracking-wider mb-1">Peringkat</p>
                    <p class="text-2xl font-bold text-emerald-400">#<?= $topsisResult['rank'] ?> <span class="text-sm text-gray-500">/ <?= $topsisResult['total'] ?></span></p>
                </div>
            </div>
            
            <div class="flex gap-3">
                <button onclick="closeTopsisModal()" class="flex-1 px-4 py-2 rounded-lg bg-white border border-gray-200 text-gray-900 hover:border-gray-300 transition-all duration-300 font-bold text-sm">
                    Tutup
                </button>
                <a href="topsis_results.php" class="flex-1 px-4 py-2 rounded-lg bg-gradient-to-r from-cyan-500 to-cyan-600 text-white hover:from-cyan-400 hover:to-cyan-500 transition-all duration-300 font-bold text-sm text-center">
                    Lihat Semua Hasil
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function closeTopsisModal() {
    const modal = document.getElementById('topsisResultModal');
    if (modal) {
        modal.querySelector('.relative').style.animation = 'scaleOut 0.2s ease-out forwards';
        setTimeout(() => modal.remove(), 200);
    }
}
</script>

<style>
@keyframes scaleIn {
    from { opacity: 0; transform: scale(0.9); }
    to { opacity: 1; transform: scale(1); }
}
@keyframes scaleOut {
    from { opacity: 1; transform: scale(1); }
    to { opacity: 0; transform: scale(0.9); }
}
.animate-scale-in { animation: scaleIn 0.3s ease-out; }
</style>
<?php endif;
?>

<?php if (!$selectedProject): ?>
<!-- Project Selection -->
<div class="bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-200 hover:border-cyan-500/30 transition-all duration-300">
    <div class="flex items-center justify-between border-b border-gray-200/50 px-6 py-4">
        <h5 class="text-lg font-bold text-gray-900 flex items-center gap-2">
            <?= icon('projects', 'w-5 h-5 text-cyan-400') ?>
            Pilih Proyek untuk Dievaluasi
        </h5>
        <span class="text-sm text-gray-500"><?= count($projects) ?> Proyek Tersedia</span>
    </div>
    
    <?php if (empty($projects)): ?>
    <div class="text-center py-12 px-6">
        <?= icon('projects', 'w-16 h-16 text-slate-600 mx-auto mb-4') ?>
        <p class="text-gray-900 font-bold uppercase tracking-wide">Belum ada proyek tersedia</p>
        <p class="text-gray-500 text-sm">Hubungi supervisor untuk menambahkan proyek</p>
    </div>
    <?php else: ?>
    <div class="p-6">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b-2 border-gray-200">
                        <th class="text-left py-4 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Kode</th>
                        <th class="text-left py-4 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Nama Proyek</th>
                        <th class="text-left py-4 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Lokasi</th>
                        <th class="text-center py-4 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Tanggal</th>
                        <th class="text-center py-4 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Status</th>
                        <th class="text-center py-4 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($projects as $project): 
                        $existingEval = $scoreController->getUserScores($project['id'], $userId);
                        $isEvaluated = !empty($existingEval);
                    ?>
                    <tr class="border-b border-gray-200 hover:bg-gradient-to-r hover:from-cyan-50 hover:to-emerald-50 transition-all duration-300 group">
                        <td class="py-4 px-4">
                            <span class="inline-flex items-center px-3 py-1.5 bg-cyan-500 text-white rounded-lg text-xs font-bold uppercase tracking-wide shadow-sm">
                                <?= htmlspecialchars($project['project_code']) ?>
                            </span>
                        </td>
                        <td class="py-4 px-4">
                            <p class="text-gray-900 font-bold group-hover:text-cyan-600 transition-colors"><?= htmlspecialchars($project['project_name']) ?></p>
                        </td>
                        <td class="py-4 px-4">
                            <span class="text-sm text-gray-600"><?= htmlspecialchars($project['location']) ?></span>
                        </td>
                        <td class="py-4 px-4 text-center">
                            <span class="text-sm text-gray-600"><?= formatDate($project['created_at']) ?></span>
                        </td>
                        <td class="py-4 px-4 text-center">
                            <?php if ($isEvaluated): ?>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-500 text-white rounded-lg text-xs font-bold uppercase shadow-sm">
                                Selesai
                            </span>
                            <?php else: ?>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-500 text-white rounded-lg text-xs font-bold uppercase shadow-sm">
                                Pending
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="py-4 px-4 text-center">
                            <a href="evaluate.php?project_id=<?= $project['id'] ?>" 
                               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg font-bold text-sm transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-0.5
                                      <?= $isEvaluated 
                                          ? 'bg-gradient-to-r from-purple-500 to-purple-600 text-white hover:from-purple-400 hover:to-purple-500' 
                                          : 'bg-gradient-to-r from-cyan-500 to-emerald-500 text-white hover:from-cyan-400 hover:to-emerald-400' ?>">
                                <?= $isEvaluated ? 'Edit' : 'Evaluasi' ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php else: ?>
<!-- Evaluation Form -->
<div class="bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-200 hover:border-cyan-500/30 transition-all duration-300">
    <div class="flex items-center justify-between border-b border-gray-200/50 px-6 py-4">
        <div>
            <h5 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                <?= icon('criteria', 'w-5 h-5 text-cyan-400') ?>
                Evaluasi: <?= htmlspecialchars($selectedProject['project_name']) ?>
            </h5>
            <p class="text-gray-900 text-sm mt-1">
                Kode: <?= htmlspecialchars($selectedProject['project_code']) ?> | 
                Lokasi: <?= htmlspecialchars($selectedProject['location']) ?>
            </p>
        </div>
        <a href="evaluate.php" class="flex items-center gap-2 px-4 py-2 rounded-lg bg-gray-50 border border-gray-200 text-gray-900 hover:border-cyan-500/50 transition-all duration-300">
            <?= icon('back', 'w-4 h-4') ?>
            <span class="font-bold text-sm">Kembali</span>
        </a>
    </div>
    
    <div class="p-6">
        <?php if (empty($criteria)): ?>
        <div class="text-center py-12">
            <?= icon('criteria', 'w-16 h-16 text-slate-600 mx-auto mb-4') ?>
            <p class="text-gray-900 font-bold uppercase tracking-wide">Belum ada kriteria untuk bidang <?= ucfirst($userRole) ?></p>
            <p class="text-gray-500 text-sm">Hubungi supervisor untuk menambahkan kriteria</p>
        </div>
        <?php elseif (count($criteria) < 1): ?>
        <div class="text-center py-12">
            <?= icon('criteria', 'w-16 h-16 text-amber-600 mx-auto mb-4') ?>
            <p class="text-gray-900 font-bold uppercase tracking-wide">Diperlukan minimal 1 kriteria untuk evaluasi TOPSIS</p>
            <p class="text-gray-500 text-sm">Hubungi supervisor untuk menambahkan kriteria</p>
        </div>
        <?php else: ?>
        
        <!-- Skala Penilaian Reference -->
        <div class="mb-6 p-4 bg-white rounded-xl border-2 border-gray-300">
            <h6 class="text-gray-900 font-bold text-sm mb-3 flex items-center gap-2">
                <?= icon('info', 'w-4 h-4') ?> Panduan Penilaian TOPSIS (Skala 1–5)
            </h6>
            
            <!-- Benefit Scale -->
            <div class="mb-4">
                <p class="text-xs font-bold text-gray-900 mb-2 flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                    KRITERIA BENEFIT (semakin tinggi semakin baik)
                </p>
                <div class="grid grid-cols-5 gap-2 text-xs">
                    <div class="bg-white p-2 rounded-lg text-center border-2 border-red-500">
                        <span class="text-red-600 font-bold text-2xl">1</span>
                        <p class="text-gray-900 mt-1 font-semibold">Sangat Buruk</p>
                    </div>
                    <div class="bg-white p-2 rounded-lg text-center border-2 border-orange-500">
                        <span class="text-orange-600 font-bold text-2xl">2</span>
                        <p class="text-gray-900 mt-1 font-semibold">Buruk</p>
                    </div>
                    <div class="bg-white p-2 rounded-lg text-center border-2 border-amber-500">
                        <span class="text-amber-600 font-bold text-2xl">3</span>
                        <p class="text-gray-900 mt-1 font-semibold">Cukup</p>
                    </div>
                    <div class="bg-white p-2 rounded-lg text-center border-2 border-emerald-500">
                        <span class="text-emerald-600 font-bold text-2xl">4</span>
                        <p class="text-gray-900 mt-1 font-semibold">Baik</p>
                    </div>
                    <div class="bg-white p-2 rounded-lg text-center border-2 border-cyan-500">
                        <span class="text-cyan-600 font-bold text-2xl">5</span>
                        <p class="text-gray-900 mt-1 font-semibold">Sangat Baik</p>
                        <p class="text-emerald-600 text-xs font-bold">✓ Terbaik</p>
                    </div>
                </div>
                <p class="text-xs text-gray-600 mt-2">Contoh: Kualitas SDM, Infrastruktur, Kemampuan Teknis → nilai 5 = sangat baik (ideal)</p>
            </div>

            <!-- Cost Scale -->
            <div>
                <p class="text-xs font-bold text-gray-900 mb-2 flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
                    </svg>
                    KRITERIA COST (semakin rendah semakin baik)
                </p>
                <div class="grid grid-cols-5 gap-2 text-xs">
                    <div class="bg-white p-2 rounded-lg text-center border-2 border-cyan-500">
                        <span class="text-cyan-600 font-bold text-2xl">1</span>
                        <p class="text-gray-900 mt-1 font-semibold">Sangat Rendah</p>
                        <p class="text-emerald-600 text-xs font-bold">✓ Terbaik</p>
                    </div>
                    <div class="bg-white p-2 rounded-lg text-center border-2 border-emerald-500">
                        <span class="text-emerald-600 font-bold text-2xl">2</span>
                        <p class="text-gray-900 mt-1 font-semibold">Rendah</p>
                    </div>
                    <div class="bg-white p-2 rounded-lg text-center border-2 border-amber-500">
                        <span class="text-amber-600 font-bold text-2xl">3</span>
                        <p class="text-gray-900 mt-1 font-semibold">Sedang</p>
                    </div>
                    <div class="bg-white p-2 rounded-lg text-center border-2 border-orange-500">
                        <span class="text-orange-600 font-bold text-2xl">4</span>
                        <p class="text-gray-900 mt-1 font-semibold">Tinggi</p>
                    </div>
                    <div class="bg-white p-2 rounded-lg text-center border-2 border-red-500">
                        <span class="text-red-600 font-bold text-2xl">5</span>
                        <p class="text-gray-900 mt-1 font-semibold">Sangat Tinggi</p>
                        <p class="text-red-600 text-xs font-bold">✗ Terburuk</p>
                    </div>
                </div>
                <p class="text-xs text-gray-600 mt-2">Contoh: Kompleksitas, Biaya, Risiko → nilai 1 = sangat rendah (ideal), nilai 5 = sangat tinggi (buruk)</p>
            </div>
        </div>
        
        <!-- Direct Scoring Form -->
        <div class="mb-4">
            <h6 class="text-gray-900 font-bold mb-2">Penilaian Kriteria untuk TOPSIS</h6>
            <p class="text-gray-900 text-sm">Berikan skor 1-5 untuk setiap kriteria berdasarkan penilaian Anda terhadap proyek ini.</p>
        </div>
        
        <form id="evaluationForm" class="space-y-4">
            <?php foreach ($criteria as $idx => $criterion): 
                $existingValue = isset($existingScores[$criterion['id']]) ? $existingScores[$criterion['id']]['value'] : 3;
            ?>
            <div class="bg-white rounded-xl p-4 border-2 border-gray-300 hover:border-cyan-500 transition-all">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="px-2 py-0.5 bg-<?= $roleColor ?>-500 text-white rounded text-xs font-bold">C<?= $idx + 1 ?></span>
                            <h6 class="text-gray-900 font-bold"><?= htmlspecialchars($criterion['name']) ?></h6>
                        </div>
                        <?php if (!empty($criterion['description'])): ?>
                        <p class="text-gray-900 text-sm"><?= htmlspecialchars($criterion['description']) ?></p>
                        <?php endif; ?>
                        <div class="flex items-center gap-2 mt-2">
                            <span class="text-xs text-gray-500">Bobot:</span>
                            <span class="text-xs font-bold text-<?= $roleColor ?>-500"><?= number_format($criterion['weight'] * 100, 0) ?>%</span>
                            <span class="text-xs text-gray-500 ml-2">Tipe:</span>
                            <span class="text-xs font-bold <?= $criterion['type'] === 'benefit' ? 'text-emerald-500' : 'text-amber-500' ?>">
                                <?= ucfirst($criterion['type']) ?>
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="range" name="scores[<?= $criterion['id'] ?>]" 
                               id="score_<?= $criterion['id'] ?>" 
                               min="1" max="5" step="1" 
                               value="<?= $existingValue ?>"
                               class="w-32 h-2 bg-slate-700 rounded-lg appearance-none cursor-pointer accent-<?= $roleColor ?>-500"
                               oninput="updateScoreDisplay(<?= $criterion['id'] ?>, this.value)">
                        <span id="scoreDisplay_<?= $criterion['id'] ?>" 
                              class="w-12 text-center px-3 py-1.5 bg-<?= $roleColor ?>-500 text-white rounded-lg font-bold text-lg">
                            <?= $existingValue ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </form>
        
        <!-- Action Buttons -->
        <div class="mt-6 flex flex-wrap items-center gap-3">
            <button onclick="saveScores()" class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 text-white font-bold hover:from-emerald-400 hover:to-emerald-500 transition-all shadow-lg shadow-emerald-500/25">
                <?= icon('save', 'w-4 h-4') ?> Simpan & Hitung TOPSIS
            </button>
            <button onclick="resetScores()" type="button" class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gray-600 text-white font-bold hover:bg-gray-700 transition-all">
                <?= icon('refresh', 'w-4 h-4') ?> Reset
            </button>
        </div>
        
        <!-- Result Display -->
        <div id="evaluationResult" class="mt-4"></div>
        
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php renderFooter(); ?>

<?php if ($selectedProject && count($criteria) >= 1): ?>
<script>
const criteriaIds = <?= json_encode(array_column($criteria, 'id')) ?>;
const projectId = <?= $selectedProjectId ?>;

function updateScoreDisplay(criteriaId, value) {
    const display = document.getElementById('scoreDisplay_' + criteriaId);
    if (display) {
        display.textContent = value;
    }
}

function resetScores() {
    const inputs = document.querySelectorAll('input[type="range"]');
    inputs.forEach(input => {
        input.value = 3;
        const criteriaId = input.name.match(/\[(\d+)\]/)[1];
        updateScoreDisplay(criteriaId, 3);
    });
}

function saveScores() {
    const form = document.getElementById('evaluationForm');
    const formData = new FormData(form);
    const scores = {};
    
    // Collect scores
    for (const [key, value] of formData.entries()) {
        const match = key.match(/scores\[(\d+)\]/);
        if (match) {
            scores[match[1]] = parseInt(value);
        }
    }
    
    // Validate all criteria have scores
    if (Object.keys(scores).length !== criteriaIds.length) {
        alert('Harap isi semua kriteria!');
        return;
    }
    
    // Send to server
    fetch('api/handler.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=save_scores&project_id=' + projectId + 
              '&scores=' + encodeURIComponent(JSON.stringify(scores))
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Show modal popup for success with TOPSIS result
            showTopsisResultModal(data);
        } else {
            // Show error in result div
            const resultDiv = document.getElementById('evaluationResult');
            resultDiv.innerHTML = '<div class="p-4 bg-red-500/20 border border-red-500/50 rounded-xl text-red-400">' + 
                '<strong>Error:</strong> ' + data.message + '</div>';
        }
    })
    .catch(err => {
        document.getElementById('evaluationResult').innerHTML = 
            '<div class="p-4 bg-red-500/20 border border-red-500/50 rounded-xl text-red-400">' +
            'Gagal menyimpan: ' + err.message + '</div>';
    });
}

function showTopsisResultModal(data) {
    // Create modal HTML
    let modalHtml = '<div id="saveResultModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">';
    modalHtml += '<div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="closeSaveResultModal()"></div>';
    modalHtml += '<div class="relative bg-gradient-to-br from-emerald-50 to-cyan-50 border border-emerald-200 rounded-2xl p-6 max-w-md w-full animate-scale-in">';
    modalHtml += '<div class="text-center">';
    
    // Success icon
    modalHtml += '<div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gradient-to-br from-emerald-500 to-cyan-500 flex items-center justify-center">';
    modalHtml += '<svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
    modalHtml += '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
    modalHtml += '</div>';
    
    // Title
    modalHtml += '<h3 class="text-xl font-bold text-gray-900 mb-2">' + data.message + '</h3>';
    
    // TOPSIS result if available
    if (data.topsis_calculated && data.topsis_score !== null) {
        modalHtml += '<div class="grid grid-cols-2 gap-4 mt-6 mb-6">';
        modalHtml += '<div class="bg-white rounded-xl p-4 border border-gray-200">';
        modalHtml += '<p class="text-gray-900 text-xs uppercase tracking-wider mb-1">Skor TOPSIS</p>';
        modalHtml += '<p class="text-2xl font-bold text-cyan-400">' + Number(data.topsis_score).toFixed(4) + '</p>';
        modalHtml += '</div>';
        modalHtml += '<div class="bg-white rounded-xl p-4 border border-gray-200">';
        modalHtml += '<p class="text-gray-900 text-xs uppercase tracking-wider mb-1">Ranking</p>';
        modalHtml += '<p class="text-2xl font-bold text-emerald-400">#' + data.rank + ' <span class="text-sm text-gray-500">/ ' + data.total_projects + '</span></p>';
        modalHtml += '</div>';
        modalHtml += '</div>';
    }
    
    // Buttons
    modalHtml += '<div class="flex gap-3">';
    modalHtml += '<button onclick="closeSaveResultModal()" class="flex-1 px-4 py-2 rounded-lg bg-white border border-gray-200 text-gray-900 hover:border-gray-300 transition-all duration-300 font-bold text-sm">';
    modalHtml += 'Tutup</button>';
    modalHtml += '<a href="topsis_results.php" class="flex-1 px-4 py-2 rounded-lg bg-gradient-to-r from-cyan-500 to-cyan-600 text-white hover:from-cyan-400 hover:to-cyan-500 transition-all duration-300 font-bold text-sm text-center">';
    modalHtml += 'Lihat Hasil TOPSIS Lengkap →</a>';
    modalHtml += '</div>';
    
    modalHtml += '</div></div></div>';
    
    // Append to body
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    document.body.style.overflow = 'hidden';
}

function closeSaveResultModal() {
    const modal = document.getElementById('saveResultModal');
    if (modal) {
        modal.querySelector('.relative').style.animation = 'scaleOut 0.2s ease-out forwards';
        setTimeout(() => {
            modal.remove();
            document.body.style.overflow = '';
        }, 200);
    }
}
</script>
<?php endif; ?>







