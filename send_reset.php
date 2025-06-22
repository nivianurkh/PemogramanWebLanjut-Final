<?php
session_start();
require 'koneksi.php';

// PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../PHPMailer/Exception.php';
require '../PHPMailer/PHPMailer.php';
require '../PHPMailer/SMTP.php';

$email = $_POST['email'];

try {
    $stmt = $db->prepare("SELECT id_pelanggan FROM pelanggan WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Generate token dan expired time
        $token = bin2hex(random_bytes(32)); // Generate token acak
        $expired = date("Y-m-d H:i:s", strtotime("+1 hour")); // Set expired time 1 jam

        // Simpan token dan expired ke DB
        $update = $db->prepare("UPDATE pelanggan SET reset_token = :token, token_expired = :expired WHERE id_pelanggan = :id");
        $result = $update->execute([
            ':token' => $token,
            ':expired' => $expired,
            ':id' => $user['id_pelanggan']
        ]);

        // Periksa apakah query berhasil
        if ($result) {
            // Buat link reset password
            $resetLink = "http://localhost/resto/crud/pelanggan/reset_password.php?token=" . $token;

            // Kirim email dengan PHPMailer
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'nivianurkh@gmail.com'; // Ganti dengan email kamu
            $mail->Password = 'tqkk xhxh gjgf mtyu'; // Ganti dengan aplikasi password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            // Setting email
            $mail->setFrom('nivianurkh@gmail.com', 'Oceans Feast'); // Ganti dengan alamat email kamu
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Reset Your Password';
            $mail->Body    = "Click the following link to reset your password:<br><a href='$resetLink'>$resetLink</a><br><br>The link is valid for 1 hour";

            if ($mail->send()) {
                $_SESSION['success'] = "The password reset link has been sent to your email";
                header("Location: forgot_password.php");
                exit();
            } else {
                $_SESSION['error'] = "Failed to send email, please try again later";
                header("Location: forgot_password.php");
                exit();
            }
        } else {
            $_SESSION['error'] = "An error occurred while updating the token data";
            header("Location: forgot_password.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Email not found in the system";
        header("Location: forgot_password.php");
        exit();
    }
} catch (Exception $e) {
    $_SESSION['error'] = "An error occurred.: {$mail->ErrorInfo}";
    header("Location: forgot_password.php");
    exit();
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header("Location: forgot_password.php");
    exit();
}
?>
