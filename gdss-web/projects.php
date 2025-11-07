<?php
/**
 * GDSS Projects Management
 * Halaman untuk mengelola proyek (CRUD)
 */

require_once 'config.php';
require_once 'functions.php';

// Cek login
if (!isLoggedIn()) {
    redirect('index.php');
}

// Cek role admin
if (!hasRole('admin')) {
    setFlashMessage('error', 'Akses ditolak. Hanya admin yang dapat mengelola proyek.');
    redirect('dashboard.php');
}

$user = getCurrentUser();
$action = $_GET['action'] ?? 'list';
$project_id = $_GET['id'] ?? null;
$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($action == 'create') {
        $data = [
            'code' => trim($_POST['code'] ?? ''),
            'name' => trim($_POST['name'] ?? ''),
            'location' => trim($_POST['location'] ?? ''),
            'date_offer' => $_POST['date_offer'] ?? '',
            'description' => trim($_POST['description'] ?? '')
        ];
        
        if (empty($data['code']) || empty($data['name']) || empty($data['location']) || empty($data['date_offer'])) {
            $error = 'Semua field wajib harus diisi';
        } else {
            if (addProject($data)) {
                setFlashMessage('success', 'Proyek berhasil ditambahkan');
                redirect('projects.php');
            } else {
                $error = 'Gagal menambahkan proyek';
            }
        }
    } elseif ($action == 'edit') {
        $data = [
            'code' => trim($_POST['code'] ?? ''),
            'name' => trim($_POST['name'] ?? ''),
            'location' => trim($_POST['location'] ?? ''),
            'date_offer' => $_POST['date_offer'] ?? '',
            'description' => trim($_POST['description'] ?? '')
        ];
        
        if (empty($data['code']) || empty($data['name']) || empty($data['location']) || empty($data['date_offer'])) {
            $error = 'Semua field wajib harus diisi';
        } else {
            if (updateProject($project_id, $data)) {
                setFlashMessage('success', 'Proyek berhasil diperbarui');
                redirect('projects.php');
            } else {
                $error = 'Gagal memperbarui proyek';
            }
        }
    }
}

// Handle delete action
if ($action == 'delete' && $project_id) {
    if (deleteProject($project_id)) {
        setFlashMessage('success', 'Proyek berhasil dihapus');
    } else {
        setFlashMessage('error', 'Gagal menghapus proyek');
    }
    redirect('projects.php');
}

// Get project data for editing
$project = null;
if ($action == 'edit' && $project_id) {
    $project = getProjectById($project_id);
    if (!$project) {
        setFlashMessage('error', 'Proyek tidak ditemukan');
        redirect('projects.php');
    }
}

// Get all projects for listing
$projects = getAllProjects();

