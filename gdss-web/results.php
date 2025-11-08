<?php
/**
 * GDSS Results Page
 * Halaman untuk menampilkan hasil perhitungan WP + BORDA
 */

require_once 'config.php';
require_once 'functions.php';

// Cek login
if (!isLoggedIn()) {
    redirect('index.php');
}

$user = getCurrentUser();
$userRole = $user['role'];

// Handle finalisasi konsensus (hanya supervisor)
if (isset($_GET['finalize']) && $_GET['finalize'] == '1' && hasRole('supervisor')) {
    if (finalizeConsensus()) {
        setFlashMessage('success', 'Konsensus BORDA telah difinalisasi dan disimpan dalam sistem.');
        redirect('results.php');
    } else {
        setFlashMessage('error', 'Gagal memfinalisasi konsensus. Silakan coba lagi.');
        redirect('results.php');
    }
}

// Get parameters
$part = $_GET['part'] ?? 'final';
$export = $_GET['export'] ?? false;

// Calculate results
$teknisResults = calculateWeightedProduct('teknis');
$administrasiResults = calculateWeightedProduct('administrasi');
$keuanganResults = calculateWeightedProduct('keuangan');
$bordaResults = calculateBordaMethod();

// Get part weights for display
$pdo = getConnection();
$stmt = $pdo->query("SELECT * FROM part_weights ORDER BY weight DESC");
$partWeights = $stmt->fetchAll();

// Check if consensus is finalized
$isFinalized = isConsensusFinalized();

