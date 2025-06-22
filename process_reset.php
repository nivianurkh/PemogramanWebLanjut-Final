<?php
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $password = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($token) || empty($password) || empty($confirm)) {
        header("Location: reset_password.php?token=$token&error=All fields are required.");
        exit();
    }

    if ($password !== $confirm) {
        header("Location: reset_password.php?token=$token&error=Password and confirmation do not match.");
        exit();
    }

    if (strlen($password) < 8 || !preg_match('/[a-zA-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        header("Location: reset_password.php?token=$token&error=Password must be at least 8 characters long and contain both letters and numbers.");
        exit();
    }

    // Use PHP time instead of NOW() to avoid timezone mismatch
    $now = date("Y-m-d H:i:s");
    $stmt = $db->prepare("SELECT * FROM pelanggan WHERE reset_token = :token AND token_expired > :now");
    $stmt->execute([
        ':token' => $token,
        ':now' => $now
    ]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header("Location: reset_password.php?token=$token&error=Invalid or expired token.");
        exit();
    }

    $hashed = password_hash($password, PASSWORD_DEFAULT);

    $update = $db->prepare("UPDATE pelanggan SET password = :password, reset_token = NULL, token_expired = NULL WHERE id_pelanggan = :id");
    $update->execute([
        ':password' => $hashed,
        ':id' => $user['id_pelanggan']
    ]);

    header("Location: login_pelanggan.php?reset=success");
    exit();
} else {
    header("Location: forgot_password.php");
    exit();
}
