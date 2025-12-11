<?php
/**
 * =====================================================
 * Register Page - GDSS System with Maintain.ly Theme
 * =====================================================
 */
require_once __DIR__ . '/config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

// Handle form submission
$registerError = '';
$registerSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'controllers/auth.php';
    $auth = new AuthController();
    $result = $auth->register($_POST);
    
    if ($result['success']) {
        $registerSuccess = $result['message'];
    } else {
        $registerError = $result['message'];
    }
}

$pageTitle = 'Register - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="id" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Inter Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-gray-50 via-white to-gray-100 min-h-screen text-slate-900">
    <!-- Animated Background Blobs -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-4 -left-4 w-72 h-72 bg-gradient-to-br from-cyan-500/20 to-emerald-500/20 rounded-full blur-3xl animate-blob"></div>
        <div class="absolute top-0 right-4 w-72 h-72 bg-gradient-to-br from-emerald-500/20 to-cyan-500/20 rounded-full blur-3xl animate-blob animation-delay-2s"></div>
        <div class="absolute -bottom-8 left-20 w-72 h-72 bg-gradient-to-br from-cyan-500/20 to-emerald-500/20 rounded-full blur-3xl animate-blob animation-delay-4s"></div>
    </div>

    <div class="container relative z-10 py-8">
        <!-- Register Row -->
        <div class="flex justify-center">
            <div class="w-full max-w-lg">
                <div class="bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-200 shadow-xl p-8 hover:border-cyan-400/50 transition-all duration-500 animate-scale-in">
                    <!-- Register Form -->
                    <div class="space-y-6">
                        <!-- Brand Header -->
                        <div class="text-center mb-8">
                            <div class="flex justify-center items-center space-x-3 mb-4">
                                <svg class="w-12 h-12 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                </svg>
                                <h1 class="text-4xl font-black bg-gradient-to-r from-cyan-400 to-emerald-400 bg-clip-text text-transparent tracking-tight">
                                    REGISTER
                                </h1>
                            </div>
                            <p class="text-gray-900 text-sm font-bold uppercase tracking-wider">Create New Account</p>
                            <div class="w-12 h-1 bg-gradient-to-r from-cyan-500 to-emerald-500 mx-auto rounded-full mt-4"></div>
                        </div>
                        
                        <!-- Alert Messages -->
                        <?php if (!empty($registerError)): ?>
                            <div class="bg-red-500/10 border border-red-500 text-red-400 backdrop-blur-sm rounded-xl p-4 mb-6 animate-slide-in flex items-center space-x-3">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                <span class="font-bold uppercase tracking-wide text-sm"><?php echo htmlspecialchars($registerError); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($registerSuccess)): ?>
                            <div class="bg-emerald-500/10 border border-emerald-500 text-emerald-400 backdrop-blur-sm rounded-xl p-4 mb-6 animate-slide-in flex items-center space-x-3">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="font-bold uppercase tracking-wide text-sm"><?php echo htmlspecialchars($registerSuccess); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Register Form -->
                        <form method="POST" action="" id="registerForm" class="space-y-6">
                            <!-- Full Name Field -->
                            <div class="space-y-2">
                                <label for="name" class="block text-sm font-bold text-gray-900 uppercase tracking-wider">
                                    Full Name
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                    </div>
                                    <input type="text" 
                                           class="w-full bg-white border border-gray-300 text-gray-900 text-sm rounded-xl pl-10 pr-4 py-3 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all duration-300 hover:border-gray-400" 
                                           id="name" 
                                           name="name" 
                                           placeholder="Enter your full name" 
                                           required 
                                                       autofocus
                                           value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                                </div>
                            </div>
                            
                            <!-- Username Field -->
                            <div class="space-y-2">
                                <label for="username" class="block text-sm font-bold text-gray-900 uppercase tracking-wider">
                                    Username
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                                        </svg>
                                    </div>
                                    <input type="text" 
                                           class="w-full bg-white border border-gray-300 text-gray-900 text-sm rounded-xl pl-10 pr-4 py-3 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all duration-300 hover:border-gray-400" 
                                           id="username" 
                                           name="username" 
                                           placeholder="Enter username" 
                                           required
                                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                                </div>
                                <p class="text-gray-900 text-xs font-semibold uppercase tracking-wider">
                                    Username must be unique and at least 3 characters
                                </p>
                            </div>
                            
                            <!-- Role Field -->
                            <div class="space-y-2">
                                <label for="role" class="block text-sm font-bold text-gray-900 uppercase tracking-wider">
                                    User Role
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </div>
                                    <select class="w-full bg-white border border-gray-300 text-gray-900 text-sm rounded-xl pl-10 pr-4 py-3 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all duration-300 hover:border-gray-400" 
                                            id="role" 
                                            name="role" 
                                            required>
                                        <option value="" class="bg-white text-gray-900">Choose Role</option>
                                        <option value="supervisor" class="bg-white text-gray-900" <?php echo (isset($_POST['role']) && $_POST['role'] == 'supervisor') ? 'selected' : ''; ?>>
                                            Supervisor
                                        </option>
                                        <option value="teknis" class="bg-white text-gray-900" <?php echo (isset($_POST['role']) && $_POST['role'] == 'teknis') ? 'selected' : ''; ?>>
                                            Technical Team
                                        </option>
                                        <option value="administrasi" class="bg-white text-gray-900" <?php echo (isset($_POST['role']) && $_POST['role'] == 'administrasi') ? 'selected' : ''; ?>>
                                            Administration
                                        </option>
                                        <option value="keuangan" class="bg-white text-gray-900" <?php echo (isset($_POST['role']) && $_POST['role'] == 'keuangan') ? 'selected' : ''; ?>>
                                            Finance
                                        </option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Password Field -->
                            <div class="space-y-2">
                                <label for="password" class="block text-sm font-bold text-gray-900 uppercase tracking-wider">
                                    Password
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                        </svg>
                                    </div>
                                    <input type="password" 
                                           class="w-full bg-white border border-gray-300 text-gray-900 text-sm rounded-xl pl-10 pr-12 py-3 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all duration-300 hover:border-gray-400" 
                                           id="password" 
                                           name="password" 
                                           placeholder="Enter your password" 
                                           required
                                           minlength="6">
                                    <button type="button" 
                                            id="togglePassword"
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-900 hover:text-black transition-colors duration-300">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                </div>
                                <p class="text-gray-900 text-xs font-semibold uppercase tracking-wider">
                                    Password minimum 6 characters
                                </p>
                            </div>
                            
                            <!-- Confirm Password Field -->
                            <div class="space-y-2">
                                <label for="confirm_password" class="block text-sm font-bold text-gray-900 uppercase tracking-wider">
                                    Confirm Password
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                        </svg>
                                    </div>
                                    <input type="password" 
                                           class="w-full bg-white border border-gray-300 text-gray-900 text-sm rounded-xl pl-10 pr-12 py-3 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all duration-300 hover:border-gray-400" 
                                           id="confirm_password" 
                                           name="confirm_password" 
                                           placeholder="Confirm your password" 
                                           required
                                           minlength="6">
                                    <button type="button" 
                                            id="toggleConfirmPassword"
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-900 hover:text-black transition-colors duration-300">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Register Button -->
                            <button type="submit" 
                                    class="w-full bg-gradient-to-r from-emerald-500 to-cyan-500 hover:from-emerald-400 hover:to-cyan-400 text-white py-4 px-6 rounded-xl text-sm font-black uppercase tracking-wider transition-all duration-300 hover:scale-105 hover:shadow-2xl hover:shadow-emerald-500/25 active:scale-95 flex items-center justify-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                </svg>
                                <span>Create Account</span>
                            </button>
                        </form>
                        
                        <!-- Login Link -->
                        <div class="text-center mt-6">
                            <p class="text-gray-900 text-sm font-semibold uppercase tracking-wide">
                                Already have an account? 
                                <a href="index.php" class="text-cyan-600 hover:text-cyan-500 font-bold underline hover:no-underline transition-all duration-300">
                                    Login Here
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- GDSS JavaScript -->
    <script src="assets/js/gdss.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Toggle password visibility for both fields
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const svg = this.querySelector('svg');
            
            if (password.type === 'password') {
                password.type = 'text';
                svg.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L7.05 7.05m2.828 2.828L12 12m7.95-7.95L21 3m-8.95 8.95l-2.829 2.828m0 0L8.05 15.05"></path>
                `;
                this.classList.add('text-cyan-400');
            } else {
                password.type = 'password';
                svg.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                `;
                this.classList.remove('text-cyan-400');
                this.classList.add('text-gray-900');
            }
        });
        
        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const confirmPassword = document.getElementById('confirm_password');
            const svg = this.querySelector('svg');
            
            if (confirmPassword.type === 'password') {
                confirmPassword.type = 'text';
                svg.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L7.05 7.05m2.828 2.828L12 12m7.95-7.95L21 3m-8.95 8.95l-2.829 2.828m0 0L8.05 15.05"></path>
                `;
                this.classList.add('text-cyan-400');
            } else {
                confirmPassword.type = 'password';
                svg.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                `;
                this.classList.remove('text-cyan-400');
                this.classList.add('text-gray-900');
            }
        });
        
        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const username = document.getElementById('username').value.trim();
            const role = document.getElementById('role').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            // Basic validation
            if (!name || !username || !role || !password || !confirmPassword) {
                e.preventDefault();
                showGlowAlert('danger', 'All fields must be filled!');
                return;
            }
            
            // Username length validation
            if (username.length < 3) {
                e.preventDefault();
                showGlowAlert('danger', 'Username must be at least 3 characters!');
                return;
            }
            
            // Password length validation
            if (password.length < 6) {
                e.preventDefault();
                showGlowAlert('danger', 'Password must be at least 6 characters!');
                return;
            }
            
            // Password confirmation validation
            if (password !== confirmPassword) {
                e.preventDefault();
                showGlowAlert('danger', 'Password confirmation does not match!');
                return;
            }
        });
        
        // Real-time password confirmation check
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && password !== confirmPassword) {
                this.classList.add('border-red-500');
                this.classList.remove('border-gray-200');
            } else {
                this.classList.remove('border-red-500');
                this.classList.add('border-gray-200');
            }
        });
        
        // Initialize theme
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof GDSS !== 'undefined') {
                GDSS.theme.init();
            }
        });
        
        // Auto dismiss alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>



