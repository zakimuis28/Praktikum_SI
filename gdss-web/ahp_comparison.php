<?php
/**
 * AHP Pairwise Comparison Page
 * Halaman untuk melakukan perbandingan berpasangan AHP
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
    setFlashMessage('info', 'Supervisor tidak dapat melakukan evaluasi AHP. Silakan login sebagai evaluator.');
    redirect('dashboard.php');
}

$step = $_GET['step'] ?? 'criteria';
$projectId = $_GET['project'] ?? null;
$criteriaId = $_GET['criteria'] ?? null;
$success = '';
$error = '';

// Handle form submission
if ($_POST && isset($_POST['save_comparisons'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!validateCSRFToken($csrf_token)) {
        $error = 'Token keamanan tidak valid';
    } else {
        $comparisons = $_POST['comparisons'] ?? [];
        $comparisonType = $_POST['comparison_type'] ?? '';
        $currentCriteriaId = $_POST['criteria_id'] ?? null;
        $currentProjectId = $_POST['project_id'] ?? null;
        
        if (!empty($comparisons)) {
            $savedCount = 0;
            $totalComparisons = count($comparisons);
            
            foreach ($comparisons as $key => $value) {
                list($i, $j) = explode('_', $key);
                
                if (is_numeric($value) && $value >= 1/9 && $value <= 9) {
                    if (savePairwiseComparison($user['id'], $comparisonType, $currentCriteriaId, $currentProjectId, $i, $j, $value)) {
                        $savedCount++;
                    }
                }
            }
            
            if ($savedCount === $totalComparisons) {
                setFlashMessage('success', 'Perbandingan berpasangan berhasil disimpan');
                
                // Redirect to next step or results
                if ($comparisonType === 'criteria') {
                    redirect('ahp_comparison.php?step=alternatives&project=' . ($projectId ?? ''));
                } else {
                    redirect('ahp_results.php');
                }
            } else {
                $error = 'Gagal menyimpan sebagian perbandingan. Silakan coba lagi.';
            }
        } else {
            $error = 'Semua perbandingan harus diisi';
        }
    }
}

// Get elements based on step
$elements = [];
$stepTitle = '';
$stepDescription = '';

if ($step === 'criteria') {
    $elements = getCriteriaByPart($userRole);
    $stepTitle = 'Perbandingan Kriteria';
    $stepDescription = 'Bandingkan kepentingan relatif antar kriteria evaluasi bidang ' . ucfirst($userRole);
} elseif ($step === 'alternatives' && $criteriaId) {
    $elements = getAllProjects();
    $stepTitle = 'Perbandingan Alternatif';
    $stepDescription = 'Bandingkan alternatif proyek untuk kriteria tertentu';
}

// Get existing comparisons
$existingComparisons = [];
if ($step === 'criteria') {
    $existingComparisons = getPairwiseComparisons($user['id'], 'criteria');
} elseif ($step === 'alternatives') {
    $existingComparisons = getPairwiseComparisons($user['id'], 'alternatives', $criteriaId, $projectId);
}

// Convert to associative array for easier access
$comparisonMatrix = [];
foreach ($existingComparisons as $comp) {
    $comparisonMatrix[$comp['element_i'] . '_' . $comp['element_j']] = $comp['comparison_value'];
}

// Get flash messages
$flashMessages = getFlashMessages();

// Saaty scale definition
$saatyScale = [
    1 => 'Sama penting',
    2 => 'Sedikit lebih penting',
    3 => 'Lebih penting',
    4 => 'Lebih penting+',
    5 => 'Sangat penting',
    6 => 'Sangat penting+',
    7 => 'Jauh lebih penting',
    8 => 'Jauh lebih penting+',
    9 => 'Mutlak lebih penting'
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - AHP Pairwise Comparison</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    
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
                    <!-- Management Section (Admin & General) -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" style="font-family: 'Poppins', sans-serif; color: #64748B; font-weight: 500;">
                            <i class="bi bi-grid-3x3-gap me-1"></i>Management
                        </a>
                        <ul class="dropdown-menu" style="border: 1px solid #E2E8F0; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                            <li><a class="dropdown-item" href="dashboard.php" style="font-family: 'Poppins', sans-serif; color: #64748B; font-size: 14px;">
                                <i class="bi bi-house me-2"></i>Dashboard
                            </a></li>
                            <li><a class="dropdown-item" href="projects.php" style="font-family: 'Poppins', sans-serif; color: #64748B; font-size: 14px;">
                                <i class="bi bi-folder me-2"></i>Kelola Proyek
                            </a></li>
                        </ul>
                    </li>
                    
                    <!-- Evaluation Section (Non-Admin Only) -->
                    <?php if (!hasRole('supervisor')): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown" style="font-family: 'Poppins', sans-serif; background: #3B82F6; color: white; border-radius: 6px; font-weight: 600; padding: 8px 12px;">
                            <i class="bi bi-clipboard-data me-1"></i>Evaluasi
                        </a>
                        <ul class="dropdown-menu" style="border: 1px solid #E2E8F0; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                            <li><a class="dropdown-item" href="evaluate.php" style="font-family: 'Poppins', sans-serif; color: #64748B; font-size: 14px;">
                                <i class="bi bi-clipboard-check me-2"></i>Evaluasi BORDA
                            </a></li>
                            <li><a class="dropdown-item active" href="ahp_comparison.php?step=criteria" style="font-family: 'Poppins', sans-serif; color: #3B82F6; font-size: 14px; font-weight: 600;">
                                <i class="bi bi-diagram-2 me-2"></i>Evaluasi AHP
                            </a></li>
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
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" style="font-family: 'Poppins', sans-serif; color: #64748B; font-weight: 500;">
                            <i class="bi bi-person-circle me-1"></i>
                            <?= escape($user['fullname']) ?>
                        </a>
                        <ul class="dropdown-menu" style="border: 1px solid #E2E8F0; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                            <li><h6 class="dropdown-header" style="font-family: 'Poppins', sans-serif; color: #64748B; font-size: 12px;">
                                <i class="bi bi-shield-check me-1"></i>
                                <?= ucfirst($user['role']) ?>
                            </h6></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php" style="font-family: 'Poppins', sans-serif; color: #64748B; font-size: 14px;">
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

        <!-- Error/Success Messages -->
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle me-2"></i>
                <?= escape($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?= escape($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- AHP Steps Progress -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="mb-0">
                                <i class="bi bi-diagram-2 me-2"></i>
                                Analytic Hierarchy Process (AHP)
                            </h4>
                            <span class="badge bg-info">Bidang: <?= ucfirst($userRole) ?></span>
                        </div>
                        
                        <div class="progress mb-3" style="height: 8px;">
                            <div class="progress-bar" style="width: <?= $step === 'criteria' ? '33%' : ($step === 'alternatives' ? '66%' : '100%') ?>%"></div>
                        </div>
                        
                        <div class="row text-center">
                            <div class="col-md-4">
                                <div class="<?= $step === 'criteria' ? 'text-primary fw-bold' : 'text-muted' ?>">
                                    <i class="bi bi-1-circle me-1"></i>
                                    Perbandingan Kriteria
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="<?= $step === 'alternatives' ? 'text-primary fw-bold' : 'text-muted' ?>">
                                    <i class="bi bi-2-circle me-1"></i>
                                    Perbandingan Alternatif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted">
                                    <i class="bi bi-3-circle me-1"></i>
                                    Hasil & Konsistensi
                                </div>
                            </div>
                        </div>
                        
                        <!-- Step Navigation Buttons -->
                        <div class="d-flex gap-2 justify-content-center mt-3">
                            <a href="ahp_comparison.php?step=criteria" 
                               class="btn btn-sm <?= $step === 'criteria' ? 'btn-primary' : 'btn-outline-primary' ?>">
                                <i class="bi bi-list-check me-1"></i>Kriteria
                            </a>
                            
                            <?php if ($step === 'alternatives' || !empty(getPairwiseComparisons($user['id'], 'criteria'))): ?>
                                <a href="ahp_comparison.php?step=alternatives<?= $criteriaId ? '&criteria=' . $criteriaId : '' ?>" 
                                   class="btn btn-sm <?= $step === 'alternatives' ? 'btn-primary' : 'btn-outline-primary' ?>">
                                    <i class="bi bi-diagram-3 me-1"></i>Alternatif
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!empty(getPairwiseComparisons($user['id'], 'criteria'))): ?>
                                <a href="ahp_results.php" class="btn btn-sm btn-outline-success">
                                    <i class="bi bi-bar-chart me-1"></i>Lihat Hasil
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Criteria Selection for Alternatives Step -->
        <?php if ($step === 'alternatives'): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-list-ul me-1"></i>
                            Pilih Kriteria untuk Perbandingan Alternatif
                        </h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Pilih kriteria yang akan digunakan untuk membandingkan alternatif proyek:</p>
                        <div class="d-flex gap-2 flex-wrap">
                            <?php 
                            $availableCriteria = getCriteriaByPart($userRole);
                            foreach ($availableCriteria as $criterion): ?>
                                <a href="ahp_comparison.php?step=alternatives&criteria=<?= $criterion['id'] ?>" 
                                   class="btn btn-sm <?= $criteriaId == $criterion['id'] ? 'btn-primary' : 'btn-outline-secondary' ?>">
                                    <i class="bi bi-check-circle me-1"></i>
                                    <?= escape($criterion['name']) ?>
                                    <?php 
                                    $altComparisons = getPairwiseComparisons($user['id'], 'alternatives', $criterion['id']);
                                    if (!empty($altComparisons)): ?>
                                        <span class="badge bg-success ms-1">✓</span>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($elements) && count($elements) >= 2): ?>
        <!-- Pairwise Comparison Form -->
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="comparison_type" value="<?= $step === 'criteria' ? 'criteria' : 'alternatives' ?>">
            <input type="hidden" name="criteria_id" value="<?= $criteriaId ?>">
            <input type="hidden" name="project_id" value="<?= $projectId ?>">
            
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="bi bi-arrow-left-right me-2"></i>
                                <?= $stepTitle ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-4"><?= $stepDescription ?></p>
                            
                            <!-- Pairwise Comparison Matrix -->
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th style="width: 25%;">Kriteria A</th>
                                            <th style="width: 50%;" class="text-center">Perbandingan</th>
                                            <th style="width: 25%;" class="text-end">Kriteria B</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php for ($i = 0; $i < count($elements); $i++): ?>
                                            <?php for ($j = $i + 1; $j < count($elements); $j++): ?>
                                                <?php
                                                $elementI = $elements[$i];
                                                $elementJ = $elements[$j];
                                                $compKey = $elementI['id'] . '_' . $elementJ['id'];
                                                $currentValue = $comparisonMatrix[$compKey] ?? 1;
                                                ?>
                                                <tr>
                                                    <td>
                                                        <div class="fw-bold text-primary"><?= escape($elementI['name']) ?></div>
                                                        <?php if (isset($elementI['description'])): ?>
                                                            <small class="text-muted"><?= escape(substr($elementI['description'], 0, 50)) ?>...</small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="d-flex align-items-center justify-content-center">
                                                            <label class="form-label me-2 mb-0">A</label>
                                                            <select name="comparisons[<?= $compKey ?>]" 
                                                                    class="form-select comparison-select mx-2" 
                                                                    style="width: auto;"
                                                                    required>
                                                                <option value="">Pilih</option>
                                                                <?php foreach ($saatyScale as $value => $label): ?>
                                                                    <option value="<?= $value ?>" <?= $currentValue == $value ? 'selected' : '' ?>>
                                                                        <?= $value ?> - <?= $label ?>
                                                                    </option>
                                                                    <?php if ($value > 1): ?>
                                                                        <option value="<?= 1/$value ?>" <?= abs($currentValue - (1/$value)) < 0.001 ? 'selected' : '' ?>>
                                                                            <?= number_format(1/$value, 3) ?> - <?= $label ?> (B > A)
                                                                        </option>
                                                                    <?php endif; ?>
                                                                <?php endforeach; ?>
                                                            </select>
                                                            <label class="form-label ms-2 mb-0">B</label>
                                                        </div>
                                                    </td>
                                                    <td class="text-end">
                                                        <div class="fw-bold text-success"><?= escape($elementJ['name']) ?></div>
                                                        <?php if (isset($elementJ['description'])): ?>
                                                            <small class="text-muted"><?= escape(substr($elementJ['description'], 0, 50)) ?>...</small>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endfor; ?>
                                        <?php endfor; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex gap-3 mt-4 flex-wrap">
                                <!-- Primary Action Button -->
                                <button type="submit" name="save_comparisons" class="btn btn-success btn-lg">
                                    <i class="bi bi-check-circle me-2"></i>
                                    Simpan & Lanjutkan
                                </button>
                                
                                <!-- Save Draft Button -->
                                <button type="button" onclick="saveDraft()" class="btn btn-outline-success">
                                    <i class="bi bi-cloud-arrow-up me-1"></i>
                                    Simpan Draft
                                </button>
                                
                                <!-- Reset/Clear Button -->
                                <button type="button" onclick="resetComparisons()" class="btn btn-outline-warning">
                                    <i class="bi bi-arrow-clockwise me-1"></i>
                                    Reset
                                </button>
                                
                                <!-- Auto Fill Diagonal -->
                                <button type="button" onclick="autoFillConsistent()" class="btn btn-outline-info">
                                    <i class="bi bi-magic me-1"></i>
                                    Auto Konsistensi
                                </button>
                                
                                <!-- Navigation Buttons -->
                                <?php if ($step === 'alternatives'): ?>
                                    <a href="ahp_comparison.php?step=criteria" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left me-1"></i>
                                        Kembali ke Kriteria
                                    </a>
                                <?php else: ?>
                                    <a href="dashboard.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-house me-1"></i>
                                        Dashboard
                                    </a>
                                <?php endif; ?>
                                
                                <!-- Quick Preview Results -->
                                <button type="button" onclick="previewResults()" class="btn btn-outline-primary" 
                                        <?= empty($existingComparisons) ? 'disabled' : '' ?>>
                                    <i class="bi bi-eye me-1"></i>
                                    Preview Hasil
                                </button>
                                
                                <!-- Help/Tutorial Button -->
                                <button type="button" data-bs-toggle="modal" data-bs-target="#tutorialModal" class="btn btn-outline-info">
                                    <i class="bi bi-question-circle me-1"></i>
                                    Tutorial
                                </button>
                            </div>
                            
                            <!-- Progress Indicator -->
                            <div class="mt-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <small class="text-muted">Progress Perbandingan:</small>
                                    <small class="text-muted"><span id="progress-text">0/0</span> selesai</small>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div id="comparison-progress" class="progress-bar bg-success" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <!-- Saaty Scale Reference -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="bi bi-info-circle me-1"></i>
                                Panduan Skala Saaty
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Nilai</th>
                                            <th>Arti</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($saatyScale as $value => $label): ?>
                                            <tr>
                                                <td><strong><?= $value ?></strong></td>
                                                <td class="small"><?= $label ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="alert alert-info mt-3">
                                <small>
                                    <strong>Petunjuk:</strong><br>
                                    - Pilih nilai 1-9 jika kriteria A lebih penting dari B<br>
                                    - Pilih nilai 0.111-1 jika kriteria B lebih penting dari A<br>
                                    - Nilai 1 berarti sama penting
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- AHP Method Info -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="bi bi-lightbulb me-1"></i>
                                Tentang AHP
                            </h6>
                        </div>
                        <div class="card-body">
                            <p class="small">
                                <strong>Analytic Hierarchy Process (AHP)</strong> adalah metode pengambilan 
                                keputusan yang dikembangkan oleh Thomas Saaty untuk memecahkan masalah 
                                kompleks dengan multiple criteria.
                            </p>
                            
                            <h6 class="small fw-bold">Keunggulan AHP:</h6>
                            <ul class="small">
                                <li>Dapat mengukur konsistensi penilaian</li>
                                <li>Mampu menangani kriteria kualitatif dan kuantitatif</li>
                                <li>Memiliki dasar matematika yang kuat</li>
                                <li>Mudah dipahami dan diimplementasikan</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <?php else: ?>
        <!-- No Elements Available -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-exclamation-triangle display-1 text-muted"></i>
                        <h5 class="mt-3 text-muted">Tidak Ada Elemen untuk Dibandingkan</h5>
                        <p class="text-muted">
                            Tidak ditemukan kriteria atau alternatif yang cukup untuk melakukan perbandingan berpasangan.<br>
                            Minimal diperlukan 2 elemen.
                        </p>
                        <a href="dashboard.php" class="btn btn-primary">
                            <i class="bi bi-house me-1"></i>Kembali ke Dashboard
                        </a>
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
                if (alert.classList.contains('alert-success')) {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.remove();
                    }, 500);
                }
            });
        }, 5000);

        // Form validation
        document.querySelector('form')?.addEventListener('submit', function(e) {
            const selects = this.querySelectorAll('.comparison-select');
            let isValid = true;
            
            selects.forEach(select => {
                if (!select.value) {
                    select.classList.add('is-invalid');
                    isValid = false;
                } else {
                    select.classList.remove('is-invalid');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Mohon lengkapi semua perbandingan');
            }
        });

        // Real-time validation
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('comparison-select')) {
                if (e.target.value) {
                    e.target.classList.remove('is-invalid');
                    e.target.classList.add('is-valid');
                }
            }
        });

        // Reciprocal value display
        document.querySelectorAll('.comparison-select').forEach(select => {
            select.addEventListener('change', function() {
                const value = parseFloat(this.value);
                if (value && value !== 1) {
                    const row = this.closest('tr');
                    const reciprocal = (1 / value).toFixed(3);
                    
                    // Visual feedback for reciprocal relationship
                    if (value > 1) {
                        row.style.backgroundColor = 'rgba(40, 167, 69, 0.1)';
                    } else if (value < 1) {
                        row.style.backgroundColor = 'rgba(220, 53, 69, 0.1)';
                    } else {
                        row.style.backgroundColor = 'rgba(255, 193, 7, 0.1)';
                    }
                }
            });
        });

        // Progress tracking
        function updateProgress() {
            const selects = document.querySelectorAll('.comparison-select');
            let filled = 0;
            
            selects.forEach(select => {
                if (select.value) filled++;
            });
            
            const percentage = selects.length > 0 ? (filled / selects.length) * 100 : 0;
            
            // Update progress info if exists
            let progressInfo = document.querySelector('.progress-info');
            if (!progressInfo) {
                progressInfo = document.createElement('small');
                progressInfo.className = 'progress-info text-muted ms-2';
                document.querySelector('.card-header h5').appendChild(progressInfo);
            }
            
            progressInfo.textContent = `(${filled}/${selects.length} perbandingan)`;
        }

        // Update progress on change
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('comparison-select')) {
                updateProgress();
            }
        });

        // Initial progress update
        updateProgress();

        // Prevent accidental page leave
        let formChanged = false;
        document.addEventListener('change', function() {
            formChanged = true;
        });

        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = 'Anda memiliki perubahan yang belum disimpan. Yakin ingin meninggalkan halaman?';
            }
        });

        // Reset form changed flag on submit
        document.querySelector('form')?.addEventListener('submit', function() {
            formChanged = false;
        });

        // Enhanced progress tracking
        function updateProgressBar() {
            const selects = document.querySelectorAll('.comparison-select');
            let filled = 0;
            
            selects.forEach(select => {
                if (select.value) filled++;
            });
            
            const percentage = selects.length > 0 ? (filled / selects.length) * 100 : 0;
            const progressBar = document.getElementById('comparison-progress');
            const progressText = document.getElementById('progress-text');
            
            if (progressBar) {
                progressBar.style.width = percentage + '%';
            }
            
            if (progressText) {
                progressText.textContent = `${filled}/${selects.length}`;
            }
        }

        // Update progress on change
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('comparison-select')) {
                updateProgressBar();
            }
        });

        // Initial progress update
        updateProgressBar();

        // Save Draft Function
        function saveDraft() {
            const formData = new FormData(document.querySelector('form'));
            formData.append('save_draft', '1');
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            }).then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Draft berhasil disimpan', 'success');
                } else {
                    showToast('Gagal menyimpan draft', 'error');
                }
            }).catch(() => {
                showToast('Terjadi kesalahan saat menyimpan', 'error');
            });
        }

        // Reset Comparisons Function
        function resetComparisons() {
            if (confirm('Apakah Anda yakin ingin mereset semua perbandingan?')) {
                document.querySelectorAll('.comparison-select').forEach(select => {
                    select.value = '';
                    select.classList.remove('is-valid', 'is-invalid');
                    select.closest('tr').style.backgroundColor = '';
                });
                updateProgressBar();
                showToast('Semua perbandingan telah direset', 'info');
            }
        }

        // Auto Fill Consistent Function (Simple heuristic)
        function autoFillConsistent() {
            if (confirm('Auto-fill akan mengisi perbandingan dengan nilai konsisten sederhana. Lanjutkan?')) {
                const selects = document.querySelectorAll('.comparison-select');
                selects.forEach((select, index) => {
                    if (!select.value) {
                        // Simple random consistent assignment
                        const values = [1, 2, 3, 0.5, 0.33];
                        select.value = values[Math.floor(Math.random() * values.length)];
                        select.dispatchEvent(new Event('change'));
                    }
                });
                updateProgressBar();
                showToast('Auto-fill selesai. Silakan periksa dan sesuaikan nilai.', 'info');
            }
        }

        // Preview Results Function
        function previewResults() {
            const formData = new FormData(document.querySelector('form'));
            const popup = window.open('', 'preview', 'width=800,height=600,scrollbars=yes');
            popup.document.write('<html><body><h3>Loading preview...</h3></body></html>');
            
            fetch('ahp_preview.php', {
                method: 'POST',
                body: formData
            }).then(response => response.text())
            .then(html => {
                popup.document.write(html);
                popup.document.close();
            }).catch(() => {
                popup.document.write('<p>Gagal memuat preview</p>');
            });
        }

        // Toast Notification Function
        function showToast(message, type = 'info') {
            const toastContainer = document.getElementById('toast-container') || createToastContainer();
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : type} border-0`;
            toast.setAttribute('role', 'alert');
            
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            
            toastContainer.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
            
            setTimeout(() => {
                toast.remove();
            }, 5000);
        }

        function createToastContainer() {
            const container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
            return container;
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 's':
                        e.preventDefault();
                        saveDraft();
                        break;
                    case 'r':
                        e.preventDefault();
                        resetComparisons();
                        break;
                }
            }
        });
    </script>

    <!-- Tutorial Modal -->
    <div class="modal fade" id="tutorialModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-book me-2"></i>
                        Tutorial AHP Pairwise Comparison
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="bi bi-1-circle me-1"></i> Langkah-langkah:</h6>
                            <ol class="small">
                                <li>Bandingkan setiap pasang kriteria/alternatif</li>
                                <li>Gunakan skala Saaty (1-9) untuk menentukan tingkat kepentingan</li>
                                <li>Nilai 1 = sama penting, 9 = sangat penting</li>
                                <li>Sistem akan otomatis menghitung konsistensi</li>
                                <li>CR ≤ 0.1 dianggap konsisten</li>
                            </ol>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="bi bi-lightbulb me-1"></i> Tips:</h6>
                            <ul class="small">
                                <li><strong>Ctrl+S:</strong> Simpan draft</li>
                                <li><strong>Ctrl+R:</strong> Reset form</li>
                                <li>Gunakan tombol "Auto Konsistensi" untuk bantuan awal</li>
                                <li>Preview hasil sebelum menyimpan final</li>
                                <li>Perhatikan indikator progress di bawah form</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        <strong>Contoh Perbandingan:</strong><br>
                        Jika Kriteria A "Lebih Penting" dari Kriteria B, pilih nilai 3.<br>
                        Jika Kriteria B "Lebih Penting" dari Kriteria A, pilih nilai 0.333 (1/3).
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <a href="https://en.wikipedia.org/wiki/Analytic_hierarchy_process" target="_blank" class="btn btn-primary">
                        <i class="bi bi-book me-1"></i>Pelajari Lebih Lanjut
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Action Buttons -->
    <div class="position-fixed bottom-0 end-0 p-4" style="z-index: 1000;">
        <div class="btn-group-vertical" role="group">
            <!-- Quick Save -->
            <button type="button" onclick="saveDraft()" class="btn btn-success btn-sm mb-2 rounded-circle" 
                    data-bs-toggle="tooltip" data-bs-placement="left" title="Simpan Draft (Ctrl+S)"
                    style="width: 50px; height: 50px;">
                <i class="bi bi-cloud-arrow-up"></i>
            </button>
            
            <!-- Show Progress -->
            <button type="button" onclick="scrollToProgress()" class="btn btn-info btn-sm mb-2 rounded-circle"
                    data-bs-toggle="tooltip" data-bs-placement="left" title="Lihat Progress"
                    style="width: 50px; height: 50px;">
                <i class="bi bi-graph-up"></i>
            </button>
            
            <!-- Back to Top -->
            <button type="button" onclick="scrollToTop()" class="btn btn-secondary btn-sm rounded-circle"
                    data-bs-toggle="tooltip" data-bs-placement="left" title="Kembali ke Atas"
                    style="width: 50px; height: 50px;">
                <i class="bi bi-arrow-up"></i>
            </button>
        </div>
    </div>

    <script>
        // Scroll functions
        function scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function scrollToProgress() {
            document.getElementById('comparison-progress').scrollIntoView({ behavior: 'smooth' });
        }

        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>