// Get flash messages
$flashMessages = getFlashMessages();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Hasil Evaluasi</title>
    
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
        .result-card {
            transition: all 0.2s ease;
            border: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        }
        .result-card:hover {
            border-color: #3B82F6;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .rank-badge {
            font-family: 'Poppins', sans-serif;
            font-size: 1.2rem;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-weight: 600;
        }
        .rank-1 { background: #FFD700; color: #1E293B; }
        .rank-2 { background: #C0C0C0; color: #1E293B; }
        .rank-3 { background: #CD7F32; color: white; }
        .rank-other { background: #64748B; color: white; }
        
        .finalize-panel {
            background: rgba(248, 250, 252, 0.9);
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        }
        
        .finalized-badge {
            background: #059669;
            color: white;
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.75rem;
        }
        
        .methodology-card {
            background: white;
            border: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
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
                    
                    <!-- Evaluation Section (Non-supervisor only) -->
                    <?php if (!hasRole('supervisor')): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" style="font-family: 'Poppins', sans-serif; color: #64748B; font-weight: 500;">
                            <i class="bi bi-diagram-2 me-1"></i>Evaluasi
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="evaluate.php">
                                    <i class="bi bi-list-ol me-1"></i>Evaluasi BORDA
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="ahp_comparison.php">
                                    <i class="bi bi-diagram-2 me-1"></i>Evaluasi AHP
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    
                    <!-- Results Section -->
                    <li class="nav-item">
                        <a class="nav-link active" href="results.php" style="font-family: 'Poppins', sans-serif; color: #3B82F6; font-weight: 500;">
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

        <!-- Finalization Panel (Supervisor Only) -->
        <?php if (hasRole('supervisor')): ?>
        <div class="finalize-panel">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="mb-2" style="font-family: 'Poppins', sans-serif; font-weight: 600; color: #1E293B;">
                        <i class="bi bi-shield-check me-2"></i>
                        Finalisasi Konsensus BORDA
                        <?php if ($isFinalized): ?>
                            <span class="finalized-badge ms-2">
                                <i class="bi bi-check-circle me-1"></i>FINALIZED
                            </span>
                        <?php endif; ?>
                    </h5>
                    <p class="text-muted mb-0" style="font-family: 'Poppins', sans-serif; font-size: 14px;">
                        <?php if ($isFinalized): ?>
                            Konsensus telah difinalisasi. Hasil ini adalah keputusan final dari grup decision maker.
                        <?php else: ?>
                            Sebagai decision maker dengan jabatan tertinggi, Anda dapat memfinalisasi hasil konsensus BORDA.
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <?php if (!$isFinalized && !empty($bordaResults)): ?>
                        <button class="btn btn-lg" onclick="confirmFinalize()" style="background: #D97706; color: white; border: none; font-family: 'Poppins', sans-serif; font-weight: 500;">
                            <i class="bi bi-award me-1"></i>Finalisasi Konsensus
                        </button>
                    <?php elseif ($isFinalized): ?>
                        <button class="btn btn-lg" disabled style="background: #059669; color: white; border: none; font-family: 'Poppins', sans-serif; font-weight: 500;">
                            <i class="bi bi-check-circle me-1"></i>Sudah Difinalisasi
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card" style="border: 1px solid #E2E8F0; border-radius: 12px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);">
                    <div class="card-body" style="padding: 20px;">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h3 class="mb-2" style="font-family: 'Poppins', sans-serif; font-weight: 700; color: #1E293B; font-size: 24px;">
                                    <i class="bi bi-trophy me-2" style="color: #3B82F6;"></i>
                                    Hasil Evaluasi Proyek
                                    <?php if ($isFinalized): ?>
                                        <span class="badge ms-2" style="background: #22C55E; font-family: 'Poppins', sans-serif; font-size: 12px; padding: 4px 8px; border-radius: 4px;">FINAL</span>
                                    <?php endif; ?>
                                </h3>
                                <p class="mb-0" style="font-family: 'Poppins', sans-serif; color: #64748B; font-size: 14px;">
                                    Ranking prioritas proyek berdasarkan evaluasi AHP per bidang dan agregasi konsensus menggunakan metode BORDA
                                    <br><small style="color: #94A3B8;"><strong>Referensi:</strong> Cahyana, N.H. & Aribowo, A.S. (2014) - GDSS untuk Menentukan Prioritas Proyek</small>
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="btn-group" role="group">
                                    <?php if (hasRole('supervisor')): ?>
                                        <button class="btn" onclick="exportResults()" style="border: 1px solid #3B82F6; color: #3B82F6; font-family: 'Poppins', sans-serif; font-weight: 500; background: white; border-radius: 8px; padding: 8px 16px; font-size: 14px;">
                                            <i class="bi bi-download me-1"></i>Export
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn" onclick="printResults()" style="border: 1px solid #64748B; color: #64748B; font-family: 'Poppins', sans-serif; font-weight: 500; background: white; border-radius: 8px; padding: 8px 16px; font-size: 14px; margin-left: 8px;">
                                        <i class="bi bi-printer me-1"></i>Print
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs mb-4" id="resultTabs" role="tablist" style="font-family: 'Poppins', sans-serif;">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $part === 'final' ? 'active' : '' ?>" 
                        id="final-tab" 
                        data-bs-toggle="tab" 
                        data-bs-target="#final" 
                        type="button" 
                        role="tab"
                        style="font-weight: 500; color: <?= $part === 'final' ? '#3B82F6' : '#64748B' ?>;">
                    <i class="bi bi-trophy me-1"></i>Ranking Final (BORDA)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $part === 'teknis' ? 'active' : '' ?>" 
                        id="teknis-tab" 
                        data-bs-toggle="tab" 
                        data-bs-target="#teknis" 
                        type="button" 
                        role="tab"
                        style="font-weight: 500; color: <?= $part === 'teknis' ? '#3B82F6' : '#64748B' ?>;">
                    <i class="bi bi-cpu me-1"></i>Hasil Teknis (AHP)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $part === 'administrasi' ? 'active' : '' ?>" 
                        id="administrasi-tab" 
                        data-bs-toggle="tab" 
                        data-bs-target="#administrasi" 
                        type="button" 
                        role="tab"
                        style="font-weight: 500; color: <?= $part === 'administrasi' ? '#3B82F6' : '#64748B' ?>;">
                    <i class="bi bi-file-text me-1"></i>Hasil Administrasi (AHP)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $part === 'keuangan' ? 'active' : '' ?>" 
                        id="keuangan-tab" 
                        data-bs-toggle="tab" 
                        data-bs-target="#keuangan" 
                        type="button" 
                        role="tab"
                        style="font-weight: 500; color: <?= $part === 'keuangan' ? '#3B82F6' : '#64748B' ?>;">
                    <i class="bi bi-currency-dollar me-1"></i>Hasil Keuangan (AHP)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" 
                        id="methodology-tab" 
                        data-bs-toggle="tab" 
                        data-bs-target="#methodology" 
                        type="button" 
                        role="tab"
                        style="font-weight: 500; color: #64748B;">
                    <i class="bi bi-info-circle me-1"></i>Metodologi
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="resultTabsContent">
            <!-- Final BORDA Results -->
            <div class="tab-pane fade <?= $part === 'final' ? 'show active' : '' ?>" id="final" role="tabpanel">
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card" style="border: 1px solid #E2E8F0; border-radius: 12px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);">
                            <div class="card-header" style="background: white; border-radius: 12px 12px 0 0; padding: 20px; border-bottom: 1px solid #E2E8F0;">
                                <h5 class="mb-0" style="font-family: 'Poppins', sans-serif; font-weight: 600; color: #1E293B; font-size: 16px;">
                                    <i class="bi bi-trophy me-2" style="color: #3B82F6;"></i>
                                    Ranking Final - Metode BORDA Tertimbang
                                    <?php if ($isFinalized): ?>
                                        <span class="badge ms-2" style="background: #22C55E; font-family: 'Poppins', sans-serif; font-size: 11px; padding: 4px 8px; border-radius: 4px;">KONSENSUS FINAL</span>
                                    <?php endif; ?>
                                </h5>
                            </div>
                            <div class="card-body" style="padding: 20px;">
                                <?php if (!empty($bordaResults)): ?>
                                    <div class="row g-3">
                                        <?php foreach ($bordaResults as $result): ?>
                                            <?php
                                            $rankClass = '';
                                            switch ($result['final_rank']) {
                                                case 1: $rankClass = 'rank-1'; break;
                                                case 2: $rankClass = 'rank-2'; break;
                                                case 3: $rankClass = 'rank-3'; break;
                                                default: $rankClass = 'rank-other'; break;
                                            }
                                            ?>
                                            <div class="col-md-6 col-lg-4">
                                                <div class="card result-card h-100" style="border: 1px solid #E2E8F0; border-radius: 12px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);">
                                                    <div class="card-body" style="padding: 16px;">
                                                        <div class="d-flex align-items-start mb-3">
                                                            <div class="rank-badge <?= $rankClass ?> me-3">
                                                                <?= $result['final_rank'] ?>
                                                            </div>
                                                            <div class="flex-grow-1">
                                                                <code class="small" style="background: #F1F5F9; padding: 2px 6px; border-radius: 4px; font-family: 'Poppins', sans-serif;"><?= escape($result['project_code']) ?></code>
                                                                <h6 class="fw-bold mt-1" style="font-family: 'Poppins', sans-serif; font-size: 14px; color: #1E293B;"><?= escape($result['project_name']) ?></h6>
                                                                <small class="text-muted" style="font-family: 'Poppins', sans-serif; font-size: 12px;">
                                                                    <i class="bi bi-geo-alt me-1"></i>
                                                                    <?= escape($result['project_location']) ?>
                                                                </small>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <small class="fw-semibold" style="font-family: 'Poppins', sans-serif; font-size: 12px; color: #64748B;">Skor BORDA:</small>
                                                                <span class="badge" style="background: #3B82F6; font-family: 'Poppins', sans-serif; font-size: 11px;"><?= formatScore($result['borda_score']) ?></span>
                                                            </div>
                                                            <?php 
                                                            $maxScore = max(array_column($bordaResults, 'borda_score'));
                                                            $scorePercentage = $maxScore > 0 ? ($result['borda_score'] / $maxScore) * 100 : 0;
                                                            ?>
                                                            <div class="progress" style="height: 6px; background-color: #E5E7EB; border-radius: 3px;">
                                                                <div class="score-bar progress-bar" style="width: <?= $scorePercentage ?>%; background-color: #3B82F6; border-radius: 3px;"></div>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="row text-center">
                                                            <div class="col-4">
                                                                <small class="text-muted" style="font-family: 'Poppins', sans-serif; font-size: 11px;">Teknis</small>
                                                                <div class="fw-bold" style="font-family: 'Poppins', sans-serif; font-size: 13px; color: #1E293B;">#<?= $result['teknis_rank'] ?: '-' ?></div>
                                                            </div>
                                                            <div class="col-4">
                                                                <small class="text-muted" style="font-family: 'Poppins', sans-serif; font-size: 11px;">Admin</small>
                                                                <div class="fw-bold" style="font-family: 'Poppins', sans-serif; font-size: 13px; color: #1E293B;">#<?= $result['administrasi_rank'] ?: '-' ?></div>
                                                            </div>
                                                            <div class="col-4">
                                                                <small class="text-muted" style="font-family: 'Poppins', sans-serif; font-size: 11px;">Keuangan</small>
                                                                <div class="fw-bold" style="font-family: 'Poppins', sans-serif; font-size: 13px; color: #1E293B;">#<?= $result['keuangan_rank'] ?: '-' ?></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <i class="bi bi-inbox fs-1 text-muted"></i>
                                        <h5 class="text-muted mt-3" style="font-family: 'Poppins', sans-serif; font-weight: 500;">Belum ada hasil evaluasi</h5>
                                        <p class="text-muted" style="font-family: 'Poppins', sans-serif; font-size: 14px;">Evaluasi belum dilakukan atau belum lengkap dari semua decision maker</p>
                                        <a href="evaluate.php" class="btn" style="background: #3B82F6; color: white; font-family: 'Poppins', sans-serif; font-weight: 500; border: none;">
                                            <i class="bi bi-clipboard-check me-1"></i>Mulai Evaluasi
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Part Weights Info -->
                <?php if (!empty($partWeights)): ?>
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card" style="border: 1px solid #E2E8F0; border-radius: 12px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);">
                            <div class="card-header" style="background: white; border-radius: 12px 12px 0 0; padding: 16px; border-bottom: 1px solid #E2E8F0;">
                                <h6 class="mb-0" style="font-family: 'Poppins', sans-serif; font-weight: 600; color: #1E293B; font-size: 14px;">
                                    <i class="bi bi-pie-chart me-1" style="color: #3B82F6;"></i>
                                    Bobot Bidang Evaluasi (Sesuai Artikel)
                                </h6>
                            </div>
                            <div class="card-body" style="padding: 16px;">
                                <?php foreach ($partWeights as $weight): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-semibold" style="font-family: 'Poppins', sans-serif; font-size: 13px; color: #1E293B;"><?= ucfirst($weight['part']) ?></span>
                                        <span class="badge" style="background: #3B82F6; font-family: 'Poppins', sans-serif; font-size: 11px; padding: 4px 8px; border-radius: 4px;"><?= formatNumber($weight['weight'] * 100, 1) ?>%</span>
                                    </div>
                                    <div class="progress mb-3" style="height: 6px; background-color: #F1F5F9; border-radius: 3px;">
                                        <div class="progress-bar" style="width: <?= $weight['weight'] * 100 ?>%; background-color: #3B82F6; border-radius: 3px;"></div>
                                    </div>
                                <?php endforeach; ?>
                                <small class="text-muted" style="font-family: 'Poppins', sans-serif; font-size: 11px;">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Bobot sesuai artikel Cahyana & Aribowo (2014): Teknis 7/13, Administrasi 4/13, Keuangan 2/13
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6">
                        <div class="card" style="border: 1px solid #E2E8F0; border-radius: 12px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);">
                            <div class="card-header" style="background: white; border-radius: 12px 12px 0 0; padding: 16px; border-bottom: 1px solid #E2E8F0;">
                                <h6 class="mb-0" style="font-family: 'Poppins', sans-serif; font-weight: 600; color: #1E293B; font-size: 14px;">
                                    <i class="bi bi-info-circle me-1" style="color: #3B82F6;"></i>
                                    Interpretasi Hasil
                                </h6>
                            </div>
                            <div class="card-body" style="padding: 16px;">
                                <div class="alert alert-success" style="background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 8px; padding: 12px;">
                                    <strong style="font-family: 'Poppins', sans-serif; color: #166534;">Prioritas Tinggi (Rank 1-2):</strong><br>
                                    <span style="font-family: 'Poppins', sans-serif; font-size: 13px; color: #166534;">Proyek yang direkomendasikan untuk dilaksanakan terlebih dahulu</span>
                                </div>
                                <div class="alert alert-warning" style="background: #FFFBEB; border: 1px solid #FDE68A; border-radius: 8px; padding: 12px;">
                                    <strong style="font-family: 'Poppins', sans-serif; color: #92400E;">Prioritas Sedang (Rank 3-4):</strong><br>
                                    <span style="font-family: 'Poppins', sans-serif; font-size: 13px; color: #92400E;">Proyek yang dapat dipertimbangkan setelah prioritas tinggi</span>
                                </div>
                                <div class="alert alert-info" style="background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 8px; padding: 12px;">
                                    <strong style="font-family: 'Poppins', sans-serif; color: #1E40AF;">Prioritas Rendah (Rank 5+):</strong><br>
                                    <span style="font-family: 'Poppins', sans-serif; font-size: 13px; color: #1E40AF;">Proyek yang dapat ditunda atau perlu evaluasi ulang</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Teknis Results -->
            <div class="tab-pane fade <?= $part === 'teknis' ? 'show active' : '' ?>" id="teknis" role="tabpanel">
                <?php echo renderWPResults($teknisResults, 'teknis', 'Teknis', 'cpu'); ?>
            </div>

            <!-- Administrasi Results -->
            <div class="tab-pane fade <?= $part === 'administrasi' ? 'show active' : '' ?>" id="administrasi" role="tabpanel">
                <?php echo renderWPResults($administrasiResults, 'administrasi', 'Administrasi', 'file-text'); ?>
            </div>

            <!-- Keuangan Results -->
            <div class="tab-pane fade <?= $part === 'keuangan' ? 'show active' : '' ?>" id="keuangan" role="tabpanel">
                <?php echo renderWPResults($keuanganResults, 'keuangan', 'Keuangan', 'currency-dollar'); ?>
            </div>

            <!-- Methodology -->
            <div class="tab-pane fade" id="methodology" role="tabpanel">
                <div class="row">
                    <div class="col-lg-8 mx-auto">
                        <div class="methodology-card card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="bi bi-diagram-3 me-2"></i>
                                    Metodologi GDSS - AHP + BORDA
                                </h5>
                            </div>
                            <div class="card-body">
                                <h6><i class="bi bi-1-circle me-1"></i>Tahap 1: Evaluasi per Bidang (Metode AHP)</h6>
                                <p>Setiap bidang (Teknis, Administrasi, Keuangan) mengevaluasi proyek menggunakan Analytic Hierarchy Process (AHP) berdasarkan perbandingan berpasangan kriteria yang relevan.</p>
                                
                                <div class="alert alert-info">
                                    <strong>Proses AHP:</strong><br>
                                    <code>1. Pairwise Comparison Matrix → 2. Priority Vector → 3. Consistency Check</code><br>
                                    <small>Menggunakan eigenvalue method untuk menentukan bobot kriteria dan skor alternatif</small>
                                </div>

                                <h6 class="mt-4"><i class="bi bi-2-circle me-1"></i>Tahap 2: Agregasi Hasil (Metode BORDA)</h6>
                                <p>Ranking hasil AHP dari ketiga bidang diagregasi menggunakan metode BORDA dengan bobot bidang yang berbeda.</p>
                                
                                <div class="alert alert-warning">
                                    <strong>Formula BORDA:</strong><br>
                                    <code>S<sub>i</sub> = Σ (n - r<sub>ij</sub> + 1) × w<sub>j</sub></code><br>
                                    <small>dimana n adalah jumlah proyek, r<sub>ij</sub> adalah ranking proyek i di bidang j, dan w<sub>j</sub> adalah bobot bidang j</small>
                                </div>

                                <h6 class="mt-4"><i class="bi bi-3-circle me-1"></i>Bobot Bidang</h6>
                                <ul>
                                    <li><strong>Teknis:</strong> 53.8% (7/13) - Aspek teknis memiliki bobot tertinggi</li>
                                    <li><strong>Administrasi:</strong> 30.8% (4/13) - Aspek regulasi dan operasional</li>
                                    <li><strong>Keuangan:</strong> 15.4% (2/13) - Aspek finansial dan ROI</li>
                                </ul>

                                <h6 class="mt-4"><i class="bi bi-4-circle me-1"></i>Interpretasi Hasil</h6>
                                <p>Proyek dengan skor BORDA tertinggi mendapat prioritas utama untuk dilaksanakan, dengan mempertimbangkan hasil evaluasi AHP dari aspek teknis, administrasi, dan keuangan secara terintegrasi.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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

        // Export results function
        function exportResults() {
            alert('Fitur export akan segera tersedia!\nData akan dapat diekspor ke format PDF dan Excel.');
        }

        // Print results function
        function printResults() {
            window.print();
        }

        // Animate score bars on load
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const scoreBars = document.querySelectorAll('.score-bar');
                scoreBars.forEach(function(bar) {
                    const width = bar.style.width;
                    bar.style.width = '0%';
                    setTimeout(function() {
                        bar.style.width = width;
                    }, 100);
                });
            }, 500);
        });

        // Tab change animation
        const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
        tabButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                const targetPane = document.querySelector(this.getAttribute('data-bs-target'));
                if (targetPane) {
                    targetPane.style.opacity = '0';
                    setTimeout(function() {
                        targetPane.style.opacity = '1';
                    }, 150);
                }
            });
        });

        // Highlight top 3 results
        document.addEventListener('DOMContentLoaded', function() {
            const resultCards = document.querySelectorAll('.result-card');
            resultCards.forEach(function(card, index) {
                if (index < 3) {
                    card.style.border = '2px solid';
                    if (index === 0) card.style.borderColor = '#ffd700';
                    else if (index === 1) card.style.borderColor = '#c0c0c0';
                    else if (index === 2) card.style.borderColor = '#cd7f32';
                }
            });
        });

        // Confirm finalize action
        function confirmFinalize() {
            const confirmed = confirm("Anda yakin ingin memfinalisasi konsensus BORDA? Tindakan ini tidak dapat dibatalkan.");
            if (confirmed) {
                window.location.href = "results.php?finalize=1";
            }
        }
    </script>
