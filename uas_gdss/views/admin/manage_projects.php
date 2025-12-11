<?php
/**
 * =====================================================
 * Manage Projects - Admin Only
 * GDSS System Project Management
 * =====================================================
 */
ob_start();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/layout.php';
requireRole('admin');

$pdo = getConnection();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_project') {
        $code = trim($_POST['project_code']);
        $name = trim($_POST['project_name']);
        $location = trim($_POST['location']);
        $description = trim($_POST['description'] ?? '');
        
        if (empty($code) || empty($name) || empty($location)) {
            setFlashMessage('danger', 'Kode, nama, dan lokasi harus diisi.');
        } else {
            $stmt = $pdo->prepare("SELECT id FROM projects WHERE project_code = ?");
            $stmt->execute([$code]);
            
            if ($stmt->fetch()) {
                setFlashMessage('danger', 'Kode proyek sudah digunakan.');
            } else {
                $stmt = $pdo->prepare("INSERT INTO projects (project_code, project_name, location, description) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$code, $name, $location, $description])) {
                    setFlashMessage('success', 'Proyek berhasil ditambahkan.');
                } else {
                    setFlashMessage('danger', 'Gagal menambahkan proyek.');
                }
            }
        }
        header('Location: manage_projects.php');
        exit();
    } elseif ($_POST['action'] === 'edit_project') {
        $projectId = (int)$_POST['project_id'];
        $code = trim($_POST['project_code']);
        $name = trim($_POST['project_name']);
        $location = trim($_POST['location']);
        $description = trim($_POST['description'] ?? '');
        
        if (empty($code) || empty($name) || empty($location)) {
            setFlashMessage('danger', 'Kode, nama, dan lokasi harus diisi.');
        } else {
            // Check if code is used by another project
            $stmt = $pdo->prepare("SELECT id FROM projects WHERE project_code = ? AND id != ?");
            $stmt->execute([$code, $projectId]);
            
            if ($stmt->fetch()) {
                setFlashMessage('danger', 'Kode proyek sudah digunakan proyek lain.');
            } else {
                $stmt = $pdo->prepare("UPDATE projects SET project_code = ?, project_name = ?, location = ?, description = ? WHERE id = ?");
                if ($stmt->execute([$code, $name, $location, $description, $projectId])) {
                    setFlashMessage('success', 'Proyek berhasil diperbarui.');
                } else {
                    setFlashMessage('danger', 'Gagal memperbarui proyek.');
                }
            }
        }
        header('Location: manage_projects.php');
        exit();
    } elseif ($_POST['action'] === 'delete_project') {
        $projectId = (int)$_POST['project_id'];
        $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
        if ($stmt->execute([$projectId])) {
            setFlashMessage('success', 'Proyek berhasil dihapus.');
        } else {
            setFlashMessage('danger', 'Gagal menghapus proyek.');
        }
        header('Location: manage_projects.php');
        exit();
    }
}

// Get all projects
$stmt = $pdo->query("SELECT * FROM projects ORDER BY created_at DESC");
$projects = $stmt->fetchAll();

$pageTitle = 'Kelola Proyek - ' . SITE_NAME;
$flashMessage = getFlashMessage();

// Render page
renderHead($pageTitle, '../../');
renderSidebar('../../', 'manage_projects');
renderHeader('KELOLA PROYEK', 'Manajemen proyek IT untuk proses evaluasi TOPSIS', 'projects', false, '', '../../');
echo flashBox($flashMessage);
?>

<!-- Statistics Cards -->
<section class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <?= statCard('Total Proyek', count($projects), 'projects', 'cyan') ?>
    <?= statCard('Proyek Aktif', count($projects), 'check', 'emerald') ?>
    <?= statCard('Menunggu Evaluasi', count($projects), 'clock', 'amber') ?>
</section>

<!-- Add Project Button -->
<section class="mb-6">
    <button onclick="toggleModal('addProjectModal')" class="flex items-center gap-2 px-6 py-3 rounded-xl font-bold uppercase tracking-wide text-sm bg-gradient-to-r from-emerald-500 to-emerald-600 text-white hover:from-emerald-400 hover:to-emerald-500 transition-all duration-300 shadow-lg shadow-emerald-500/25">
        <?= icon('add', 'w-5 h-5') ?>
        Tambah Proyek
    </button>
</section>

