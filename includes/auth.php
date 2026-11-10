<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION["admin_id"])) {
    header("Location: /dubey_kirana_store/admin/login.php");
    exit;
}

?>