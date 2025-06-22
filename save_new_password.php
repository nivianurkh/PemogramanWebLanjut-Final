<?php
require 'koneksi.php';

$token = $_POST['token'];
$newPassword = password_hash($_POST['password'], PASSWORD_BCRYPT);

// Cek token
$stmt = $conn->prepare("SELECT email FROM reset_password WHERE token = :token AND expired_at > NOW()");
$stmt->bindParam(':token', $token);
$stmt->execute();

if ($stmt->rowCount() === 0) {
    die("Token tidak valid atau kadaluarsa.");
}

$data = $stmt->fetch();
$email = $data['email'];

// Update password di tabel pelanggan
$update = $conn->prepare("UPDATE pelanggan SET password = :password WHERE email = :email");
$update->execute([
    ':password' => $newPassword,
    ':email' => $email
]);

// Hapus token setelah digunakan
$delete = $conn->prepare("DELETE FROM reset_password WHERE token = :token");
$delete->execute([':token' => $token]);

echo "Password berhasil diubah. Silakan <a href='login_pelanggan.php'>login</a>.";
?>
