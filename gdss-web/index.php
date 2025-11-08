<?php
require_once 'config.php';
require_once 'functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

// Proses login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi';
    } else {
        $user = authenticate($username, $password);
        if ($user) {
            redirect('dashboard.php');
        } else {
            $error = 'Username atau password salah';
        }
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
    <title><?php echo APP_NAME; ?> - Login</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #F8FAFC 0%, #E2E8F0 100%);
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
        }
        
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(226, 232, 240, 0.8);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
        }
        
        .login-left {
            background: linear-gradient(135deg, #3B82F6, #1E40AF);
            color: white;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: center;
        }
        
        .login-right {
            padding: 50px 40px;
        }
        
        .demo-accounts {
            background: rgba(248, 250, 252, 0.8);
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .demo-account {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 12px;
            border: 1px solid rgba(226, 232, 240, 0.8);
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .demo-account:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border-color: #3B82F6;
        }
        
        .demo-account:last-child {
            margin-bottom: 0;
        }
        
        .demo-account .role-badge {
            font-size: 0.8rem;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .demo-account .credentials {
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            color: #666;
        }
        
        .form-control {
            border-radius: 8px;
            border: 1px solid #D1D5DB;
            padding: 12px 16px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.2s ease;
        }
        
        .form-control:focus {
            border-color: #3B82F6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            outline: none;
        }
        
        .btn-login {
            background: #3B82F6;
            border: none;
            border-radius: 8px;
            padding: 12px 30px;
            font-weight: 500;
            font-family: 'Poppins', sans-serif;
            color: white;
            transition: all 0.2s ease;
        }
        
        .btn-login:hover {
            background: #1D4ED8;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            color: white;
        }
        
        .system-logo {
            margin-bottom: 20px;
            opacity: 0.9;
        }
        
        .feature-list {
            text-align: left;
            margin-top: 30px;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            font-size: 0.95rem;
        }
        
        .feature-item i {
            margin-right: 12px;
            color: rgba(255, 255, 255, 0.8);
        }
        
        .reference-note {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            font-size: 12px;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .login-left, .login-right {
                padding: 40px 30px;
            }
            
            .demo-accounts {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="row g-0">
                <!-- Left Panel - System Info -->
                <div class="col-lg-5">
                    <div class="login-left">
                        <div class="system-logo mb-4">
                            <img src="assets/images/logo.svg" alt="GDSS Logo" width="80" height="80">
                        </div>
                        
                        <h2 class="mb-3 fw-bold" style="font-family: 'Poppins', sans-serif;">GDSS</h2>
                        <h6 class="mb-2 fw-normal opacity-90" style="font-size: 14px;">Group Decision Support System</h6>
                        <p class="mb-4 small opacity-75" style="font-size: 12px;">
                            <i class="bi bi-diagram-2 me-1"></i>
                            <strong>AHP + BORDA Method</strong>
                        </p>
                        
                        <p class="mb-4 opacity-80">
                            Sistem pendukung keputusan kelompok untuk menentukan prioritas proyek IT 
                            menggunakan metode Analytic Hierarchy Process (AHP) dan agregasi BORDA.
                        </p>
                        
                        <div class="feature-list">
                            <div class="feature-item">
                                <i class="bi bi-diagram-2"></i>
                                <span>AHP Pairwise Comparison</span>
                            </div>
                            <div class="feature-item">
                                <i class="bi bi-check-circle"></i>
                                <span>Consistency Validation (CR ≤ 0.1)</span>
                            </div>
                            <div class="feature-item">
                                <i class="bi bi-trophy"></i>
                                <span>BORDA Consensus Aggregation</span>
                            </div>
                            <div class="feature-item">
                                <i class="bi bi-people-fill"></i>
                                <span>Multi-Role Decision Making</span>
                            </div>
                            <div class="feature-item">
                                <i class="bi bi-shield-check"></i>
                                <span>Secure Role-Based Access</span>
                            </div>
                        </div>
                        
                        <!-- Reference Citation -->
                        <div class="reference-note">
                            <i class="bi bi-journal-text me-2"></i>
                            <strong>Enhanced with AHP Method:</strong><br>
                            Saaty Scale (1-9) Pairwise Comparison<br>
                            <em>Analytic Hierarchy Process + BORDA</em>
                        </div>
                    </div>
                </div>
                
                <!-- Right Panel - Login Form -->
                <div class="col-lg-7">
                    <div class="login-right">
                        <h3 class="mb-4 fw-bold text-center">Login ke Sistem</h3>
                        
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
                        
                        <!-- Demo Accounts -->
                        <div class="demo-accounts">
                            <h6 class="mb-2 fw-bold text-center">
                                <i class="bi bi-key me-2"></i>Akun Demo - Klik untuk Login Cepat
                            </h6>
                            <p class="text-center small text-muted mb-3">
                                <i class="bi bi-arrow-repeat me-1"></i>
                                <strong>AHP Workflow:</strong> Pairwise Comparison → Consistency Check → Global Ranking
                            </p>
                            
                            <div class="demo-account" onclick="fillLogin('supervisor', 'supervisor123')">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong><i class="bi bi-shield-check me-2"></i>Supervisor</strong>
                                    <span class="badge bg-danger role-badge">SUPERVISOR</span>
                                </div>
                                <div class="credentials">
                                    <i class="bi bi-person me-1"></i>Username: <strong>supervisor</strong> | 
                                    <i class="bi bi-lock me-1"></i>Password: <strong>supervisor123</strong>
                                </div>
                                <small class="text-muted">Kelola proyek, finalisasi konsensus & laporan</small>
                            </div>
                            
                            <div class="demo-account" onclick="fillLogin('teknis', 'teknis123')">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong><i class="bi bi-cpu me-2"></i>Bidang Teknis</strong>
                                    <span class="badge bg-primary role-badge">TEKNIS</span>
                                </div>
                                <div class="credentials">
                                    <i class="bi bi-person me-1"></i>Username: <strong>teknis</strong> | 
                                    <i class="bi bi-lock me-1"></i>Password: <strong>teknis123</strong>
                                </div>
                                <small class="text-muted">AHP Evaluasi 5 kriteria teknis (Bobot BORDA: 7/13)</small>
                            </div>
                            
                            <div class="demo-account" onclick="fillLogin('administrasi', 'administrasi123')">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong><i class="bi bi-file-text me-2"></i>Bidang Administrasi</strong>
                                    <span class="badge bg-success role-badge">ADMINISTRASI</span>
                                </div>
                                <div class="credentials">
                                    <i class="bi bi-person me-1"></i>Username: <strong>administrasi</strong> | 
                                    <i class="bi bi-lock me-1"></i>Password: <strong>administrasi123</strong>
                                </div>
                                <small class="text-muted">AHP Evaluasi 4 kriteria administrasi (Bobot BORDA: 4/13)</small>
                            </div>
                            
                            <div class="demo-account" onclick="fillLogin('keuangan', 'keuangan123')">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong><i class="bi bi-currency-dollar me-2"></i>Bidang Keuangan</strong>
                                    <span class="badge bg-warning role-badge">KEUANGAN</span>
                                </div>
                                <div class="credentials">
                                    <i class="bi bi-person me-1"></i>Username: <strong>keuangan</strong> | 
                                    <i class="bi bi-lock me-1"></i>Password: <strong>keuangan123</strong>
                                </div>
                                <small class="text-muted">AHP Evaluasi 3 kriteria keuangan (Bobot BORDA: 2/13)</small>
                            </div>
                        </div>
                        
                        <!-- Login Form -->
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            
                            <div class="mb-3">
                                <label for="username" class="form-label fw-semibold">
                                    <i class="bi bi-person me-1"></i>Username
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="username" 
                                       name="username" 
                                       placeholder="Masukkan username"
                                       value="<?= escape($_POST['username'] ?? '') ?>"
                                       required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="password" class="form-label fw-semibold">
                                    <i class="bi bi-lock me-1"></i>Password
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control" 
                                           id="password" 
                                           name="password" 
                                           placeholder="Masukkan password"
                                           required>
                                    <button class="btn btn-outline-secondary" 
                                            type="button" 
                                            id="togglePassword">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-login btn-lg">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>
                                    Login ke Dashboard
                                </button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-4">
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                GDSS dengan Analytic Hierarchy Process & BORDA Consensus
                            </small>
                            <br>
                            <small class="text-muted mt-1 d-block">
                                <i class="bi bi-diagram-2 me-1"></i>
                                Pairwise Comparison • Consistency Check • Multi-Criteria Decision Analysis
                            </small>
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
        // Fill login form when demo account is clicked
        function fillLogin(username, password) {
            document.getElementById('username').value = username;
            document.getElementById('password').value = password;
            
            // Add visual feedback
            const demoAccounts = document.querySelectorAll('.demo-account');
            demoAccounts.forEach(account => {
                account.style.borderColor = 'rgba(0, 0, 0, 0.1)';
                account.style.backgroundColor = 'white';
            });
            
            event.currentTarget.style.borderColor = '#06b6d4';
            event.currentTarget.style.backgroundColor = 'rgba(6, 182, 212, 0.1)';
            
            // Show AHP info
            const roleMap = {
                'supervisor': 'Supervisor - Kelola sistem & finalisasi konsensus',
                'teknis': 'Bidang Teknis - Perbandingan berpasangan 5 kriteria teknis',
                'administrasi': 'Bidang Administrasi - Perbandingan berpasangan 4 kriteria administrasi', 
                'keuangan': 'Bidang Keuangan - Perbandingan berpasangan 3 kriteria keuangan'
            };
            
            // Focus on login button with role info
            setTimeout(() => {
                document.querySelector('.btn-login').focus();
                console.log(`Selected: ${roleMap[username] || username}`);
            }, 200);
        }
        
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordField = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                passwordField.type = 'password';
                icon.className = 'bi bi-eye';
            }
        });
        
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
        
        // Form animation on focus
        const formControls = document.querySelectorAll('.form-control');
        formControls.forEach(control => {
            control.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
                this.parentElement.style.transition = 'transform 0.2s ease';
            });
            
            control.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
        
        // Add loading state on form submit
        document.querySelector('form').addEventListener('submit', function() {
            const submitBtn = document.querySelector('.btn-login');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Memverifikasi...';
            submitBtn.disabled = true;
            
            // Re-enable if form submission fails (for validation errors)
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 3000);
        });
    </script>
</body>
</html>