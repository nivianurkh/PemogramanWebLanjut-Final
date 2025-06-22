<?php
session_start();

// Hapus semua variabel dalam session
session_unset();

// Hancurkan session-nya
session_destroy();

// Arahkan ke halaman login
header("Location: login_pelanggan.php");
exit;
?>
