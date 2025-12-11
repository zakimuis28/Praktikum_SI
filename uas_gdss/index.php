<?php
/**
 * =====================================================
 * Login Page - GDSS System
 * =====================================================
 */
require_once __DIR__ . '/config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

// Handle form submission
$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'controllers/auth.php';
    $auth = new AuthController();
    $result = $auth->login($_POST['username'] ?? '', $_POST['password'] ?? '');
    
    if ($result['success']) {
        header('Location: dashboard.php');
        exit();
    } else {
        $loginError = $result['message'];
    }
}

$pageTitle = 'Login - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="id" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Bootstrap 5 CSS (for existing components) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Inter Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        @keyframes blob { 0%, 100% { transform: translate(0, 0) scale(1); } 33% { transform: translate(30px, -50px) scale(1.1); } 66% { transform: translate(-20px, 20px) scale(0.9); } }
        .animate-blob { animation: blob 7s infinite; }
        .animation-delay-2s { animation-delay: 2s; }
        .animation-delay-4s { animation-delay: 4s; }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
        .animate-float { animation: float 3s ease-in-out infinite; }
        @keyframes scaleIn { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }
        .animate-scale-in { animation: scaleIn 0.5s ease-out forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fade-in { animation: fadeIn 0.5s ease-out forwards; }
        @keyframes gradientText { 0%, 100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }
        .animate-gradient-text { background: linear-gradient(90deg, #06b6d4, #10b981, #8b5cf6, #06b6d4); background-size: 300% 100%; -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; animation: gradientText 4s ease infinite; }
        .delay-100 { animation-delay: 0.1s; opacity: 0; }
        .delay-200 { animation-delay: 0.2s; opacity: 0; }
        .delay-300 { animation-delay: 0.3s; opacity: 0; }
        .hover-lift { transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .hover-lift:hover { transform: translateY(-3px); box-shadow: 0 10px 40px rgba(0,0,0,0.3); }
        .btn-ripple { position: relative; overflow: hidden; }
        .btn-ripple::after { content: ''; position: absolute; width: 100%; height: 100%; top: 0; left: 0; pointer-events: none; background-image: radial-gradient(circle, #fff 10%, transparent 10.01%); background-repeat: no-repeat; background-position: 50%; transform: scale(10, 10); opacity: 0; transition: transform .5s, opacity 1s; }
        .btn-ripple:active::after { transform: scale(0, 0); opacity: .3; transition: 0s; }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 via-white to-gray-100 min-h-screen flex items-center justify-center">
    <!-- Animated Background Blobs -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-4 -left-4 w-72 h-72 bg-gradient-to-br from-cyan-500/20 to-emerald-500/20 rounded-full blur-3xl animate-blob"></div>
        <div class="absolute top-0 right-4 w-72 h-72 bg-gradient-to-br from-emerald-500/20 to-cyan-500/20 rounded-full blur-3xl animate-blob animation-delay-2s"></div>
        <div class="absolute -bottom-8 left-20 w-72 h-72 bg-gradient-to-br from-cyan-500/20 to-emerald-500/20 rounded-full blur-3xl animate-blob animation-delay-4s"></div>
    </div>

    <div class="container relative z-10">
        <!-- Login Row -->
        <div class="flex justify-center">
            <div class="w-full max-w-md">
                <div class="bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-200 shadow-xl p-8 hover:border-cyan-400/50 transition-all duration-500 animate-scale-in hover-lift">
                    <!-- Login Form -->
                    <div class="w-full">
                        <div class="space-y-6">
                            <!-- Brand Header -->
                            <div class="text-center mb-8">
                                <div class="flex justify-center items-center space-x-3 mb-4">
                                    <svg class="w-12 h-12 text-cyan-400 animate-float" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                    </svg>
                                    <h1 class="text-4xl font-black animate-gradient-text tracking-tight">
                                        GDSS
                                    </h1>
                                </div>
                                <p class="text-gray-900 text-sm font-bold uppercase tracking-wider animate-fade-in delay-100">Group Decision Support System</p>
                                <div class="w-12 h-1 bg-gradient-to-r from-cyan-500 to-emerald-500 mx-auto rounded-full mt-4 animate-fade-in delay-200"></div>
                            </div>
                            
                            <!-- Alert Messages -->
                            <?php if (!empty($loginError)): ?>
                                <div class="bg-red-500/10 border border-red-500 text-red-400 backdrop-blur-sm rounded-xl p-4 mb-6 animate-slide-in flex items-center space-x-3">
                                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                    <span class="font-bold uppercase tracking-wide text-sm"><?php echo htmlspecialchars($loginError); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($_GET['logout'])): ?>
                                <div class="bg-emerald-500/10 border border-emerald-500 text-emerald-400 backdrop-blur-sm rounded-xl p-4 mb-6 animate-slide-in flex items-center space-x-3">
                                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="font-bold uppercase tracking-wide text-sm">LOGOUT SUCCESSFUL</span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($_GET['timeout'])): ?>
                                <div class="bg-yellow-500/10 border border-yellow-500 text-yellow-400 backdrop-blur-sm rounded-xl p-4 mb-6 animate-slide-in flex items-center space-x-3">
                                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="font-bold uppercase tracking-wide text-sm">SESSION TIMEOUT - Please login again</span>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Login Form -->
                            <form method="POST" action="" id="loginForm" class="space-y-6">
                                <!-- Username Field -->
                                <div class="space-y-2">
                                    <label for="username" class="block text-sm font-bold text-gray-900 uppercase tracking-wider">
                                        Username
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                        </div>
                                        <input type="text" 
                                               class="w-full bg-white border border-gray-300 text-gray-900 text-sm rounded-xl pl-10 pr-4 py-3 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all duration-300 hover:border-gray-400" 
                                               id="username" 
                                               name="username" 
                                               placeholder="Enter your username" 
                                               required 
                                               autofocus>
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
                                               required>
                                        <button type="button" 
                                                id="togglePassword"
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-900 hover:text-black transition-colors duration-300">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Remember Me -->
                                <div class="flex items-center">
                                    <input type="checkbox" 
                                           id="rememberMe"
                                           class="w-4 h-4 text-cyan-500 bg-white border-gray-300 rounded focus:ring-cyan-500 focus:ring-2">
                                    <label for="rememberMe" class="ml-2 block text-sm text-gray-900 font-semibold uppercase tracking-wide">
                                        Remember Me
                                    </label>
                                </div>
                                
                                <!-- Login Button -->
                                <button type="submit" 
                                        class="w-full bg-gradient-to-r from-cyan-500 to-emerald-500 hover:from-cyan-400 hover:to-emerald-400 text-white py-4 px-6 rounded-xl text-sm font-black uppercase tracking-wider transition-all duration-300 hover:scale-105 hover:shadow-2xl hover:shadow-cyan-500/25 active:scale-95 flex items-center justify-center space-x-2 btn-ripple">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                                    </svg>
                                    <span>Sign In</span>
                                </button>
                            </form>
                            
                            <!-- Register Link -->
                            <div class="text-center mt-6">
                                <p class="text-gray-900 text-sm font-semibold uppercase tracking-wide">
                                    Need an account? 
                                    <a href="register.php" class="text-cyan-600 hover:text-cyan-500 font-bold underline hover:no-underline transition-all duration-300">
                                        Register Here
                                    </a>
                                </p>
                            </div>
                            
                            <!-- Divider -->
                            <div class="my-8 border-t border-gray-200"></div>
                            
                            <!-- Demo Accounts Info -->
                            <div class="bg-gray-50 backdrop-blur-sm rounded-xl border border-gray-200 p-6">
                                <div class="flex items-center space-x-2 mb-4">
                                    <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <h6 class="text-gray-900 font-black uppercase tracking-wide">
                                        Demo Accounts
                                    </h6>
                                    <span class="text-xs text-gray-500">(click to fill)</span>
                                </div>
                                <div class="space-y-2 text-sm">
                                    <button type="button" onclick="fillCredentials('admin', 'admin123')" 
                                            class="w-full flex justify-between items-center py-2 px-3 rounded-lg hover:bg-white transition-all duration-200 group cursor-pointer border border-gray-200">
                                        <span class="text-gray-900 font-bold uppercase group-hover:text-rose-600 transition-colors">Admin (CRUD):</span>
                                        <code class="bg-gray-100 text-rose-600 px-2 py-1 rounded text-xs font-mono group-hover:ring-2 group-hover:ring-rose-500/50">admin / admin123</code>
                                    </button>
                                    <button type="button" onclick="fillCredentials('supervisor', 'supervisor123')" 
                                            class="w-full flex justify-between items-center py-2 px-3 rounded-lg hover:bg-white transition-all duration-200 group cursor-pointer border border-gray-200">
                                        <span class="text-gray-900 font-bold uppercase group-hover:text-purple-600 transition-colors">Supervisor (DM1):</span>
                                        <code class="bg-gray-100 text-purple-600 px-2 py-1 rounded text-xs font-mono group-hover:ring-2 group-hover:ring-purple-500/50">supervisor / supervisor123</code>
                                    </button>
                                    <button type="button" onclick="fillCredentials('teknis', 'teknis123')" 
                                            class="w-full flex justify-between items-center py-2 px-3 rounded-lg hover:bg-white transition-all duration-200 group cursor-pointer border border-gray-200">
                                        <span class="text-gray-900 font-bold uppercase group-hover:text-cyan-600 transition-colors">Teknis (DM2):</span>
                                        <code class="bg-gray-100 text-cyan-600 px-2 py-1 rounded text-xs font-mono group-hover:ring-2 group-hover:ring-cyan-500/50">teknis / teknis123</code>
                                    </button>
                                    <button type="button" onclick="fillCredentials('keuangan', 'keuangan123')" 
                                            class="w-full flex justify-between items-center py-2 px-3 rounded-lg hover:bg-white transition-all duration-200 group cursor-pointer border border-gray-200">
                                        <span class="text-gray-900 font-bold uppercase group-hover:text-amber-600 transition-colors">Keuangan (DM3):</span>
                                        <code class="bg-gray-100 text-amber-600 px-2 py-1 rounded text-xs font-mono group-hover:ring-2 group-hover:ring-amber-500/50">keuangan / keuangan123</code>
                                    </button>
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
    
    <!-- Custom JS -->
    <script>
        // Fill credentials function for demo accounts
        function fillCredentials(username, password) {
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');
            
            // Add animation
            usernameInput.classList.add('ring-2', 'ring-cyan-500');
            passwordInput.classList.add('ring-2', 'ring-cyan-500');
            
            // Fill values
            usernameInput.value = username;
            passwordInput.value = password;
            
            // Remove animation after 500ms
            setTimeout(() => {
                usernameInput.classList.remove('ring-2', 'ring-cyan-500');
                passwordInput.classList.remove('ring-2', 'ring-cyan-500');
            }, 500);
            
            // Focus on login button
            document.querySelector('button[type="submit"]').focus();
        }
        
        // Toggle password visibility
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
        
        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            
            if (!username || !password) {
                e.preventDefault();
                alert('Username dan password harus diisi!');
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


