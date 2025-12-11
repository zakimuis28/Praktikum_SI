<?php
/**
 * =====================================================
 * Layout Include - GDSS System
 * Shared sidebar, header, and helper functions
 * =====================================================
 */

// Ensure config is loaded
if (!function_exists('getConnection')) {
    die('Config not loaded. Please include config/config.php first.');
}

// =====================================================
// HELPER FUNCTIONS
// =====================================================

function icon($name, $class = 'w-5 h-5') {
    $icons = [
        'home' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
        'users' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z',
        'projects' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10',
        'criteria' => 'M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
        'chart' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        'trophy' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z',
        'clock' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        'results' => 'M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
        'settings' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
        'logout' => 'M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1',
        'user' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
        'bolt' => 'M13 10V3L4 14h7v7l9-11h-7z',
        'check' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'warning' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        'info' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'close' => 'M6 18L18 6M6 6l12 12',
        'chevron' => 'M19 9l-7 7-7-7',
        'bulb' => 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z',
        'shield' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
        'back' => 'M10 19l-7-7m0 0l7-7m-7 7h18',
        'add' => 'M12 4v16m8-8H4',
        'edit' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
        'delete' => 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16',
        'save' => 'M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4',
        'star' => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
        'print' => 'M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z',
        'excel' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        'crown' => 'M5 3l3.5 4L12 3l3.5 4L19 3v12H5V3zM5 15h14v3a2 2 0 01-2 2H7a2 2 0 01-2-2v-3z',
        'filter' => 'M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z',
        'cogs' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
        'clipboard' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
        'dollar' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'location' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z',
        'calendar' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        'eye' => 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z',
        'calculate' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z',
        'database' => 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4',
        'table' => 'M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z',
    ];
    $path = $icons[$name] ?? $icons['home'];
    return '<svg class="'.$class.'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="'.$path.'"></path></svg>';
}

function menuItem($href, $label, $iconName, $active = false, $danger = false) {
    $baseClass = 'flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-300';
    if ($danger) {
        $class = $baseClass . ' text-red-400 hover:text-red-300 hover:bg-red-500/10';
    } elseif ($active) {
        $class = $baseClass . ' bg-cyan-500 text-white shadow-lg shadow-cyan-500/30';
    } else {
        $class = $baseClass . ' text-gray-600 hover:text-gray-900 hover:bg-gray-100';
    }
    $onclick = $danger ? ' onclick="showLogoutModal(); return false;"' : '';
    return '<a href="'.$href.'" class="'.$class.'"'.$onclick.'>'.icon($iconName).'<span>'.$label.'</span></a>';
}

function statCard($title, $value, $iconName, $color, $delay = '') {
    $colors = [
        'cyan' => 'from-cyan-500 to-cyan-600 shadow-cyan-500/25',
        'emerald' => 'from-emerald-500 to-emerald-600 shadow-emerald-500/25',
        'amber' => 'from-amber-500 to-amber-600 shadow-amber-500/25',
        'purple' => 'from-purple-500 to-purple-600 shadow-purple-500/25',
        'red' => 'from-red-500 to-red-600 shadow-red-500/25',
        'blue' => 'from-blue-500 to-blue-600 shadow-blue-500/25',
    ];
    $gradient = $colors[$color] ?? $colors['cyan'];
    $delayClass = $delay ? 'delay-'.$delay : '';
    return '
    <div class="bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-200 p-6 card-hover animate-scale-in '.$delayClass.'">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-900 text-xs font-bold uppercase tracking-wider mb-1">'.$title.'</p>
                <p class="text-3xl font-black text-gray-900 count-up">'.$value.'</p>
            </div>
            <div class="w-14 h-14 bg-gradient-to-br '.$gradient.' rounded-xl flex items-center justify-center shadow-lg animate-float">
                '.icon($iconName, 'w-7 h-7 text-white').'
            </div>
        </div>
    </div>';
}

function flashBox($msg) {
    if (!$msg) return '';
    $type = $msg['type'];
    $colors = [
        'success' => 'bg-emerald-500/10 border-emerald-500 text-emerald-400',
        'danger' => 'bg-red-500/10 border-red-500 text-red-400',
        'error' => 'bg-red-500/10 border-red-500 text-red-400',
        'info' => 'bg-cyan-500/10 border-cyan-500 text-cyan-400',
        'warning' => 'bg-amber-500/10 border-amber-500 text-amber-400',
    ];
    $icons = ['success' => 'check', 'danger' => 'warning', 'error' => 'warning', 'info' => 'info', 'warning' => 'warning'];
    $colorClass = $colors[$type] ?? $colors['info'];
    $iconName = $icons[$type] ?? 'info';
    
    return '
    <div class="'.$colorClass.' backdrop-blur-sm border rounded-xl p-4 mb-6 flex items-center justify-between animate-fade-in">
        <div class="flex items-center gap-3">
            '.icon($iconName, 'w-6 h-6').'
            <span class="font-bold uppercase tracking-wide">'.htmlspecialchars($msg['message']).'</span>
        </div>
        <button onclick="this.parentElement.remove()" class="opacity-70 hover:opacity-100">'.icon('close', 'w-5 h-5').'</button>
    </div>';
}

