<?php
/**
 * GDSS Dashboard
 * Halaman utama setelah login
 */

require_once 'config.php';  // This will handle session
require_once 'functions.php';

requireLogin();

$user = getCurrentUser();
$userRole = $user['role'];
$projects = getProjects();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
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
        <div class="container">
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
                        <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown" style="font-family: 'Poppins', sans-serif; background: #3B82F6; color: white; border-radius: 6px; font-weight: 600; padding: 8px 12px;">
                            <i class="bi bi-grid-3x3-gap me-1"></i>Management
                        </a>
                        <ul class="dropdown-menu" style="border: 1px solid #E2E8F0; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                            <li><a class="dropdown-item active" href="dashboard.php" style="font-family: 'Poppins', sans-serif; color: #3B82F6; font-size: 14px; font-weight: 600;">
                                <i class="bi bi-house me-2"></i>Dashboard
                            </a></li>
                            <li><a class="dropdown-item" href="projects.php" style="font-family: 'Poppins', sans-serif; color: #64748B; font-size: 14px;">
                                <i class="bi bi-folder me-2"></i>Kelola Proyek
                            </a></li>
                        </ul>
                    </li>
                    
                    <!-- Evaluation Section (Non-Supervisor Only) -->
                    <?php if (!hasRole('supervisor')): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" style="font-family: 'Poppins', sans-serif; color: #64748B; font-weight: 500;">
                            <i class="bi bi-clipboard-data me-1"></i>Evaluasi
                        </a>
                        <ul class="dropdown-menu" style="border: 1px solid #E2E8F0; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                            <li><a class="dropdown-item" href="evaluate.php" style="font-family: 'Poppins', sans-serif; color: #64748B; font-size: 14px;">
                                <i class="bi bi-clipboard-check me-2"></i>Evaluasi BORDA
                            </a></li>
                            <li><a class="dropdown-item" href="ahp_comparison.php?step=criteria" style="font-family: 'Poppins', sans-serif; color: #64748B; font-size: 14px;">
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
                            <?php echo htmlspecialchars($user['fullname']); ?>
                        </a>
                        <ul class="dropdown-menu" style="border: 1px solid #E2E8F0; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                            <li><h6 class="dropdown-header" style="font-family: 'Poppins', sans-serif; color: #64748B; font-size: 12px;">
                                <i class="bi bi-shield-check me-1"></i>
                                <?php echo ucfirst($user['role']); ?>
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

    <div class="container mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col">
                <h2 style="font-family: 'Poppins', sans-serif; font-weight: 700; color: #1E293B; font-size: 24px;"><i class="fas fa-tachometer-alt me-2" style="color: #3B82F6;"></i>Dashboard</h2>
                <p style="font-family: 'Poppins', sans-serif; color: #64748B; font-size: 14px; margin-bottom: 0;">
                    <?php if (hasRole('supervisor')): ?>
                        Kelola proyek dan pantau progres evaluasi
                    <?php else: ?>
                        Lakukan evaluasi AHP untuk bidang <?php echo ucfirst($userRole); ?>
                    <?php endif; ?>
                </p>
            </div>
            <?php if (hasRole('supervisor')): ?>
            <div class="col-auto">
                <a href="projects.php?action=create" class="btn btn-primary" style="background: #3B82F6; border-color: #3B82F6; font-family: 'Poppins', sans-serif; font-weight: 500; border-radius: 8px; padding: 8px 16px;">
                    <i class="fas fa-plus me-1"></i>Proyek Baru
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- AHP Evaluation Section (for evaluators only) -->
        <?php if (!hasRole('supervisor')): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card" style="border: 1px solid #E2E8F0; border-radius: 12px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);">
                    <div class="card-header" style="background: #3B82F6; border-radius: 12px 12px 0 0; padding: 16px 20px;">
                        <h5 class="mb-0" style="font-family: 'Poppins', sans-serif; font-weight: 600; color: white; font-size: 16px;">
                            <i class="bi bi-diagram-2 me-2"></i>
                            Evaluasi AHP - Bidang <?= ucfirst($userRole) ?>
                        </h5>
                    </div>
                    <div class="card-body" style="padding: 20px;">
                        <div class="row">
                            <div class="col-md-8">
                                <p class="mb-3" style="font-family: 'Poppins', sans-serif; color: #64748B; font-size: 14px; line-height: 1.6;">
                                    Lakukan evaluasi menggunakan metode Analytic Hierarchy Process (AHP) 
                                    dengan perbandingan berpasangan untuk mendapatkan hasil yang lebih akurat.
                                </p>
                                
                                <div class="d-flex gap-3 flex-wrap">
                                    <a href="ahp_comparison.php?step=criteria" class="btn btn-primary" style="background: #3B82F6; border-color: #3B82F6; font-family: 'Poppins', sans-serif; font-weight: 500; border-radius: 8px; padding: 8px 16px; font-size: 14px;">
                                        <i class="bi bi-1-circle me-1"></i>
                                        Mulai Perbandingan Kriteria
                                    </a>
                                    
                                    <a href="ahp_comparison.php?step=alternatives" class="btn btn-outline-secondary" style="border-color: #64748B; color: #64748B; font-family: 'Poppins', sans-serif; font-weight: 500; border-radius: 8px; padding: 8px 16px; font-size: 14px;">
                                        <i class="bi bi-2-circle me-1"></i>
                                        Perbandingan Alternatif
                                    </a>
                                    
                                    <a href="ahp_results.php" class="btn btn-success" style="background: #22C55E; border-color: #22C55E; font-family: 'Poppins', sans-serif; font-weight: 500; border-radius: 8px; padding: 8px 16px; font-size: 14px;">
                                        <i class="bi bi-bar-chart me-1"></i>
                                        Lihat Hasil AHP
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div style="background: #F8FAFC; border-radius: 8px; padding: 16px;">
                                    <h6 class="mb-2" style="font-family: 'Poppins', sans-serif; font-weight: 600; color: #1E293B; font-size: 14px;">
                                        <i class="bi bi-info-circle me-1" style="color: #3B82F6;"></i>
                                        Metode AHP
                                    </h6>
                                    <ul style="font-family: 'Poppins', sans-serif; font-size: 12px; color: #64748B; margin-bottom: 0; padding-left: 16px;">
                                        <li>Perbandingan berpasangan (1-9 skala Saaty)</li>
                                        <li>Validasi konsistensi otomatis (CR ≤ 0.1)</li>
                                        <li>Perhitungan priority vector</li>
                                        <li>Hasil global score terintegrasi</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-white" style="background: #3B82F6; border: none; border-radius: 12px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);">
                    <div class="card-body" style="padding: 20px;">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 style="font-family: 'Poppins', sans-serif; font-weight: 500; font-size: 14px; margin-bottom: 8px;">Total Proyek</h5>
                                <h3 style="font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 32px; margin: 0;"><?php echo count($projects); ?></h3>
                            </div>
                            <div>
                                <i class="fas fa-project-diagram" style="font-size: 32px; opacity: 0.8;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white" style="background: #22C55E; border: none; border-radius: 12px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);">
                    <div class="card-body" style="padding: 20px;">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 style="font-family: 'Poppins', sans-serif; font-weight: 500; font-size: 14px; margin-bottom: 8px;">Aktif</h5>
                                <h3 style="font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 32px; margin: 0;"><?php echo count(array_filter($projects, function($p) { return $p['status'] == 'active'; })); ?></h3>
                            </div>
                            <div>
                                <i class="fas fa-check-circle" style="font-size: 32px; opacity: 0.8;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white" style="background: #64748B; border: none; border-radius: 12px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);">
                    <div class="card-body" style="padding: 20px;">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 style="font-family: 'Poppins', sans-serif; font-weight: 500; font-size: 14px; margin-bottom: 8px;">Selesai</h5>
                                <h3 style="font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 32px; margin: 0;"><?php echo count(array_filter($projects, function($p) { return $p['status'] == 'completed'; })); ?></h3>
                            </div>
                            <div>
                                <i class="fas fa-flag-checkered" style="font-size: 32px; opacity: 0.8;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Projects List -->
        <div class="card" style="border: 1px solid #E2E8F0; border-radius: 12px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);">
            <div class="card-header" style="background: white; border-radius: 12px 12px 0 0; padding: 20px; border-bottom: 1px solid #E2E8F0;">
                <h5 style="font-family: 'Poppins', sans-serif; font-weight: 600; color: #1E293B; font-size: 16px; margin: 0;"><i class="fas fa-list me-2" style="color: #3B82F6;"></i>Daftar Proyek</h5>
            </div>
            <div class="card-body" style="padding: 20px;">
                <?php if (empty($projects)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-folder-open" style="font-size: 48px; color: #94A3B8; margin-bottom: 16px;"></i>
                        <h5 style="font-family: 'Poppins', sans-serif; font-weight: 600; color: #1E293B; font-size: 16px;">Belum ada proyek</h5>
                        <?php if (hasRole('supervisor')): ?>
                            <p style="font-family: 'Poppins', sans-serif; color: #64748B; font-size: 14px;">Mulai dengan membuat proyek baru</p>
                            <a href="projects.php?action=create" class="btn btn-primary" style="background: #3B82F6; border-color: #3B82F6; font-family: 'Poppins', sans-serif; font-weight: 500; border-radius: 8px; padding: 8px 16px;">
                                <i class="fas fa-plus me-1"></i>Buat Proyek Pertama
                            </a>
                        <?php else: ?>
                            <p style="font-family: 'Poppins', sans-serif; color: #64748B; font-size: 14px;">Belum ada proyek untuk dievaluasi. Hubungi supervisor untuk menambah proyek.</p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover" style="font-family: 'Poppins', sans-serif;">
                            <thead>
                                <tr style="background: #F8FAFC;">
                                    <th style="font-weight: 600; color: #64748B; font-size: 13px; border-bottom: 1px solid #E2E8F0; padding: 12px;">Nama Proyek</th>
                                    <th style="font-weight: 600; color: #64748B; font-size: 13px; border-bottom: 1px solid #E2E8F0; padding: 12px;">Deskripsi</th>
                                    <th style="font-weight: 600; color: #64748B; font-size: 13px; border-bottom: 1px solid #E2E8F0; padding: 12px;">Tanggal Dibuat</th>
                                    <th style="font-weight: 600; color: #64748B; font-size: 13px; border-bottom: 1px solid #E2E8F0; padding: 12px;">Status</th>
                                    <th style="font-weight: 600; color: #64748B; font-size: 13px; border-bottom: 1px solid #E2E8F0; padding: 12px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($projects as $project): ?>
                                <tr style="border-bottom: 1px solid #F1F5F9;">
                                    <td style="padding: 12px;">
                                        <strong style="color: #1E293B; font-size: 14px; font-weight: 500;"><?php echo htmlspecialchars($project['name']); ?></strong>
                                    </td>
                                    <td style="color: #64748B; font-size: 14px; padding: 12px;"><?php echo htmlspecialchars(substr($project['description'], 0, 100)); ?>...</td>
                                    <td style="color: #64748B; font-size: 14px; padding: 12px;"><?php echo date('d/m/Y H:i', strtotime($project['created_at'])); ?></td>
                                    <td style="padding: 12px;">
                                        <span class="badge" style="background: <?php echo $project['status'] == 'active' ? '#22C55E' : '#64748B'; ?>; font-family: 'Poppins', sans-serif; font-size: 11px; font-weight: 500; padding: 4px 8px; border-radius: 4px; color: white;">
                                            <?php echo ucfirst($project['status']); ?>
                                        </span>
                                    </td>
                                    <td style="padding: 12px;">
                                        <div class="d-flex gap-2">
                                            <?php if (!hasRole('supervisor')): ?>
                                                <a href="ahp_comparison.php?step=criteria" class="btn btn-outline-primary" title="Evaluasi AHP" style="border-color: #3B82F6; color: #3B82F6; font-family: 'Poppins', sans-serif; font-weight: 500; font-size: 12px; padding: 6px 10px; border-radius: 6px;">
                                                    <i class="bi bi-diagram-2"></i>
                                                </a>
                                                <a href="ahp_results.php" class="btn btn-outline-secondary" title="Hasil AHP" style="border-color: #64748B; color: #64748B; font-family: 'Poppins', sans-serif; font-weight: 500; font-size: 12px; padding: 6px 10px; border-radius: 6px;">
                                                    <i class="bi bi-bar-chart"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <a href="results.php" class="btn btn-outline-success" title="Hasil Konsensus" style="border-color: #22C55E; color: #22C55E; font-family: 'Poppins', sans-serif; font-weight: 500; font-size: 12px; padding: 6px 10px; border-radius: 6px;">
                                                <i class="fas fa-trophy"></i>
                                            </a>
                                            
                                            <?php if (hasRole('supervisor')): ?>
                                                <a href="projects.php?action=edit&id=<?php echo $project['id']; ?>" class="btn btn-outline-warning" title="Edit Proyek" style="border-color: #F59E0B; color: #F59E0B; font-family: 'Poppins', sans-serif; font-weight: 500; font-size: 12px; padding: 6px 10px; border-radius: 6px;">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>