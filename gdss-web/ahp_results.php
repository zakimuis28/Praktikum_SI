<?php
/**
 * AHP Results Page
 * Halaman untuk menampilkan hasil AHP dan validasi konsistensi
 */

require_once 'config.php';
require_once 'functions.php';

// Cek login
if (!isLoggedIn()) {
    redirect('index.php');
}

$user = getCurrentUser();
$userRole = $user['role'];

// Supervisor tidak bisa evaluasi
if (hasRole('supervisor')) {
    setFlashMessage('info', 'Supervisor tidak dapat melihat hasil evaluasi individual. Silakan cek halaman Hasil untuk melihat hasil konsensus.');
    redirect('results.php');
}

$success = '';
$error = '';

// Calculate AHP results
$criteriaComparisons = getPairwiseComparisons($user['id'], 'criteria');
$criteria = getCriteriaByPart($userRole);
$projects = getAllProjects();

// Build criteria matrix and calculate priorities
$criteriaMatrix = [];
$criteriaIds = array_column($criteria, 'id');
$criteriaCount = count($criteriaIds);

if ($criteriaCount >= 2 && !empty($criteriaComparisons)) {
    $criteriaMatrix = buildPairwiseMatrix($criteriaComparisons, $criteriaIds);
    $criteriaPriorities = calculatePriorityVector($criteriaMatrix);
    $criteriaConsistency = calculateConsistency($criteriaMatrix, $criteriaPriorities);
    
    // Create indexed priorities array for criteria
    $indexedCriteriaPriorities = [];
    foreach ($criteriaIds as $index => $criteriaId) {
        $indexedCriteriaPriorities[$criteriaId] = $criteriaPriorities[$index];
    }
} else {
    $criteriaPriorities = [];
    $indexedCriteriaPriorities = [];
    $criteriaConsistency = ['CI' => 0, 'CR' => 0, 'is_consistent' => false];
}

// Calculate alternative priorities for each criterion
$alternativePriorities = [];
$alternativeConsistency = [];

foreach ($criteriaIds as $criteriaId) {
    $altComparisons = getPairwiseComparisons($user['id'], 'alternatives', $criteriaId);
    $projectIds = array_column($projects, 'id');
    
    if (!empty($altComparisons) && count($projectIds) >= 2) {
        $altMatrix = buildPairwiseMatrix($altComparisons, $projectIds);
        $altPriorities = calculatePriorityVector($altMatrix);
        
        // Create indexed priorities array for alternatives
        $indexedAltPriorities = [];
        foreach ($projectIds as $index => $projectId) {
            $indexedAltPriorities[$projectId] = $altPriorities[$index];
        }
        
        $alternativePriorities[$criteriaId] = $indexedAltPriorities;
        $alternativeConsistency[$criteriaId] = calculateConsistency($altMatrix, $altPriorities);
    }
}

// Calculate global scores
$globalScores = [];
if (!empty($indexedCriteriaPriorities) && !empty($alternativePriorities)) {
    $globalScores = calculateAHPGlobalScores($indexedCriteriaPriorities, $alternativePriorities);
}

// Save AHP results if calculation is complete
if (!empty($globalScores)) {
    foreach ($globalScores as $projectId => $score) {
        saveAHPResult($user['id'], $projectId, $score);
    }
}

// Check overall consistency
$overallConsistent = $criteriaConsistency['is_consistent'];
foreach ($alternativeConsistency as $consistency) {
    if (!$consistency['is_consistent']) {
        $overallConsistent = false;
        break;
    }
}

