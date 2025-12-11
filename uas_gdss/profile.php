<?php
/**
 * =====================================================
 * Profile Page - GDSS System
 * User Profile Management
 * =====================================================
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/layout.php';
requireLogin();

// Get user data
$pdo = getConnection();
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_profile') {
        $name = trim($_POST['name']);
        $username = trim($_POST['username']);
        
        if (empty($name) || empty($username)) {
            setFlashMessage('danger', 'Nama dan username harus diisi.');
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $_SESSION['user_id']]);
            
            if ($stmt->fetch()) {
                setFlashMessage('danger', 'Username sudah digunakan oleh user lain.');
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, username = ? WHERE id = ?");
                if ($stmt->execute([$name, $username, $_SESSION['user_id']])) {
                    $_SESSION['name'] = $name;
                    $_SESSION['username'] = $username;
                    setFlashMessage('success', 'Profil berhasil diperbarui.');
                    header('Location: profile.php');
                    exit();
                } else {
                    setFlashMessage('danger', 'Gagal memperbarui profil.');
                }
            }
        }
    } elseif ($_POST['action'] === 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            setFlashMessage('danger', 'Semua field password harus diisi.');
        } elseif ($new_password !== $confirm_password) {
            setFlashMessage('danger', 'Konfirmasi password tidak cocok.');
        } elseif (strlen($new_password) < 6) {
            setFlashMessage('danger', 'Password baru minimal 6 karakter.');
        } elseif (!password_verify($current_password, $user['password'])) {
            setFlashMessage('danger', 'Password saat ini salah.');
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            
            if ($stmt->execute([$hashed_password, $_SESSION['user_id']])) {
                setFlashMessage('success', 'Password berhasil diubah.');
                header('Location: profile.php');
                exit();
            } else {
                setFlashMessage('danger', 'Gagal mengubah password.');
            }
        }
    }
}

$pageTitle = 'Profil - ' . SITE_NAME;
$flashMessage = getFlashMessage();
$roleColors = getRoleColors($user['role']);

// Render page
renderHead($pageTitle);
renderSidebar('', 'profile');
renderHeader('PROFIL PENGGUNA', 'Kelola informasi akun Anda', 'user');
echo flashBox($flashMessage);
?>

<!-- Profile Header Card -->
<section class="mb-8 bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-200 p-8 text-center card-hover animate-slide-up">
    <div class="w-24 h-24 mx-auto mb-4 bg-gradient-to-br <?= $roleColors['gradient'] ?> rounded-full flex items-center justify-center shadow-xl <?= $roleColors['shadow'] ?> animate-float">
        <?= icon('user', 'w-12 h-12 text-white') ?>
    </div>
    <h3 class="text-2xl font-black text-gray-900 mb-2 animate-fade-in delay-200"><?= htmlspecialchars($user['name']) ?></h3>
    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-gradient-to-r <?= $roleColors['badge'] ?> text-white text-sm font-bold uppercase shadow-lg <?= $roleColors['shadow'] ?> animate-bounce-in delay-300">
        <span class="w-2 h-2 bg-white rounded-full animate-pulse"></span>
        <?= htmlspecialchars($user['role']) ?>
    </span>
    <p class="text-gray-900 mt-4 flex items-center justify-center gap-2 animate-fade-in delay-400">
        <?= icon('calendar', 'w-4 h-4') ?>
        Member since <?= date('d F Y', strtotime($user['created_at'])) ?>
    </p>
</section>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Update Profile Form -->
    <section class="bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-200 card-hover animate-fade-left delay-300">
        <div class="border-b border-gray-200/50 px-6 py-4">
            <h5 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                <?= icon('edit', 'w-5 h-5 text-cyan-400') ?>
                Update Profil
            </h5>
        </div>
        <div class="p-6">
            <form method="POST" class="space-y-6">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-900 uppercase tracking-wider">Nama Lengkap</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" 
                           class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all duration-300" required>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-900 uppercase tracking-wider">Username</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" 
                           class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all duration-300" required>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-900 uppercase tracking-wider">Role</label>
                    <input type="text" value="<?= ucfirst(htmlspecialchars($user['role'])) ?>" 
                           class="w-full px-4 py-3 bg-slate-700/50 border border-gray-300 rounded-lg text-gray-900 cursor-not-allowed" readonly>
                    <p class="text-xs text-gray-500">Role tidak dapat diubah. Hubungi supervisor jika diperlukan.</p>
                </div>
                
                <button type="submit" class="w-full flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-bold uppercase tracking-wide text-sm bg-gradient-to-r from-cyan-500 to-cyan-600 text-white hover:from-cyan-400 hover:to-cyan-500 transition-all duration-300">
                    <?= icon('save', 'w-5 h-5') ?>
                    Simpan Perubahan
                </button>
            </form>
        </div>
    </section>

    <!-- Change Password Form -->
    <section class="bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-200 hover:border-emerald-500/30 transition-all duration-500">
        <div class="border-b border-gray-200/50 px-6 py-4">
            <h5 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                <?= icon('shield', 'w-5 h-5 text-emerald-400') ?>
                Ubah Password
            </h5>
        </div>
        <div class="p-6">
            <form method="POST" id="changePasswordForm" class="space-y-6">
                <input type="hidden" name="action" value="change_password">
                
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-900 uppercase tracking-wider">Password Saat Ini</label>
                    <div class="relative">
                        <input type="password" name="current_password" id="current_password"
                               class="w-full px-4 py-3 pr-12 bg-white border border-gray-300 rounded-lg text-gray-900 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all duration-300" 
                               placeholder="Masukkan password saat ini" required>
                        <button type="button" onclick="togglePassword('current_password')" 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-900 hover:text-black">
                            <?= icon('eye', 'w-5 h-5') ?>
                        </button>
                    </div>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-900 uppercase tracking-wider">Password Baru</label>
                    <div class="relative">
                        <input type="password" name="new_password" id="new_password" minlength="6"
                               class="w-full px-4 py-3 pr-12 bg-white border border-gray-300 rounded-lg text-gray-900 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all duration-300" 
                               placeholder="Masukkan password baru" required>
                        <button type="button" onclick="togglePassword('new_password')" 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-900 hover:text-black">
                            <?= icon('eye', 'w-5 h-5') ?>
                        </button>
                    </div>
                    <p class="text-xs text-gray-500">Minimal 6 karakter</p>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-900 uppercase tracking-wider">Konfirmasi Password</label>
                    <div class="relative">
                        <input type="password" name="confirm_password" id="confirm_password" minlength="6"
                               class="w-full px-4 py-3 pr-12 bg-white border border-gray-300 rounded-lg text-gray-900 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all duration-300" 
                               placeholder="Konfirmasi password baru" required>
                        <button type="button" onclick="togglePassword('confirm_password')" 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-900 hover:text-black">
                            <?= icon('eye', 'w-5 h-5') ?>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="w-full flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-bold uppercase tracking-wide text-sm bg-gradient-to-r from-emerald-500 to-emerald-600 text-white hover:from-emerald-400 hover:to-emerald-500 transition-all duration-300">
                    <?= icon('shield', 'w-5 h-5') ?>
                    Ubah Password
                </button>
            </form>
        </div>
    </section>
</div>

<!-- Action Buttons -->
<section class="mt-8 bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-200 p-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <a href="dashboard.php" class="flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-bold uppercase tracking-wide text-sm bg-gradient-to-r from-cyan-500 to-cyan-600 text-white hover:from-cyan-400 hover:to-cyan-500 transition-all duration-300">
            <?= icon('home', 'w-5 h-5') ?>
            Kembali ke Dashboard
        </a>
        <a href="logout.php" onclick="return confirm('Yakin ingin logout?')" 
           class="flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-bold uppercase tracking-wide text-sm bg-gradient-to-r from-red-500 to-red-600 text-white hover:from-red-400 hover:to-red-500 transition-all duration-300">
            <?= icon('logout', 'w-5 h-5') ?>
            Logout
        </a>
    </div>
</section>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    field.type = field.type === 'password' ? 'text' : 'password';
}

document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (newPassword !== confirmPassword) {
        e.preventDefault();
        alert('Konfirmasi password tidak cocok!');
        return false;
    }
});
</script>

<?php renderFooter(); ?>