function actionButton($href, $label, $iconName, $color, $isButton = false, $onclick = '', $size = 'normal') {
    $colors = [
        'primary' => 'bg-gradient-to-r from-cyan-500 to-cyan-600 hover:from-cyan-400 hover:to-cyan-500 text-white shadow-lg shadow-cyan-500/25',
        'success' => 'bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-400 hover:to-emerald-500 text-white shadow-lg shadow-emerald-500/25',
        'warning' => 'bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-white shadow-lg shadow-amber-500/25',
        'info' => 'bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-400 hover:to-purple-500 text-white shadow-lg shadow-purple-500/25',
        'danger' => 'bg-gradient-to-r from-red-500 to-red-600 hover:from-red-400 hover:to-red-500 text-white shadow-lg shadow-red-500/25',
        'outline' => 'bg-transparent border border-gray-300 text-gray-900 hover:border-cyan-500 hover:text-cyan-400',
        'outline-success' => 'bg-transparent border border-emerald-600 text-emerald-400 hover:border-emerald-400 hover:text-emerald-300',
        'outline-primary' => 'bg-transparent border border-cyan-600 text-cyan-400 hover:border-cyan-400 hover:text-cyan-300',
    ];
    $colorClass = $colors[$color] ?? $colors['primary'];
    $sizeClass = $size === 'sm' ? 'px-3 py-2 text-xs' : 'px-6 py-3 text-sm';
    $baseClass = 'quick-action inline-flex items-center gap-3 rounded-2xl font-extrabold uppercase tracking-wider transition-all duration-300 ' . $sizeClass;

    $iconSizeClass = $size === 'sm' ? 'w-4 h-4' : 'w-6 h-6';

    $inner = '<span class="qa-icon flex items-center justify-center rounded-lg p-2">'.icon($iconName, $iconSizeClass).'</span>';
    $inner .= '<span class="qa-label">'.htmlspecialchars($label).'</span>';

    if ($isButton) {
        return '<button type="button" onclick="'.$onclick.'" class="'.$baseClass.' '.$colorClass.'">'.$inner.'</button>';
    }
    return '<a href="'.$href.'" class="'.$baseClass.' '.$colorClass.'">'.$inner.'</a>';
}

function getRoleColors($role) {
    $roleColors = [
        'supervisor' => [
            'gradient' => 'from-purple-500 to-purple-600',
            'shadow' => 'shadow-purple-500/30',
            'bg' => 'bg-purple-500/20',
            'text' => 'text-purple-400',
            'badge' => 'from-purple-500 to-purple-600',
        ],
        'teknis' => [
            'gradient' => 'from-cyan-500 to-cyan-600',
            'shadow' => 'shadow-cyan-500/30',
            'bg' => 'bg-cyan-500/20',
            'text' => 'text-cyan-400',
            'badge' => 'from-cyan-500 to-cyan-600',
        ],
        'admin' => [
            'gradient' => 'from-rose-500 to-rose-600',
            'shadow' => 'shadow-rose-500/30',
            'bg' => 'bg-rose-500/20',
            'text' => 'text-rose-400',
            'badge' => 'from-rose-500 to-rose-600',
        ],
        'keuangan' => [
            'gradient' => 'from-amber-500 to-amber-600',
            'shadow' => 'shadow-amber-500/30',
            'bg' => 'bg-amber-500/20',
            'text' => 'text-amber-400',
            'badge' => 'from-amber-500 to-amber-600',
        ],
    ];
    return $roleColors[$role] ?? $roleColors['teknis'];
}

// =====================================================
// LAYOUT RENDERING FUNCTIONS
// =====================================================

