<?php
// Mulai session
session_start();
 
// Hapus semua variabel session
$_SESSION = array();
 
// Hancurkan session
session_destroy();
 
// Arahkan ke halaman login
header("location: login.php");
exit;
?>
