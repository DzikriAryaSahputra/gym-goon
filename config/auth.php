<?php
// config/auth.php
session_start();

// Mencegah browser menyimpan cache halaman (Mengatasi bug tombol Back setelah Logout)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

function isLoggedIn() {
    return isset($_SESSION['member_id']);
}

function isRegularLoggedIn() {
    return isset($_SESSION['regular_id']);
}

function requireLogin() {
    if (!isLoggedIn() && !isRegularLoggedIn()) {
        header("Location: login");
        exit;
    }
}

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function requireAdmin() {
    if (!isAdminLoggedIn()) {
        header("Location: login");
        exit;
    }
}

// Function to sanitize inputs
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
?>
