<?php
/**
 * TOPSIS Matrix View
 * Menampilkan matriks keputusan, normalisasi, dan matriks terbobot
 * 
 * @package GDSS TOPSIS + BORDA
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/layout.php';
require_once __DIR__ . '/../../controllers/topsis_controller.php';

// Check login
requireLogin();

$userRole = $_SESSION['role'];
$pageTitle = 'Matriks TOPSIS';

// Get field parameter
$field = isset($_GET['field']) ? $_GET['field'] : '';

// Validate field access
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
}

if (!in_array($field, $allowedFields)) {
    header('Location: ../../topsis_results.php');
    exit;
}

$topsisController = new TopsisController();
$details = $topsisController->getTopsisCalculationDetails($field);

// Get decision matrix, normalized matrix, weighted matrix
$decisionMatrix = $details['decision_matrix'] ?? [];
$normalizedMatrix = $details['normalized_matrix'] ?? [];
$weightedMatrix = $details['weighted_matrix'] ?? [];
$criteria = $details['criteria'] ?? [];
$projects = $details['projects'] ?? [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - GDSS TOPSIS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .matrix-cell {
            min-width: 80px;
            text-align: center;
        }
        .matrix-table {
            font-size: 0.875rem;
        }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-6">
        <!-- Header -->
        <div class="mb-6 flex items-center justify-between no-print">
            <div>
                <a href="../../topsis_results.php?field=<?php echo $field; ?>" class="text-blue-600 hover:text-blue-800 mb-2 inline-block">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali ke Hasil TOPSIS
                </a>
                <h1 class="text-3xl font-bold text-gray-900">
                    <i class="fas fa-table mr-2 text-indigo-600"></i>
                    Matriks Keputusan TOPSIS
                </h1>
                <p class="text-gray-900">Bidang: <span class="font-semibold text-indigo-600"><?php echo ucfirst($field); ?></span></p>
            </div>
            <button onclick="window.print()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                <i class="fas fa-print mr-2"></i>Cetak
            </button>
        </div>

        <?php if (empty($decisionMatrix)): ?>
        <div class="bg-white rounded-lg shadow p-8 text-center">
            <div class="text-gray-400 mb-4">
                <i class="fas fa-table fa-4x"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Matriks Belum Tersedia</h3>
            <p class="text-gray-500">Silakan lakukan perhitungan TOPSIS terlebih dahulu.</p>
        </div>
        <?php else: ?>

        <!-- Step 1: Decision Matrix (X) -->
        <div class="bg-white rounded-lg shadow mb-6 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-4">
                <h2 class="font-bold text-lg">
                    <i class="fas fa-th mr-2"></i>
                    Langkah 1: Matriks Keputusan (X)
                </h2>
                <p class="text-blue-100 text-sm">Nilai mentah skor setiap alternatif terhadap setiap kriteria</p>
            </div>
            <div class="overflow-x-auto p-4">
                <table class="matrix-table w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border border-gray-300 px-4 py-2 text-left">Alternatif</th>
                            <?php foreach ($criteria as $c): ?>
                            <th class="border border-gray-300 px-4 py-2 matrix-cell">
                                <div class="font-semibold"><?php echo htmlspecialchars($c['name']); ?></div>
                                <div class="text-xs text-gray-500">(<?php echo $c['type']; ?>)</div>
                            </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $project): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="border border-gray-300 px-4 py-2 font-medium"><?php echo htmlspecialchars($project['name']); ?></td>
                            <?php foreach ($criteria as $c): ?>
                            <td class="border border-gray-300 px-4 py-2 matrix-cell font-mono">
                                <?php 
                                $value = $decisionMatrix[$project['id']][$c['id']] ?? 0;
                                echo number_format($value, 2);
                                ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Step 2: Normalized Matrix (R) -->
        <div class="bg-white rounded-lg shadow mb-6 overflow-hidden">
            <div class="bg-gradient-to-r from-green-600 to-green-700 text-white px-6 py-4">
                <h2 class="font-bold text-lg">
                    <i class="fas fa-percentage mr-2"></i>
                    Langkah 2: Matriks Ternormalisasi (R)
                </h2>
                <p class="text-green-100 text-sm">r<sub>ij</sub> = x<sub>ij</sub> / √(Σx<sub>ij</sub>²)</p>
            </div>
            <div class="overflow-x-auto p-4">
                <table class="matrix-table w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border border-gray-300 px-4 py-2 text-left">Alternatif</th>
                            <?php foreach ($criteria as $c): ?>
                            <th class="border border-gray-300 px-4 py-2 matrix-cell">
                                <?php echo htmlspecialchars($c['name']); ?>
                            </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $project): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="border border-gray-300 px-4 py-2 font-medium"><?php echo htmlspecialchars($project['name']); ?></td>
                            <?php foreach ($criteria as $c): ?>
                            <td class="border border-gray-300 px-4 py-2 matrix-cell font-mono text-green-700">
                                <?php 
                                $value = $normalizedMatrix[$project['id']][$c['id']] ?? 0;
                                echo number_format($value, 4);
                                ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Step 3: Weighted Matrix (V) -->
        <div class="bg-white rounded-lg shadow mb-6 overflow-hidden">
            <div class="bg-gradient-to-r from-purple-600 to-purple-700 text-white px-6 py-4">
                <h2 class="font-bold text-lg">
                    <i class="fas fa-balance-scale mr-2"></i>
                    Langkah 3: Matriks Terbobot (V)
                </h2>
                <p class="text-purple-100 text-sm">v<sub>ij</sub> = w<sub>j</sub> × r<sub>ij</sub></p>
            </div>
            <div class="overflow-x-auto p-4">
                <!-- Weights Row -->
                <div class="mb-4 p-3 bg-purple-50 rounded-lg">
                    <span class="font-semibold text-purple-800">Bobot (w):</span>
                    <?php foreach ($criteria as $c): ?>
                    <span class="ml-4 text-sm">
                        <span class="text-gray-900"><?php echo htmlspecialchars($c['name']); ?>:</span>
                        <span class="font-mono font-bold text-purple-700"><?php echo number_format($c['weight'], 3); ?></span>
                    </span>
                    <?php endforeach; ?>
                </div>
                
                <table class="matrix-table w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border border-gray-300 px-4 py-2 text-left">Alternatif</th>
                            <?php foreach ($criteria as $c): ?>
                            <th class="border border-gray-300 px-4 py-2 matrix-cell">
                                <?php echo htmlspecialchars($c['name']); ?>
                            </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $project): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="border border-gray-300 px-4 py-2 font-medium"><?php echo htmlspecialchars($project['name']); ?></td>
                            <?php foreach ($criteria as $c): ?>
                            <td class="border border-gray-300 px-4 py-2 matrix-cell font-mono text-purple-700">
                                <?php 
                                $value = $weightedMatrix[$project['id']][$c['id']] ?? 0;
                                echo number_format($value, 4);
                                ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Step 4: Ideal Solutions -->
        <div class="bg-white rounded-lg shadow mb-6 overflow-hidden">
            <div class="bg-gradient-to-r from-yellow-500 to-orange-500 text-white px-6 py-4">
                <h2 class="font-bold text-lg">
                    <i class="fas fa-star mr-2"></i>
                    Langkah 4: Solusi Ideal Positif (A⁺) dan Negatif (A⁻)
                </h2>
                <p class="text-yellow-100 text-sm">A⁺ = max(v) untuk benefit, min(v) untuk cost | A⁻ = min(v) untuk benefit, max(v) untuk cost</p>
            </div>
            <div class="overflow-x-auto p-4">
                <table class="matrix-table w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border border-gray-300 px-4 py-2 text-left">Solusi</th>
                            <?php foreach ($criteria as $c): ?>
                            <th class="border border-gray-300 px-4 py-2 matrix-cell">
                                <?php echo htmlspecialchars($c['name']); ?>
                            </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="bg-green-50">
                            <td class="border border-gray-300 px-4 py-2 font-bold text-green-700">
                                <i class="fas fa-plus-circle mr-1"></i> A⁺ (Ideal Positif)
                            </td>
                            <?php foreach ($criteria as $c): ?>
                            <td class="border border-gray-300 px-4 py-2 matrix-cell font-mono text-green-700 font-bold">
                                <?php 
                                $value = $details['ideal_positive'][$c['id']] ?? 0;
                                echo number_format($value, 4);
                                ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <tr class="bg-red-50">
                            <td class="border border-gray-300 px-4 py-2 font-bold text-red-700">
                                <i class="fas fa-minus-circle mr-1"></i> A⁻ (Ideal Negatif)
                            </td>
                            <?php foreach ($criteria as $c): ?>
                            <td class="border border-gray-300 px-4 py-2 matrix-cell font-mono text-red-700 font-bold">
                                <?php 
                                $value = $details['ideal_negative'][$c['id']] ?? 0;
                                echo number_format($value, 4);
                                ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Legend -->
        <div class="bg-white rounded-lg shadow p-4 no-print">
            <h4 class="font-semibold text-gray-900 mb-3">
                <i class="fas fa-info-circle mr-2 text-blue-600"></i>
                Keterangan
            </h4>
            <div class="grid md:grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="inline-block w-4 h-4 bg-green-100 border border-green-400 rounded mr-2"></span>
                    <strong>Benefit:</strong> Semakin tinggi nilai, semakin baik (dimaksimalkan)
                </div>
                <div>
                    <span class="inline-block w-4 h-4 bg-red-100 border border-red-400 rounded mr-2"></span>
                    <strong>Cost:</strong> Semakin rendah nilai, semakin baik (diminimalkan)
                </div>
            </div>
        </div>

        <?php endif; ?>
    </div>
</body>
</html>