// Get flash messages
$flashMessages = getFlashMessages();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Kelola Proyek</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    
    <style>
        .project-card {
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
        }
        .project-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-teal);
        }
        .project-status {
            font-size: 0.8rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
        }
        .action-buttons .btn {
            padding: 0.375rem 0.75rem;
            margin: 0 2px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #06b6d4, #f59e0b);">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-diagram-3 me-2"></i>
                GDSS
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-house me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="projects.php">
                            <i class="bi bi-folder me-1"></i>Proyek
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="results.php">
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

        <!-- Error Message -->
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?= escape($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Success Message -->
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle me-2"></i>
                <?= escape($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($action == 'create' || $action == 'edit'): ?>
        <!-- Create/Edit Project Form -->
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-<?= $action == 'create' ? 'plus-circle' : 'pencil-square' ?> me-2"></i>
                            <?= $action == 'create' ? 'Tambah Proyek Baru' : 'Edit Proyek' ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="code" class="form-label">Kode Proyek *</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="code" 
                                               name="code" 
                                               value="<?= $project ? escape($project['code']) : '' ?>" 
                                               placeholder="Contoh: PRJ-2024-001"
                                               required>
                                        <div class="form-text">Kode unik untuk identifikasi proyek</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="date_offer" class="form-label">Tanggal Penawaran *</label>
                                        <input type="date" 
                                               class="form-control" 
                                               id="date_offer" 
                                               name="date_offer" 
                                               value="<?= $project ? $project['date_offer'] : date('Y-m-d') ?>" 
                                               required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="name" class="form-label">Nama Proyek *</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="name" 
                                       name="name" 
                                       value="<?= $project ? escape($project['name']) : '' ?>" 
                                       placeholder="Contoh: Sistem Informasi Manajemen Karyawan"
                                       required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="location" class="form-label">Lokasi *</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="location" 
                                       name="location" 
                                       value="<?= $project ? escape($project['location']) : '' ?>" 
                                       placeholder="Contoh: Jakarta Pusat"
                                       required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Deskripsi Proyek</label>
                                <textarea class="form-control" 
                                          id="description" 
                                          name="description" 
                                          rows="4" 
                                          placeholder="Jelaskan detail proyek, tujuan, dan ruang lingkup"><?= $project ? escape($project['description']) : '' ?></textarea>
                                <div class="form-text">Berikan deskripsi yang jelas tentang proyek ini</div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i>
                                    <?= $action == 'create' ? 'Tambah Proyek' : 'Simpan Perubahan' ?>
                                </button>
                                <a href="projects.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle me-1"></i>Batal
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-info-circle me-2"></i>Panduan
                        </h6>
                    </div>
                    <div class="card-body">
                        <h6>Tips Mengelola Proyek:</h6>
                        <ul class="small">
                            <li>Gunakan kode proyek yang konsisten</li>
                            <li>Nama harus deskriptif dan jelas</li>
                            <li>Lokasi harus spesifik</li>
                            <li>Tanggal penawaran harus akurat</li>
                            <li>Deskripsi membantu evaluator memahami konteks</li>
                        </ul>
                        
                        <div class="alert alert-info mt-3">
                            <small>
                                <strong>Info:</strong> Proyek yang sudah dievaluasi tidak dapat dihapus untuk menjaga integritas data.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php else: ?>
        <!-- Projects List -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="bi bi-folder me-2"></i>Kelola Proyek</h2>
                <p class="text-muted mb-0">Tambah, edit, atau hapus proyek untuk evaluasi</p>
            </div>
            <a href="projects.php?action=create" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>Tambah Proyek
            </a>
        </div>
        
        <?php if (!empty($projects)): ?>
            <div class="row g-4">
                <?php foreach ($projects as $proj): ?>
                    <div class="col-lg-6 col-xl-4">
                        <div class="card project-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <code class="small"><?= escape($proj['code']) ?></code>
                                        <h6 class="card-title mt-1 mb-0"><?= escape($proj['name']) ?></h6>
                                    </div>
                                    <span class="badge bg-<?= $proj['status'] === 'active' ? 'success' : 'secondary' ?> project-status">
                                        <?= ucfirst($proj['status']) ?>
                                    </span>
                                </div>
                                
                                <div class="mb-3">
                                    <p class="card-text text-muted small mb-2">
                                        <i class="bi bi-geo-alt me-1"></i><?= escape($proj['location']) ?>
                                    </p>
                                    <p class="card-text text-muted small mb-2">
                                        <i class="bi bi-calendar me-1"></i><?= formatDate($proj['date_offer']) ?>
                                    </p>
                                    <?php if (!empty($proj['description'])): ?>
                                        <p class="card-text small">
                                            <?= escape(substr($proj['description'], 0, 100)) ?>
                                            <?= strlen($proj['description']) > 100 ? '...' : '' ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="action-buttons d-flex gap-1">
                                    <a href="projects.php?action=edit&id=<?= $proj['id'] ?>" 
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button class="btn btn-outline-danger btn-sm" 
                                            onclick="confirmDelete(<?= $proj['id'] ?>, '<?= escape($proj['name']) ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <a href="evaluate.php?project=<?= $proj['id'] ?>" 
                                       class="btn btn-outline-success btn-sm">
                                        <i class="bi bi-clipboard-check"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="bi bi-folder-x display-1 text-muted"></i>
                    <h5 class="mt-3 text-muted">Belum ada proyek</h5>
                    <p class="text-muted">Mulai dengan menambahkan proyek pertama untuk dievaluasi</p>
                    <a href="projects.php?action=create" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>Tambah Proyek Pertama
                    </a>
                </div>
            </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle me-2 text-warning"></i>
                        Konfirmasi Hapus
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus proyek <strong id="projectName"></strong>?</p>
                    <div class="alert alert-warning">
                        <small>
                            <i class="bi bi-info-circle me-1"></i>
                            Tindakan ini tidak dapat dibatalkan. Semua data evaluasi terkait akan ikut terhapus.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i>Hapus Proyek
                    </a>
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

        // Confirm delete function
        function confirmDelete(projectId, projectName) {
            document.getElementById('projectName').textContent = projectName;
            document.getElementById('confirmDeleteBtn').href = 'projects.php?action=delete&id=' + projectId;
            
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }

        // Auto-generate project code
        document.getElementById('name')?.addEventListener('input', function() {
            const codeField = document.getElementById('code');
            if (!codeField.value) {
                const name = this.value;
                const year = new Date().getFullYear();
                const shortName = name.split(' ').map(word => word.charAt(0)).join('').toUpperCase();
                const randomNum = Math.floor(Math.random() * 999) + 1;
                codeField.value = `${shortName}-${year}-${randomNum.toString().padStart(3, '0')}`;
            }
        });

        // Form validation
        document.querySelector('form')?.addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Mohon lengkapi semua field yang wajib diisi');
            }
        });
    </script>
</body>
</html>