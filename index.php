<?php
// Selalu include config.php di awal untuk session dan BASE_URL
require_once __DIR__ . '/config.php';

if (isset($_SESSION['login_admin']) && $_SESSION['login_admin'] === true) {
    header("Location: " . BASE_URL . "dashboard.php");
} else {
    header("Location: " . BASE_URL . "auth/login.php");
}
exit;
?>