<?php
/**
 * =====================================================
 * Progress Page - Admin Only
 * GDSS System Evaluation Progress Monitoring
 * =====================================================
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/layout.php';
require_once __DIR__ . '/../../controllers/project_controller.php';
require_once __DIR__ . '/../../controllers/score_controller.php';
require_once __DIR__ . '/../../controllers/topsis_controller.php';
requireRole('admin');

$projectController = new ProjectController();
$scoreController = new ScoreController();
$topsisController = new TopsisController();
$pdo = getConnection();

// Get all projects
$projects = $projectController->getAllProjects();

// Get all decision makers
$stmt = $pdo->prepare("SELECT * FROM users WHERE role IN ('supervisor', 'teknis', 'keuangan') ORDER BY role, name");
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

// Calculate overall stats
$totalEvaluations = count($projects) * count($decisionMakers);
$completedEvaluations = 0;
foreach ($progressData as $data) {
    foreach ($data['evaluators'] as $eval) {
        if ($eval['has_scores']) $completedEvaluations++;
    }
}
$completionRate = $totalEvaluations > 0 ? ($completedEvaluations / $totalEvaluations) * 100 : 0;

$pageTitle = 'Progress Evaluasi - ' . SITE_NAME;
$flashMessage = getFlashMessage();

// Render page
renderHead($pageTitle, '../../');
renderSidebar('../../', 'progress');
renderHeader('PROGRESS EVALUASI', 'Pantau kemajuan evaluasi dari semua Decision Maker', 'chart', false, '', '../../');
echo flashBox($flashMessage);
?>

<!-- Statistics Cards -->
<section class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <?= statCard('Total Proyek', count($projects), 'projects', 'cyan') ?>
    <?= statCard('Decision Maker', count($decisionMakers), 'users', 'emerald') ?>
    <?= statCard('Evaluasi Selesai', $completedEvaluations . '/' . $totalEvaluations, 'check', 'amber') ?>
    <?= statCard('Completion Rate', number_format($completionRate, 1) . '%', 'chart', 'purple') ?>
</section>

<!-- Overall Progress Bar -->
<section class="mb-8 bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-200 p-6">
    <h5 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
        <?= icon('chart', 'w-5 h-5 text-cyan-400') ?>
        Progress Keseluruhan
    </h5>
    <div class="flex items-center justify-between text-sm text-gray-900 mb-2">
        <span>Evaluasi Selesai</span>
        <span><?= $completedEvaluations ?>/<?= $totalEvaluations ?></span>
    </div>
    <div class="h-6 bg-white rounded-full overflow-hidden">
        <div class="h-full bg-gradient-to-r from-cyan-500 to-emerald-500 rounded-full transition-all duration-500 flex items-center justify-center text-xs font-bold text-white" 
             style="width: <?= $completionRate ?>%">
            <?= number_format($completionRate, 1) ?>%
        </div>
    </div>
</section>

<!-- Decision Maker Legend -->
<section class="mb-8 bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-200 p-6">
    <h5 class="text-lg font-bold text-gray-900 mb-4">Decision Makers</h5>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <?php foreach (['supervisor' => 'purple', 'teknis' => 'cyan', 'keuangan' => 'amber'] as $role => $color): 
            $dms = array_filter($decisionMakers, fn($d) => $d['role'] === $role);
        ?>
        <div class="p-4 bg-<?= $color ?>-500/10 border border-<?= $color ?>-500/30 rounded-xl">
            <h6 class="text-<?= $color ?>-400 font-bold uppercase tracking-wider text-sm mb-2"><?= ucfirst($role) ?></h6>
            <?php foreach ($dms as $dm): ?>
            <p class="text-gray-900 text-sm"><?= htmlspecialchars($dm['name']) ?></p>
            <?php endforeach; ?>
            <?php if (empty($dms)): ?>
            <p class="text-gray-500 text-sm italic">Belum ada</p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Progress Table -->
<section class="bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-200">
    <div class="flex items-center justify-between border-b border-gray-200/50 px-6 py-4">
        <h5 class="text-lg font-bold text-gray-900 flex items-center gap-2">
            <?= icon('criteria', 'w-5 h-5 text-cyan-400') ?>
            Detail Progress per Proyek
        </h5>
    </div>
    <div class="p-6">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Proyek</th>
                        <?php foreach ($decisionMakers as $dm): ?>
                        <th class="text-center py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">
                            <?= htmlspecialchars(substr($dm['name'], 0, 10)) ?>
                            <br><span class="text-<?= ['supervisor' => 'purple', 'teknis' => 'cyan', 'keuangan' => 'amber'][$dm['role']] ?>-400"><?= ucfirst($dm['role']) ?></span>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($progressData as $projectId => $data): ?>
                    <tr class="border-b border-gray-200/50 hover:bg-gray-100 transition-colors duration-200">
                        <td class="py-4 px-4">
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
                        <td class="py-4 px-4 text-center">
                            <?php if ($eval && $eval['has_topsis']): ?>
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-emerald-500 text-white rounded-full text-xs font-bold shadow-md">
                                <?= icon('check', 'w-3 h-3') ?> TOPSIS
                            </span>
                            <?php elseif ($eval && $eval['has_scores']): ?>
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-amber-500 text-white rounded-full text-xs font-bold shadow-md">
                                <?= icon('clock', 'w-3 h-3') ?> Scored
                            </span>
                            <?php else: ?>
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-gray-500 text-white rounded-full text-xs font-bold shadow-md">
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

<!-- Legend -->
<section class="mt-6 flex flex-wrap gap-4 text-sm">
    <div class="flex items-center gap-2">
        <span class="px-3 py-1 bg-emerald-500/20 text-emerald-400 rounded-full text-xs font-bold">TOPSIS</span>
        <span class="text-gray-900">= Evaluasi & TOPSIS selesai</span>
    </div>
    <div class="flex items-center gap-2">
        <span class="px-3 py-1 bg-amber-500/20 text-amber-400 rounded-full text-xs font-bold">Scored</span>
        <span class="text-gray-900">= Sudah dinilai, belum TOPSIS</span>
    </div>
    <div class="flex items-center gap-2">
        <span class="px-3 py-1 bg-slate-700 text-white rounded-full text-xs font-bold">Pending</span>
        <span class="text-gray-900">= Belum evaluasi</span>
    </div>
</section>

<?php renderFooter(); ?>






