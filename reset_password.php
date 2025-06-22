<?php
include 'koneksi.php';

date_default_timezone_set("Asia/Jakarta");
$token = $_GET['token'] ?? '';

if (!$token) {
    die("Token is required.");
}

// Ambil data berdasarkan token
$stmt = $db->prepare("SELECT * FROM pelanggan WHERE reset_token = :token");
$stmt->execute([':token' => $token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Invalid token.");
}

// Cek apakah token expired
if (strtotime($user['token_expired']) < time()) {
    // Token expired — buat token baru dan redirect
    $new_token = bin2hex(random_bytes(32));
    $new_expired = date("Y-m-d H:i:s", strtotime("+6 hour"));

    $update = $db->prepare("UPDATE pelanggan SET reset_token = :new_token, token_expired = :new_expired WHERE id_pelanggan = :id");
    $update->execute([
        ':new_token' => $new_token,
        ':new_expired' => $new_expired,
        ':id' => $user['id_pelanggan']
    ]);

    // Redirect ke URL dengan token baru
    header("Location: reset_password.php?token=" . $new_token);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en" class="light-style customizer-hide" dir="ltr" data-theme="theme-default" data-assets-path="../../dash/assets/" data-template="vertical-menu-template-free">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Reset Password</title>
  <link rel="shortcut icon" href="../../img/hero.PNG"> 
  <link rel="stylesheet" href="../../dash/assets/vendor/fonts/boxicons.css" />
  <link rel="stylesheet" href="../../dash/assets/vendor/css/core.css" />
  <link rel="stylesheet" href="../../dash/assets/vendor/css/theme-default.css" />
  <link rel="stylesheet" href="../../dash/assets/css/demo.css" />
  <link rel="stylesheet" href="../../dash/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
  <link rel="stylesheet" href="../../dash/assets/vendor/css/pages/page-auth.css" />
  <script src="../../dash/assets/vendor/js/helpers.js"></script>
  <script src="../../dash/assets/js/config.js"></script>
</head>

<body>
  <div class="container-xxl">
    <div class="authentication-wrapper authentication-basic container-p-y">
      <div class="authentication-inner">
        <div class="card">
          <div class="card-body">
            <div class="app-brand justify-content-center">
              <span class="app-brand-logo demo">
                <img src="../../img/hero.PNG" alt="" style="width: 60px;">
              </span>
            </div>
            <h4 class="mb-2" style="text-align:center;">Reset Password</h4>

            <?php if (isset($_GET['error'])): ?>
              <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($_GET['error']) ?>
              </div>
            <?php endif; ?>

            <form action="process_reset.php" method="POST" id="resetForm">
              <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
              
              <div class="mb-3 form-password-toggle">
                <label class="form-label" for="new_password">New Password</label>
                <div class="input-group input-group-merge">
                  <input type="password" id="new_password" class="form-control" name="new_password" placeholder="••••••••••••" required />
                  <span class="input-group-text cursor-pointer" id="toggleNewPassword"><i class="bx bx-hide"></i></span>
                </div>
              </div>

              <div class="mb-3 form-password-toggle">
                <label class="form-label" for="confirm_password">Confirmation Password</label>
                <div class="input-group input-group-merge">
                  <input type="password" id="confirm_password" class="form-control" name="confirm_password" placeholder="••••••••••••" required />
                  <span class="input-group-text cursor-pointer" id="toggleConfirmPassword"><i class="bx bx-hide"></i></span>
                </div>
              </div>

              <div class="mb-3">
                <button class="btn btn-primary d-grid w-100" type="submit">Change Password</button>
              </div>
            </form>

          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- JS -->
  <script src="../../dash/assets/vendor/libs/popper/popper.js"></script>
  <script src="../../dash/assets/vendor/js/bootstrap.js"></script>
  <script src="../../dash/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
  <script src="../../dash/assets/vendor/js/menu.js"></script>
  <script src="../../dash/assets/js/main.js"></script>

  <!-- Form validation -->
  <script>
    document.getElementById("resetForm").addEventListener("submit", function (e) {
      const newPassword = document.getElementById("new_password").value;
      const confirmPassword = document.getElementById("confirm_password").value;

      const oldAlert = document.querySelector(".alert-danger");
      if (oldAlert) oldAlert.remove();

      let errorMessage = "";

      if (newPassword.length < 8 || !/[a-zA-Z]/.test(newPassword) || !/[0-9]/.test(newPassword)) {
        errorMessage = "Password must be at least 8 characters and contain both letters and numbers.";
      } else if (newPassword !== confirmPassword) {
        errorMessage = "Confirmation password does not match.";
      }

      if (errorMessage) {
        e.preventDefault();
        const alert = document.createElement("div");
        alert.className = "alert alert-danger";
        alert.role = "alert";
        alert.textContent = errorMessage;

        const cardBody = document.querySelector(".card-body");
        cardBody.insertBefore(alert, cardBody.children[2]);

        return false;
      }
    });
  </script>
</body>
</html>
