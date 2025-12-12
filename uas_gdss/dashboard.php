<?php
/**
 * Dashboard - GDSS System
 * Clean dashboard using shared layout
 */
require_once __DIR__ . '/config/config.php';
requireLogin();

// Include shared layout
require_once __DIR__ . '/includes/layout.php';

$flashMessage = getFlashMessage();
$pageTitle = 'Dashboard - ' . SITE_NAME;
$userRole = $_SESSION['role'];
$userName = $_SESSION['name'];
$isAdmin = hasRole('admin');
$isDecisionMaker = hasRole('supervisor') || hasRole('teknis') || hasRole('keuangan');

// Get statistics
$db = getConnection();
$totalUsers = 0;
$totalProjects = 0;
$totalCriteria = 0;
$completedEvaluations = 0;
$pendingEvaluations = 0;

try {
    // Total projects
    $stmt = $db->query("SELECT COUNT(*) FROM projects");
    $totalProjects = $stmt->fetchColumn();
    
    if ($isAdmin) {
        // Total users (excluding admin)
        $stmt = $db->query("SELECT COUNT(*) FROM users WHERE role != 'admin'");
        $totalUsers = $stmt->fetchColumn();
        
        // Total criteria (all fields)
        $stmt = $db->query("SELECT COUNT(*) FROM criteria");
        $totalCriteria = $stmt->fetchColumn();
        
        // Completed evaluations
        $stmt = $db->query("SELECT COUNT(DISTINCT CONCAT(user_id, '-', project_id)) FROM scores");
        $completedEvaluations = $stmt->fetchColumn();
        
        // Get all decision makers for progress tracking
        require_once __DIR__ . '/controllers/project_controller.php';
        require_once __DIR__ . '/controllers/score_controller.php';
        require_once __DIR__ . '/controllers/topsis_controller.php';
        
        $projectController = new ProjectController();
        $scoreController = new ScoreController();
        $topsisController = new TopsisController();
        
        $projects = $projectController->getAllProjects();
        $stmt = $db->prepare("SELECT * FROM users WHERE role IN ('supervisor', 'teknis', 'keuangan') ORDER BY role, name");
        $stmt->execute();
        $decisionMakers = $stmt->fetchAll();
        
        // Get evaluation progress for each project and decision maker
        $progressData = [];
        foreach ($projects as $project) {
            $progressData[$project['id']] = [
                'project' => $project,
                'evaluators' => []
            ];
            
            foreach ($decisionMakers as $dm) {
                $scores = $scoreController->getUserScores($project['id'], $dm['id']);
                $topsisResult = $topsisController->getTopsisResults($dm['role']);
                $hasTopsis = false;
                if (!empty($topsisResult)) {
                    foreach ($topsisResult as $r) {
                        if ($r['project_id'] == $project['id']) {
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
        
        $totalEvaluations = count($projects) * count($decisionMakers);
        $completedEvaluationsCount = 0;
        foreach ($progressData as $data) {
            foreach ($data['evaluators'] as $eval) {
                if ($eval['has_scores']) $completedEvaluationsCount++;
            }
        }
        $completionRate = $totalEvaluations > 0 ? ($completedEvaluationsCount / $totalEvaluations) * 100 : 0;
    } else {
        // User-specific stats
        $userId = $_SESSION['user_id'];
        $roleField = $userRole;
        
        // Criteria for this role
        $stmt = $db->prepare("SELECT COUNT(*) FROM criteria WHERE field = ?");
        $stmt->execute([$roleField]);
        $totalCriteria = $stmt->fetchColumn();
        
        // Completed evaluations for this user
        $stmt = $db->prepare("SELECT COUNT(DISTINCT project_id) FROM scores WHERE user_id = ?");
        $stmt->execute([$userId]);
        $completedEvaluations = $stmt->fetchColumn();
        
        // Pending evaluations
        $pendingEvaluations = max(0, $totalProjects - $completedEvaluations);
    }
} catch (Exception $e) {
    // Use defaults on error
}

// Render layout
renderHead($pageTitle, '');
renderSidebar('', 'dashboard');
renderHeader('DASHBOARD', 'Real-time Analytics', 'home');
?>

<!-- Flash Messages -->
<?= flashBox($flashMessage) ?>

<?php if (isset($_GET['access_denied'])): ?>
    <?= flashBox(['type' => 'danger', 'message' => 'ACCESS DENIED - Anda tidak memiliki izin untuk mengakses halaman tersebut']) ?>
<?php endif; ?>

<!-- Welcome Section -->
<section class="mb-8 bg-white rounded-2xl border border-gray-200 p-8 hover:border-cyan-500 transition-all duration-500">
    <div class="flex flex-col md:flex-row items-center gap-6">
        <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-2xl flex items-center justify-center shadow-xl shadow-blue-500/20">
            <?= icon('bolt', 'w-10 h-10 text-white') ?>
        </div>
        <div class="text-center md:text-left flex-1">
            <h3 class="text-3xl font-black bg-gradient-to-r from-blue-500 to-cyan-500 bg-clip-text text-transparent mb-2">
                WELCOME BACK
            </h3>
            <h4 class="text-xl font-bold text-gray-900 uppercase tracking-wide mb-2"><?= htmlspecialchars($userName) ?></h4>
            <p class="text-gray-600">
                <?php if ($isAdmin): ?>
                    Manage users, projects, criteria, and monitor system evaluations.
                <?php else: ?>
                    Provide project evaluations using TOPSIS methodology.
                <?php endif; ?>
            </p>
        </div>
    </div>
</section>

<!-- Statistics Cards -->
<section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
    <?php if ($isAdmin): ?>
        <?= statCard('Total Proyek', $totalProjects, 'projects', 'cyan') ?>
        <?= statCard('Decision Maker', count($decisionMakers), 'users', 'emerald') ?>
        <?= statCard('Evaluasi Selesai', $completedEvaluationsCount . '/' . $totalEvaluations, 'check', 'amber') ?>
        <?= statCard('Completion Rate', number_format($completionRate, 1) . '%', 'chart', 'purple') ?>
    <?php else: ?>
        <?= statCard('Total Proyek', $totalProjects, 'projects', 'cyan') ?>
        <?= statCard('Evaluasi Selesai', $completedEvaluations, 'check', 'emerald') ?>
        <?= statCard('Evaluasi Pending', $pendingEvaluations, 'clock', 'amber') ?>
        <?= statCard('Kriteria ' . ucfirst($userRole), $totalCriteria, 'criteria', 'purple') ?>
    <?php endif; ?>
</section>

<?php if ($isAdmin): ?>
<!-- Progress Keseluruhan (Admin Only) -->
<section class="mb-8 bg-white rounded-2xl border border-gray-200 p-6">
    <h5 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
        <?= icon('chart', 'w-5 h-5 text-cyan-400') ?>
        Progress Keseluruhan
    </h5>
    <div class="flex items-center justify-between text-sm text-gray-900 mb-2">
        <span>Evaluasi Selesai</span>
        <span><?= $completedEvaluationsCount ?>/<?= $totalEvaluations ?></span>
    </div>
    <div class="h-6 bg-white rounded-full overflow-hidden">
        <div class="h-full bg-gradient-to-r from-cyan-500 to-emerald-500 rounded-full transition-all duration-500 flex items-center justify-center text-xs font-bold text-white" 
             style="width: <?= $completionRate ?>%">
            <?= number_format($completionRate, 1) ?>%
        </div>
    </div>
</section>

<!-- Progress Table (Admin Only) -->
<section class="bg-white rounded-2xl border border-gray-200 mb-8">
    <div class="flex items-center justify-between border-b border-gray-200/50 px-6 py-4">
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
                            <?= htmlspecialchars(substr($dm['name'], 0, 10)) ?>
                            <br><span class="text-<?= ['supervisor' => 'purple', 'teknis' => 'cyan', 'keuangan' => 'amber'][$dm['role']] ?>-400"><?= ucfirst($dm['role']) ?></span>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($progressData as $projectId => $data): ?>
                    <tr class="border-b border-gray-200 hover:bg-gray-50 transition-colors duration-200">
                        <td class="py-4 px-4 border-r border-gray-200">
                            <div>
                                <span class="px-2 py-1 bg-cyan-500/20 text-cyan-400 rounded text-xs font-bold">
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
</section>

<!-- Legend (Admin Only) -->
<section class="mb-8 flex flex-wrap gap-4 text-sm">
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
<?php else: ?>

<!-- Progress Section with Visual Enhancement (Decision Maker) -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    
    <!-- Main Progress Card -->
    <section class="lg:col-span-2 bg-white rounded-2xl border border-gray-200 p-6 hover:border-cyan-500 transition-all duration-500">
        <div class="flex items-center justify-between mb-6">
            <h5 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                <?= icon('chart', 'w-5 h-5 text-cyan-400') ?>
                <?php if ($isAdmin): ?>
                    Statistik Evaluasi Sistem
                <?php else: ?>
                    Progress Evaluasi <?= ucfirst($userRole) ?>
                <?php endif; ?>
            </h5>
            <span class="px-3 py-1 bg-emerald-500 text-white rounded-full text-xs font-bold uppercase tracking-wider">
                Live
            </span>
        </div>
        
        <?php 
        $progress = $totalProjects > 0 ? ($completedEvaluations / $totalProjects) * 100 : 0;
        ?>
        
        <!-- Circular Progress Indicator -->
        <div class="flex items-center gap-8">
            <div class="relative w-32 h-32">
                <svg class="w-32 h-32 transform -rotate-90" viewBox="0 0 120 120">
                    <circle cx="60" cy="60" r="54" fill="none" stroke="#1e293b" stroke-width="8"/>
                    <circle cx="60" cy="60" r="54" fill="none" stroke="url(#progressGradient)" stroke-width="8" 
                            stroke-linecap="round" stroke-dasharray="339.292" 
                            stroke-dashoffset="<?= 339.292 * (1 - $progress / 100) ?>"
                            class="transition-all duration-1000"/>
                    <defs>
                        <linearGradient id="progressGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="#06b6d4"/>
                            <stop offset="100%" stop-color="#10b981"/>
                        </linearGradient>
                    </defs>
                </svg>
                <div class="absolute inset-0 flex items-center justify-center">
                    <span class="text-2xl font-black text-gray-900"><?= number_format($progress, 0) ?>%</span>
                </div>
            </div>
            
            <div class="flex-1 space-y-4">
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-900">Evaluasi Selesai</span>
                        <span class="text-emerald-400 font-bold"><?= $completedEvaluations ?></span>
                    </div>
                    <div class="h-2 bg-white rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-emerald-500 to-emerald-400 rounded-full transition-all duration-500" 
                             style="width: <?= $progress ?>%"></div>
                    </div>
                </div>
                
                <?php if (!$isAdmin): ?>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-900">Evaluasi Pending</span>
                        <span class="text-amber-400 font-bold"><?= $pendingEvaluations ?></span>
                    </div>
                    <div class="h-2 bg-white rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-amber-500 to-amber-400 rounded-full transition-all duration-500" 
                             style="width: <?= $totalProjects > 0 ? ($pendingEvaluations / $totalProjects) * 100 : 0 ?>%"></div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-900">Total Proyek</span>
                        <span class="text-cyan-400 font-bold"><?= $totalProjects ?></span>
                    </div>
                    <div class="h-2 bg-white rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-cyan-500 to-cyan-400 rounded-full" style="width: 100%"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- System Status Panel -->
    <section class="bg-white rounded-2xl border border-gray-200 p-6 hover:border-purple-500 transition-all duration-500">
        <h5 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
            <?= icon('bulb', 'w-5 h-5 text-purple-400') ?>
            System Status
        </h5>
        
        <div class="space-y-3">
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-gray-900 text-sm">Status Server</span>
                    <span class="flex items-center gap-2 text-emerald-400 text-sm font-bold">
                        <span class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></span>
                        Online
                    </span>
                </div>
                
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-gray-900 text-sm">Database</span>
                    <span class="flex items-center gap-2 text-emerald-400 text-sm font-bold">
                        <span class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></span>
                        Connected
                    </span>
                </div>
                
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-gray-900 text-sm">Last Update</span>
                    <span class="text-cyan-400 text-sm font-bold"><?= date('H:i') ?></span>
                </div>
        </div>
    </section>
</div>

<!-- Evaluation History Section (Decision Maker Only) -->
<?php if ($isDecisionMaker): 
    require_once __DIR__ . '/controllers/score_controller.php';
    $scoreController = new ScoreController();
    $evaluationHistory = $scoreController->getUserEvaluationHistory($_SESSION['user_id']);
    
    // Reverse array to show from earliest to latest
    $evaluationHistory = array_reverse($evaluationHistory);
    
    // Chart data
    $chartLabels = array_column($evaluationHistory, 'project_code');
    $chartScores = array_map(fn($e) => round($e['avg_score']), $evaluationHistory);
?>

<?php if (!empty($evaluationHistory)): ?>
<!-- Chart -->
<section class="mb-8 bg-white rounded-2xl border border-gray-200 p-6">
    <h5 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
        <?= icon('chart', 'w-5 h-5 text-cyan-400') ?>
        Trend Nilai Evaluasi
    </h5>
    <canvas id="evalChart" height="100"></canvas>
</section>

<!-- Evaluation History Table -->
<section class="bg-white rounded-2xl border border-gray-200">
    <div class="flex items-center justify-between border-b border-gray-200/50 px-6 py-4">
        <h5 class="text-lg font-bold text-gray-900 flex items-center gap-2">
            <?= icon('clock', 'w-5 h-5 text-cyan-400') ?>
            Riwayat Evaluasi
        </h5>
    </div>
    <div class="p-6">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">#</th>
                        <th class="text-left py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Kode</th>
                        <th class="text-left py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Nama Proyek</th>
                        <th class="text-center py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Jumlah Kriteria</th>
                        <th class="text-center py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Rata-rata Nilai</th>
                        <th class="text-center py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Tanggal</th>
                        <th class="text-center py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($evaluationHistory as $index => $evaluation): ?>
                    <tr class="border-b border-gray-200/50 hover:bg-gray-100 transition-colors duration-200">
                        <td class="py-4 px-4 text-gray-900"><?= $index + 1 ?></td>
                        <td class="py-4 px-4">
                            <span class="px-2 py-1 bg-cyan-500 text-white rounded text-xs font-bold">
                                <?= htmlspecialchars($evaluation['project_code']) ?>
                            </span>
                        </td>
                        <td class="py-4 px-4 text-gray-900 font-medium"><?= htmlspecialchars($evaluation['project_name']) ?></td>
                        <td class="py-4 px-4 text-center text-gray-900"><?= $evaluation['criteria_count'] ?? '-' ?></td>
                        <td class="py-4 px-4 text-center">
                            <span class="font-bold text-emerald-400"><?= round($evaluation['avg_score']) ?></span>
                        </td>
                        <td class="py-4 px-4 text-center text-gray-900 text-sm"><?= formatDate($evaluation['last_updated'] ?? $evaluation['created_at'] ?? '-') ?></td>
                        <td class="py-4 px-4 text-center">
                            <a href="evaluate.php?project_id=<?= $evaluation['project_id'] ?>" 
                               class="inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-gray-600 text-white hover:bg-gray-700 text-xs font-bold transition-colors duration-200">
                                <?= icon('edit', 'w-3 h-3') ?> Edit
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('evalChart');
    if (ctx) {
        new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: <?= json_encode($chartLabels) ?>,
                datasets: [{
                    label: 'Rata-rata Nilai',
                    data: <?= json_encode($chartScores) ?>,
                    borderColor: 'rgb(6, 182, 212)',
                    backgroundColor: 'rgba(6, 182, 212, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 6,
                    pointBackgroundColor: 'rgb(6, 182, 212)'
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
                        max: 5,
                        grid: { color: 'rgba(148, 163, 184, 0.1)' },
                        ticks: { color: '#94a3b8' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#94a3b8' }
                    }
                }
            }
        });
    }
});
</script>
<?php endif; ?>
<?php endif; ?>

<?php endif; ?>

<?php renderFooter(); ?>





