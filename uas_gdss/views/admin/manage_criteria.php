<?php
/**
 * =====================================================
 * Manage Criteria - Admin Only
 * GDSS System Criteria Management
 * =====================================================
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/layout.php';
requireRole('admin');

$pdo = getConnection();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_criteria') {
        $name = trim($_POST['name']);
        $type = $_POST['type'];
        $weight = (float)$_POST['weight'];
        $field = $_POST['field'];
        
        if (empty($name) || empty($type) || empty($field) || $weight <= 0) {
            setFlashMessage('danger', 'Semua field harus diisi dengan benar.');
        } else {
            $stmt = $pdo->prepare("INSERT INTO criteria (name, type, weight, field) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$name, $type, $weight, $field])) {
                setFlashMessage('success', 'Kriteria berhasil ditambahkan.');
            } else {
                setFlashMessage('danger', 'Gagal menambahkan kriteria.');
            }
        }
        header('Location: manage_criteria.php');
        exit();
    } elseif ($_POST['action'] === 'edit_criteria') {
        $criteriaId = (int)$_POST['criteria_id'];
        $name = trim($_POST['name']);
        $type = $_POST['type'];
        $weight = (float)$_POST['weight'];
        $field = $_POST['field'];
        
        if (empty($name) || empty($type) || empty($field) || $weight <= 0) {
            setFlashMessage('danger', 'Semua field harus diisi dengan benar.');
        } else {
            $stmt = $pdo->prepare("UPDATE criteria SET name = ?, type = ?, weight = ?, field = ? WHERE id = ?");
            if ($stmt->execute([$name, $type, $weight, $field, $criteriaId])) {
                setFlashMessage('success', 'Kriteria berhasil diperbarui.');
            } else {
                setFlashMessage('danger', 'Gagal memperbarui kriteria.');
            }
        }
        header('Location: manage_criteria.php');
        exit();
    } elseif ($_POST['action'] === 'delete_criteria') {
        $criteriaId = (int)$_POST['criteria_id'];
        $stmt = $pdo->prepare("DELETE FROM criteria WHERE id = ?");
        if ($stmt->execute([$criteriaId])) {
            setFlashMessage('success', 'Kriteria berhasil dihapus.');
        } else {
            setFlashMessage('danger', 'Gagal menghapus kriteria.');
        }
        header('Location: manage_criteria.php');
        exit();
    }
}

// Get all criteria grouped by field
$stmt = $pdo->query("SELECT * FROM criteria ORDER BY field, name");
$allCriteria = $stmt->fetchAll();

$criteriaByField = [
    'teknis' => array_filter($allCriteria, fn($c) => $c['field'] === 'teknis'),
    'supervisor' => array_filter($allCriteria, fn($c) => $c['field'] === 'supervisor'),
    'keuangan' => array_filter($allCriteria, fn($c) => $c['field'] === 'keuangan')
];

$pageTitle = 'Kelola Kriteria - ' . SITE_NAME;
$flashMessage = getFlashMessage();

// Render page
renderHead($pageTitle, '../../');
renderSidebar('../../', 'manage_criteria');
renderHeader('KELOLA KRITERIA', 'Manajemen kriteria evaluasi untuk setiap bidang Decision Maker', 'criteria', false, '', '../../');
echo flashBox($flashMessage);
?>

<!-- Statistics Cards -->
<section class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <?= statCard('Total Kriteria', count($allCriteria), 'criteria', 'cyan') ?>
    <?= statCard('Kriteria Supervisor', count($criteriaByField['supervisor']), 'shield', 'purple') ?>
    <?= statCard('Kriteria Teknis', count($criteriaByField['teknis']), 'cogs', 'cyan') ?>
    <?= statCard('Kriteria Keuangan', count($criteriaByField['keuangan']), 'dollar', 'amber') ?>
</section>

<!-- Add Criteria Button -->
<section class="mb-6">
    <button onclick="toggleModal('addCriteriaModal')" class="flex items-center gap-2 px-6 py-3 rounded-xl font-bold uppercase tracking-wide text-sm bg-gradient-to-r from-purple-500 to-purple-600 text-white hover:from-purple-400 hover:to-purple-500 transition-all duration-300 shadow-lg shadow-purple-500/25">
        <?= icon('add', 'w-5 h-5') ?>
        Tambah Kriteria
    </button>
</section>

<!-- Criteria by Field -->
<?php 
$fieldConfigs = [
    'supervisor' => ['title' => 'Kriteria Supervisor', 'color' => 'purple', 'icon' => 'shield'],
    'teknis' => ['title' => 'Kriteria Teknis', 'color' => 'cyan', 'icon' => 'cogs'],
    'keuangan' => ['title' => 'Kriteria Keuangan', 'color' => 'amber', 'icon' => 'dollar']
];

foreach ($fieldConfigs as $field => $config): 
    $criteria = $criteriaByField[$field];
?>
<section class="mb-8 bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-200">
    <div class="flex items-center justify-between border-b border-gray-200/50 px-6 py-4">
        <h5 class="text-lg font-bold text-gray-900 flex items-center gap-2">
            <?= icon($config['icon'], 'w-5 h-5 text-'.$config['color'].'-400') ?>
            <?= $config['title'] ?>
            <span class="px-2 py-1 bg-<?= $config['color'] ?>-500/20 text-<?= $config['color'] ?>-400 rounded-full text-xs font-bold">
                <?= count($criteria) ?>
            </span>
        </h5>
    </div>
    <div class="p-6">
        <?php if (empty($criteria)): ?>
        <div class="text-center py-8 text-gray-900">
            <p>Belum ada kriteria untuk bidang ini</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="border-b-2 border-gray-300">
                        <th class="text-left py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs w-16">#</th>
                        <th class="text-left py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Nama Kriteria</th>
                        <th class="text-center py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs w-32">Tipe</th>
                        <th class="text-center py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs w-24">Bobot</th>
                        <th class="text-center py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs w-48">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($criteria as $c): ?>
                    <tr class="border-b border-gray-200 hover:bg-gray-50 transition-colors duration-200">
                        <td class="py-4 px-4 text-gray-900"><?= $i++ ?></td>
                        <td class="py-4 px-4 text-gray-900 font-medium"><?= htmlspecialchars($c['name']) ?></td>
                        <td class="py-4 px-4 text-center">
                            <span class="px-3 py-1 rounded-full text-xs font-bold <?= $c['type'] === 'benefit' ? 'bg-emerald-500 text-white' : 'bg-rose-500 text-white' ?>">
                                <?= ucfirst($c['type']) ?>
                            </span>
                        </td>
                        <td class="py-4 px-4 text-center text-gray-900 font-bold"><?= $c['weight'] ?></td>
                        <td class="py-4 px-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button onclick="openEditCriteriaModal(<?= htmlspecialchars(json_encode($c)) ?>)" class="inline-flex items-center gap-1 px-3 py-2 rounded-lg bg-cyan-500 text-white hover:bg-cyan-600 text-xs font-bold transition-colors duration-200">
                                    <?= icon('edit', 'w-3 h-3') ?> Edit
                                </button>
                                <form method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus kriteria ini?')">
                                    <input type="hidden" name="action" value="delete_criteria">
                                    <input type="hidden" name="criteria_id" value="<?= $c['id'] ?>">
                                    <button type="submit" class="inline-flex items-center gap-1 px-3 py-2 rounded-lg bg-rose-500 text-white hover:bg-rose-600 text-xs font-bold transition-colors duration-200">
                                        <?= icon('delete', 'w-3 h-3') ?> Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php endforeach; ?>

<!-- Add Criteria Modal -->
<div id="addCriteriaModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal('addCriteriaModal')"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md">
        <div class="bg-white border border-gray-200 rounded-2xl shadow-xl">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                <h5 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <?= icon('add', 'w-5 h-5 text-purple-400') ?>
                    Tambah Kriteria Baru
                </h5>
                <button onclick="toggleModal('addCriteriaModal')" class="text-gray-900 hover:text-black">
                    <?= icon('close', 'w-5 h-5') ?>
                </button>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="add_criteria">
                
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-900 uppercase tracking-wider">Nama Kriteria</label>
                    <input type="text" name="name" class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-all duration-300" required>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-900 uppercase tracking-wider">Bidang</label>
                    <select name="field" class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-all duration-300" required>
                        <option value="">Pilih Bidang...</option>
                        <option value="teknis">Teknis</option>
                        <option value="supervisor">supervisor</option>
                        <option value="keuangan">Keuangan</option>
                    </select>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-900 uppercase tracking-wider">Tipe</label>
                    <select name="type" class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-all duration-300" required>
                        <option value="">Pilih Tipe...</option>
                        <option value="benefit">Benefit (Semakin tinggi semakin baik)</option>
                        <option value="cost">Cost (Semakin rendah semakin baik)</option>
                    </select>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-900 uppercase tracking-wider">Bobot</label>
                    <input type="number" name="weight" min="0.1" step="0.1" class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-all duration-300" required>
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="toggleModal('addCriteriaModal')" class="flex-1 px-4 py-3 rounded-xl font-bold uppercase tracking-wide text-sm bg-slate-700 text-gray-900 hover:bg-slate-600 transition-all duration-300">
                        Batal
                    </button>
                    <button type="submit" class="flex-1 flex items-center justify-center gap-2 px-4 py-3 rounded-xl font-bold uppercase tracking-wide text-sm bg-gradient-to-r from-purple-500 to-purple-600 text-white hover:from-purple-400 hover:to-purple-500 transition-all duration-300">
                        <?= icon('save', 'w-5 h-5') ?>
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Criteria Modal -->
<div id="editCriteriaModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal('editCriteriaModal')"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md">
        <div class="bg-white border border-gray-200 rounded-2xl shadow-xl">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                <h5 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <?= icon('edit', 'w-5 h-5 text-cyan-400') ?>
                    Edit Kriteria
                </h5>
                <button onclick="toggleModal('editCriteriaModal')" class="text-gray-900 hover:text-black">
                    <?= icon('close', 'w-5 h-5') ?>
                </button>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="edit_criteria">
                <input type="hidden" name="criteria_id" id="edit_criteria_id">
                
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-900 uppercase tracking-wider">Nama Kriteria</label>
                    <input type="text" name="name" id="edit_criteria_name" class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-all duration-300" required>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-900 uppercase tracking-wider">Bidang</label>
                    <select name="field" id="edit_criteria_field" class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-all duration-300" required>
                        <option value="teknis">Teknis</option>
                        <option value="supervisor">supervisor</option>
                        <option value="keuangan">Keuangan</option>
                    </select>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-900 uppercase tracking-wider">Tipe</label>
                    <select name="type" id="edit_criteria_type" class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-all duration-300" required>
                        <option value="benefit">Benefit (Semakin tinggi semakin baik)</option>
                        <option value="cost">Cost (Semakin rendah semakin baik)</option>
                    </select>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-900 uppercase tracking-wider">Bobot</label>
                    <input type="number" name="weight" id="edit_criteria_weight" min="0.1" step="0.1" class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-all duration-300" required>
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="toggleModal('editCriteriaModal')" class="flex-1 px-4 py-3 rounded-xl font-bold uppercase tracking-wide text-sm bg-slate-700 text-gray-900 hover:bg-slate-600 transition-all duration-300">
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

function openEditCriteriaModal(criteria) {
    document.getElementById('edit_criteria_id').value = criteria.id;
    document.getElementById('edit_criteria_name').value = criteria.name;
    document.getElementById('edit_criteria_field').value = criteria.field;
    document.getElementById('edit_criteria_type').value = criteria.type;
    document.getElementById('edit_criteria_weight').value = criteria.weight;
    toggleModal('editCriteriaModal');
}
</script>

<?php renderFooter(); ?>






