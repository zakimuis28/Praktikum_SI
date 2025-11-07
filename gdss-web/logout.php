<?php
/**
 * GDSS Logout Page
 * Halaman untuk logout dan mengakhiri session
 */

require_once 'config.php';
require_once 'functions.php';

// Logout user
logoutUser();

// Set flash message untuk login page
setFlashMessage('success', 'Anda telah berhasil logout dari sistem');

// Redirect ke halaman login
redirect('index.php');
?>