// Handle recalculation request
if ($_POST && isset($_POST['recalculate'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (validateCSRFToken($csrf_token)) {
        setFlashMessage('info', 'Silakan lakukan perbaikan perbandingan yang tidak konsisten');
        redirect('ahp_comparison.php?step=criteria');
    }
}

// Get flash messages
$flashMessages = getFlashMessages();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Hasil AHP</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    
    <!-- Chart.js for visualization -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #F8FAFC;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white" style="box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
                <img src="assets/images/logo.svg" alt="GDSS Logo" width="32" height="32" class="me-2">
                <span style="font-family: 'Poppins', sans-serif; font-weight: 600; color: #3B82F6;">GDSS</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <!-- Management Section -->
                    <?php if (hasRole('admin')): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" style="font-family: 'Poppins', sans-serif; color: #64748B; font-weight: 500;">
                            <i class="bi bi-speedometer2 me-1"></i>Management
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="dashboard.php">
                                    <i class="bi bi-house me-1"></i>Dashboard
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="projects.php">
                                    <i class="bi bi-folder me-1"></i>Kelola Proyek
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    
                    <!-- Evaluation Section (Non-admin only) -->
                    <?php if (!hasRole('admin')): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown" style="font-family: 'Poppins', sans-serif; color: #3B82F6; font-weight: 500;">
                            <i class="bi bi-diagram-2 me-1"></i>Evaluasi
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="evaluate.php">
                                    <i class="bi bi-list-ol me-1"></i>Evaluasi BORDA
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item active" href="ahp_comparison.php">
                                    <i class="bi bi-diagram-2 me-1"></i>Evaluasi AHP
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    
                    <!-- Results Section -->
                    <li class="nav-item">
                        <a class="nav-link" href="results.php" style="font-family: 'Poppins', sans-serif; color: #64748B; font-weight: 500;">
                            <i class="bi bi-trophy me-1"></i>Hasil
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i>
                            <?= escape($user['fullname']) ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><h6 class="dropdown-header">
                                <i class="bi bi-shield-check me-1"></i>
                                <?= ucfirst($user['role']) ?>
                            </h6></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="bi bi-box-arrow-right me-1"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid mt-4">
        <!-- Flash Messages -->
        <?php if (!empty($flashMessages)): ?>
            <?php foreach ($flashMessages as $type => $messages): ?>
                <?php foreach ($messages as $message): ?>
                    <div class="alert alert-<?= $type === 'error' ? 'danger' : $type ?> alert-dismissible fade show">
                        <i class="bi bi-<?= $type === 'success' ? 'check-circle' : ($type === 'error' ? 'exclamation-triangle' : 'info-circle') ?> me-2"></i>
                        <?= escape($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-1">
                                    <i class="bi bi-bar-chart me-2"></i>
                                    Hasil AHP - <?= ucfirst($userRole) ?>
                                </h4>
                                <p class="text-muted mb-0">
                                    Analisis hasil perbandingan berpasangan dan validasi konsistensi
                                </p>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-<?= $overallConsistent ? 'success' : 'warning' ?> fs-6">
                                    <i class="bi bi-<?= $overallConsistent ? 'check-circle' : 'exclamation-triangle' ?> me-1"></i>
                                    <?= $overallConsistent ? 'Konsisten' : 'Perlu Perbaikan' ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (empty($criteriaComparisons)): ?>
        <!-- No Comparisons Available -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-exclamation-circle display-1 text-muted"></i>
                        <h5 class="mt-3 text-muted">Belum Ada Perbandingan</h5>
                        <p class="text-muted">
                            Anda belum melakukan perbandingan berpasangan AHP.<br>
                            Silakan mulai dengan perbandingan kriteria terlebih dahulu.
                        </p>
                        <a href="ahp_comparison.php?step=criteria" class="btn btn-primary btn-lg">
                            <i class="bi bi-diagram-2 me-2"></i>
                            Mulai Evaluasi AHP
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <?php else: ?>

        <!-- Consistency Check Summary -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-shield-check me-1"></i>
                            Validasi Konsistensi Kriteria
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <div class="mb-2">
                                    <small class="text-muted">Consistency Index (CI)</small>
                                    <div class="fw-bold"><?= number_format($criteriaConsistency['CI'], 4) ?></div>
                                </div>
                                <div>
                                    <small class="text-muted">Consistency Ratio (CR)</small>
                                    <div class="fw-bold">
                                        <?= number_format($criteriaConsistency['CR'], 4) ?>
                                        <span class="badge bg-<?= $criteriaConsistency['is_consistent'] ? 'success' : 'danger' ?> ms-2">
                                            <?= $criteriaConsistency['CR'] <= 0.1 ? '≤ 0.1' : '> 0.1' ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-4 text-center">
                                <div class="circular-progress" data-percent="<?= min(100, (1 - $criteriaConsistency['CR']) * 100) ?>">
                                    <i class="bi bi-<?= $criteriaConsistency['is_consistent'] ? 'check-circle text-success' : 'x-circle text-danger' ?>" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-list-check me-1"></i>
                            Status Konsistensi Alternatif
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($alternativeConsistency)): ?>
                            <?php 
                            $consistentCount = 0;
                            foreach ($alternativeConsistency as $consistency) {
                                if ($consistency['is_consistent']) $consistentCount++;
                            }
                            ?>
                            <div class="progress mb-3" style="height: 20px;">
                                <div class="progress-bar bg-success" 
                                     style="width: <?= count($alternativeConsistency) > 0 ? ($consistentCount / count($alternativeConsistency)) * 100 : 0 ?>%">
                                    <span class="fw-bold"><?= $consistentCount ?>/<?= count($alternativeConsistency) ?></span>
                                </div>
                            </div>
                            
                            <div class="small">
                                <?php foreach ($criteria as $criterion): ?>
                                    <?php if (isset($alternativeConsistency[$criterion['id']])): ?>
                                        <?php $consistency = $alternativeConsistency[$criterion['id']]; ?>
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="text-truncate" style="max-width: 60%;"><?= escape($criterion['name']) ?></span>
                                            <span class="badge bg-<?= $consistency['is_consistent'] ? 'success' : 'warning' ?>">
                                                CR: <?= number_format($consistency['CR'], 3) ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted">
                                <i class="bi bi-clock"></i>
                                Belum ada perbandingan alternatif
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Criteria Priorities -->
        <div class="row mb-4">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-bar-chart me-1"></i>
                            Bobot Prioritas Kriteria
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($indexedCriteriaPriorities)): ?>
                            <div class="table-responsive mb-3">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Kriteria</th>
                                            <th class="text-center">Bobot</th>
                                            <th class="text-center">Persentase</th>
                                            <th>Visualisasi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($criteria as $criterion): ?>
                                            <?php if (isset($indexedCriteriaPriorities[$criterion['id']])): ?>
                                                <?php 
                                                $priority = $indexedCriteriaPriorities[$criterion['id']];
                                                $percentage = $priority * 100;
                                                ?>
                                                <tr>
                                                    <td>
                                                        <div class="fw-bold"><?= escape($criterion['name']) ?></div>
                                                        <small class="text-muted"><?= escape($criterion['description']) ?></small>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="fw-bold"><?= number_format($priority, 4) ?></span>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge bg-primary"><?= number_format($percentage, 1) ?>%</span>
                                                    </td>
                                                    <td>
                                                        <div class="progress" style="height: 8px;">
                                                            <div class="progress-bar bg-primary" 
                                                                 style="width: <?= $percentage ?>%"></div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Criteria Chart -->
                            <canvas id="criteriaChart" style="max-height: 300px;"></canvas>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-exclamation-triangle"></i>
                                Data prioritas kriteria tidak dapat dihitung
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Global Rankings -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-trophy me-1"></i>
                            Peringkat Global
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($globalScores)): ?>
                            <?php 
                            arsort($globalScores);
                            $rank = 1;
                            ?>
                            <?php foreach ($globalScores as $projectId => $score): ?>
                                <?php 
                                $project = null;
                                foreach ($projects as $p) {
                                    if ($p['id'] == $projectId) {
                                        $project = $p;
                                        break;
                                    }
                                }
                                ?>
                                <?php if ($project): ?>
                                    <div class="d-flex align-items-center mb-3 <?= $rank === 1 ? 'border-start border-warning border-4 ps-3' : '' ?>">
                                        <div class="me-3">
                                            <span class="badge bg-<?= $rank === 1 ? 'warning' : ($rank === 2 ? 'secondary' : 'light') ?> text-<?= $rank <= 2 ? 'dark' : 'muted' ?> fs-6">
                                                #<?= $rank ?>
                                            </span>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold"><?= escape($project['name']) ?></div>
                                            <div class="small text-muted">
                                                Skor: <?= number_format($score, 4) ?>
                                            </div>
                                            <div class="progress mt-1" style="height: 4px;">
                                                <div class="progress-bar bg-<?= $rank === 1 ? 'warning' : 'primary' ?>" 
                                                     style="width: <?= ($score / max($globalScores)) * 100 ?>%"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php $rank++; ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-muted">
                                <i class="bi bi-clock"></i>
                                <div class="mt-2">Menunggu hasil perhitungan</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!$overallConsistent): ?>
        <!-- Inconsistency Warning -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-warning">
                    <div class="d-flex align-items-start">
                        <div class="me-3">
                            <i class="bi bi-exclamation-triangle fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="alert-heading">Peringatan: Ketidakkonsistenan Terdeteksi</h6>
                            <p class="mb-3">
                                Beberapa perbandingan Anda tidak konsisten (CR > 0.1). Hal ini dapat mempengaruhi 
                                keandalan hasil AHP. Disarankan untuk meninjau kembali perbandingan yang tidak konsisten.
                            </p>
                            
                            <div class="mb-3">
                                <strong>Yang perlu diperbaiki:</strong>
                                <ul class="mb-0 mt-2">
                                    <?php if (!$criteriaConsistency['is_consistent']): ?>
                                        <li>Perbandingan kriteria (CR: <?= number_format($criteriaConsistency['CR'], 4) ?>)</li>
                                    <?php endif; ?>
                                    <?php foreach ($alternativeConsistency as $criteriaId => $consistency): ?>
                                        <?php if (!$consistency['is_consistent']): ?>
                                            <?php 
                                            $criterion = null;
                                            foreach ($criteria as $c) {
                                                if ($c['id'] == $criteriaId) {
                                                    $criterion = $c;
                                                    break;
                                                }
                                            }
                                            ?>
                                            <?php if ($criterion): ?>
                                                <li>Perbandingan alternatif untuk "<?= escape($criterion['name']) ?>" (CR: <?= number_format($consistency['CR'], 4) ?>)</li>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <button type="submit" name="recalculate" class="btn btn-warning">
                                    <i class="bi bi-arrow-repeat me-1"></i>
                                    Perbaiki Perbandingan
                                </button>
                                <small class="text-muted ms-2">
                                    Akan kembali ke halaman perbandingan untuk diperbaiki
                                </small>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex gap-3 flex-wrap">
                            <a href="ahp_comparison.php?step=criteria" class="btn btn-outline-primary">
                                <i class="bi bi-pencil me-1"></i>
                                Edit Perbandingan Kriteria
                            </a>
                            
                            <?php if (!empty($criteria)): ?>
                                <a href="ahp_comparison.php?step=alternatives&criteria=<?= $criteria[0]['id'] ?>" class="btn btn-outline-secondary">
                                    <i class="bi bi-diagram-2 me-1"></i>
                                    Edit Perbandingan Alternatif
                                </a>
                            <?php endif; ?>
                            
                            <a href="results.php" class="btn btn-success">
                                <i class="bi bi-trophy me-1"></i>
                                Lihat Hasil Konsensus
                            </a>
                            
                            <button onclick="window.print()" class="btn btn-outline-info">
                                <i class="bi bi-printer me-1"></i>
                                Cetak Hasil
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php endif; ?>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                if (alert.classList.contains('alert-success') || alert.classList.contains('alert-info')) {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.remove();
                    }, 500);
                }
            });
        }, 8000);

        // Criteria Chart
        <?php if (!empty($indexedCriteriaPriorities)): ?>
        const ctx = document.getElementById('criteriaChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: [
                    <?php foreach ($criteria as $criterion): ?>
                        <?php if (isset($indexedCriteriaPriorities[$criterion['id']])): ?>
                            '<?= escape($criterion['name']) ?>',
                        <?php endif; ?>
                    <?php endforeach; ?>
                ],
                datasets: [{
                    data: [
                        <?php foreach ($criteria as $criterion): ?>
                            <?php if (isset($indexedCriteriaPriorities[$criterion['id']])): ?>
                                <?= $indexedCriteriaPriorities[$criterion['id']] ?>,
                            <?php endif; ?>
                        <?php endforeach; ?>
                    ],
                    backgroundColor: [
                        '#0d6efd', '#6610f2', '#6f42c1', '#d63384', 
                        '#dc3545', '#fd7e14', '#ffc107', '#198754',
                        '#20c997', '#0dcaf0'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed;
                                const percentage = (value * 100).toFixed(1);
                                return label + ': ' + percentage + '%';
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>

        // Print styles
        const printStyles = `
            <style>
                @media print {
                    .navbar, .btn, .alert .btn-close { display: none !important; }
                    .card { border: 1px solid #ddd !important; box-shadow: none !important; }
                    .card-header { background: #f8f9fa !important; }
                    body { background: white !important; }
                    .alert { border: 1px solid #ddd !important; }
                }
            </style>
        `;
        document.head.insertAdjacentHTML('beforeend', printStyles);

        // Consistency indicator animation
        document.addEventListener('DOMContentLoaded', function() {
            const indicators = document.querySelectorAll('.circular-progress');
            indicators.forEach(indicator => {
                const percent = indicator.getAttribute('data-percent');
                if (percent > 70) {
                    indicator.style.animation = 'pulse 2s infinite';
                }
            });
        });

        // Real-time updates (if needed)
        function refreshResults() {
            // Could be implemented to auto-refresh results
            // Currently just shows last calculation
        }

        // Tooltips for consistency ratios
        document.addEventListener('DOMContentLoaded', function() {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        // Export functionality (could be enhanced)
        function exportResults() {
            const content = document.querySelector('.container-fluid').innerHTML;
            const newWindow = window.open('', '_blank');
            newWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Hasil AHP - <?= escape($user['fullname']) ?></title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                    <style>
                        body { padding: 20px; }
                        .navbar, .btn { display: none; }
                    </style>
                </head>
                <body>
                    ${content}
                </body>
                </html>
            `);
            newWindow.document.close();
            newWindow.print();
        }
    </script>
</body>
</html>