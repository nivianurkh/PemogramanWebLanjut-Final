<?php
session_start();
$error = $_SESSION['error'] ?? null;
$success = $_SESSION['success'] ?? null;
unset($_SESSION['error'], $_SESSION['success']);
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
            <?php if ($error): ?> 
              <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            <?php endif; ?>

            <?php if ($success): ?> 
              <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            <?php endif; ?>

            <div class="app-brand justify-content-center">
              <span class="app-brand-logo demo">
                <img src="../../img/hero.PNG" alt="" style="width: 60px;">
              </span>
            </div>
            <h4 class="mb-2" style="text-align:center;">Forgot Password</h4>

            <form id="formAuthentication" class="mb-3" action="send_reset.php" method="POST">
              <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="text" class="form-control" id="email" name="email" placeholder="Enter your email" autofocus required />
              </div>

              <div class="mb-3">
                <button class="btn btn-primary d-grid w-100" type="submit">Send Link Reset</button>
              </div>
            </form>
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

</body>
</html>
