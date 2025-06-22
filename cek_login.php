<?php 
session_start();
include("koneksi.php");

if (isset($_POST['login'])) {
    $username = $_POST['username']; 
    $password = $_POST['password']; 

    $sql = "SELECT * FROM pelanggan WHERE username = :username OR email = :username";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        if (password_verify($password, $user['password'])) {
            // ✅ Simpan id_pelanggan ke session (PENTING)
            $_SESSION['id_pelanggan'] = $user['id_pelanggan'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['firstname'] = $user['firstname']; // Pastikan kolom ini ada

            header("Location: timeline.php"); // Atau menu.php kalau ingin langsung ke menu
            exit();
        } else {
            $_SESSION['error'] = "Incorrect password.";
        }
    } else {
        $_SESSION['error'] = "Username not found.";
    }

    header("Location: login_pelanggan.php");
    exit();
} else {
    $_SESSION['error'] = "Please fill in the login form first.";
    header("Location: login_pelanggan.php");
    exit();
}
?>
