<?php
// ============================================================
//  auth.php — Session Protection
//  Include this at the TOP of every admin page except login.php
//  Redirects to login if not logged in
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}
?>
