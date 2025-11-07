<?php
/**
 * GDSS Evaluation Page
 * Halaman untuk evaluasi proyek per bidang
 */

require_once 'config.php';
require_once 'functions.php';

// Cek login
if (!isLoggedIn()) {
    redirect('index.php');
}

$user = getCurrentUser();
$userRole = $user['role'];

// Admin tidak bisa evaluasi
if (hasRole('admin')) {
    setFlashMessage('info', 'Administrator tidak dapat melakukan evaluasi. Silakan login sebagai evaluator.');
    redirect('dashboard.php');
}

$projectId = $_GET['project'] ?? null;
$success = '';
$error = '';

// Handle form submission
if ($_POST && isset($_POST['save_evaluation'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!validateCSRFToken($csrf_token)) {
        $error = 'Token keamanan tidak valid';
    } else {
        $projectId = $_POST['project_id'] ?? null;
        $scores = $_POST['scores'] ?? [];
        $notes = $_POST['notes'] ?? '';
        
        if (!$projectId) {
            $error = 'Proyek tidak valid';
        } elseif (empty($scores)) {
            $error = 'Semua kriteria harus dinilai';
        } else {
            $savedCount = 0;
            $totalCriteria = count($scores);
            
            foreach ($scores as $criteriaId => $score) {
                if (is_numeric($score) && $score >= 1 && $score <= 10) {
                    if (saveEvaluation($projectId, $user['id'], $criteriaId, $score, $notes)) {
                        $savedCount++;
                    }
                }
            }
            
            if ($savedCount === $totalCriteria) {
                setFlashMessage('success', 'Evaluasi berhasil disimpan');
                redirect('evaluate.php');
            } else {
                $error = 'Gagal menyimpan sebagian evaluasi. Silakan coba lagi.';
            }
        }
    }
}

// Get projects for selection
$projects = getAllProjects();

// Get criteria for user's role
$criteria = getCriteriaByPart($userRole);

// Get selected project details
$selectedProject = null;
$existingEvaluations = [];

if ($projectId) {
    $selectedProject = getProjectById($projectId);
    if ($selectedProject) {
        $existingEvaluations = getUserEvaluations($projectId, $user['id']);
    }
}

// Convert existing evaluations to associative array for easier access
$existingScores = [];
foreach ($existingEvaluations as $eval) {
    $existingScores[$eval['criteria_id']] = $eval['score'];
}

// Get evaluation progress
$evaluationProgress = getEvaluationProgress($user['id'], $userRole);

// Get flash messages
$flashMessages = getFlashMessages();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Evaluasi Proyek</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    
    <style>
        .criteria-card {
            transition: all 0.3s ease;
            border-left: 5px solid var(--primary-teal);
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }
        .criteria-card:hover {
            box-shadow: 0 12px 35px rgba(0,0,0,0.15);
            transform: translateY(-3px);
        }
        .criteria-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.75rem;
            line-height: 1.4;
        }
        .criteria-description {
            font-size: 0.95rem;
            color: #64748b;
            margin-bottom: 1rem;
            line-height: 1.5;
        }
        .criteria-weight {
            font-size: 0.85rem;
            font-weight: 600;
            background: linear-gradient(135deg, #06b6d4, #f59e0b);
            color: white;
            padding: 0.35rem 0.8rem;
            border-radius: 20px;
        }
        .score-section {
            background: #f8fafc;
            border-radius: 10px;
            padding: 1.25rem;
            margin-top: 1rem;
        }
        .score-input {
            font-size: 1.4rem;
            font-weight: 700;
            text-align: center;
            border: 3px solid #e2e8f0;
            border-radius: 12px;
            height: 60px;
            width: 100%;
            transition: all 0.3s ease;
        }
        .score-input:focus {
            border-color: var(--primary-teal);
            box-shadow: 0 0 0 4px rgba(6, 182, 212, 0.15);
            transform: scale(1.05);
        }
        .score-range {
            background: linear-gradient(90deg, #ef4444 0%, #f59e0b 50%, #10b981 100%);
            height: 8px;
            border-radius: 4px;
            margin: 15px 0;
        }
        .score-labels {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
            color: #64748b;
            font-weight: 500;
        }
        .evaluation-progress {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow-md);
            margin-bottom: 2rem;
        }
        .criteria-number {
            background: linear-gradient(135deg, #06b6d4, #f59e0b);
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        .criteria-container {
            max-width: 800px;
            margin: 0 auto;
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
                        <a class="nav-link" href="projects.php">
                            <i class="bi bi-folder me-1"></i>Proyek
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="evaluate.php">
                            <i class="bi bi-clipboard-check me-1"></i>Evaluasi
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

        <!-- Evaluation Progress -->
        <div class="evaluation-progress animate-fadeIn">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h4 class="mb-2">
                        <i class="bi bi-clipboard-check me-2 text-primary"></i>
                        Evaluasi Proyek - Bidang <?= ucfirst($userRole) ?>
                    </h4>
                    <p class="text-muted mb-0">
                        Progress evaluasi Anda: <strong><?= $evaluationProgress['completed'] ?></strong> 
                        dari <strong><?= $evaluationProgress['total'] ?></strong> proyek 
                        (<strong><?= $evaluationProgress['percentage'] ?>%</strong> selesai)
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="progress" style="height: 12px;">
                        <div class="progress-bar" style="width: <?= $evaluationProgress['percentage'] ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!$projectId): ?>
        <!-- Project Selection -->
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-list-check me-2"></i>
                            Pilih Proyek untuk Dievaluasi
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($projects)): ?>
                            <div class="row g-3">
                                <?php foreach ($projects as $project): ?>
                                    <?php
                                    $isCompleted = hasCompletedEvaluation($project['id'], $user['id'], $userRole);
                                    $cardClass = $isCompleted ? 'border-success' : 'border-primary';
                                    $badgeClass = $isCompleted ? 'bg-success' : 'bg-warning';
                                    $badgeText = $isCompleted ? 'Selesai' : 'Belum Selesai';
                                    ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="card h-100 <?= $cardClass ?> fade-highlight">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <code class="bg-light px-2 py-1 rounded"><?= escape($project['code']) ?></code>
                                                    <span class="badge <?= $badgeClass ?>"><?= $badgeText ?></span>
                                                </div>
                                                
                                                <h6 class="card-title"><?= escape($project['name']) ?></h6>
                                                <p class="card-text text-muted small">
                                                    <i class="bi bi-geo-alt me-1"></i><?= escape($project['location']) ?><br>
                                                    <i class="bi bi-calendar me-1"></i><?= formatDate($project['date_offer']) ?>
                                                </p>
                                                
                                                <div class="d-grid">
                                                    <a href="evaluate.php?project=<?= $project['id'] ?>" 
                                                       class="btn <?= $isCompleted ? 'btn-outline-success' : 'btn-primary' ?>">
                                                        <i class="bi bi-<?= $isCompleted ? 'eye' : 'clipboard-check' ?> me-1"></i>
                                                        <?= $isCompleted ? 'Lihat Evaluasi' : 'Mulai Evaluasi' ?>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-inbox fs-1 text-muted"></i>
                                <h5 class="text-muted mt-3">Belum ada proyek tersedia</h5>
                                <p class="text-muted">Silakan hubungi administrator untuk menambahkan proyek</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php else: ?>
        <!-- Evaluation Form -->
        <div class="row">
            <div class="col-lg-12">
                <!-- Project Info -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h5 class="mb-1">
                                    <code class="me-2"><?= escape($selectedProject['code']) ?></code>
                                    <?= escape($selectedProject['name']) ?>
                                </h5>
                                <p class="text-muted mb-0">
                                    <i class="bi bi-geo-alt me-1"></i><?= escape($selectedProject['location']) ?> | 
                                    <i class="bi bi-calendar me-1"></i><?= formatDate($selectedProject['date_offer']) ?>
                                </p>
                                <?php if (!empty($selectedProject['description'])): ?>
                                    <p class="text-muted mt-2 mb-0">
                                        <i class="bi bi-info-circle me-1"></i><?= escape($selectedProject['description']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4 text-end">
                                <a href="evaluate.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left me-1"></i>Pilih Proyek Lain
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Evaluation Form -->
                <form method="POST" action="" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="project_id" value="<?= $projectId ?>">
                    
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="bi bi-clipboard-data me-2"></i>
                                Evaluasi Kriteria - Bidang <?= ucfirst($userRole) ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Evaluation Guidelines -->
                            <div class="alert alert-info mb-4">
                                <h6><i class="bi bi-info-circle me-1"></i>Panduan Penilaian:</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <ul class="mb-0">
                                            <li><strong>Skor 1-3:</strong> Buruk / Sangat rendah</li>
                                            <li><strong>Skor 4-6:</strong> Cukup / Sedang</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <ul class="mb-0">
                                            <li><strong>Skor 7-8:</strong> Baik / Tinggi</li>
                                            <li><strong>Skor 9-10:</strong> Sangat baik / Sangat tinggi</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Criteria Evaluation - Vertical Layout -->
                            <div class="criteria-container">
                                <?php foreach ($criteria as $index => $criterion): ?>
                                    <div class="criteria-card p-4 animate-fadeIn" style="animation-delay: <?= $index * 0.1 ?>s">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <div class="criteria-number"><?= $index + 1 ?></div>
                                                
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <h6 class="criteria-title"><?= escape($criterion['name']) ?></h6>
                                                    <span class="criteria-weight">Bobot: <?= formatNumber($criterion['weight'] * 100, 1) ?>%</span>
                                                </div>
                                                
                                                <p class="criteria-description"><?= escape($criterion['description']) ?></p>
                                                
                                                <?php if ($criterion['type'] === 'cost'): ?>
                                                    <div class="alert alert-warning py-2">
                                                        <small><i class="bi bi-info-circle me-1"></i>
                                                        <strong>Kriteria Cost:</strong> Semakin rendah nilai semakin baik</small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="col-md-4">
                                                <div class="score-section">
                                                    <label class="form-label fw-bold mb-3">Berikan Skor (1-10):</label>
                                                    
                                                    <input type="number" 
                                                           class="form-control score-input mb-3" 
                                                           name="scores[<?= $criterion['id'] ?>]" 
                                                           value="<?= $existingScores[$criterion['id']] ?? '' ?>"
                                                           min="1" 
                                                           max="10" 
                                                           step="0.1"
                                                           placeholder="1-10"
                                                           required>
                                                
                                                    <div class="score-range"></div>
                                                    <div class="score-labels">
                                                        <span><i class="bi bi-arrow-down"></i> Buruk (1)</span>
                                                        <span>Baik (10) <i class="bi bi-arrow-up"></i></span>
                                                    </div>
                                                    
                                                    <div class="invalid-feedback">
                                                        Skor harus antara 1-10
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Notes -->
                            <div class="mt-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0"><i class="bi bi-chat-left-text me-1"></i>Catatan Evaluasi</h6>
                                    </div>
                                    <div class="card-body">
                                        <textarea class="form-control" 
                                                  id="notes" 
                                                  name="notes" 
                                                  rows="4"
                                                  placeholder="Tambahkan catatan, komentar, atau penjelasan tambahan terkait evaluasi ini..."><?= escape($existingEvaluations[0]['notes'] ?? '') ?></textarea>
                                        <small class="text-muted">Catatan ini akan membantu dalam review dan analisis hasil evaluasi</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="mt-4 d-flex gap-3 justify-content-center">
                                <button type="submit" name="save_evaluation" class="btn btn-success btn-lg px-5">
                                    <i class="bi bi-check-circle me-2"></i>
                                    Simpan Evaluasi
                                </button>
                                <a href="evaluate.php" class="btn btn-outline-secondary btn-lg px-4">
                                    <i class="bi bi-arrow-left me-1"></i>
                                    Kembali
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Form validation
        (function() {
            'use strict';
            
            var forms = document.querySelectorAll('.needs-validation');
            
            Array.prototype.slice.call(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    
                    form.classList.add('was-validated');
                }, false);
            });
        })();
        
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
        
        // Real-time score validation and visual feedback
        document.addEventListener('DOMContentLoaded', function() {
            const scoreInputs = document.querySelectorAll('.score-input');
            
            scoreInputs.forEach(function(input) {
                input.addEventListener('input', function() {
                    const value = parseFloat(this.value);
                    const card = this.closest('.criteria-card');
                    
                    // Reset border colors
                    card.style.borderLeftColor = '#06b6d4';
                    
                    if (value >= 1 && value <= 10) {
                        this.style.borderColor = '#10b981';
                        this.style.backgroundColor = '#f0fdf4';
                        
                        if (value <= 3) {
                            card.style.borderLeftColor = '#ef4444';
                        } else if (value <= 6) {
                            card.style.borderLeftColor = '#f59e0b';
                        } else {
                            card.style.borderLeftColor = '#10b981';
                        }
                    } else if (this.value !== '') {
                        this.style.borderColor = '#ef4444';
                        this.style.backgroundColor = '#fef2f2';
                    } else {
                        this.style.borderColor = '#e2e8f0';
                        this.style.backgroundColor = '#ffffff';
                    }
                });
                
                // Add focus effect
                input.addEventListener('focus', function() {
                    this.closest('.criteria-card').style.transform = 'translateY(-2px)';
                    this.closest('.criteria-card').style.boxShadow = '0 12px 35px rgba(0,0,0,0.15)';
                });
                
                input.addEventListener('blur', function() {
                    this.closest('.criteria-card').style.transform = 'translateY(0)';
                    this.closest('.criteria-card').style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.1)';
                });
                
                // Trigger initial validation
                if (input.value) {
                    input.dispatchEvent(new Event('input'));
                }
            });
        });
        
        // Calculate and display evaluation progress
        function updateEvaluationProgress() {
            const scoreInputs = document.querySelectorAll('.score-input');
            let filledInputs = 0;
            
            scoreInputs.forEach(function(input) {
                if (input.value && input.value >= 1 && input.value <= 10) {
                    filledInputs++;
                }
            });
            
            const progress = scoreInputs.length > 0 ? (filledInputs / scoreInputs.length) * 100 : 0;
            
            // Update progress in card header if exists
            let progressInfo = document.querySelector('.progress-info');
            if (!progressInfo) {
                progressInfo = document.createElement('small');
                progressInfo.className = 'progress-info text-muted ms-2';
                document.querySelector('.card-header h5').appendChild(progressInfo);
            }
            
            progressInfo.textContent = `(${filledInputs}/${scoreInputs.length} kriteria)`;
        }
        
        // Update progress on input change
        document.addEventListener('input', function(e) {
            if (e.target.classList.contains('score-input')) {
                updateEvaluationProgress();
            }
        });
        
        // Initial progress update
        updateEvaluationProgress();
        
        // Scroll to first empty input
        document.addEventListener('DOMContentLoaded', function() {
            const firstEmptyInput = document.querySelector('.score-input:invalid, .score-input[value=""]');
            if (firstEmptyInput) {
                setTimeout(() => {
                    firstEmptyInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 500);
            }
        });
        
        // Prevent accidental page leave with unsaved changes
        let formChanged = false;
        document.addEventListener('input', function() {
            formChanged = true;
        });
        
        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = 'Anda memiliki perubahan yang belum disimpan. Yakin ingin meninggalkan halaman?';
            }
        });
        
        // Reset form changed flag on submit
        document.querySelector('form').addEventListener('submit', function() {
            formChanged = false;
        });
    </script>
</body>
</html>