<?php
session_start();

// Hapus semua variabel session spesifik admin
unset($_SESSION['login_admin']);
unset($_SESSION['admin_id']);
unset($_SESSION['admin_nama']);
unset($_SESSION['admin_email']);
unset($_SESSION['admin_role']);

// Hancurkan session jika ingin benar-benar bersih (opsional jika ada session lain)
// session_destroy(); 
// Atau cara yang lebih aman untuk menghancurkan semua data session:
$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();


header("Location: login.php");
exit;
?>