<!-- Projects Grid -->
<section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
    <?php if (empty($projects)): ?>
    <div class="col-span-full bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-200 p-12 text-center">
        <?= icon('projects', 'w-20 h-20 text-slate-600 mx-auto mb-4') ?>
        <h4 class="text-xl font-bold text-gray-900 mb-2">Belum Ada Proyek</h4>
        <p class="text-gray-900">Klik tombol "Tambah Proyek" untuk membuat proyek baru</p>
    </div>
    <?php else: ?>
    <?php foreach ($projects as $project): ?>
    <div class="bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-200 overflow-hidden hover:border-emerald-500/30 transition-all duration-300">
        <div class="bg-gradient-to-r from-emerald-500/20 to-cyan-500/20 px-6 py-4 border-b border-gray-200/50">
            <div class="flex items-start justify-between">
                <span class="px-3 py-1 bg-emerald-500/20 text-emerald-400 rounded-full text-xs font-bold uppercase">
                    <?= htmlspecialchars($project['project_code']) ?>
                </span>
                <div class="flex items-center gap-2">
                    <button onclick="openEditProjectModal(<?= htmlspecialchars(json_encode($project)) ?>)" class="text-cyan-400 hover:text-cyan-300 transition-colors duration-200">
                        <?= icon('edit', 'w-5 h-5') ?>
                    </button>
                    <form method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus proyek ini?')">
                        <input type="hidden" name="action" value="delete_project">
                        <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                        <button type="submit" class="text-red-400 hover:text-red-300 transition-colors duration-200">
                            <?= icon('delete', 'w-5 h-5') ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <div class="p-6">
            <h5 class="text-lg font-bold text-gray-900 mb-3"><?= htmlspecialchars($project['project_name']) ?></h5>
            <div class="space-y-2 text-sm text-gray-900">
                <p class="flex items-center gap-2">
                    <?= icon('location', 'w-4 h-4') ?>
                    <?= htmlspecialchars($project['location']) ?>
                </p>
                <p class="flex items-center gap-2">
                    <?= icon('calendar', 'w-4 h-4') ?>
                    <?= formatDate($project['created_at']) ?>
                </p>
                <?php if (!empty($project['description'])): ?>
                <p class="text-gray-500 text-xs mt-2"><?= htmlspecialchars(substr($project['description'], 0, 100)) ?>...</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</section>

<!-- Add Project Modal -->
<div id="addProjectModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal('addProjectModal')"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md">
        <div class="bg-white border border-gray-200 rounded-2xl shadow-xl">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                <h5 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <?= icon('add', 'w-5 h-5 text-emerald-400') ?>
                    Tambah Proyek Baru
                </h5>
                <button onclick="toggleModal('addProjectModal')" class="text-gray-900 hover:text-black">
                    <?= icon('close', 'w-5 h-5') ?>
                </button>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="add_project">
                
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-900 uppercase tracking-wider">Kode Proyek</label>
                    <input type="text" name="project_code" class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all duration-300" required>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-900 uppercase tracking-wider">Nama Proyek</label>
                    <input type="text" name="project_name" class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all duration-300" required>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-900 uppercase tracking-wider">Lokasi</label>
                    <input type="text" name="location" class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all duration-300" required>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-900 uppercase tracking-wider">Deskripsi (Opsional)</label>
                    <textarea name="description" rows="3" class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all duration-300"></textarea>
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="toggleModal('addProjectModal')" class="flex-1 px-4 py-3 rounded-xl font-bold uppercase tracking-wide text-sm bg-slate-700 text-gray-900 hover:bg-slate-600 transition-all duration-300">
                        Batal
                    </button>
                    <button type="submit" class="flex-1 flex items-center justify-center gap-2 px-4 py-3 rounded-xl font-bold uppercase tracking-wide text-sm bg-gradient-to-r from-emerald-500 to-emerald-600 text-white hover:from-emerald-400 hover:to-emerald-500 transition-all duration-300">
                        <?= icon('save', 'w-5 h-5') ?>
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Project Modal -->
<div id="editProjectModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal('editProjectModal')"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md">
        <div class="bg-white border border-gray-200 rounded-2xl shadow-xl">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                <h5 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <?= icon('edit', 'w-5 h-5 text-cyan-400') ?>
                    Edit Proyek
                </h5>
                <button onclick="toggleModal('editProjectModal')" class="text-gray-900 hover:text-black">
                    <?= icon('close', 'w-5 h-5') ?>
                </button>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="edit_project">
                <input type="hidden" name="project_id" id="edit_project_id">
                
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-900 uppercase tracking-wider">Kode Proyek</label>
                    <input type="text" name="project_code" id="edit_project_code" class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all duration-300" required>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-900 uppercase tracking-wider">Nama Proyek</label>
                    <input type="text" name="project_name" id="edit_project_name" class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all duration-300" required>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-900 uppercase tracking-wider">Lokasi</label>
                    <input type="text" name="location" id="edit_location" class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all duration-300" required>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-900 uppercase tracking-wider">Deskripsi (Opsional)</label>
                    <textarea name="description" id="edit_description" rows="3" class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all duration-300"></textarea>
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="toggleModal('editProjectModal')" class="flex-1 px-4 py-3 rounded-xl font-bold uppercase tracking-wide text-sm bg-slate-700 text-gray-900 hover:bg-slate-600 transition-all duration-300">
                        Batal
                    </button>
                    <button type="submit" class="flex-1 flex items-center justify-center gap-2 px-4 py-3 rounded-xl font-bold uppercase tracking-wide text-sm bg-gradient-to-r from-cyan-500 to-cyan-600 text-white hover:from-cyan-400 hover:to-cyan-500 transition-all duration-300">
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

function openEditProjectModal(project) {
    document.getElementById('edit_project_id').value = project.id;
    document.getElementById('edit_project_code').value = project.project_code;
    document.getElementById('edit_project_name').value = project.project_name;
    document.getElementById('edit_location').value = project.location;
    document.getElementById('edit_description').value = project.description || '';
    toggleModal('editProjectModal');
}
</script>

<?php renderFooter(); ?>







