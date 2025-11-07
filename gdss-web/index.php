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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
        }
        
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
            max-width: 1000px;
            width: 100%;
        }
        
        .login-left {
            background: linear-gradient(135deg, #06b6d4, #f59e0b);
            color: white;
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: center;
        }
        
        .login-right {
            padding: 60px 40px;
        }
        
        .demo-accounts {
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.1), rgba(245, 158, 11, 0.1));
            border: 1px solid rgba(6, 182, 212, 0.2);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .demo-account {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .demo-account:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-color: #06b6d4;
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
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            padding: 12px 16px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #06b6d4;
            box-shadow: 0 0 0 0.2rem rgba(6, 182, 212, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #06b6d4, #f59e0b);
            border: none;
            border-radius: 12px;
            padding: 12px 30px;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(6, 182, 212, 0.3);
            color: white;
        }
        
        .system-logo {
            font-size: 4rem;
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
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            font-size: 0.85rem;
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
                        <div class="system-logo">
                            <i class="bi bi-diagram-3"></i>
                        </div>
                        
                        <h2 class="mb-3 fw-bold">GDSS</h2>
                        <h5 class="mb-4 fw-normal opacity-90">Group Decision Support System</h5>
                        
                        <p class="mb-4 opacity-80">
                            Sistem pendukung keputusan kelompok untuk menentukan prioritas proyek IT 
                            menggunakan metode Weighted Product dan agregasi BORDA.
                        </p>
                        
                        <div class="feature-list">
                            <div class="feature-item">
                                <i class="bi bi-people-fill"></i>
                                <span>Multi-Role Decision Making</span>
                            </div>
                            <div class="feature-item">
                                <i class="bi bi-calculator"></i>
                                <span>Weighted Product Method</span>
                            </div>
                            <div class="feature-item">
                                <i class="bi bi-trophy"></i>
                                <span>BORDA Consensus Aggregation</span>
                            </div>
                            <div class="feature-item">
                                <i class="bi bi-shield-check"></i>
                                <span>Secure Role-Based Access</span>
                            </div>
                        </div>
                        
                        <!-- Reference Citation -->
                        <div class="reference-note">
                            <i class="bi bi-journal-text me-2"></i>
                            <strong>Based on SINTA Research:</strong><br>
                            Cahyana, N.H. & Aribowo, A.S. (2014)<br>
                            <em>"GDSS untuk Menentukan Prioritas Proyek"</em>
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
                            <h6 class="mb-3 fw-bold text-center">
                                <i class="bi bi-key me-2"></i>Akun Demo - Klik untuk Login Cepat
                            </h6>
                            
                            <div class="demo-account" onclick="fillLogin('admin', 'admin123')">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong><i class="bi bi-shield-check me-2"></i>Administrator</strong>
                                    <span class="badge bg-danger role-badge">ADMIN</span>
                                </div>
                                <div class="credentials">
                                    <i class="bi bi-person me-1"></i>Username: <strong>admin</strong> | 
                                    <i class="bi bi-lock me-1"></i>Password: <strong>admin123</strong>
                                </div>
                                <small class="text-muted">Kelola proyek, finalisasi konsensus & laporan</small>
                            </div>
                            
                            <div class="demo-account" onclick="fillLogin('teknis', 'teknis123')">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong><i class="bi bi-cpu me-2"></i>Tim Teknis</strong>
                                    <span class="badge bg-primary role-badge">TEKNIS</span>
                                </div>
                                <div class="credentials">
                                    <i class="bi bi-person me-1"></i>Username: <strong>teknis</strong> | 
                                    <i class="bi bi-lock me-1"></i>Password: <strong>teknis123</strong>
                                </div>
                                <small class="text-muted">Evaluasi 5 kriteria teknis (Bobot: 53.8%)</small>
                            </div>
                            
                            <div class="demo-account" onclick="fillLogin('administrasi', 'admin123')">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong><i class="bi bi-file-text me-2"></i>Tim Administrasi</strong>
                                    <span class="badge bg-success role-badge">ADMINISTRASI</span>
                                </div>
                                <div class="credentials">
                                    <i class="bi bi-person me-1"></i>Username: <strong>administrasi</strong> | 
                                    <i class="bi bi-lock me-1"></i>Password: <strong>admin123</strong>
                                </div>
                                <small class="text-muted">Evaluasi 4 kriteria administrasi (Bobot: 30.8%)</small>
                            </div>
                            
                            <div class="demo-account" onclick="fillLogin('keuangan', 'keuangan123')">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong><i class="bi bi-currency-dollar me-2"></i>Tim Keuangan</strong>
                                    <span class="badge bg-warning role-badge">KEUANGAN</span>
                                </div>
                                <div class="credentials">
                                    <i class="bi bi-person me-1"></i>Username: <strong>keuangan</strong> | 
                                    <i class="bi bi-lock me-1"></i>Password: <strong>keuangan123</strong>
                                </div>
                                <small class="text-muted">Evaluasi 3 kriteria keuangan (Bobot: 15.4%)</small>
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
                                Implementasi GDSS berdasarkan artikel terakreditasi SINTA
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
            demoAccounts.forEach(account => account.style.borderColor = 'rgba(0, 0, 0, 0.1)');
            
            event.currentTarget.style.borderColor = '#06b6d4';
            event.currentTarget.style.backgroundColor = 'rgba(6, 182, 212, 0.1)';
            
            // Focus on login button
            setTimeout(() => {
                document.querySelector('.btn-login').focus();
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