<?php
/**
 * TOPSIS Results Page
 * Menampilkan hasil perhitungan TOPSIS per field Decision Maker
 * 
 * @package GDSS TOPSIS + BORDA
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/controllers/topsis_controller.php';

// Check login
requireLogin();

$userRole = $_SESSION['role'];
$pageTitle = 'Hasil TOPSIS - ' . ucfirst($userRole);

// Determine which field to show based on role
$allowedFields = [];
switch ($userRole) {
    case 'admin':
        $allowedFields = ['supervisor', 'teknis', 'keuangan'];
        break;
    case 'supervisor':
        $allowedFields = ['supervisor'];
        break;
    case 'teknis':
        $allowedFields = ['teknis'];
        break;
    case 'keuangan':
        $allowedFields = ['keuangan'];
        break;
    default:
        $allowedFields = [];
}

// Get selected field from query param
$selectedField = isset($_GET['field']) ? $_GET['field'] : (count($allowedFields) > 0 ? $allowedFields[0] : '');

// Validate field access
if (!in_array($selectedField, $allowedFields)) {
    $selectedField = count($allowedFields) > 0 ? $allowedFields[0] : '';
}

$topsisController = new TopsisController();
$topsisResults = [];
$calculationDetails = [];
$message = '';
$messageType = '';

// Handle calculate action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'calculate' && !empty($selectedField)) {
        $result = $topsisController->calculateTopsis($selectedField);
        if ($result['success']) {
            $message = $result['message'];
            $messageType = 'success';
        } else {
            $message = $result['message'];
            $messageType = 'error';
        }
    }
}

// Get TOPSIS results for selected field
if (!empty($selectedField)) {
    $topsisResults = $topsisController->getTopsisResults($selectedField);
    $calculationDetails = $topsisController->getTopsisCalculationDetails($selectedField);
}

// Check if user has evaluated all projects
$hasEvaluated = false;
if (!empty($selectedField)) {
    try {
        $db = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        $stmt = $db->prepare("
            SELECT COUNT(DISTINCT s.project_id) as evaluated_count,
                   (SELECT COUNT(*) FROM projects WHERE status = 'active') as total_projects
            FROM scores s
            JOIN criteria c ON s.criteria_id = c.id
            WHERE s.user_id = :user_id AND c.field = :field
        ");
        $stmt->execute(['user_id' => $_SESSION['user_id'], 'field' => $selectedField]);
        $evalStatus = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $hasEvaluated = $evalStatus['evaluated_count'] >= $evalStatus['total_projects'] && $evalStatus['total_projects'] > 0;
    } catch (PDOException $e) {
        // Silent fail
    }
}

// Include layout after all logic is complete
require_once __DIR__ . '/includes/layout.php';

// Prepare flash message
$flashMessage = null;
if (!empty($message)) {
    $flashMessage = [
        'type' => $messageType === 'success' ? 'success' : 'danger',
        'message' => $message
    ];
}

// Render layout
renderHead($pageTitle, '');
renderSidebar('', 'topsis_results');
renderHeader('HASIL PERHITUNGAN TOPSIS', 'Technique for Order Preference by Similarity to Ideal Solution', 'results');
echo flashBox($flashMessage);
?>

<!-- Statistics Cards -->
<?php 
$totalProjectsCount = 0;
$evaluatedCount = !empty($topsisResults) ? count($topsisResults) : 0;
$highestScore = !empty($topsisResults) ? max(array_column($topsisResults, 'topsis_score')) : 0;
$avgScore = !empty($topsisResults) ? array_sum(array_column($topsisResults, 'topsis_score')) / count($topsisResults) : 0;

try {
    $db = getConnection();
    $stmt = $db->query("SELECT COUNT(*) FROM projects WHERE status = 'active'");
    $totalProjectsCount = $stmt->fetchColumn();
} catch (Exception $e) {
    // Silent fail
}
?>
<section class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <?= statCard('Total Proyek', $totalProjectsCount, 'projects', 'cyan') ?>
    <?= statCard('Sudah Dihitung', $evaluatedCount, 'check', 'emerald') ?>
    <?= statCard('Skor Tertinggi', number_format($highestScore, 4), 'chart', 'amber') ?>
    <?= statCard('Rata-rata Skor', number_format($avgScore, 4), 'results', 'purple') ?>
</section>

<!-- Field Selector (for admin) -->
<?php if (count($allowedFields) > 1): ?>
<section class="mb-6">
    <div class="bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-200 p-6">
        <label class="block text-sm font-bold text-gray-900 uppercase tracking-wider mb-3">Pilih Bidang Decision Maker:</label>
        <div class="flex flex-wrap gap-3">
            <?php foreach ($allowedFields as $field): 
                $colors = [
                    'supervisor' => ['bg' => 'bg-purple-500', 'hover' => 'hover:bg-purple-600', 'icon' => 'fa-user-tie'],
                    'teknis' => ['bg' => 'bg-cyan-500', 'hover' => 'hover:bg-cyan-600', 'icon' => 'fa-cogs'],
                    'keuangan' => ['bg' => 'bg-amber-500', 'hover' => 'hover:bg-amber-600', 'icon' => 'fa-coins']
                ];
                $c = $colors[$field];
                $active = $selectedField === $field;
            ?>
            <a href="?field=<?php echo $field; ?>" 
               class="flex items-center gap-2 px-6 py-3 rounded-xl font-bold uppercase tracking-wide text-sm transition-all duration-300 <?php echo $active ? $c['bg'] . ' text-white shadow-lg' : 'bg-gray-200 text-gray-900 ' . $c['hover'] . ' hover:text-white'; ?>">
                <i class="fas <?php echo $c['icon']; ?>"></i>
                <?php echo ucfirst($field); ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Role Access Information -->
<?php if (!empty($selectedField) && $userRole !== 'admin' && $userRole !== $selectedField): ?>
<div class="mb-6 bg-gradient-to-r from-blue-50 to-cyan-50 border border-blue-200 rounded-xl p-4">
    <div class="flex items-start gap-3">
        <div class="flex-shrink-0">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <div class="flex-1">
            <h5 class="text-sm font-bold text-blue-900 mb-1">Mode Viewing - Hanya Lihat</h5>
            <p class="text-sm text-blue-800">Anda dapat melihat hasil perhitungan TOPSIS untuk bidang ini. Hanya <strong><?php echo ucfirst($selectedField); ?></strong> atau <strong>Admin</strong> yang dapat melakukan perhitungan TOPSIS.</p>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Action Button (Hitung TOPSIS) -->
<?php if (!empty($selectedField) && ($userRole === 'admin' || $userRole === $selectedField)): ?>
<section class="mb-6">
    <div class="bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-200 p-6">
        <div class="flex flex-col md:flex-row items-center justify-between gap-4">
            <div>
                <h5 class="text-lg font-bold text-gray-900">Perhitungan TOPSIS - Bidang <?php echo ucfirst($selectedField); ?></h5>
                <p class="text-gray-900 text-sm">
                    <?php if ($hasEvaluated || $userRole === 'admin'): ?>
                    Klik tombol untuk menghitung ulang ranking berdasarkan data evaluasi terbaru
                    <?php else: ?>
                    <span class="text-orange-600">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        Anda belum menyelesaikan evaluasi semua proyek (tetap bisa menghitung jika ada data evaluasi)
                    </span>
                    <?php endif; ?>
                </p>
            </div>
            <form method="POST" class="inline">
                <input type="hidden" name="action" value="calculate">
                <button type="submit" 
                        class="flex items-center gap-2 px-6 py-3 rounded-xl font-bold uppercase tracking-wide text-sm transition-all duration-300 bg-gradient-to-r from-blue-500 to-indigo-600 text-white hover:from-blue-400 hover:to-indigo-500 shadow-lg shadow-blue-500/25">
                    <?= icon('calculate', 'w-5 h-5') ?>
                    Hitung TOPSIS
                </button>
            </form>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Toggle Cards Row -->
<section class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
    <!-- Metodologi TOPSIS Card -->
    <div class="bg-gradient-to-r from-blue-500 to-cyan-500 backdrop-blur-sm rounded-xl border-2 border-blue-300 overflow-hidden shadow-lg hover:shadow-xl transition-all duration-300">
        <div class="flex items-center justify-between px-4 py-3 cursor-pointer hover:bg-white/10 transition-colors" onclick="openMethodologyModal()">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-white/95 rounded-lg shadow-md">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h5 class="text-sm font-bold text-white drop-shadow-md">Metodologi TOPSIS</h5>
                    <p class="text-xs text-blue-50">Penjelasan & rumus perhitungan</p>
                </div>
            </div>
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
            </svg>
        </div>
    </div>
    
    <!-- Detail Perhitungan TOPSIS Card -->
    <div class="bg-gradient-to-r from-indigo-500 to-purple-500 backdrop-blur-sm rounded-xl border-2 border-indigo-300 overflow-hidden shadow-lg hover:shadow-xl transition-all duration-300">
        <div class="flex items-center justify-between px-4 py-3 cursor-pointer hover:bg-white/10 transition-colors" onclick="openCalculationModal()">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-white/95 rounded-lg shadow-md">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <h5 class="text-sm font-bold text-white drop-shadow-md">Detail Perhitungan TOPSIS</h5>
                    <p class="text-xs text-indigo-50">Matriks & detail kalkulasi</p>
                </div>
            </div>
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
            </svg>
        </div>
    </div>
</section>

<!-- Metodologi TOPSIS Modal -->
<div id="methodologyModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4" onclick="closeMethodologyModal(event)">
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm"></div>
    <div class="relative bg-white backdrop-blur-md rounded-2xl border-2 border-blue-400 shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-auto" onclick="event.stopPropagation()">
        <div class="sticky top-0 z-10 bg-gradient-to-r from-blue-500 to-cyan-500 flex items-center justify-between px-6 py-4 border-b-2 border-blue-300">
            <h5 class="text-lg font-bold text-white flex items-center gap-2 drop-shadow-md">
                <?= icon('info', 'w-5 h-5') ?>
                Metodologi TOPSIS
            </h5>
            <button onclick="closeMethodologyModal()" class="p-2 hover:bg-white/20 rounded-lg transition-colors">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Penjelasan Metode -->
                <div>
                    <h6 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-2">📖 Penjelasan Metode</h6>
                    <p class="text-gray-700 text-sm leading-relaxed mb-3">
                        <strong class="text-blue-600">TOPSIS</strong> (Technique for Order Preference by Similarity to Ideal Solution) adalah metode pengambilan keputusan multikriteria yang dikembangkan oleh Hwang dan Yoon (1981).
                    </p>
                    <p class="text-gray-700 text-sm leading-relaxed">
                        Metode ini mengukur kedekatan setiap alternatif dengan solusi ideal positif (A⁺) dan solusi ideal negatif (A⁻). Alternatif terbaik adalah yang paling dekat dengan solusi ideal positif dan paling jauh dari solusi ideal negatif.
                    </p>
                </div>
                
                <!-- Rumus -->
                <div>
                    <h6 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-2">📐 Rumus Perhitungan</h6>
                    <div class="bg-gray-50 rounded-xl p-4 text-sm space-y-3">
                        <div>
                            <p class="text-gray-700 text-xs mb-1">Step 1: Normalisasi Vektor</p>
                            <p class="text-blue-600 font-bold">r<sub>ij</sub> = x<sub>ij</sub> / √(Σx<sub>ij</sub>²)</p>
                        </div>
                        <div>
                            <p class="text-gray-700 text-xs mb-1">Step 2: Matriks Terbobot</p>
                            <p class="text-cyan-600 font-bold">v<sub>ij</sub> = w<sub>j</sub> × r<sub>ij</sub></p>
                        </div>
                        <div>
                            <p class="text-gray-700 text-xs mb-1">Step 3: Jarak Euclidean</p>
                            <p class="text-indigo-600 font-bold">D⁺ = √Σ(v<sub>ij</sub> - A⁺<sub>j</sub>)²</p>
                        </div>
                        <div>
                            <p class="text-gray-700 text-xs mb-1">Step 4: Nilai Preferensi</p>
                            <p class="text-purple-600 font-bold">C<sub>i</sub> = D⁻ / (D⁺ + D⁻)</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Langkah Detail -->
            <div class="mt-4 pt-4 border-t border-gray-200/50">
                <h6 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-3">📋 Langkah-langkah Detail</h6>
                <div class="grid md:grid-cols-3 gap-4">
                    <div class="bg-gradient-to-br from-blue-50 to-cyan-50 rounded-xl p-4 border-l-4 border-blue-500 shadow-md hover:shadow-lg transition-shadow">
                        <div class="text-blue-600 font-bold mb-2 text-base">1. Matriks Keputusan</div>
                        <p class="text-sm text-gray-700">Membangun matriks X dengan nilai skor setiap alternatif terhadap setiap kriteria.</p>
                    </div>
                    <div class="bg-gradient-to-br from-cyan-50 to-teal-50 rounded-xl p-4 border-l-4 border-cyan-500 shadow-md hover:shadow-lg transition-shadow">
                        <div class="text-cyan-600 font-bold mb-2 text-base">2. Normalisasi</div>
                        <p class="text-sm text-gray-700">Normalisasi vektor untuk menghilangkan pengaruh satuan berbeda.</p>
                    </div>
                    <div class="bg-gradient-to-br from-teal-50 to-emerald-50 rounded-xl p-4 border-l-4 border-teal-500 shadow-md hover:shadow-lg transition-shadow">
                        <div class="text-teal-600 font-bold mb-2 text-base">3. Matriks Terbobot</div>
                        <p class="text-sm text-gray-700">Mengalikan matriks ternormalisasi dengan bobot kriteria.</p>
                    </div>
                    <div class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-xl p-4 border-l-4 border-indigo-500 shadow-md hover:shadow-lg transition-shadow">
                        <div class="text-indigo-600 font-bold mb-2 text-base">4. Solusi Ideal</div>
                        <p class="text-sm text-gray-700">Menentukan A⁺ (ideal positif) dan A⁻ (ideal negatif).</p>
                    </div>
                    <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-4 border-l-4 border-purple-500 shadow-md hover:shadow-lg transition-shadow">
                        <div class="text-purple-600 font-bold mb-2 text-base">5. Jarak Euclidean</div>
                        <p class="text-sm text-gray-700">Menghitung jarak setiap alternatif ke solusi ideal.</p>
                    </div>
                    <div class="bg-gradient-to-br from-pink-50 to-rose-50 rounded-xl p-4 border-l-4 border-pink-500 shadow-md hover:shadow-lg transition-shadow">
                        <div class="text-pink-600 font-bold mb-2 text-base">6. Nilai Preferensi</div>
                        <p class="text-sm text-gray-700">Nilai mendekati 1 = alternatif terbaik.</p>
                    </div>
                </div>
            </div>
            
            <!-- Bobot DM -->
            <div class="mt-4 pt-4 border-t border-gray-200/50">
                <h6 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-3">⚖️ Bidang Decision Maker</h6>
                <div class="flex flex-wrap gap-3">
                    <?php 
                    $dmFields = [
                        'supervisor' => ['label' => 'Supervisor', 'desc' => 'Bidang Manajerial & Kepemimpinan'],
                        'teknis' => ['label' => 'Teknis', 'desc' => 'Bidang Teknis & Operasional'],
                        'keuangan' => ['label' => 'Keuangan', 'desc' => 'Bidang Finansial & Anggaran']
                    ];
                    $colors = [
                        'supervisor' => ['bg' => 'bg-purple-50', 'text' => 'text-purple-700', 'dot' => 'bg-purple-500'],
                        'teknis' => ['bg' => 'bg-cyan-50', 'text' => 'text-cyan-700', 'dot' => 'bg-cyan-500'],
                        'keuangan' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'dot' => 'bg-amber-500']
                    ];
                    foreach ($dmFields as $field => $info):
                        $c = $colors[$field];
                    ?>
                    <div class="flex-1 min-w-[200px] flex items-start gap-2 px-4 py-3 <?= $c['bg'] ?> rounded-lg border border-<?= str_replace('bg-', '', $c['bg']) ?>">
                        <div class="w-3 h-3 <?= $c['dot'] ?> rounded-full mt-1"></div>
                        <div>
                            <span class="<?= $c['text'] ?> font-bold block"><?= $info['label'] ?></span>
                            <span class="text-gray-600 text-xs"><?= $info['desc'] ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <p class="text-xs text-gray-600 mt-3">
                    <strong>Catatan:</strong> Setiap DM melakukan perhitungan TOPSIS independen untuk bidangnya. Hasil TOPSIS dari semua DM kemudian dikombinasikan menggunakan metode BORDA untuk menghasilkan ranking konsensus final.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Detail Perhitungan TOPSIS Modal -->
<div id="calculationModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4" onclick="closeCalculationModal(event)">
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm"></div>
    <div class="relative bg-white backdrop-blur-md rounded-2xl border-2 border-indigo-400 shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-auto" onclick="event.stopPropagation()">
        <div class="sticky top-0 z-10 bg-gradient-to-r from-indigo-500 to-purple-500 flex items-center justify-between px-6 py-4 border-b-2 border-indigo-300">
            <h5 class="text-lg font-bold text-white flex items-center gap-2 drop-shadow-md">
                <?= icon('calculate', 'w-5 h-5') ?>
                Detail Perhitungan TOPSIS - Bidang <?php echo ucfirst($selectedField); ?>
            </h5>
            <button onclick="closeCalculationModal()" class="p-2 hover:bg-white/20 rounded-lg transition-colors">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="p-6">
            <!-- Tabel Bobot Decision Maker -->
            <div class="bg-gray-50 backdrop-blur-sm rounded-2xl border border-gray-200 mb-6">
                <div class="flex items-center justify-between border-b border-gray-200/50 px-6 py-4">
                    <h5 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <?= icon('users', 'w-5 h-5 text-purple-400') ?>
                        Bobot Decision Maker (DM)
                    </h5>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Kode</th>
                                    <th class="text-left py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Decision Maker</th>
                                    <th class="text-center py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Bobot (w)</th>
                                    <th class="text-center py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Bobot Normalisasi (w')</th>
                                    <th class="text-center py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Persentase</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $dmWeights = [
                                    'supervisor' => 7,
                                    'teknis' => 4,
                                    'keuangan' => 2
                                ];
                                $totalDMWeight = array_sum($dmWeights);
                                $dmIdx = 1;
                                $dmColors = [
                                    'supervisor' => 'text-purple-400',
                                    'teknis' => 'text-cyan-400',
                                    'keuangan' => 'text-amber-400'
                                ];
                                foreach ($dmWeights as $field => $weight): 
                                    $normWeight = $weight / $totalDMWeight;
                                    $percentage = round($normWeight * 100);
                                ?>
                                <tr class="border-b border-gray-200/50 hover:bg-gray-100 transition-colors duration-200">
                                    <td class="py-3 px-4">
                                        <span class="<?= $dmColors[$field] ?> font-bold">DM<?= $dmIdx ?></span>
                                    </td>
                                    <td class="py-3 px-4 text-gray-900"><?= ucfirst($field) ?></td>
                                    <td class="py-3 px-4 text-center text-gray-900 font-medium"><?= $weight ?></td>
                                    <td class="py-3 px-4 text-center text-amber-400 font-mono"><?= number_format($normWeight, 2) ?></td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="px-2 py-1 bg-emerald-500/20 text-emerald-400 rounded text-xs font-bold"><?= $percentage ?>%</span>
                                    </td>
                                </tr>
                                <?php $dmIdx++; endforeach; ?>
                                <tr class="bg-gray-100 font-bold">
                                    <td colspan="2" class="py-3 px-4 text-right text-gray-900">Total:</td>
                                    <td class="py-3 px-4 text-center text-emerald-400"><?= $totalDMWeight ?></td>
                                    <td class="py-3 px-4 text-center text-emerald-400">1</td>
                                    <td class="py-3 px-4 text-center text-emerald-400">100%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tabel Ranking TOPSIS per Decision Maker -->
            <div class="bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-200 mb-6">
                <div class="flex items-center justify-between border-b border-gray-200/50 px-6 py-4">
                    <h5 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <?= icon('results', 'w-5 h-5 text-cyan-400') ?>
                        Ranking TOPSIS per Decision Maker
                    </h5>
                    <span class="text-xs text-gray-900">Input dari hasil perhitungan TOPSIS masing-masing DM</span>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Proyek</th>
                                    <th class="text-center py-3 px-4 text-purple-400 font-bold uppercase tracking-wider text-xs">Rank DM1<br><span class="text-xs text-gray-500">(Supervisor)</span></th>
                                    <th class="text-center py-3 px-4 text-cyan-400 font-bold uppercase tracking-wider text-xs">Rank DM2<br><span class="text-xs text-gray-500">(Teknis)</span></th>
                                    <th class="text-center py-3 px-4 text-amber-400 font-bold uppercase tracking-wider text-xs">Rank DM3<br><span class="text-xs text-gray-500">(Keuangan)</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Get TOPSIS rankings for all DMs
                                $allRankings = [];
                                foreach (['supervisor', 'teknis', 'keuangan'] as $field) {
                                    $fieldResults = $topsisController->getTopsisResults($field);
                                    foreach ($fieldResults as $result) {
                                        $allRankings[$result['project_id']][$field] = [
                                            'rank' => $result['rank'],
                                            'project_code' => $result['project_code'],
                                            'project_name' => $result['project_name']
                                        ];
                                    }
                                }
                                
                                $altIdx = 1;
                                foreach ($allRankings as $projectId => $rankings):
                                    $projectCode = $rankings['supervisor']['project_code'] ?? 
                                                  $rankings['teknis']['project_code'] ?? 
                                                  $rankings['keuangan']['project_code'] ?? '';
                                ?>
                                <tr class="border-b border-gray-200/50 hover:bg-gray-100 transition-colors duration-200">
                                    <td class="py-3 px-4">
                                        <span class="text-amber-400 font-bold">A<?= $altIdx ?></span>
                                        <span class="text-gray-900 ml-2"><?= htmlspecialchars($projectCode) ?></span>
                                    </td>
                                    <?php foreach (['supervisor', 'teknis', 'keuangan'] as $field): ?>
                                    <td class="py-3 px-4 text-center">
                                        <?php if (isset($rankings[$field]['rank'])): ?>
                                        <span class="text-gray-900 font-medium"><?= $rankings[$field]['rank'] ?></span>
                                        <?php else: ?>
                                        <span class="text-gray-500 italic">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php $altIdx++; endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <?php if (!empty($calculationDetails) && $calculationDetails['success']): ?>
            
            <?php 
            // Check if we have complete data
            $hasCompleteData = !empty($calculationDetails['decision_matrix']) && 
                              count($calculationDetails['decision_matrix']) === count($calculationDetails['projects']);
            
            if (!$hasCompleteData): 
            ?>
            <div class="bg-yellow-50 border-2 border-yellow-400 rounded-xl p-6 mb-6">
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 text-yellow-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div>
                        <h6 class="font-bold text-yellow-900 mb-2">Data Evaluasi Belum Lengkap</h6>
                        <p class="text-sm text-yellow-800 mb-2">
                            Perhitungan detail TOPSIS memerlukan data evaluasi yang lengkap untuk semua proyek dan semua kriteria.
                        </p>
                        <p class="text-sm text-yellow-800">
                            <strong>Status:</strong> Hanya <?= count($calculationDetails['decision_matrix']) ?> dari <?= count($calculationDetails['projects']) ?> proyek yang memiliki data evaluasi lengkap.
                        </p>
                        <p class="text-sm text-yellow-800 mt-2">
                            Silakan lengkapi evaluasi terlebih dahulu di halaman <a href="evaluate.php" class="font-bold underline">Evaluasi Proyek</a>.
                        </p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Tabel 1: Kriteria dan Bobot -->
            <div class="bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-200 mb-6">
                <div class="border-b border-gray-200/50 px-6 py-4">
                    <h6 class="text-lg font-bold text-gray-900">
                        1. Kriteria & Bobot
                    </h6>
                </div>
                <div class="p-6 overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gradient-to-r from-purple-500 to-cyan-500">
                                <th class="border-2 border-gray-300 px-4 py-2 text-white font-bold">Kode</th>
                                <th class="border-2 border-gray-300 px-4 py-2 text-white font-bold">Nama Kriteria</th>
                                <th class="border-2 border-gray-300 px-4 py-2 text-white font-bold">Tipe</th>
                                <th class="border-2 border-gray-300 px-4 py-2 text-white font-bold">Bobot (w)</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            <?php 
                            $cIdx = 1;
                            foreach ($calculationDetails['criteria'] as $criteria): 
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="border-2 border-gray-300 px-4 py-2 text-center font-bold">C<?= $cIdx ?></td>
                                <td class="border-2 border-gray-300 px-4 py-2"><?= htmlspecialchars($criteria['name']) ?></td>
                                <td class="border-2 border-gray-300 px-4 py-2 text-center">
                                    <span class="px-2 py-1 rounded text-xs font-bold <?= $criteria['type'] === 'benefit' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                        <?= ucfirst($criteria['type']) ?>
                                    </span>
                                </td>
                                <td class="border-2 border-gray-300 px-4 py-2 text-center font-bold"><?= rtrim(rtrim(number_format($criteria['weight'], 3), '0'), '.') ?></td>
                            </tr>
                            <?php $cIdx++; endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if ($hasCompleteData): ?>
            <!-- Tabel 2: Matriks Keputusan (Decision Matrix) -->
            <div class="bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-200 mb-6">
                <div class="border-b border-gray-200/50 px-6 py-4">
                    <h6 class="text-lg font-bold text-gray-900">
                        2. Nilai Setiap Kriteria untuk Masing-masing Alternatif
                    </h6>
                    <p class="text-sm text-gray-600 mt-1">Matriks Keputusan Awal (X)</p>
                </div>
                <div class="p-6 overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gradient-to-r from-purple-500 to-cyan-500">
                                <th class="border-2 border-gray-300 px-4 py-2 text-white font-bold">Alternatif</th>
                                <th class="border-2 border-gray-300 px-4 py-2 text-white font-bold">Kode Proyek</th>
                                <?php 
                                $cIdx = 1;
                                foreach ($calculationDetails['criteria'] as $criteria): 
                                ?>
                                <th class="border-2 border-gray-300 px-4 py-2 text-white font-bold">C<?= $cIdx ?></th>
                                <?php $cIdx++; endforeach; ?>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            <?php 
                            $aIdx = 1;
                            foreach ($calculationDetails['projects'] as $project):
                                $projectId = $project['id'];
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="border-2 border-gray-300 px-4 py-2 text-center font-bold">A<?= $aIdx ?></td>
                                <td class="border-2 border-gray-300 px-4 py-2"><?= htmlspecialchars($project['code'] ?? $project['name'] ?? 'Proyek ' . $aIdx) ?></td>
                                <?php foreach ($calculationDetails['criteria'] as $criteria): ?>
                                <td class="border-2 border-gray-300 px-4 py-2 text-center">
                                    <?= isset($calculationDetails['decision_matrix'][$projectId][$criteria['id']]) 
                                        ? rtrim(rtrim(number_format($calculationDetails['decision_matrix'][$projectId][$criteria['id']], 2), '0'), '.') 
                                        : '0' ?>
                                </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php $aIdx++; endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tabel 3: Matriks Keputusan dengan Nilai Numerik (sama dengan tabel 2) -->
            <div class="bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-200 mb-6">
                <div class="border-b border-gray-200/50 px-6 py-4">
                    <h6 class="text-lg font-bold text-gray-900">
                        3. Matriks Keputusan dengan Nilai Numerik
                    </h6>
                    <p class="text-sm text-gray-600 mt-1">Data numerik dari matriks keputusan</p>
                </div>
                <div class="p-6 overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gradient-to-r from-purple-500 to-cyan-500">
                                <th class="border-2 border-gray-300 px-4 py-2 text-white font-bold">Kriteria</th>
                                <?php 
                                $aIdx = 1;
                                foreach ($calculationDetails['projects'] as $project): 
                                ?>
                                <th class="border-2 border-gray-300 px-4 py-2 text-white font-bold">A<?= $aIdx ?></th>
                                <?php $aIdx++; endforeach; ?>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            <?php 
                            $cIdx = 1;
                            foreach ($calculationDetails['criteria'] as $criteria):
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="border-2 border-gray-300 px-4 py-2 font-bold">C<?= $cIdx ?></td>
                                <?php foreach ($calculationDetails['projects'] as $project): ?>
                                <td class="border-2 border-gray-300 px-4 py-2 text-center">
                                    <?= isset($calculationDetails['decision_matrix'][$project['id']][$criteria['id']]) 
                                        ? rtrim(rtrim(number_format($calculationDetails['decision_matrix'][$project['id']][$criteria['id']], 0), '0'), '.') 
                                        : '0' ?>
                                </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php $cIdx++; endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tabel 4: Matriks Keputusan Ternormalisasi -->
            <div class="bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-200 mb-6">
                <div class="border-b border-gray-200/50 px-6 py-4">
                    <h6 class="text-lg font-bold text-gray-900">
                        4. Matriks Keputusan Ternormalisasi
                    </h6>
                    <p class="text-sm text-gray-600 mt-1">Rumus: r<sub>ij</sub> = x<sub>ij</sub> / √(Σx<sub>ij</sub>²)</p>
                </div>
                <div class="p-6 overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gradient-to-r from-purple-500 to-cyan-500">
                                <th class="border-2 border-gray-300 px-4 py-2 text-white font-bold">Alternatif</th>
                                <th class="border-2 border-gray-300 px-4 py-2 text-white font-bold">Kode Proyek</th>
                                <?php 
                                $cIdx = 1;
                                foreach ($calculationDetails['criteria'] as $criteria): 
                                ?>
                                <th class="border-2 border-gray-300 px-4 py-2 text-white font-bold">C<?= $cIdx ?></th>
                                <?php $cIdx++; endforeach; ?>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            <?php 
                            $aIdx = 1;
                            foreach ($calculationDetails['projects'] as $project):
                                $projectId = $project['id'];
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="border-2 border-gray-300 px-4 py-2 text-center font-bold">A<?= $aIdx ?></td>
                                <td class="border-2 border-gray-300 px-4 py-2"><?= htmlspecialchars($project['code'] ?? $project['name'] ?? 'Proyek ' . $aIdx) ?></td>
                                <?php foreach ($calculationDetails['criteria'] as $criteria): ?>
                                <td class="border-2 border-gray-300 px-4 py-2 text-center">
                                    <?= isset($calculationDetails['normalized_matrix'][$projectId][$criteria['id']]) 
                                        ? number_format($calculationDetails['normalized_matrix'][$projectId][$criteria['id']], 4) 
                                        : '0' ?>
                                </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php $aIdx++; endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tabel 5: Matriks Keputusan Ternormalisasi Terbobot -->
            <div class="bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-200 mb-6">
                <div class="border-b border-gray-200/50 px-6 py-4">
                    <h6 class="text-lg font-bold text-gray-900">
                        5. Matriks Keputusan Ternormalisasi Terbobot
                    </h6>
                    <p class="text-sm text-gray-600 mt-1">Rumus: v<sub>ij</sub> = w<sub>j</sub> × r<sub>ij</sub></p>
                </div>
                <div class="p-6 overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gradient-to-r from-amber-500 to-purple-500">
                                <th class="border-2 border-gray-300 px-4 py-2 text-white font-bold">Alternatif</th>
                                <th class="border-2 border-gray-300 px-4 py-2 text-white font-bold">Kode Proyek</th>
                                <?php 
                                $cIdx = 1;
                                foreach ($calculationDetails['criteria'] as $criteria): 
                                ?>
                                <th class="border-2 border-gray-300 px-4 py-2 text-white font-bold">C<?= $cIdx ?></th>
                                <?php $cIdx++; endforeach; ?>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            <?php 
                            $aIdx = 1;
                            foreach ($calculationDetails['projects'] as $project):
                                $projectId = $project['id'];
                            ?>
                            <tr class="hover:bg-amber-50">
                                <td class="border-2 border-gray-300 px-4 py-2 text-center font-bold">A<?= $aIdx ?></td>
                                <td class="border-2 border-gray-300 px-4 py-2"><?= htmlspecialchars($project['code'] ?? $project['name'] ?? 'Proyek ' . $aIdx) ?></td>
                                <?php foreach ($calculationDetails['criteria'] as $criteria): ?>
                                <td class="border-2 border-gray-300 px-4 py-2 text-center">
                                    <?= isset($calculationDetails['weighted_matrix'][$projectId][$criteria['id']]) 
                                        ? number_format($calculationDetails['weighted_matrix'][$projectId][$criteria['id']], 4) 
                                        : '0' ?>
                                </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php $aIdx++; endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tabel 6: Solusi Ideal Positif dan Negatif -->
            <div class="bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-200 mb-6">
                <div class="border-b border-gray-200/50 px-6 py-4">
                    <h6 class="text-lg font-bold text-gray-900">
                        6. Solusi Ideal Positif dan Negatif
                    </h6>
                    <p class="text-sm text-gray-600 mt-1">A⁺ (max untuk benefit, min untuk cost) | A⁻ (min untuk benefit, max untuk cost)</p>
                </div>
                <div class="p-6 overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gradient-to-r from-cyan-500 to-purple-500">
                                <th class="border-2 border-gray-300 px-4 py-2 text-white font-bold">Kriteria</th>
                                <?php 
                                $cIdx = 1;
                                foreach ($calculationDetails['criteria'] as $criteria): 
                                ?>
                                <th class="border-2 border-gray-300 px-4 py-2 text-white font-bold">C<?= $cIdx ?></th>
                                <?php $cIdx++; endforeach; ?>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            <tr class="hover:bg-gray-50">
                                <td class="border-2 border-gray-300 px-4 py-2 font-bold">Solusi Ideal Positif (A⁺)</td>
                                <?php foreach ($calculationDetails['criteria'] as $criteria): ?>
                                <td class="border-2 border-gray-300 px-4 py-2 text-center">
                                    <?= isset($calculationDetails['ideal_solutions']['positive'][$criteria['id']]) 
                                        ? number_format($calculationDetails['ideal_solutions']['positive'][$criteria['id']], 4) 
                                        : '0' ?>
                                </td>
                                <?php endforeach; ?>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="border-2 border-gray-300 px-4 py-2 font-bold">Solusi Ideal Negatif (A⁻)</td>
                                <?php foreach ($calculationDetails['criteria'] as $criteria): ?>
                                <td class="border-2 border-gray-300 px-4 py-2 text-center">
                                    <?= isset($calculationDetails['ideal_solutions']['negative'][$criteria['id']]) 
                                        ? number_format($calculationDetails['ideal_solutions']['negative'][$criteria['id']], 4) 
                                        : '0' ?>
                                </td>
                                <?php endforeach; ?>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tabel 7: Jarak ke Solusi Ideal -->
            <div class="bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-200 mb-6">
                <div class="border-b border-gray-200/50 px-6 py-4">
                    <h6 class="text-lg font-bold text-gray-900">
                        7. Jarak Tiap Alternatif dari Solusi Ideal Positif dan Negatif
                    </h6>
                    <p class="text-sm text-gray-600 mt-1">D⁺ = √(Σ(v<sub>ij</sub> - A⁺<sub>j</sub>)²) | D⁻ = √(Σ(v<sub>ij</sub> - A⁻<sub>j</sub>)²)</p>
                </div>
                <div class="p-6 overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gradient-to-r from-purple-500 to-amber-500">
                                <th class="border-2 border-gray-300 px-4 py-2 text-white font-bold">Alternatif</th>
                                <th class="border-2 border-gray-300 px-4 py-2 text-white font-bold">Kode Proyek</th>
                                <th class="border-2 border-gray-300 px-4 py-2 text-white font-bold">D⁺ (Jarak ke A⁺)</th>
                                <th class="border-2 border-gray-300 px-4 py-2 text-white font-bold">D⁻ (Jarak ke A⁻)</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            <?php 
                            $aIdx = 1;
                            foreach ($calculationDetails['projects'] as $project):
                                $projectId = $project['id'];
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="border-2 border-gray-300 px-4 py-2 text-center font-bold">A<?= $aIdx ?></td>
                                <td class="border-2 border-gray-300 px-4 py-2"><?= htmlspecialchars($project['code'] ?? $project['name'] ?? 'Proyek ' . $aIdx) ?></td>
                                <td class="border-2 border-gray-300 px-4 py-2 text-center">
                                    <?= isset($calculationDetails['distances'][$projectId]['positive']) 
                                        ? number_format($calculationDetails['distances'][$projectId]['positive'], 4) 
                                        : '0' ?>
                                </td>
                                <td class="border-2 border-gray-300 px-4 py-2 text-center">
                                    <?= isset($calculationDetails['distances'][$projectId]['negative']) 
                                        ? number_format($calculationDetails['distances'][$projectId]['negative'], 4) 
                                        : '0' ?>
                                </td>
                            </tr>
                            <?php $aIdx++; endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tabel 8: Skor Akhir (Nilai Preferensi) -->
            <div class="bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-200 mb-6">
                <div class="border-b border-gray-200/50 px-6 py-4">
                    <h6 class="text-lg font-bold text-gray-900">
                        8. Skor Akhir dari Setiap Alternatif
                    </h6>
                    <p class="text-sm text-gray-600 mt-1">Rumus: C<sub>i</sub> = D⁻ / (D⁺ + D⁻)</p>
                </div>
                <div class="p-6 overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gradient-to-r from-cyan-500 to-amber-500">
                                <th class="border-2 border-gray-300 px-4 py-2 text-white font-bold">Alternatif</th>
                                <th class="border-2 border-gray-300 px-4 py-2 text-white font-bold">Kode Proyek</th>
                                <th class="border-2 border-gray-300 px-4 py-2 text-white font-bold">Nilai Preferensi (C<sub>i</sub>)</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            <?php 
                            $aIdx = 1;
                            $bestScore = 0;
                            $bestIdx = 0;
                            
                            // Find best score
                            foreach ($calculationDetails['results'] as $result) {
                                if ($result['score'] > $bestScore) {
                                    $bestScore = $result['score'];
                                }
                            }
                            
                            foreach ($calculationDetails['projects'] as $project):
                                $projectId = $project['id'];
                                $score = 0;
                                foreach ($calculationDetails['results'] as $result) {
                                    if ($result['project_id'] == $projectId) {
                                        $score = $result['score'];
                                        break;
                                    }
                                }
                                $isBest = ($score == $bestScore && $bestScore > 0);
                            ?>
                            <tr class="<?= $isBest ? 'bg-amber-100' : 'hover:bg-gray-50' ?>">
                                <td class="border-2 border-gray-300 px-4 py-2 text-center font-bold">A<?= $aIdx ?></td>
                                <td class="border-2 border-gray-300 px-4 py-2"><?= htmlspecialchars($project['code'] ?? $project['name'] ?? 'Proyek ' . $aIdx) ?></td>
                                <td class="border-2 border-gray-300 px-4 py-2 text-center font-bold">
                                    <?= number_format($score, 4) ?>
                                </td>
                            </tr>
                            <?php $aIdx++; endforeach; ?>
                        </tbody>
                    </table>
                    <?php 
                    // Find best alternative
                    $bestAlt = 0;
                    $aIdx = 1;
                    foreach ($calculationDetails['projects'] as $project):
                        $projectId = $project['id'];
                        $score = 0;
                        foreach ($calculationDetails['results'] as $result) {
                            if ($result['project_id'] == $projectId) {
                                $score = $result['score'];
                                if ($score == $bestScore && $bestScore > 0) {
                                    $bestAlt = $aIdx;
                                }
                                break;
                            }
                        }
                        $aIdx++;
                    endforeach;
                    ?>
                    <?php if ($bestAlt > 0): ?>
                    <div class="mt-4 p-4 bg-gradient-to-r from-amber-500 to-purple-500 rounded-lg">
                        <p class="text-white font-bold">
                            Alternatif terbaik adalah <span class="text-xl">A<?= $bestAlt ?></span> dengan nilai preferensi (C<sub>i</sub>) = <span class="text-xl"><?= number_format($bestScore, 4) ?></span>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php endif; // End hasCompleteData ?>

            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function openMethodologyModal() {
    document.getElementById('methodologyModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function closeMethodologyModal(event) {
    if (event && event.target !== event.currentTarget) return;
    document.getElementById('methodologyModal').classList.add('hidden');
    document.body.style.overflow = '';
}

function openCalculationModal() {
    document.getElementById('calculationModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function closeCalculationModal(event) {
    if (event && event.target !== event.currentTarget) return;
    document.getElementById('calculationModal').classList.add('hidden');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeMethodologyModal();
        closeCalculationModal();
    }
});
</script>

<!-- Ranking TOPSIS -->
<?php if (!empty($topsisResults)): ?>
<section class="mb-8 bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-200">
    <div class="flex items-center justify-between border-b border-gray-200/50 px-6 py-4">
        <h5 class="text-lg font-bold text-gray-900 flex items-center gap-2">
            <?= icon('trophy', 'w-5 h-5 text-yellow-400') ?>
            Ranking TOPSIS - Bidang <?php echo ucfirst($selectedField); ?>
        </h5>
    </div>
    <div class="p-6">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Rank</th>
                        <th class="text-left py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Kode</th>
                        <th class="text-left py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Nama Proyek</th>
                        <th class="text-center py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">D⁺</th>
                        <th class="text-center py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">D⁻</th>
                        <th class="text-center py-3 px-4 text-gray-900 font-bold uppercase tracking-wider text-xs">Nilai Preferensi (Ci)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topsisResults as $result): 
                        $rank = $result['rank'];
                        $rankColors = [1 => 'text-yellow-400', 2 => 'text-gray-400', 3 => 'text-amber-600'];
                        $rankColor = $rankColors[$rank] ?? 'text-gray-900';
                    ?>
                    <tr class="border-b border-gray-200/50 hover:bg-gray-100 transition-colors duration-200">
                        <td class="py-4 px-4">
                            <?php if ($rank === 1): ?>
                                <div class="flex items-center justify-center w-12 h-12 rounded-full bg-gradient-to-br from-yellow-400 to-yellow-600 shadow-lg">
                                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                </div>
                            <?php elseif ($rank === 2): ?>
                                <div class="flex items-center justify-center w-12 h-12 rounded-full bg-gradient-to-br from-gray-300 to-gray-500 shadow-md">
                                    <span class="text-white font-bold text-lg"><?= $rank ?></span>
                                </div>
                            <?php elseif ($rank === 3): ?>
                                <div class="flex items-center justify-center w-12 h-12 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 shadow-md">
                                    <span class="text-white font-bold text-lg"><?= $rank ?></span>
                                </div>
                            <?php else: ?>
                                <div class="flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 border-2 border-gray-300">
                                    <span class="text-gray-600 font-bold text-lg"><?= $rank ?></span>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="py-4 px-4">
                            <span class="font-mono text-sm text-gray-900 font-medium"><?php echo htmlspecialchars($result['project_code'] ?? '-'); ?></span>
                        </td>
                        <td class="py-4 px-4">
                            <div class="font-bold text-gray-900"><?php echo htmlspecialchars($result['project_name']); ?></div>
                        </td>
                        <td class="py-4 px-4 text-center">
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-red-50 rounded-lg">
                                <span class="text-red-600 font-bold"><?php echo number_format($result['d_positive'], 4); ?></span>
                            </span>
                        </td>
                        <td class="py-4 px-4 text-center">
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-green-50 rounded-lg">
                                <span class="text-green-600 font-bold"><?php echo number_format($result['d_negative'], 4); ?></span>
                            </span>
                        </td>
                        <td class="py-4 px-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <div class="w-20 bg-gray-200 rounded-full h-2">
                                    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-2 rounded-full transition-all duration-300" style="width: <?php echo ($result['topsis_score'] * 100); ?>%"></div>
                                </div>
                                <span class="font-bold text-blue-600"><?php echo number_format($result['topsis_score'], 4); ?></span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- Chart Section -->
<section class="mb-8">
    <div class="bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-200 p-6">
        <h5 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
            <?= icon('chart', 'w-5 h-5 text-cyan-400') ?>
            Grafik Ranking TOPSIS
        </h5>
        <canvas id="topsisChart" height="150"></canvas>
    </div>
</section>

<!-- Kesimpulan & Rekomendasi -->
<section class="mb-8">
    <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl border border-blue-400 overflow-hidden shadow-xl">
        <div class="px-6 py-4 bg-white/10 border-b border-white/20">
            <h5 class="text-lg font-bold text-white flex items-center gap-2">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                Kesimpulan & Rekomendasi Keputusan
            </h5>
        </div>
        <div class="p-6">
            <?php 
            // Get winner data (rank 1)
            $winnerData = null;
            foreach ($topsisResults as $result) {
                if ($result['rank'] === 1) {
                    $winnerData = $result;
                    break;
                }
            }
            
            if ($winnerData):
                // Get scores for comparison
                $allScores = array_column($topsisResults, 'topsis_score');
                rsort($allScores);
                $highestScore = $allScores[0];
                $secondHighestScore = count($allScores) > 1 ? $allScores[1] : 0;
                $scoreDifference = $highestScore - $secondHighestScore;
            ?>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Proyek Terbaik -->
                <div class="bg-white/95 backdrop-blur-sm rounded-xl p-6 border-2 border-yellow-400 shadow-lg">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-3 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-full">
                            <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider font-bold">Proyek Terbaik</p>
                            <h3 class="text-xl font-black text-gray-900"><?= htmlspecialchars($winnerData['project_name']) ?></h3>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between py-2 px-3 bg-gray-50 rounded-lg">
                            <span class="text-sm text-gray-600">Kode Proyek:</span>
                            <span class="font-bold text-amber-600"><?= htmlspecialchars($winnerData['project_code']) ?></span>
                        </div>
                        <div class="flex items-center justify-between py-2 px-3 bg-gray-50 rounded-lg">
                            <span class="text-sm text-gray-600">Nilai Preferensi (Ci):</span>
                            <span class="text-2xl font-black text-emerald-600"><?= number_format($winnerData['topsis_score'], 4) ?></span>
                        </div>
                        <div class="flex items-center justify-between py-2 px-3 bg-gray-50 rounded-lg">
                            <span class="text-sm text-gray-600">Selisih dengan Rank 2:</span>
                            <span class="font-bold text-purple-600">+<?= number_format($scoreDifference, 4) ?></span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 mt-3">
                            <div class="py-2 px-3 bg-red-50 rounded-lg text-center">
                                <span class="text-xs text-red-600 block">D⁺</span>
                                <span class="font-bold text-red-700"><?= number_format($winnerData['d_positive'], 4) ?></span>
                            </div>
                            <div class="py-2 px-3 bg-green-50 rounded-lg text-center">
                                <span class="text-xs text-green-600 block">D⁻</span>
                                <span class="font-bold text-green-700"><?= number_format($winnerData['d_negative'], 4) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rekomendasi -->
                <div class="bg-white/95 backdrop-blur-sm rounded-xl p-6 border border-gray-200">
                    <div class="flex items-start gap-3 mb-4">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-2">Rekomendasi Keputusan</h4>
                            <p class="text-gray-700 text-sm leading-relaxed mb-3">
                                Berdasarkan perhitungan metode <strong class="text-blue-600">TOPSIS</strong> (Technique for Order Preference by Similarity to Ideal Solution) 
                                untuk bidang <strong class="text-indigo-600"><?= ucfirst($selectedField) ?></strong>, 
                                proyek <strong class="text-amber-600"><?= htmlspecialchars($winnerData['project_code']) ?></strong> 
                                memperoleh nilai preferensi tertinggi <strong class="text-emerald-600"><?= number_format($winnerData['topsis_score'], 4) ?></strong>.
                            </p>
                            <div class="p-3 bg-blue-50 border-l-4 border-blue-500 rounded mb-3">
                                <p class="text-sm font-bold text-blue-800 mb-1">✓ Keputusan Final:</p>
                                <p class="text-sm text-blue-700">
                                    <strong><?= htmlspecialchars($winnerData['project_name']) ?></strong> direkomendasikan 
                                    sebagai proyek prioritas utama berdasarkan kedekatan dengan solusi ideal positif.
                                </p>
                            </div>
                            <div class="p-3 bg-gray-50 rounded">
                                <p class="text-xs text-gray-600 mb-1"><strong>Interpretasi Nilai:</strong></p>
                                <p class="text-xs text-gray-700">
                                    Nilai preferensi mendekati <strong>1.0</strong> menunjukkan proyek ini memiliki karakteristik 
                                    paling mendekati solusi ideal positif (A⁺) dan paling jauh dari solusi ideal negatif (A⁻).
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php endif; ?>
        </div>
    </div>
</section>

<?php else: ?>
<!-- No Results -->
<section class="bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-200 p-12 text-center">
    <?= icon('calculator', 'w-20 h-20 text-gray-400 mx-auto mb-4') ?>
    <h4 class="text-xl font-bold text-gray-900 mb-2">Belum Ada Hasil TOPSIS</h4>
    <p class="text-gray-900 mb-6">
        Belum ada hasil perhitungan TOPSIS untuk bidang <?php echo ucfirst($selectedField); ?>.<br>
        Pastikan semua evaluasi telah dilakukan, kemudian klik tombol "Hitung TOPSIS".
    </p>
    <?php if ($userRole === $selectedField || $userRole === 'admin'): ?>
    <a href="evaluate.php" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-xl hover:from-blue-400 hover:to-indigo-500 transition-all duration-300 font-bold uppercase tracking-wide shadow-lg">
        <?= icon('clipboard', 'w-5 h-5') ?>
        Lakukan Evaluasi
    </a>
    <?php endif; ?>
</section>
<?php endif; ?>

<!-- Chart.js for visualization -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php if (!empty($topsisResults)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('topsisChart').getContext('2d');
    
    // Reverse the data for chronological order (2501 -> 2505)
    const reversedResults = <?= json_encode(array_reverse($topsisResults)) ?>;
    const labels = reversedResults.map(r => r.project_code);
    const data = reversedResults.map(r => parseFloat(r.topsis_score).toFixed(4));
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Nilai Preferensi (Ci)',
                data: data,
                backgroundColor: [
                    'rgba(234, 179, 8, 1)',
                    'rgba(156, 163, 175, 1)',
                    'rgba(249, 115, 22, 1)',
                    'rgba(59, 130, 246, 1)',
                    'rgba(139, 92, 246, 1)',
                    'rgba(236, 72, 153, 1)'
                ],
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 1,
                    ticks: { 
                        color: '#94a3b8',
                        stepSize: 0.2
                    },
                    grid: { color: 'rgba(148, 163, 184, 0.1)' }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#94a3b8' }
                }
            }
        }
    });
});
</script>
<?php endif; ?>

<?php renderFooter(); ?>
