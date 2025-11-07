<?php
/**
 * GDSS Dashboard
 * Halaman utama setelah login
 */

require_once 'config.php';  // This will handle session
require_once 'functions.php';

requireLogin();

$projects = getProjects();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-chart-line me-2"></i><?php echo APP_NAME; ?>
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="fas fa-user me-1"></i>
                    Selamat datang, <?php echo $_SESSION['full_name'] ?? $_SESSION['username']; ?>
                </span>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col">
                <h2><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h2>
                <p class="text-muted">Kelola proyek pengambilan keputusan Anda</p>
            </div>
            <div class="col-auto">
                <a href="projects.php?action=create" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>Proyek Baru
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5>Total Proyek</h5>
                                <h3><?php echo count($projects); ?></h3>
                            </div>
                            <div>
                                <i class="fas fa-project-diagram fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5>Aktif</h5>
                                <h3><?php echo count(array_filter($projects, function($p) { return $p['status'] == 'active'; })); ?></h3>
                            </div>
                            <div>
                                <i class="fas fa-check-circle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5>Selesai</h5>
                                <h3><?php echo count(array_filter($projects, function($p) { return $p['status'] == 'completed'; })); ?></h3>
                            </div>
                            <div>
                                <i class="fas fa-flag-checkered fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Projects List -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list me-2"></i>Daftar Proyek</h5>
            </div>
            <div class="card-body">
                <?php if (empty($projects)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                        <h5>Belum ada proyek</h5>
                        <p class="text-muted">Mulai dengan membuat proyek baru</p>
                        <a href="projects.php?action=create" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Buat Proyek Pertama
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nama Proyek</th>
                                    <th>Deskripsi</th>
                                    <th>Tanggal Dibuat</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($projects as $project): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($project['name']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars(substr($project['description'], 0, 100)); ?>...</td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($project['created_at'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $project['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($project['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="evaluate.php?id=<?php echo $project['id']; ?>" class="btn btn-outline-primary" title="Evaluasi">
                                                <i class="fas fa-chart-bar"></i>
                                            </a>
                                            <a href="results.php?id=<?php echo $project['id']; ?>" class="btn btn-outline-success" title="Hasil">
                                                <i class="fas fa-trophy"></i>
                                            </a>
                                            <a href="projects.php?action=edit&id=<?php echo $project['id']; ?>" class="btn btn-outline-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>