function renderHead($pageTitle, $basePath = '') {
    $cssPath = $basePath . 'assets/css/style.css';
    $jsPath = $basePath . 'assets/js/gdss.js';
    ?>
<!DOCTYPE html>
<html lang="id" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="<?= $cssPath ?>" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="<?= $jsPath ?>" defer></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .animate-fade-in { animation: fadeIn 0.3s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .sidebar { background: linear-gradient(180deg, rgba(255,255,255,0.98) 0%, rgba(248,250,252,0.98) 100%); backdrop-filter: blur(20px); border-right: 1px solid #e2e8f0; }
        .bg-blob { background: linear-gradient(135deg, #0ea5e9, #10b981); filter: blur(60px); opacity: 0.15; }
        
        /* Mobile Sidebar Toggle */
        @media (max-width: 1024px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.3s ease-in-out; }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0 !important; }
            .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 35; }
            .sidebar-overlay.active { display: block; }
        }
        @media (min-width: 1025px) {
            .mobile-toggle, .sidebar-overlay { display: none !important; }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 via-white to-gray-100 min-h-screen text-gray-900">
    <!-- Background Blobs -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none -z-10">
        <div class="absolute -top-20 -left-20 w-96 h-96 bg-blob rounded-full opacity-15"></div>
        <div class="absolute top-1/2 -right-20 w-80 h-80 bg-blob rounded-full opacity-10"></div>
    </div>
    <?php
}

function renderSidebar($basePath = '', $activePage = '') {
    $userRole = $_SESSION['role'] ?? 'guest';
    $userName = $_SESSION['name'] ?? 'User';
    $isAdmin = hasRole('admin');
    $isDecisionMaker = hasRole('supervisor') || hasRole('teknis') || hasRole('keuangan');
    $roleColors = getRoleColors($userRole);
    ?>
    <div class="flex min-h-screen">
        <!-- Mobile Toggle Button -->
        <button onclick="toggleSidebar()" class="mobile-toggle fixed top-4 left-4 z-50 p-3 bg-white border border-gray-200 rounded-xl shadow-lg lg:hidden hover:bg-gray-100 transition-all duration-300">
            <svg id="menuIcon" class="w-6 h-6 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
            <svg id="closeIcon" class="w-6 h-6 text-cyan-400 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
        
        <!-- Overlay -->
        <div id="sidebarOverlay" class="sidebar-overlay" onclick="toggleSidebar()"></div>
        
        <!-- Sidebar -->
        <aside id="sidebar" class="sidebar w-64 fixed left-0 top-0 h-full border-r border-gray-200/50 p-5 flex flex-col z-40">
            <!-- Brand -->
            <div class="text-center mb-6">
                <div class="flex items-center justify-center gap-2 mb-1">
                    <?= icon('bulb', 'w-7 h-7 text-cyan-400') ?>
                    <h1 class="text-2xl font-black bg-gradient-to-r from-cyan-400 to-emerald-400 bg-clip-text text-transparent">GDSS</h1>
                </div>
                <p class="text-gray-500 text-xs font-bold uppercase tracking-widest">Decision Support</p>
            </div>

            <!-- User Profile -->
            <div class="text-center mb-6 p-4 bg-gray-50 rounded-2xl border border-gray-200">
                <div class="w-14 h-14 mx-auto mb-3 bg-gradient-to-br <?= $roleColors['gradient'] ?> rounded-full flex items-center justify-center shadow-lg <?= $roleColors['shadow'] ?>">
                    <?= icon('user', 'w-7 h-7 text-white') ?>
                </div>
                <h2 class="text-gray-900 font-bold text-sm uppercase tracking-wide mb-2"><?= htmlspecialchars($userName) ?></h2>
                <span class="inline-flex items-center gap-1.5 bg-gradient-to-r <?= $roleColors['badge'] ?> text-white px-3 py-1 rounded-full text-xs font-black uppercase">
                    <span class="w-1.5 h-1.5 bg-white rounded-full animate-pulse"></span>
                    <?= htmlspecialchars($userRole) ?>
                </span>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 space-y-1.5">
                <?= menuItem($basePath . 'dashboard.php', 'Dashboard', 'home', $activePage === 'dashboard') ?>
                
                <?php if ($isAdmin): ?>
                    <?= menuItem($basePath . 'views/admin/manage_users.php', 'Kelola User', 'users', $activePage === 'manage_users') ?>
                    <?= menuItem($basePath . 'views/admin/manage_projects.php', 'Kelola Proyek', 'projects', $activePage === 'manage_projects') ?>
                    <?= menuItem($basePath . 'views/admin/manage_criteria.php', 'Kelola Kriteria', 'criteria', $activePage === 'manage_criteria') ?>
                <?php endif; ?>
                
                <?php if ($isDecisionMaker): ?>
                    <?= menuItem($basePath . 'evaluate.php', 'Evaluasi ' . ucfirst($userRole), 'criteria', $activePage === 'evaluate') ?>
                    <?= menuItem($basePath . 'topsis_results.php', 'Hasil TOPSIS', 'results', $activePage === 'topsis_results') ?>
                <?php endif; ?>
                
                <!-- Hasil BORDA - accessible by decision makers only (not admin) -->
                <?php if ($isDecisionMaker): ?>
                    <?= menuItem($basePath . 'borda_result.php', 'Hasil BORDA', 'trophy', $activePage === 'borda_result') ?>
                <?php endif; ?>
                
                <div class="my-4 border-t border-gray-200/50"></div>
                <?= menuItem($basePath . 'profile.php', 'Profil', 'settings', $activePage === 'profile') ?>
                <?= menuItem($basePath . 'logout.php', 'Logout', 'logout', false, true) ?>
            </nav>
        </aside>
    <?php
}

function renderHeader($title, $subtitle = '', $iconName = 'home', $showBackButton = false, $backUrl = '', $basePath = '') {
    $userRole = $_SESSION['role'] ?? 'guest';
    $roleColors = getRoleColors($userRole);
    $profileLink = $basePath . 'profile.php';
    ?>
        <!-- Main Content -->
        <main class="main-content flex-1 ml-64">
            <!-- Header -->
            <header class="sticky top-0 z-30 bg-white/90 backdrop-blur-xl border-b border-gray-200/50 px-8 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <?php if ($showBackButton && $backUrl): ?>
                        <a href="<?= $backUrl ?>" class="p-2 rounded-lg bg-gray-50 border border-gray-200 hover:border-cyan-500/50 transition-all duration-300">
                            <?= icon('back', 'w-5 h-5 text-cyan-400') ?>
                        </a>
                        <?php endif; ?>
                        <?= icon($iconName, 'w-7 h-7 text-cyan-400') ?>
                        <div>
                            <h2 class="text-2xl font-black bg-gradient-to-r from-cyan-400 to-emerald-400 bg-clip-text text-transparent"><?= htmlspecialchars($title) ?></h2>
                            <?php if ($subtitle): ?>
                            <p class="text-gray-900 text-sm"><?= htmlspecialchars($subtitle) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-2 bg-gray-50 rounded-lg px-3 py-2 border border-gray-200">
                            <?= icon('clock', 'w-4 h-4 text-cyan-400') ?>
                            <span id="currentTime" class="text-gray-900 text-sm font-medium"></span>
                        </div>
                        <a href="<?= $profileLink ?>" class="w-9 h-9 bg-gradient-to-br <?= $roleColors['gradient'] ?> rounded-lg flex items-center justify-center <?= $roleColors['shadow'] ?> hover:scale-110 hover:shadow-lg transition-all duration-300 cursor-pointer" title="Profil">
                            <?= icon('user', 'w-5 h-5 text-white') ?>
                        </a>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <div class="p-8">
    <?php
}

function renderFooter() {
    ?>
            </div>
        </main>
    </div>

    <script>
        // Sidebar toggle for mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const menuIcon = document.getElementById('menuIcon');
            const closeIcon = document.getElementById('closeIcon');
            
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
            menuIcon.classList.toggle('hidden');
            closeIcon.classList.toggle('hidden');
        }
        
        // Auto dismiss alerts
        setTimeout(() => {
            document.querySelectorAll('.animate-fade-in').forEach(el => {
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 300);
            });
        }, 5000);

        // Real-time clock update
        function updateClock() {
            const now = new Date();
            const day = String(now.getDate()).padStart(2, '0');
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const year = now.getFullYear();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            
            const timeString = `${day}/${month}/${year} ${hours}:${minutes}:${seconds}`;
            const timeElement = document.getElementById('currentTime');
            if (timeElement) {
                timeElement.textContent = timeString;
            }
        }

        // Update clock immediately and then every second
        updateClock();
        setInterval(updateClock, 1000);
    </script>
</body>
</html>
    <?php
}

// =====================================================
// CARD COMPONENTS
// =====================================================

function renderCard($title, $iconName = 'info', $content = '', $headerActions = '') {
    ?>
    <div class="bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-200 hover:border-cyan-500/30 transition-all duration-300">
        <div class="flex items-center justify-between border-b border-gray-200/50 px-6 py-4">
            <h5 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                <?= icon($iconName, 'w-5 h-5 text-cyan-400') ?>
                <?= htmlspecialchars($title) ?>
            </h5>
            <?php if ($headerActions): ?>
            <div class="flex items-center gap-2">
                <?= $headerActions ?>
            </div>
            <?php endif; ?>
        </div>
        <div class="p-6">
            <?= $content ?>
        </div>
    </div>
    <?php
}







