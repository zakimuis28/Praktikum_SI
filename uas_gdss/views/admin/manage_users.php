<?php
/**
 * =====================================================
 * Manage Users - Admin Only
 * GDSS System User Management
 * =====================================================
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/layout.php';
requireRole('admin');

$pdo = getConnection();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_user') {
        $name = trim($_POST['name']);
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $role = $_POST['role'];
        
        if (empty($name) || empty($username) || empty($password) || empty($role)) {
            setFlashMessage('danger', 'Semua field harus diisi.');
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($stmt->fetch()) {
                setFlashMessage('danger', 'Username sudah digunakan.');
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name, username, password, role) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$name, $username, $hashed, $role])) {
                    setFlashMessage('success', 'User berhasil ditambahkan.');
                } else {
                    setFlashMessage('danger', 'Gagal menambahkan user.');
                }
            }
        }
        header('Location: manage_users.php');
        exit();
    } elseif ($_POST['action'] === 'edit_user') {
        $userId = (int)$_POST['user_id'];
        $name = trim($_POST['name']);
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $role = $_POST['role'];
        
        if (empty($name) || empty($username) || empty($role)) {
            setFlashMessage('danger', 'Nama, username, dan role harus diisi.');
        } else {
            // Check if username already used by other user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $userId]);
            
            if ($stmt->fetch()) {
                setFlashMessage('danger', 'Username sudah digunakan user lain.');
            } else {
                // Update with or without password
                if (!empty($password)) {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, username = ?, password = ?, role = ? WHERE id = ?");
                    $result = $stmt->execute([$name, $username, $hashed, $role, $userId]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, username = ?, role = ? WHERE id = ?");
                    $result = $stmt->execute([$name, $username, $role, $userId]);
                }
                
                if ($result) {
                    setFlashMessage('success', 'User berhasil diperbarui.');
                } else {
                    setFlashMessage('danger', 'Gagal memperbarui user.');
                }
            }
        }
        header('Location: manage_users.php');
        exit();
    } elseif ($_POST['action'] === 'delete_user') {
        $userId = (int)$_POST['user_id'];
        if ($userId !== $_SESSION['user_id']) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt->execute([$userId])) {
                setFlashMessage('success', 'User berhasil dihapus.');
            } else {
                setFlashMessage('danger', 'Gagal menghapus user.');
            }
        } else {
            setFlashMessage('danger', 'Tidak bisa menghapus akun sendiri.');
        }
        header('Location: manage_users.php');
        exit();
    }
}

// Get all users
$stmt = $pdo->query("SELECT * FROM users ORDER BY role, name");
$users = $stmt->fetchAll();

$pageTitle = 'Kelola User - ' . SITE_NAME;
$flashMessage = getFlashMessage();

// Statistics
$stats = [
    'total' => count($users),
    'supervisor' => count(array_filter($users, fn($u) => $u['role'] === 'supervisor')),
    'decision_maker' => count(array_filter($users, fn($u) => in_array($u['role'], ['teknis', 'administrasi', 'keuangan'])))
];

// Render page
renderHead($pageTitle, '../../');
renderSidebar('../../', 'manage_users');
renderHeader('KELOLA USER', 'Manajemen pengguna sistem GDSS', 'users', false, '', '../../');
echo flashBox($flashMessage);
?>

<!-- Statistics Cards -->
<section class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <?= statCard('Total User', $stats['total'], 'users', 'cyan') ?>
    <?= statCard('Supervisor', $stats['supervisor'], 'shield', 'emerald') ?>
    <?= statCard('Decision Maker', $stats['decision_maker'], 'criteria', 'amber') ?>
</section>

<!-- Add User Button -->
<section class="mb-6">
    <button onclick="toggleModal('addUserModal')" class="flex items-center gap-2 px-6 py-3 rounded-xl font-bold uppercase tracking-wide text-sm bg-gradient-to-r from-cyan-500 to-cyan-600 text-white hover:from-cyan-400 hover:to-cyan-500 transition-all duration-300 shadow-lg shadow-cyan-500/25">
        <?= icon('add', 'w-5 h-5') ?>
        Tambah User
    </button>
</section>

<!-- Users Table -->
<section class="bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-200">
    <div class="flex items-center justify-between border-b border-gray-200/50 px-6 py-4">
        <h5 class="text-lg font-bold text-gray-900 flex items-center gap-2">
            <?= icon('users', 'w-5 h-5 text-cyan-400') ?>
            Daftar User
        </h5>
    </div>
    <div class="p-6">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">#</th>
                        <th class="text-left py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Nama</th>
                        <th class="text-left py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Username</th>
                        <th class="text-center py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Role</th>
                        <th class="text-center py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Terdaftar</th>
                        <th class="text-center py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $index => $u): 
                        $roleColors = [
                            'supervisor' => 'bg-purple-500/20 text-purple-400',
                            'teknis' => 'bg-cyan-500/20 text-cyan-400',
                            'administrasi' => 'bg-emerald-500/20 text-emerald-400',
                            'keuangan' => 'bg-amber-500/20 text-amber-400'
                        ];
                        $roleColor = $roleColors[$u['role']] ?? 'bg-slate-500/20 text-gray-900';
                    ?>
                    <tr class="border-b border-gray-200/50 hover:bg-gray-100 transition-colors duration-200">
                        <td class="py-4 px-4 text-gray-900"><?= $index + 1 ?></td>
                        <td class="py-4 px-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-cyan-500 to-emerald-500 rounded-full flex items-center justify-center">
                                    <?= icon('user', 'w-5 h-5 text-slate-900') ?>
                                </div>
                                <span class="text-gray-900 font-medium"><?= htmlspecialchars($u['name']) ?></span>
                            </div>
                        </td>
                        <td class="py-4 px-4 text-gray-900"><?= htmlspecialchars($u['username']) ?></td>
                        <td class="py-4 px-4 text-center">
                            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase <?= $roleColor ?>">
                                <?= htmlspecialchars($u['role']) ?>
                            </span>
                        </td>
                        <td class="py-4 px-4 text-center text-gray-900 text-sm"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                        <td class="py-4 px-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button onclick="openEditModal(<?= htmlspecialchars(json_encode($u)) ?>)" class="inline-flex items-center gap-1 px-3 py-2 rounded-lg bg-cyan-500 text-white hover:bg-cyan-600 text-xs font-bold transition-colors duration-200">
                                    <?= icon('edit', 'w-3 h-3') ?> Edit
                                </button>
                                <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                <form method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus user ini?')">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="inline-flex items-center gap-1 px-3 py-2 rounded-lg bg-rose-500 text-white hover:bg-rose-600 text-xs font-bold transition-colors duration-200">
                                        <?= icon('delete', 'w-3 h-3') ?> Hapus
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- Add User Modal -->
<div id="addUserModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal('addUserModal')"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md">
        <div class="bg-white border border-gray-200 rounded-2xl shadow-xl">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                <h5 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <?= icon('add', 'w-5 h-5 text-cyan-400') ?>
                    Tambah User Baru
                </h5>
                <button onclick="toggleModal('addUserModal')" class="text-gray-900 hover:text-black">
                    <?= icon('close', 'w-5 h-5') ?>
                </button>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="add_user">
                
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-900 uppercase tracking-wider">Nama Lengkap</label>
                    <input type="text" name="name" class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all duration-300" required>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-900 uppercase tracking-wider">Username</label>
                    <input type="text" name="username" class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all duration-300" required>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-900 uppercase tracking-wider">Password</label>
                    <input type="password" name="password" minlength="6" class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all duration-300" required>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-900 uppercase tracking-wider">Role</label>
                    <select name="role" class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all duration-300" required>
                        <option value="">Pilih Role...</option>
                        <option value="admin">Admin</option>
                        <option value="supervisor">Supervisor (DM1)</option>
                        <option value="teknis">Teknis (DM2)</option>
                        <option value="keuangan">Keuangan (DM3)</option>
                    </select>
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="toggleModal('addUserModal')" class="flex-1 px-4 py-3 rounded-xl font-bold uppercase tracking-wide text-sm bg-slate-700 text-gray-900 hover:bg-slate-600 transition-all duration-300">
                        Batal
                    </button>
                    <button type="submit" class="flex-1 flex items-center justify-center gap-2 px-4 py-3 rounded-xl font-bold uppercase tracking-wide text-sm bg-gradient-to-r from-cyan-500 to-cyan-600 text-white hover:from-cyan-400 hover:to-cyan-500 transition-all duration-300">
                        <?= icon('save', 'w-5 h-5') ?>
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal('editUserModal')"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md">
        <div class="bg-white border border-gray-200 rounded-2xl shadow-xl">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                <h5 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <?= icon('edit', 'w-5 h-5 text-cyan-400') ?>
                    Edit User
                </h5>
                <button onclick="toggleModal('editUserModal')" class="text-gray-900 hover:text-black">
                    <?= icon('close', 'w-5 h-5') ?>
                </button>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-900 uppercase tracking-wider">Nama Lengkap</label>
                    <input type="text" name="name" id="edit_name" class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all duration-300" required>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-900 uppercase tracking-wider">Username</label>
                    <input type="text" name="username" id="edit_username" class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all duration-300" required>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-900 uppercase tracking-wider">Password <span class="text-gray-500 font-normal normal-case">(kosongkan jika tidak diubah)</span></label>
                    <input type="password" name="password" id="edit_password" minlength="6" class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all duration-300">
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-900 uppercase tracking-wider">Role</label>
                    <select name="role" id="edit_role" class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all duration-300" required>
                        <option value="admin">Admin</option>
                        <option value="supervisor">Supervisor (DM1)</option>
                        <option value="teknis">Teknis (DM2)</option>
                        <option value="keuangan">Keuangan (DM3)</option>
                    </select>
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="toggleModal('editUserModal')" class="flex-1 px-4 py-3 rounded-xl font-bold uppercase tracking-wide text-sm bg-slate-700 text-gray-900 hover:bg-slate-600 transition-all duration-300">
                        Batal
                    </button>
                    <button type="submit" class="flex-1 flex items-center justify-center gap-2 px-4 py-3 rounded-xl font-bold uppercase tracking-wide text-sm bg-gradient-to-r from-emerald-500 to-emerald-600 text-white hover:from-emerald-400 hover:to-emerald-500 transition-all duration-300">
                        <?= icon('save', 'w-5 h-5') ?>
                        Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleModal(id) {
    const modal = document.getElementById(id);
    modal.classList.toggle('hidden');
}

function openEditModal(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_name').value = user.name;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_password').value = '';
    document.getElementById('edit_role').value = user.role;
    toggleModal('editUserModal');
}
</script>

<?php renderFooter(); ?>







