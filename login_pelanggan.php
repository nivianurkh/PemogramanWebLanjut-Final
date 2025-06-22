<?php
session_start();
$error = isset($_SESSION['error']) ? $_SESSION['error'] : null;
unset($_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="en" class="light-style customizer-hide" dir="ltr" data-theme="theme-default" data-assets-path="../../dash/assets/" data-template="vertical-menu-template-free">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login</title>
  <link rel="shortcut icon" href="../../img/hero.PNG"> 
  <link rel="stylesheet" href="../../dash/assets/vendor/fonts/boxicons.css" />
  <link rel="stylesheet" href="../../dash/assets/vendor/css/core.css" />
  <link rel="stylesheet" href="../../dash/assets/vendor/css/theme-default.css" />
  <link rel="stylesheet" href="../../dash/assets/css/demo.css" />
  <link rel="stylesheet" href="../../dash/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
  <link rel="stylesheet" href="../../dash/assets/vendor/css/pages/page-auth.css" />
  <script src="../../dash/assets/vendor/js/helpers.js"></script>
  <script src="../../dash/assets/js/config.js"></script>
  <script src="../../dash/assets/vendor/libs/jquery/jquery.js"></script>
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
            <h4 class="mb-2">Welcome to Ocean's Feast! 👋</h4>
            <p class="mb-4">Please sign-in to your account and order your food</p>

            <?php if ($error): ?>
              <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form id="formAuthentication" class="mb-3" action="cek_login.php" method="POST">
              <div class="mb-3">
                <label for="username" class="form-label">Username / Email</label>
                <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username or email" autofocus required />
              </div>

              <div class="mb-3 form-password-toggle">
                <label class="form-label" for="password">Password</label>
                <div class="input-group input-group-merge">
                  <input type="password" id="password" class="form-control" name="password" placeholder="••••••••••••" required />
                  <span class="input-group-text cursor-pointer" id="togglePassword"><i class="bx bx-hide"></i></span>
                </div>
              </div>

              <div class="mb-3">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="remember-me" name="remember_me" />
                  <label class="form-check-label" for="remember-me">Remember Me</label>
                </div>

              </div>

              <div class="mb-3">
                <button class="btn btn-primary d-grid w-100" type="submit" name="login">Sign in</button>
              </div>
            </form>

            <p class="text-center">
              <span>Don't have an account?</span>
              <a href="register_pelanggan.php">
                <span>Please register here</span>
              </a>
            </p>
            <p class="text-center">
              <span>Forgot your password? <a href="forgot_password.php">klik here!</a></span>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="../../dash/assets/vendor/libs/popper/popper.js"></script>
  <script src="../../dash/assets/vendor/js/bootstrap.js"></script>
  <script src="../../dash/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
  <script src="../../dash/assets/vendor/js/menu.js"></script>
  <script src="../../dash/assets/js/main.js"></script>

  <script>
    document.getElementById('toggle-password').addEventListener('click', function () {
            const passwordField = document.getElementById('password');
            const icon = this.querySelector('i');
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.classList.remove('bx-hide');
                icon.classList.add('bx-show');
            } else {
                passwordField.type = 'password';
                icon.classList.remove('bx-show');
                icon.classList.add('bx-hide');
            }
        });
</script>
<script>
  // Simpan data login jika Remember Me dicentang
  document.getElementById('formAuthentication').addEventListener('submit', function () {
    const rememberMe = document.getElementById('remember-me').checked;
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;

    if (rememberMe) {
      // Simpan ke cookie (kadaluarsa 7 hari)
      document.cookie = "remember_username=" + encodeURIComponent(username) + "; max-age=" + 60 * 60 * 24 * 7 + "; path=/";
      document.cookie = "remember_password=" + encodeURIComponent(password) + "; max-age=" + 60 * 60 * 24 * 7 + "; path=/";
    } else {
      // Hapus cookie jika tidak dicentang
      document.cookie = "remember_username=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
      document.cookie = "remember_password=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
    }
  });

  // Fungsi bantu untuk ambil nilai cookie
  function getCookie(name) {
    const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
    if (match) return decodeURIComponent(match[2]);
    return '';
  }

  // Isi otomatis jika cookie ada
  window.onload = function () {
    const savedUsername = getCookie('remember_username');
    const savedPassword = getCookie('remember_password');

    if (savedUsername && savedPassword) {
      document.getElementById('username').value = savedUsername;
      document.getElementById('password').value = savedPassword;
      document.getElementById('remember-me').checked = true;
    }
  };
</script>

      

</body>
</html>
