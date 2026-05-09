<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php"); exit;
}

function require_admin(): void {
    if (($_SESSION['role'] ?? '') !== 'admin') {
        header("Location: ../admin/admin_dashboard.php?error=access_denied"); exit;
    }
}