</body>
</html>

<?php
/**
 * Function to render AHP results for each part
 */
function renderWPResults($results, $part, $partName, $icon) {
    ob_start();
    ?>
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-<?= $icon ?> me-2"></i>
                Hasil Evaluasi Bidang <?= $partName ?> (Metode AHP)
            </h5>
        </div>
        <div class="card-body">
            <?php if (!empty($results)): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Kode</th>
                                <th>Nama Proyek</th>
                                <th>Skor AHP</th>
                                <th>Score Visual</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $result): ?>
                                <tr>
                                    <td>
                                        <span class="badge badge-rank <?= getRankBadgeClass($result['rank']) ?>">
                                            #<?= $result['rank'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <code><?= escape($result['project_code']) ?></code>
                                    </td>
                                    <td>
                                        <strong><?= escape($result['project_name']) ?></strong>
                                    </td>
                                    <td>
                                        <span class="fw-bold"><?= formatScore($result['wp_value']) ?></span>
                                    </td>
                                    <td>
                                        <?php 
                                        $maxAHP = max(array_column($results, 'wp_value'));
                                        $percentage = ($maxAHP > 0) ? ($result['wp_value'] / $maxAHP) * 100 : 0;
                                        ?>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar" 
                                                 style="width: <?= $percentage ?>%"
                                                 title="<?= formatScore($result['wp_value']) ?>">
                                                <?= formatScore($result['wp_value']) ?>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Criteria Details -->
                <div class="mt-4">
                    <h6><i class="bi bi-list-check me-1"></i>Kriteria Evaluasi Bidang <?= $partName ?></h6>
                    <div class="row">
                        <?php 
                        $criteria = getCriteriaByPart($part);
                        foreach ($criteria as $criterion): 
                        ?>
                            <div class="col-md-6 mb-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="small"><?= escape($criterion['name']) ?></span>
                                    <span class="badge bg-secondary"><?= formatNumber($criterion['weight'] * 100, 1) ?>%</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted"></i>
                    <h5 class="text-muted mt-3">Belum ada evaluasi untuk bidang <?= $partName ?></h5>
                    <p class="text-muted">Evaluasi belum dilakukan atau belum lengkap</p>
                    <a href="evaluate.php" class="btn btn-primary">
                        <i class="bi bi-clipboard-check me-1"></i>Mulai Evaluasi
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>